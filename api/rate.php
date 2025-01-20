<?php
require_once '../config.php';

// Enable error logging
error_log("Rating API called");

session_start();
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

// Log received data
$input = file_get_contents('php://input');
error_log("Received data: " . $input);

$data = json_decode($input, true);
$comic_id = $data['comic_id'] ?? 0;
$rating = $data['rating'] ?? 0;
$user_id = $_SESSION['user_id'];

error_log("Processing rating - User ID: $user_id, Comic ID: $comic_id, Rating: $rating");

try {
    // Validate rating value
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Invalid rating value');
    }

    // Check if user has already rated
    $existing = fetchOne("SELECT * FROM ratings WHERE user_id = ? AND comic_id = ?", 
        [$user_id, $comic_id]);

    if ($existing) {
        error_log("Updating existing rating");
        // Update existing rating
        query("UPDATE ratings SET rating = ?, updated_at = NOW() WHERE user_id = ? AND comic_id = ?", 
            [$rating, $user_id, $comic_id]);
    } else {
        error_log("Adding new rating");
        // Add new rating
        query("INSERT INTO ratings (user_id, comic_id, rating) VALUES (?, ?, ?)", 
            [$user_id, $comic_id, $rating]);
    }

    // Get updated average rating
    $newRating = fetchOne("
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as total_ratings
        FROM ratings 
        WHERE comic_id = ?", 
        [$comic_id]
    );

    // Update comic's rating
    query("UPDATE comics SET rating = ? WHERE comic_id = ?", 
        [$newRating['avg_rating'], $comic_id]);

    echo json_encode([
        'status' => 'success',
        'avg_rating' => round($newRating['avg_rating'], 1),
        'total_ratings' => $newRating['total_ratings']
    ]);
} catch (Exception $e) {
    error_log("Error in rating API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update rating: ' . $e->getMessage()]);
} 
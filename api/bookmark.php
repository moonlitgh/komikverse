<?php
require_once '../config.php';

header('Content-Type: application/json');

// Enable error logging
error_log("Bookmark API called");

// Check if user is logged in
if (!isLoggedIn()) {
    error_log("User not logged in");
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

// Get comic_id from POST data
$data = json_decode(file_get_contents('php://input'), true);
$comic_id = isset($data['comic_id']) ? (int)$data['comic_id'] : 0;

if (!$comic_id) {
    error_log("Invalid comic ID");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid comic ID']);
    exit;
}

error_log("Processing bookmark - User ID: " . $_SESSION['user_id'] . ", Comic ID: " . $comic_id);

try {
    // Check if already bookmarked
    $existing = fetchOne(
        "SELECT * FROM bookmarks WHERE user_id = ? AND comic_id = ?",
        [$_SESSION['user_id'], $comic_id]
    );

    if ($existing) {
        error_log("Removing existing bookmark");
        // Remove bookmark
        query(
            "DELETE FROM bookmarks WHERE user_id = ? AND comic_id = ?",
            [$_SESSION['user_id'], $comic_id]
        );
        echo json_encode([
            'status' => 'removed',
            'message' => 'Bookmark removed successfully'
        ]);
    } else {
        error_log("Adding new bookmark");
        // Add bookmark
        query(
            "INSERT INTO bookmarks (user_id, comic_id, created_at) VALUES (?, ?, NOW())",
            [$_SESSION['user_id'], $comic_id]
        );
        echo json_encode([
            'status' => 'added',
            'message' => 'Bookmark added successfully'
        ]);
    }
} catch (Exception $e) {
    error_log("Error in bookmark API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update bookmark']);
} 
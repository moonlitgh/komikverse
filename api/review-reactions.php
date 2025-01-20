<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate input
        if (empty($data['review_id']) || empty($data['type']) || 
            !in_array($data['type'], ['like', 'dislike'])) {
            throw new Exception('Invalid input');
        }

        // Check if user already reacted
        $existing = fetchOne(
            "SELECT type FROM review_reactions WHERE review_id = ? AND user_id = ?",
            [$data['review_id'], $_SESSION['user_id']]
        );

        if ($existing) {
            if ($existing['type'] === $data['type']) {
                // Remove reaction if same type
                query(
                    "DELETE FROM review_reactions WHERE review_id = ? AND user_id = ?",
                    [$data['review_id'], $_SESSION['user_id']]
                );
            } else {
                // Update reaction if different type
                query(
                    "UPDATE review_reactions SET type = ? WHERE review_id = ? AND user_id = ?",
                    [$data['type'], $data['review_id'], $_SESSION['user_id']]
                );
            }
        } else {
            // Insert new reaction
            query(
                "INSERT INTO review_reactions (review_id, user_id, type) VALUES (?, ?, ?)",
                [$data['review_id'], $_SESSION['user_id'], $data['type']]
            );
        }

        // Get updated counts
        $counts = fetchOne("
            SELECT 
                (SELECT COUNT(*) FROM review_reactions WHERE review_id = ? AND type = 'like') as likes,
                (SELECT COUNT(*) FROM review_reactions WHERE review_id = ? AND type = 'dislike') as dislikes,
                (SELECT type FROM review_reactions WHERE review_id = ? AND user_id = ?) as user_reaction
            ",
            [$data['review_id'], $data['review_id'], $data['review_id'], $_SESSION['user_id']]
        );

        echo json_encode([
            'status' => 'success',
            'likes' => $counts['likes'],
            'dislikes' => $counts['dislikes'],
            'user_reaction' => $counts['user_reaction']
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
} 
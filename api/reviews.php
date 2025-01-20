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
        if (empty($data['content']) || empty($data['comic_id'])) {
            throw new Exception('Missing required fields');
        }

        // Check if user already reviewed this comic
        $existing = fetchOne(
            "SELECT review_id FROM reviews WHERE user_id = ? AND comic_id = ?",
            [$_SESSION['user_id'], $data['comic_id']]
        );

        if ($existing) {
            throw new Exception('You have already reviewed this comic');
        }

        // Insert review
        query(
            "INSERT INTO reviews (user_id, comic_id, content) VALUES (?, ?, ?)",
            [$_SESSION['user_id'], $data['comic_id'], $data['content']]
        );

        echo json_encode([
            'status' => 'success',
            'message' => 'Review posted successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
} 
<?php
require_once '../config.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$comic_id = $data['comic_id'] ?? 0;
$chapter_id = $data['chapter_id'] ?? 0;
$page_number = $data['page_number'] ?? 1;
$user_id = $_SESSION['user_id'];

try {
    // Update or insert reading progress
    query("INSERT INTO reading_history (user_id, comic_id, chapter_id, page_number) 
           VALUES (?, ?, ?, ?) 
           ON DUPLICATE KEY UPDATE 
           chapter_id = VALUES(chapter_id),
           page_number = VALUES(page_number),
           last_read = NOW()", 
        [$user_id, $comic_id, $chapter_id, $page_number]);

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update progress']);
} 
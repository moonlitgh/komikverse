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
        if (empty($data['review_id']) || empty($data['reason']) || 
            !in_array($data['reason'], ['spam', 'inappropriate', 'spoiler', 'other'])) {
            throw new Exception('Invalid input');
        }

        // Check if user already reported this review
        $existing = fetchOne(
            "SELECT report_id FROM review_reports WHERE review_id = ? AND user_id = ?",
            [$data['review_id'], $_SESSION['user_id']]
        );

        if ($existing) {
            throw new Exception('You have already reported this review');
        }

        // Insert report
        query(
            "INSERT INTO review_reports (review_id, user_id, reason) VALUES (?, ?, ?)",
            [$data['review_id'], $_SESSION['user_id'], $data['reason']]
        );

        // Check if review has multiple reports
        $reportCount = fetchOne(
            "SELECT COUNT(*) as count FROM review_reports WHERE review_id = ?",
            [$data['review_id']]
        );

        if ($reportCount['count'] >= 3) {
            // Auto-hide review if it has 3 or more reports
            query(
                "UPDATE reviews SET status = 'reported' WHERE review_id = ?",
                [$data['review_id']]
            );
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Review reported successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
} 
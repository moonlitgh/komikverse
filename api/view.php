<?php
require_once '../config.php';

header('Content-Type: application/json');

// Get comic_id from POST data
$data = json_decode(file_get_contents('php://input'), true);
$comic_id = isset($data['comic_id']) ? (int)$data['comic_id'] : 0;

if (!$comic_id) {
    echo json_encode(['error' => 'Invalid comic ID']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Update comics total views
    $stmt = $pdo->prepare("UPDATE comics SET view_count = view_count + 1 WHERE comic_id = ?");
    $stmt->execute([$comic_id]);

    // Update daily views
    $stmt = $pdo->prepare("
        INSERT INTO daily_views (comic_id, view_date, view_count) 
        VALUES (?, CURDATE(), 1) 
        ON DUPLICATE KEY UPDATE view_count = view_count + 1
    ");
    $stmt->execute([$comic_id]);

    // Update weekly views
    $stmt = $pdo->prepare("
        INSERT INTO weekly_views (comic_id, week_number, view_count) 
        VALUES (?, DATE_FORMAT(NOW(), '%Y%u'), 1) 
        ON DUPLICATE KEY UPDATE view_count = view_count + 1
    ");
    $stmt->execute([$comic_id]);

    // Update monthly views
    $stmt = $pdo->prepare("
        INSERT INTO monthly_views (comic_id, month_number, view_count) 
        VALUES (?, DATE_FORMAT(NOW(), '%Y%m'), 1) 
        ON DUPLICATE KEY UPDATE view_count = view_count + 1
    ");
    $stmt->execute([$comic_id]);

    $pdo->commit();

    // Get updated counts
    $stmt = $pdo->prepare("
        SELECT 
            c.view_count as total_views,
            COALESCE(d.view_count, 0) as daily_views,
            COALESCE(w.view_count, 0) as weekly_views,
            COALESCE(m.view_count, 0) as monthly_views
        FROM comics c
        LEFT JOIN daily_views d ON c.comic_id = d.comic_id AND d.view_date = CURDATE()
        LEFT JOIN weekly_views w ON c.comic_id = w.comic_id AND w.week_number = DATE_FORMAT(NOW(), '%Y%u')
        LEFT JOIN monthly_views m ON c.comic_id = m.comic_id AND m.month_number = DATE_FORMAT(NOW(), '%Y%m')
        WHERE c.comic_id = ?
    ");
    $stmt->execute([$comic_id]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'views' => $counts
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("View counting error: " . $e->getMessage());
    echo json_encode(['error' => 'Failed to update view count']);
}
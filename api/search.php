<?php
require_once '../config.php';

$query = $_GET['q'] ?? '';
$genre = $_GET['genre'] ?? '';
$status = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 12;
$offset = ($page - 1) * $limit;

try {
    $params = [];
    $where = [];
    
    if ($query) {
        $where[] = "c.title LIKE ?";
        $params[] = "%$query%";
    }
    
    if ($genre) {
        $where[] = "EXISTS (
            SELECT 1 FROM comic_genres cg 
            JOIN genres g ON cg.genre_id = g.genre_id 
            WHERE cg.comic_id = c.comic_id AND g.slug = ?
        )";
        $params[] = $genre;
    }
    
    if ($status) {
        $where[] = "c.status = ?";
        $params[] = $status;
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    $comics = fetchAll("
        SELECT 
            c.*,
            GROUP_CONCAT(DISTINCT g.name) as genres
        FROM comics c
        LEFT JOIN comic_genres cg ON c.comic_id = cg.comic_id
        LEFT JOIN genres g ON cg.genre_id = g.genre_id
        $whereClause
        GROUP BY c.comic_id
        ORDER BY c.view_count DESC
        LIMIT ? OFFSET ?
    ", [...$params, $limit, $offset]);
    
    echo json_encode([
        'status' => 'success',
        'data' => $comics
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Search failed']);
} 
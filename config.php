<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'darkverse');

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Create PDO connection
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Function to handle database queries
    function query($sql, $params = []) {
        global $pdo;
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Log error and show user-friendly message
            error_log("Database Error: " . $e->getMessage());
            throw new Exception("Database error occurred. Please try again later.");
        }
    }

    // Helper function to get single row
    function fetchOne($sql, $params = []) {
        return query($sql, $params)->fetch();
    }

    // Helper function to get multiple rows
    function fetchAll($sql, $params = []) {
        return query($sql, $params)->fetchAll();
    }

    // Helper function to get last inserted ID
    function lastInsertId() {
        global $pdo;
        return $pdo->lastInsertId();
    }

    // Helper function to sanitize input
    function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    // Helper function to check if user is logged in
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Helper function to check if user is admin
    function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    // Helper function to redirect
    function redirect($path) {
        header("Location: $path");
        exit();
    }

    // Helper function to set flash message
    function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    // Helper function to display flash message
    function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    // Helper function to generate slug
    function generateSlug($text) {
        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        // Trim
        $text = trim($text, '-');
        // Remove duplicate -
        $text = preg_replace('~-+~', '-', $text);
        // Lowercase
        $text = strtolower($text);
        
        return $text;
    }

    // Helper function to format date
    function formatDate($date) {
        return date('F j, Y', strtotime($date));
    }

    // Helper function to handle file upload
    function uploadFile($file, $destination, $allowedTypes = ['jpg', 'jpeg', 'png']) {
        try {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Upload failed with error code ' . $file['error']);
            }

            $fileInfo = pathinfo($file['name']);
            $extension = strtolower($fileInfo['extension']);

            if (!in_array($extension, $allowedTypes)) {
                throw new Exception('Invalid file type');
            }

            $newFileName = uniqid() . '.' . $extension;
            $uploadPath = $destination . $newFileName;

            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                throw new Exception('Failed to move uploaded file');
            }

            return $newFileName;
        } catch (Exception $e) {
            error_log("File Upload Error: " . $e->getMessage());
            throw new Exception("File upload failed. Please try again.");
        }
    }

    // Constants for pagination
    define('ITEMS_PER_PAGE', 20);

    // Helper function for pagination
    function paginate($total, $page, $perPage = ITEMS_PER_PAGE) {
        $totalPages = ceil($total / $perPage);
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;
        
        return [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'per_page' => $perPage
        ];
    }

} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Connection Error: " . $e->getMessage());
    die("Could not connect to the database. Please try again later.");
}
?> 
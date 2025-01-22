<?php
session_start();
require_once '../config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../login.php');
}

// Fetch user data
$user = fetchOne("SELECT * FROM users WHERE user_id = ?", [$_SESSION['user_id']]);

try {
    // Fetch reading history with error handling
    $readingHistory = fetchAll("
        SELECT 
            c.comic_id,
            c.title,
            c.cover_image,
            ch.chapter_number,
            ch.title as chapter_title,
            rh.read_at as last_read
        FROM comics c
        LEFT JOIN reading_history rh ON c.comic_id = rh.comic_id
        LEFT JOIN chapters ch ON rh.chapter_id = ch.chapter_id
        WHERE rh.user_id = ?
        ORDER BY rh.read_at DESC
        LIMIT 5
    ", [$_SESSION['user_id']]) ?? [];

    // Fetch continue reading with error handling
    $continueReading = fetchAll("
        SELECT 
            c.comic_id,
            c.title,
            c.cover_image,
            ch.chapter_number,
            (SELECT COUNT(*) FROM chapters WHERE comic_id = c.comic_id) as total_chapters,
            MAX(rh.read_at) as last_read
        FROM comics c
        LEFT JOIN reading_history rh ON c.comic_id = rh.comic_id
        LEFT JOIN chapters ch ON rh.chapter_id = ch.chapter_id
        WHERE rh.user_id = ?
        GROUP BY c.comic_id, c.title, c.cover_image, ch.chapter_number
        ORDER BY last_read DESC
        LIMIT 3
    ", [$_SESSION['user_id']]) ?? [];

    // Get user stats
    $userStats = fetchOne("
        SELECT 
            COUNT(DISTINCT rh.comic_id) as comics_read,
            COUNT(DISTINCT b.comic_id) as bookmark_count,
            (SELECT COUNT(*) FROM collections WHERE user_id = ?) as collection_count,
            SUM(rh.reading_time) as total_reading_time
        FROM users u
        LEFT JOIN reading_history rh ON u.user_id = rh.user_id
        LEFT JOIN bookmarks b ON u.user_id = b.user_id
        WHERE u.user_id = ?
    ", [$_SESSION['user_id'], $_SESSION['user_id']]) ?? [
        'comics_read' => 0,
        'bookmark_count' => 0,
        'collection_count' => 0
    ];

    // Merge stats into user array
    $user = array_merge($user ?? [], $userStats);

} catch (Exception $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $readingHistory = [];
    $continueReading = [];
    $user['comics_read'] = 0;
    $user['bookmark_count'] = 0;
    $user['collection_count'] = 0;
}

// Pastikan semua tabel yang diperlukan sudah ada
$requiredTables = "
CREATE TABLE IF NOT EXISTS reading_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    comic_id INT NOT NULL,
    chapter_id INT NOT NULL,
    read_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (comic_id) REFERENCES comics(comic_id) ON DELETE CASCADE,
    FOREIGN KEY (chapter_id) REFERENCES chapters(chapter_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS bookmarks (
    bookmark_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    comic_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (comic_id) REFERENCES comics(comic_id) ON DELETE CASCADE,
    UNIQUE KEY unique_bookmark (user_id, comic_id)
);

CREATE TABLE IF NOT EXISTS collections (
    collection_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
";

try {
    // Execute table creation queries
    $queries = explode(';', $requiredTables);
    foreach ($queries as $query) {
        if (trim($query)) {
            query($query);
        }
    }
} catch (Exception $e) {
    error_log("Table creation error: " . $e->getMessage());
}

function formatReadingTime($seconds) {
    if (!$seconds) return '0h';
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    if ($hours > 0) {
        return $hours . 'h ' . ($minutes > 0 ? $minutes . 'm' : '');
    }
    return $minutes . 'm';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - DarkVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: '#070709',
                        wine: '#482D2E',
                        rust: '#824334',
                        flame: '#F42C1D',
                        crimson: '#AE191B',
                        blood: '#701C1A',
                    }
                }
            }
        }
    </script>
    <style>
        /* Copy semua style dari index.php */
        .font-fantasy { font-family: 'Cinzel', serif; }
        .font-main { font-family: 'Nunito', sans-serif; }
        
        .dark-gradient {
            background: linear-gradient(135deg, #482D2E, #070709);
        }

        .flame-text {
            background: linear-gradient(45deg, #F42C1D, #AE191B);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(174, 25, 27, 0.2);
        }

        .glow-border {
            position: relative;
        }
        
        .glow-border::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid #F42C1D;
            border-radius: inherit;
            animation: borderGlow 2s ease-in-out infinite;
            pointer-events: none;
        }

        @keyframes borderGlow {
            0%, 100% { box-shadow: 0 0 5px #F42C1D; }
            50% { box-shadow: 0 0 20px #F42C1D; }
        }

        /* Fire Effect untuk sidebar */
        .fire-bg {
            position: relative;
            overflow: hidden;
        }

        .fire-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, #701C1A, #F42C1D);
            opacity: 0.1;
            animation: firePulse 3s ease-in-out infinite;
        }

        @keyframes firePulse {
            0%, 100% { opacity: 0.1; }
            50% { opacity: 0.2; }
        }
    </style>
</head>
<body class="bg-dark font-main text-gray-200">
    <!-- Sidebar dengan tema dark fantasy -->
    <aside class="fixed left-0 top-0 h-screen w-64 bg-dark/95 border-r border-wine/30 p-4 fire-bg">
        <div class="mb-8">
            <h1 class="text-2xl font-fantasy flame-text hover:scale-105 transition-transform">DarkVerse</h1>
        </div>
        
        <!-- User Profile Preview dengan glow effect -->
        <div class="flex items-center gap-3 p-3 bg-wine/10 rounded-lg mb-8 glow-border">
            <img 
                src="../assets/images/avatars/<?= htmlspecialchars($user['avatar']) ?>" 
                alt="User Avatar" 
                class="w-12 h-12 rounded-full border-2 border-flame"
            >
            <div>
                <h2 class="font-semibold"><?= htmlspecialchars($user['username']) ?></h2>
                <p class="text-sm text-gray-400">Level <?= $user['level'] ?? 1 ?></p>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="space-y-2">
            <a href="dashboard.php" class="flex items-center gap-3 p-3 rounded-lg bg-wine/20 text-flame">
                <span>📊</span> Dashboard
            </a>
            <a href="library.php" class="flex items-center gap-3 p-3 rounded-lg hover:bg-wine/20 hover:text-flame transition-colors">
                <span>📚</span> My Library
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-wine/20 hover:text-flame transition-colors">
                <span>📖</span> Reading History
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-wine/20 hover:text-flame transition-colors">
                <span>🔖</span> Bookmarks
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-wine/20 hover:text-flame transition-colors">
                <span>📑</span> Collections
            </a>
            <a href="#" class="flex items-center gap-3 p-3 rounded-lg hover:bg-wine/20 hover:text-flame transition-colors">
                <span>⚙️</span> Settings
            </a>
        </nav>
    </aside>

    <!-- Main Content dengan dark theme -->
    <main class="ml-64 p-8 bg-gradient-to-b from-dark to-wine/5">
        <!-- Overview Section -->
        <section id="overview" class="mb-12">
            <h2 class="text-3xl font-bold mb-6 flame-text font-fantasy">Dashboard Overview</h2>
            
            <!-- Stats Grid dengan glow effect -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-6">
                    <h3 class="text-gray-400 mb-2">Comics Read</h3>
                    <p class="text-2xl font-bold text-flame"><?= $user['comics_read'] ?></p>
                </div>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-6">
                    <h3 class="text-gray-400 mb-2">Reading Time</h3>
                    <p class="text-2xl font-bold text-flame">
                        <?= formatReadingTime($userStats['total_reading_time'] ?? 0) ?>
                    </p>
                </div>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-6">
                    <h3 class="text-gray-400 mb-2">Bookmarks</h3>
                    <p class="text-2xl font-bold text-flame"><?= $user['bookmark_count'] ?></p>
                </div>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-6">
                    <h3 class="text-gray-400 mb-2">Collections</h3>
                    <p class="text-2xl font-bold text-flame"><?= $user['collection_count'] ?></p>
                </div>
            </div>

            <!-- Continue Reading dengan card hover effect -->
            <div class="mb-8">
                <h3 class="text-xl font-bold mb-4 text-flame">Continue Reading</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <?php if (!empty($continueReading)): ?>
                        <?php foreach ($continueReading as $comic): ?>
                            <div class="card-hover glow-border bg-dark border border-wine/30 rounded-lg overflow-hidden">
                                <div class="flex">
                                    <img 
                                        src="../assets/cover/<?= htmlspecialchars($comic['cover_image']) ?>" 
                                        alt="Comic Cover" 
                                        class="w-24 h-32 object-cover"
                                    >
                                    <div class="p-4 flex-1">
                                        <h4 class="font-semibold text-flame mb-1">
                                            <?= htmlspecialchars($comic['title']) ?>
                                        </h4>
                                        <p class="text-sm text-gray-400 mb-2">
                                            Chapter <?= htmlspecialchars($comic['chapter_number']) ?>
                                        </p>
                                        <div class="w-full bg-wine/20 rounded-full h-1 mb-2">
                                            <div class="bg-flame h-1 rounded-full" 
                                                 style="width: <?= ($comic['chapter_number'] * 100) / max(1, $comic['total_chapters']) ?>%">
                                            </div>
                                        </div>
                                        <a href="../comic-detail.php?id=<?= $comic['comic_id'] ?>" 
                                           class="block w-full bg-flame text-white py-1 rounded text-sm text-center">
                                            Continue
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-3 text-center text-gray-400 py-8">
                            No comics to continue reading.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Reading Activity dengan data dari database -->
            <div class="bg-dark border border-wine/30 rounded-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-flame">Reading Activity</h3>
                <?php
                // Fetch reading activity for last 7 days
                $readingActivity = fetchAll("
                    SELECT 
                        DATE(read_date) as read_day,
                        COUNT(*) as chapters_read,
                        SUM(reading_time) as daily_time
                    FROM reading_history
                    WHERE user_id = ? 
                    AND read_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 DAY)
                    GROUP BY DATE(read_date)
                    ORDER BY read_day ASC
                ", [$_SESSION['user_id']]);

                // Create array for all 7 days
                $days = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $days[$date] = 0;
                }

                // Fill in actual reading times
                foreach ($readingActivity as $activity) {
                    $days[$activity['read_day']] = $activity['daily_time'];
                }

                // Get max reading time for scaling
                $maxTime = max($days) ?: 1;
                ?>

                <div class="h-48 flex items-end gap-2">
                    <?php foreach ($days as $date => $time): ?>
                        <?php 
                        $height = ($time / $maxTime) * 100;
                        $dayName = date('D', strtotime($date));
                        $tooltipText = $time > 0 ? formatReadingTime($time) : 'No reading';
                        ?>
                        <div class="flex-1 flex flex-col items-center">
                            <div class="w-full h-[<?= $height ?>%] bg-flame/<?= $time > 0 ? '100' : '20' ?> rounded-t 
                                        hover:bg-flame/80 transition-colors cursor-pointer relative group">
                                <!-- Tooltip -->
                                <div class="absolute bottom-full mb-2 left-1/2 transform -translate-x-1/2 
                                            bg-darker text-gray-300 px-2 py-1 rounded text-xs whitespace-nowrap
                                            opacity-0 group-hover:opacity-100 transition-opacity">
                                    <?= $tooltipText ?>
                                </div>
                            </div>
                            <div class="mt-2 text-sm text-gray-400"><?= $dayName ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Legend -->
                <div class="mt-4 flex items-center justify-end gap-4 text-sm text-gray-400">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-flame rounded"></div>
                        <span>Reading activity</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 bg-flame/20 rounded"></div>
                        <span>No activity</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- My Library Section -->
        <section id="library" class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold flame-text font-fantasy">My Library</h2>
                <div class="flex gap-4">
                    <a href="my_library.php" class="px-4 py-2 bg-flame text-white rounded-lg hover:bg-crimson">
                        View All
                    </a>
                </div>
            </div>

            <!-- Library Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php foreach ($readingHistory as $comic): ?>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                    <img src="assets/images/comic2.jpg" alt="Comic Cover" class="w-full h-48 object-cover">
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-flame mb-1"><?= htmlspecialchars($comic['title']) ?></h3>
                        <p class="text-xs text-gray-400">Last read: <?= date('d M', strtotime($comic['last_read'])) ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Reading History -->
        <section id="reading-history" class="mb-12">
            <h2 class="text-3xl font-bold mb-6 flame-text font-fantasy">Reading History</h2>
            <div class="bg-dark border border-wine/30 rounded-lg">
                <?php if (!empty($readingHistory)): ?>
                    <?php foreach ($readingHistory as $history): ?>
                        <div class="p-4 border-b border-wine/30">
                            <div class="flex items-center gap-4">
                                <img 
                                    src="../assets/cover/<?= htmlspecialchars($history['cover_image']) ?>" 
                                    alt="Comic Cover" 
                                    class="w-16 h-20 object-cover rounded"
                                >
                                <div class="flex-1">
                                    <h3 class="font-semibold text-flame">
                                        <?= htmlspecialchars($history['title']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-400">
                                        Chapter <?= htmlspecialchars($history['chapter_number']) ?>
                                        <?php if (!empty($history['chapter_title'])): ?>
                                            - <?= htmlspecialchars($history['chapter_title']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Read <?= date('d M', strtotime($history['last_read'])) ?>
                                    </p>
                                </div>
                                <a href="../comic-detail.php?id=<?= $history['comic_id'] ?>" 
                                   class="px-4 py-2 bg-wine/20 text-flame rounded-lg hover:bg-wine/30">
                                    Read Again
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="p-8 text-center text-gray-400">
                        No reading history yet.
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Recent Activity Section -->
        <div class="bg-dark border border-wine/30 rounded-lg p-6 mb-8">
            <h3 class="text-xl font-bold mb-4 flame-text">Recent Activity</h3>
            
            <?php
            // Fetch user's recent activity (reading history, bookmarks, etc.)
            $recentActivity = fetchAll("
                SELECT 
                    'read' as type,
                    c.comic_id,
                    c.title,
                    c.cover_image,
                    ch.chapter_number,
                    rh.read_at as activity_date
                FROM reading_history rh
                JOIN comics c ON rh.comic_id = c.comic_id
                JOIN chapters ch ON rh.chapter_id = ch.chapter_id
                WHERE rh.user_id = ?
                
                UNION ALL
                
                SELECT 
                    'bookmark' as type,
                    c.comic_id,
                    c.title,
                    c.cover_image,
                    NULL as chapter_number,
                    b.created_at as activity_date
                FROM bookmarks b
                JOIN comics c ON b.comic_id = c.comic_id
                WHERE b.user_id = ?
                
                ORDER BY activity_date DESC
                LIMIT 5
            ", [$_SESSION['user_id'], $_SESSION['user_id']]);
            ?>

            <?php if (!empty($recentActivity)): ?>
                <div class="space-y-4">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="flex items-center gap-4 p-4 bg-wine/10 rounded-lg hover:bg-wine/20 transition-colors">
                            <!-- Comic Cover -->
                            <img 
                                src="../assets/cover/<?= htmlspecialchars($activity['cover_image']) ?>" 
                                alt="<?= htmlspecialchars($activity['title']) ?>" 
                                class="w-16 h-24 object-cover rounded"
                            >
                            
                            <!-- Activity Info -->
                            <div class="flex-1">
                                <h4 class="font-semibold text-flame">
                                    <?= htmlspecialchars($activity['title']) ?>
                                </h4>
                                <p class="text-sm text-gray-400">
                                    <?php if ($activity['type'] === 'read'): ?>
                                        Read Chapter <?= htmlspecialchars($activity['chapter_number']) ?>
                                    <?php else: ?>
                                        Added to Library
                                    <?php endif; ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?= date('M d, Y H:i', strtotime($activity['activity_date'])) ?>
                                </p>
                            </div>
                            
                            <!-- Action Button -->
                            <a href="../comic-detail.php?id=<?= $activity['comic_id'] ?>" 
                               class="px-4 py-2 bg-flame text-white rounded hover:bg-crimson transition-colors">
                                <?= $activity['type'] === 'read' ? 'Continue' : 'Read' ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-400 text-center py-8">No recent activity yet.</p>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Copy script dari index.php untuk animasi dan efek
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('nav a');
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navLinks.forEach(l => l.classList.remove('bg-wine/20', 'text-flame'));
                    this.classList.add('bg-wine/20', 'text-flame');
                });
            });
        });
    </script>
</body>
</html> 
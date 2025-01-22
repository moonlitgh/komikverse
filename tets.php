<?php
session_start();
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

// Fetch reading history with detailed information
$readingHistory = fetchAll("
    SELECT 
        c.comic_id,
        c.title as comic_title,
        c.cover_image,
        ch.chapter_id,
        ch.chapter_number,
        ch.title as chapter_title,
        rh.read_at,
        rh.page_number,
        (SELECT COUNT(*) FROM chapters WHERE comic_id = c.comic_id) as total_chapters
    FROM reading_history rh
    JOIN comics c ON rh.comic_id = c.comic_id
    JOIN chapters ch ON rh.chapter_id = ch.chapter_id
    WHERE rh.user_id = ?
    ORDER BY rh.read_at DESC
", [$_SESSION['user_id']]);

// Group by date for better organization
$groupedHistory = [];
foreach ($readingHistory as $history) {
    $date = date('Y-m-d', strtotime($history['read_at']));
    $groupedHistory[$date][] = $history;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading History - DarkVerse</title>
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
        .font-fantasy { font-family: 'Cinzel', serif; }
        .font-main { font-family: 'Nunito', sans-serif; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-darker text-gray-300">
    <?php include '../includes/navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold flame-text font-fantasy">Reading History</h1>
            
            <!-- Filter options if needed -->
            <div class="flex gap-4">
                <select id="timeFilter" class="bg-dark border border-wine/30 rounded-lg px-4 py-2 text-gray-300">
                    <option value="all">All Time</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                </select>
            </div>
        </div>

        <?php if (!empty($groupedHistory)): ?>
            <?php foreach ($groupedHistory as $date => $entries): ?>
                <div class="mb-8">
                    <!-- Date Header -->
                    <h2 class="text-xl font-semibold text-flame mb-4">
                        <?= date('F j, Y', strtotime($date)) ?>
                    </h2>

                    <!-- History Cards -->
                    <div class="space-y-4">
                        <?php foreach ($entries as $history): ?>
                            <div class="card-hover bg-dark border border-wine/30 rounded-lg p-4">
                                <div class="flex items-center gap-4">
                                    <!-- Comic Cover -->
                                    <img src="../assets/cover/<?= htmlspecialchars($history['cover_image']) ?>" 
                                         alt="<?= htmlspecialchars($history['comic_title']) ?>" 
                                         class="w-20 h-28 object-cover rounded">
                                    
                                    <!-- Info -->
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-flame">
                                            <?= htmlspecialchars($history['comic_title']) ?>
                                        </h3>
                                        <p class="text-sm text-gray-400 mt-1">
                                            Chapter <?= htmlspecialchars($history['chapter_number']) ?>
                                            <?php if (!empty($history['chapter_title'])): ?>
                                                - <?= htmlspecialchars($history['chapter_title']) ?>
                                            <?php endif; ?>
                                        </p>
                                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                            <span>
                                                Page <?= $history['page_number'] ?>
                                            </span>
                                            <span>
                                                <?= date('H:i', strtotime($history['read_at'])) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex flex-col gap-2">
                                        <a href="../reader.php?comic=<?= $history['comic_id'] ?>&chapter=<?= $history['chapter_id'] ?>" 
                                           class="px-4 py-2 bg-flame text-white rounded hover:bg-crimson transition-colors">
                                            Continue
                                        </a>
                                        <a href="../comic-detail.php?id=<?= $history['comic_id'] ?>" 
                                           class="px-4 py-2 bg-wine/20 text-flame rounded hover:bg-wine/30 transition-colors">
                                            Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Load More Button -->
            <div class="text-center mt-8">
                <button id="loadMore" class="px-6 py-2 bg-wine/20 text-flame rounded-lg hover:bg-wine/30 transition-colors">
                    Load More
                </button>
            </div>

        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-400">No reading history yet.</p>
                <a href="../index.php" class="inline-block mt-4 px-6 py-2 bg-flame text-white rounded-lg hover:bg-crimson">
                    Start Reading
                </a>
            </div>
        <?php endif; ?>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Time filter handling
        const timeFilter = document.getElementById('timeFilter');
        if (timeFilter) {
            timeFilter.addEventListener('change', function() {
                window.location.href = 'reading_history.php?filter=' + this.value;
            });
        }

        // Load more functionality
        let page = 1;
        const loadMore = document.getElementById('loadMore');
        if (loadMore) {
            loadMore.addEventListener('click', function() {
                page++;
                // Add AJAX call here to load more history
                // For now, just hide the button after first click
                this.style.display = 'none';
            });
        }
    });
    </script>
</body>
</html> 
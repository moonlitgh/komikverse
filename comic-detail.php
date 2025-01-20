<?php
session_start();
require_once 'config.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get comic ID from URL
$comic_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
error_log("Accessing comic ID: " . $comic_id); // Debug log

if (!$comic_id) {
    redirect('404.php');
}

try {
    // Basic comic info with error checking
    $comic = fetchOne("SELECT * FROM comics WHERE comic_id = ?", [$comic_id]);
    error_log("Comic query result: " . print_r($comic, true)); // Debug log
    
    if (!$comic) {
        error_log("Comic not found with ID: " . $comic_id);
        redirect('404.php');
    }

    // Get genres
    $genres = fetchAll("
        SELECT g.name 
        FROM comic_genres cg 
        JOIN genres g ON cg.genre_id = g.genre_id 
        WHERE cg.comic_id = ?
    ", [$comic_id]);
    
    $comic['genres'] = !empty($genres) ? implode(', ', array_column($genres, 'name')) : '';

    // Get chapters count
    $chaptersCount = fetchOne("
        SELECT COUNT(*) as total 
        FROM chapters 
        WHERE comic_id = ?
    ", [$comic_id]);
    
    $comic['total_chapters'] = $chaptersCount ? $chaptersCount['total'] : 0;

    // Get chapters list
    $chapters = fetchAll("
        SELECT * FROM chapters 
        WHERE comic_id = ? 
        ORDER BY chapter_number DESC
    ", [$comic_id]);
    
    $comic['chapters'] = $chapters ?: [];

    // Get ratings
    $ratings = fetchOne("
        SELECT 
            COUNT(*) as count,
            COALESCE(AVG(rating), 0) as average 
        FROM ratings 
        WHERE comic_id = ?
    ", [$comic_id]);
    
    $comic['rating_count'] = $ratings ? $ratings['count'] : 0;
    $comic['avg_rating'] = $ratings ? $ratings['average'] : 0;

    // Get bookmarks
    $bookmarks = fetchOne("
        SELECT COUNT(*) as total 
        FROM bookmarks 
        WHERE comic_id = ?
    ", [$comic_id]);
    
    $comic['bookmark_count'] = $bookmarks ? $bookmarks['total'] : 0;

    // Get views
    $views = fetchOne("
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
    ", [$comic_id]);

    // Set view counts with defaults
    $comic['total_views'] = $views ? ($views['total_views'] ?? 0) : 0;
    $comic['daily_views'] = $views ? ($views['daily_views'] ?? 0) : 0;
    $comic['weekly_views'] = $views ? ($views['weekly_views'] ?? 0) : 0;
    $comic['monthly_views'] = $views ? ($views['monthly_views'] ?? 0) : 0;

    error_log("Final comic data: " . print_r($comic, true)); // Debug log

} catch (Exception $e) {
    error_log("Error in comic-detail.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    $comic = null;
}

// Check if we have valid comic data
if (!$comic) {
    ?>
    <div class="min-h-screen bg-dark flex items-center justify-center">
        <div class="text-center p-8 bg-dark/50 rounded-lg shadow-lg max-w-md mx-auto">
            <h1 class="text-2xl text-flame mb-4">Error Loading Comic</h1>
            <p class="text-gray-400 mb-6">Sorry, we couldn't load the comic details. Please try again later.</p>
            <a href="index.php" class="inline-block bg-flame text-white px-6 py-2 rounded-lg hover:bg-crimson transition-colors">
                Return to Home
            </a>
        </div>
    </div>
    <?php
    exit;
}

// Fetch similar comics
$similar_comics = fetchAll("
    SELECT 
        c.*,
        GROUP_CONCAT(DISTINCT g.name) as genres
    FROM comics c
    JOIN comic_genres cg1 ON c.comic_id = cg1.comic_id
    JOIN comic_genres cg2 ON cg2.genre_id = cg1.genre_id
    JOIN genres g ON cg1.genre_id = g.genre_id
    WHERE cg2.comic_id = ? AND c.comic_id != ?
    GROUP BY c.comic_id
    LIMIT 4
", [$comic_id, $comic_id]);

// Update view count
query("UPDATE comics SET view_count = view_count + 1 WHERE comic_id = ?", [$comic_id]);

// Check if user has bookmarked this comic
$isBookmarked = false;
if (isset($_SESSION['user_id'])) {
    $isBookmarked = fetchOne(
        "SELECT * FROM bookmarks WHERE user_id = ? AND comic_id = ?",
        [$_SESSION['user_id'], $comic['comic_id']]
    ) ? true : false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($comic['title']) ?> - DarkVerse</title>
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

        .btn-glow {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-glow:hover {
            box-shadow: 0 0 20px rgba(244, 44, 29, 0.4);
        }

        .btn-glow::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                transparent,
                rgba(244, 44, 29, 0.1),
                transparent
            );
            transform: rotate(45deg);
            animation: glow 1.5s linear infinite;
        }

        @keyframes glow {
            0% { transform: rotate(45deg) translateX(-100%); }
            100% { transform: rotate(45deg) translateX(100%); }
        }

        /* Glowing Border Effect */
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

        /* Floating Elements */
        .float {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Fire Effect */
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

        /* Smoke Effect */
        .smoke {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: smoke 3s ease-out infinite;
        }

        @keyframes smoke {
            0% { 
                transform: translateY(0) scale(1);
                opacity: 0.5;
            }
            100% { 
                transform: translateY(-50px) scale(3);
                opacity: 0;
            }
        }

        /* Blood Drip Effect */
        .blood-drip {
            position: relative;
            overflow: hidden;
        }

        .blood-drip::after {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            width: 2px;
            height: 10px;
            background: #AE191B;
            animation: drip 2s ease-in infinite;
        }

        @keyframes drip {
            0% { transform: translateY(-100%); }
            100% { transform: translateY(100%); }
        }

        /* Scroll Reveal */
        .reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.6s ease-out;
        }

        .reveal.active {
            opacity: 1;
            transform: translateY(0);
        }

        /* Floating Skulls Effect */
        .skull {
            position: absolute;
            width: 20px;
            height: 20px;
            opacity: 0;
            pointer-events: none;
            animation: floatSkull 8s ease-in-out infinite;
        }

        @keyframes floatSkull {
            0% {
                transform: translateY(100vh) rotate(0deg) scale(0.5);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateY(-20vh) rotate(360deg) scale(1.5);
                opacity: 0;
            }
        }

        /* Red Mist Effect */
        .mist {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, transparent 50%, #70101030 100%);
            mix-blend-mode: multiply;
            animation: mistPulse 10s ease-in-out infinite;
        }

        @keyframes mistPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.7; }
        }

        /* Flying Ravens Effect */
        .raven {
            position: absolute;
            pointer-events: none;
            animation: ravenFly 15s linear infinite;
        }

        @keyframes ravenFly {
            0% {
                transform: translate(-100vw, 50vh) rotate(15deg) scale(0.5);
                opacity: 0;
            }
            10% {
                opacity: 0.8;
            }
            90% {
                opacity: 0.8;
            }
            100% {
                transform: translate(100vw, 10vh) rotate(-15deg) scale(1);
                opacity: 0;
            }
        }
        
        /* Comic Detail Specific Styles */
        .chapter-list {
            max-height: 400px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #F42C1D #070709;
        }
        
        .chapter-list::-webkit-scrollbar {
            width: 8px;
        }
        
        .chapter-list::-webkit-scrollbar-track {
            background: #070709;
        }
        
        .chapter-list::-webkit-scrollbar-thumb {
            background-color: #F42C1D;
            border-radius: 20px;
        }

        .rating-stars {
            display: inline-flex;
            gap: 4px;
        }

        .star {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .star:hover {
            transform: scale(1.2);
        }
    </style>
</head>
<body class="bg-dark font-main text-gray-200">
    <!-- Navbar (sama seperti halaman lain) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Comic Detail Header -->
    <section class="pt-24 pb-12 dark-gradient relative overflow-hidden">
        <div class="container mx-auto px-4 relative z-10">
            <div class="flex flex-col md:flex-row gap-8">
                <!-- Cover Image -->
                <div class="w-full md:w-1/3 lg:w-1/4">
                    <div class="relative group">
                        <img 
                            src="assets/cover/<?= htmlspecialchars($comic['cover_image']) ?>" 
                            alt="<?= htmlspecialchars($comic['title']) ?>" 
                            class="w-full rounded-lg shadow-lg glow-border"
                        >
                        <div class="absolute inset-0 bg-gradient-to-t from-dark to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col gap-3 mt-4">
                        <div class="flex gap-4 mb-6">
                            <a href="read.php?comic=<?= $comic['comic_id'] ?>&chapter=1" 
                               class="flex-1 btn-glow bg-flame text-white text-center py-3 px-6 rounded-lg hover:bg-crimson transition-colors">
                                Start Reading
                            </a>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button id="bookmarkBtn" 
                                        data-comic-id="<?= $comic['comic_id'] ?>"
                                        class="px-6 py-3 rounded-lg border border-wine/30 hover:border-flame transition-colors <?= $isBookmarked ? 'bg-flame/20' : 'bg-dark/80' ?>">
                                    <span id="bookmarkIcon" class="text-flame"><?= $isBookmarked ? '★' : '☆' ?></span>
                                    <span id="bookmarkText" class="text-white ml-2"><?= $isBookmarked ? 'Bookmarked' : 'Add to Library' ?></span>
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="px-6 py-3 bg-dark/80 text-white rounded-lg border border-wine/30 hover:border-flame transition-colors">
                                    Login to Bookmark
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Comic Info -->
                <div class="flex-1">
                    <h1 class="text-3xl font-bold text-flame mb-2"><?= htmlspecialchars($comic['title']) ?></h1>
                    <p class="text-gray-400 mb-4">
                        By <?= htmlspecialchars($comic['author'] ?? 'Unknown') ?>
                        • <?= strtolower($comic['status'] ?? 'unknown') ?>
                        • <?= number_format($comic['view_count'] ?? 0) ?> views
                    </p>

                    <!-- Genres -->
                    <div class="flex flex-wrap gap-2 mb-4">
                        <?php if ($comic['genres']): ?>
                            <?php foreach (explode(',', $comic['genres']) as $genre): ?>
                                <span class="bg-blood/20 text-flame text-sm px-3 py-1 rounded">
                                    <?= htmlspecialchars(trim($genre)) ?>
                                </span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Rating Component -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-2">Rate this Comic</h3>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php
                            // Get user's current rating if exists
                            $userRating = fetchOne("
                                SELECT rating 
                                FROM ratings 
                                WHERE user_id = ? AND comic_id = ?", 
                                [$_SESSION['user_id'], $comic['comic_id']]
                            );
                            $currentRating = $userRating ? $userRating['rating'] : 0;
                            ?>
                            <div class="flex items-center gap-4">
                                <div class="rating-stars" data-comic-id="<?= $comic['comic_id'] ?>">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star cursor-pointer text-2xl <?= $i <= $currentRating ? 'text-flame' : 'text-gray-400' ?>" 
                                              data-rating="<?= $i ?>">
                                            <?= $i <= $currentRating ? '★' : '☆' ?>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-gray-400" id="ratingStats">
                                    <?= number_format($comic['avg_rating'] ?? 0, 1) ?> 
                                    (<?= number_format($comic['rating_count'] ?? 0) ?> ratings)
                                </span>
                            </div>
                        <?php else: ?>
                            <div class="flex items-center gap-4">
                                <div class="rating-stars opacity-50">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="text-2xl text-gray-400">☆</span>
                                    <?php endfor; ?>
                                </div>
                                <a href="login.php" class="text-flame hover:underline">Login to rate</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Synopsis Section -->
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-flame mb-2">Synopsis</h2>
                        <div class="bg-dark/50 p-4 rounded-lg">
                            <p class="text-gray-300 leading-relaxed">
                                <?= nl2br(htmlspecialchars($comic['description'] ?? 'No synopsis available.')) ?>
                            </p>
                        </div>
                    </div>

                    <!-- Statistics Section -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-dark/50 rounded-lg">
                            <div class="text-2xl font-bold text-flame mb-1">
                                <?= number_format((int)$comic['total_chapters']) ?>
                            </div>
                            <div class="text-gray-400 text-sm">Chapters</div>
                        </div>
                        <div class="text-center p-4 bg-dark/50 rounded-lg">
                            <div class="text-2xl font-bold text-flame mb-1">
                                <?= number_format((int)$comic['bookmark_count']) ?>
                            </div>
                            <div class="text-gray-400 text-sm">Bookmarks</div>
                        </div>
                        <div class="text-center p-4 bg-dark/50 rounded-lg">
                            <div class="text-2xl font-bold text-flame mb-1">
                                <?= number_format((int)$comic['total_views']) ?>
                            </div>
                            <div class="text-gray-400 text-sm">Total Views</div>
                        </div>
                    </div>

                    <!-- Period Views Section -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center p-4 bg-dark/50 rounded-lg">
                            <div class="text-xl font-bold text-flame mb-1">
                                <?= number_format((int)$comic['daily_views']) ?>
                            </div>
                            <div class="text-gray-400 text-sm">Today</div>
                        </div>
                        <div class="text-center p-4 bg-dark/50 rounded-lg">
                            <div class="text-xl font-bold text-flame mb-1">
                                <?= number_format((int)$comic['weekly_views']) ?>
                            </div>
                            <div class="text-gray-400 text-sm">This Week</div>
                        </div>
                        <div class="text-center p-4 bg-dark/50 rounded-lg">
                            <div class="text-xl font-bold text-flame mb-1">
                                <?= number_format((int)$comic['monthly_views']) ?>
                            </div>
                            <div class="text-gray-400 text-sm">This Month</div>
                        </div>
                    </div>

                    <!-- New Buttons -->
                    <div class="flex gap-4 mb-6">
                        <?php if (isLoggedIn()): ?>
                            <!-- Bookmark Button -->
                            <button id="bookmarkBtn" 
                                    data-comic-id="<?= $comic['comic_id'] ?>"
                                    class="flex items-center gap-2 px-6 py-3 rounded-lg border-2 border-flame text-flame hover:bg-flame hover:text-white transition-colors <?= $isBookmarked ? 'bg-flame/20' : '' ?>">
                                <span id="bookmarkIcon" class="text-xl"><?= $isBookmarked ? '★' : '☆' ?></span>
                                <span id="bookmarkText"><?= $isBookmarked ? 'Bookmarked' : 'Add to Library' ?></span>
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="flex items-center gap-2 px-6 py-3 rounded-lg border-2 border-flame text-flame hover:bg-flame hover:text-white transition-colors">
                                <span class="text-xl">☆</span>
                                <span>Login to Bookmark</span>
                            </a>
                        <?php endif; ?>

                        <!-- Read Now Button -->
                        <?php if (!empty($chapters)): ?>
                            <?php
                            // Get the first or latest chapter
                            $latestChapter = reset($chapters); // Gets first element of array (latest chapter)
                            $readUrl = "read.php?comic=" . $comic['comic_id'] . "&chapter=" . $latestChapter['chapter_number'];
                            ?>
                            <a href="<?= $readUrl ?>" 
                               class="flex items-center gap-2 px-6 py-3 rounded-lg bg-flame text-white hover:bg-crimson transition-colors">
                                <span class="text-xl">►</span>
                                <span>Read Now</span>
                            </a>
                        <?php else: ?>
                            <button disabled 
                                    class="flex items-center gap-2 px-6 py-3 rounded-lg bg-gray-600 text-white cursor-not-allowed">
                                <span class="text-xl">►</span>
                                <span>No Chapters Yet</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Chapters List -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-6">Chapters</h2>
            <div class="chapter-list bg-wine/10 rounded-lg p-4">
                <?php foreach ($chapters as $chapter): ?>
                    <a href="read.php?comic=<?= $comic['comic_id'] ?>&chapter=<?= $chapter['chapter_number'] ?>" 
                       class="flex items-center justify-between p-4 hover:bg-wine/20 rounded transition-colors">
                        <div>
                            <span class="font-semibold">Chapter <?= $chapter['chapter_number'] ?></span>
                            <?php if ($chapter['title']): ?>
                                <span class="text-gray-400 ml-2">- <?= htmlspecialchars($chapter['title']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="text-gray-400 text-sm">
                            <?= number_format($chapter['view_count']) ?> views
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Similar Comics -->
    <?php if (!empty($similar_comics)): ?>
    <section class="py-12 bg-wine/5">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold mb-6">Similar Comics</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($similar_comics as $similar): ?>
                    <a href="comic-detail.php?id=<?= $similar['comic_id'] ?>" 
                       class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                        <div class="relative aspect-[3/4]">
                            <img 
                                src="assets/cover/<?= htmlspecialchars($similar['cover_image']) ?>" 
                                alt="<?= htmlspecialchars($similar['title']) ?>"
                                class="w-full h-full object-cover"
                            >
                            <div class="absolute inset-0 bg-gradient-to-t from-dark to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-flame mb-1">
                                <?= htmlspecialchars($similar['title']) ?>
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <?php 
                                $similarGenres = explode(',', $similar['genres']);
                                $firstTwoGenres = array_slice($similarGenres, 0, 2);
                                foreach ($firstTwoGenres as $genre): 
                                ?>
                                    <span class="bg-blood/20 text-flame text-xs px-2 py-1 rounded">
                                        <?= htmlspecialchars(trim($genre)) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Reviews Section -->
    <section class="py-12 bg-dark/50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-flame">Reviews</h2>
                <?php if (isLoggedIn()): ?>
                    <?php
                    $userReview = fetchOne("
                        SELECT * FROM reviews 
                        WHERE user_id = ? AND comic_id = ?", 
                        [$_SESSION['user_id'], $comic_id]
                    );
                    ?>
                    <?php if (!$userReview): ?>
                        <button id="writeReviewBtn" 
                                class="px-4 py-2 bg-flame text-white rounded hover:bg-crimson transition-colors">
                            Write Review
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Review Form Modal -->
            <div id="reviewModal" class="fixed inset-0 bg-black/50 hidden z-50">
                <div class="absolute inset-0 flex items-center justify-center p-4">
                    <div class="bg-dark border border-wine/30 rounded-lg p-6 max-w-2xl w-full">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold text-flame">Write Your Review</h3>
                            <button type="button" id="closeReviewModal" class="text-gray-400 hover:text-flame">
                                ✕
                            </button>
                        </div>
                        <form id="reviewForm" class="space-y-4">
                            <input type="hidden" name="comic_id" value="<?= $comic_id ?>">
                            <div>
                                <label for="reviewContent" class="block text-gray-400 mb-2">Your Review</label>
                                <textarea id="reviewContent" 
                                        name="content" 
                                        rows="5"
                                        class="w-full bg-darker border border-wine/30 rounded p-3 
                                               text-gray-100 placeholder-gray-500
                                               focus:border-flame focus:ring-1 focus:ring-flame"
                                        placeholder="Share your thoughts about this comic..."
                                        required></textarea>
                            </div>
                            <div class="flex justify-end gap-4">
                                <button type="button" 
                                        id="cancelReview"
                                        class="px-4 py-2 border border-wine/30 text-gray-300 rounded 
                                               hover:bg-wine/20 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-4 py-2 bg-flame text-white rounded 
                                               hover:bg-crimson transition-colors">
                                    Post Review
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Reviews List -->
            <div class="space-y-6" id="reviewsList">
                <?php
                $reviews = fetchAll("
                    SELECT 
                        r.*,
                        u.username,
                        u.avatar,
                        (SELECT COUNT(*) FROM review_reactions WHERE review_id = r.review_id AND type = 'like') as likes,
                        (SELECT COUNT(*) FROM review_reactions WHERE review_id = r.review_id AND type = 'dislike') as dislikes,
                        CASE 
                            WHEN ? IS NOT NULL THEN 
                                (SELECT type FROM review_reactions WHERE review_id = r.review_id AND user_id = ?)
                            ELSE NULL
                        END as user_reaction
                    FROM reviews r
                    JOIN users u ON r.user_id = u.user_id
                    WHERE r.comic_id = ? AND r.status = 'active'
                    ORDER BY r.created_at DESC",
                    [
                        isLoggedIn() ? $_SESSION['user_id'] : null,
                        isLoggedIn() ? $_SESSION['user_id'] : null,
                        $comic_id
                    ]
                );
                ?>

                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-wine/10 rounded-lg p-6">
                            <div class="flex items-start gap-4">
                                <img src="assets/avatars/<?= htmlspecialchars($review['avatar']) ?>" 
                                     alt="<?= htmlspecialchars($review['username']) ?>"
                                     class="w-12 h-12 rounded-full">
                                <div class="flex-1">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="font-semibold text-flame">
                                                <?= htmlspecialchars($review['username']) ?>
                                            </h4>
                                            <p class="text-sm text-gray-400">
                                                <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                            </p>
                                        </div>
                                        <?php if (isLoggedIn()): ?>
                                            <div class="flex items-center gap-2">
                                                <button class="reaction-btn <?= $review['user_reaction'] === 'like' ? 'text-flame' : 'text-gray-400' ?>"
                                                        data-review-id="<?= $review['review_id'] ?>"
                                                        data-type="like">
                                                    <span class="text-xl">👍</span>
                                                    <span class="likes-count"><?= $review['likes'] ?></span>
                                                </button>
                                                <button class="reaction-btn <?= $review['user_reaction'] === 'dislike' ? 'text-flame' : 'text-gray-400' ?>"
                                                        data-review-id="<?= $review['review_id'] ?>"
                                                        data-type="dislike">
                                                    <span class="text-xl">👎</span>
                                                    <span class="dislikes-count"><?= $review['dislikes'] ?></span>
                                                </button>
                                                <?php if ($_SESSION['user_id'] !== $review['user_id']): ?>
                                                    <button class="report-btn text-gray-400 hover:text-flame"
                                                            data-review-id="<?= $review['review_id'] ?>">
                                                        ⚠️
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-4 text-white">
                                        <?= nl2br(htmlspecialchars($review['content'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-12 text-gray-400">
                        No reviews yet. Be the first to write one!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        // Bookmark functionality
        const bookmarkBtn = document.getElementById('bookmarkBtn');
        if (bookmarkBtn) {
            bookmarkBtn.addEventListener('click', async () => {
                try {
                    const response = await fetch('api/bookmark.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            comic_id: bookmarkBtn.dataset.comicId
                        })
                    });

                    // Pastikan response bisa di-parse sebagai JSON
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        console.error('Failed to parse JSON response:', e);
                        throw new Error('Invalid server response');
                    }
                    
                    if (response.ok && data) {
                        const icon = document.getElementById('bookmarkIcon');
                        const text = document.getElementById('bookmarkText');
                        
                        if (data.status === 'added') {
                            icon.textContent = '★';
                            text.textContent = 'Bookmarked';
                            bookmarkBtn.classList.add('bg-flame/20');
                        } else if (data.status === 'removed') {
                            icon.textContent = '☆';
                            text.textContent = 'Add to Library';
                            bookmarkBtn.classList.remove('bg-flame/20');
                        }
                    } else {
                        throw new Error(data.error || 'Failed to update bookmark');
                    }
                } catch (error) {
                    console.error('Error updating bookmark:', error);
                    // Refresh halaman alih-alih menampilkan alert
                    location.reload();
                }
            });
        }

        // Rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const ratingStars = document.querySelector('.rating-stars');
            if (!ratingStars) return;

            const stars = ratingStars.querySelectorAll('.star');
            const comicId = ratingStars.dataset.comicId;
            const ratingStats = document.getElementById('ratingStats');

            // Hover effect
            stars.forEach((star, index) => {
                star.addEventListener('mouseover', () => {
                    stars.forEach((s, i) => {
                        s.textContent = i <= index ? '★' : '☆';
                        s.classList.toggle('text-flame', i <= index);
                        s.classList.toggle('text-gray-400', i > index);
                    });
                });

                star.addEventListener('mouseout', () => {
                    const currentRating = getCurrentRating();
                    stars.forEach((s, i) => {
                        s.textContent = i < currentRating ? '★' : '☆';
                        s.classList.toggle('text-flame', i < currentRating);
                        s.classList.toggle('text-gray-400', i >= currentRating);
                    });
                });
            });

            // Click handler
            stars.forEach(star => {
                star.addEventListener('click', async () => {
                    const rating = star.dataset.rating;
                    try {
                        const response = await fetch('api/rate.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                comic_id: comicId,
                                rating: rating
                            })
                        });

                        let data;
                        try {
                            data = await response.json();
                        } catch (e) {
                            console.error('Failed to parse JSON response:', e);
                            throw new Error('Invalid server response');
                        }
                        
                        if (response.ok && data) {
                            // Update stars visual
                            stars.forEach((s, i) => {
                                s.textContent = i < rating ? '★' : '☆';
                                s.classList.toggle('text-flame', i < rating);
                                s.classList.toggle('text-gray-400', i >= rating);
                            });

                            // Update rating stats
                            if (ratingStats && data.avg_rating !== undefined) {
                                ratingStats.textContent = `${data.avg_rating} (${data.total_ratings} ratings)`;
                            }
                        } else {
                            throw new Error(data.error || 'Failed to update rating');
                        }
                    } catch (error) {
                        console.error('Error rating comic:', error);
                        // Refresh halaman alih-alih menampilkan alert
                        location.reload();
                    }
                });
            });

            function getCurrentRating() {
                return Array.from(stars).filter(s => s.textContent === '★').length;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Record view when page loads
            fetch('api/view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    comic_id: <?= $comic['comic_id'] ?>
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    console.log('View recorded successfully');
                }
            })
            .catch(error => {
                console.error('Error recording view:', error);
            });
        });
    </script>
    <script src="assets/js/reviews.js"></script>
</body>
</html> 
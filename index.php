<?php
require_once 'config.php';

// Fetch hot comics
$hotComics = fetchAll("
    SELECT c.*, GROUP_CONCAT(g.name) as genres 
    FROM comics c 
    LEFT JOIN comic_genres cg ON c.comic_id = cg.comic_id 
    LEFT JOIN genres g ON cg.genre_id = g.genre_id 
    WHERE c.is_featured = 1 
    GROUP BY c.comic_id 
    ORDER BY COALESCE(c.rating, 0) DESC, c.view_count DESC 
    LIMIT 4
");

// Fetch latest updates
$latestUpdates = fetchAll("
    SELECT 
        c.comic_id, 
        c.title, 
        c.cover_image, 
        c.created_at,
        COALESCE(ch.chapter_number, 'New') as chapter_number,
        GROUP_CONCAT(g.name) as genres
    FROM comics c
    LEFT JOIN chapters ch ON c.comic_id = ch.comic_id
    LEFT JOIN comic_genres cg ON c.comic_id = cg.comic_id
    LEFT JOIN genres g ON cg.genre_id = g.genre_id
    GROUP BY c.comic_id
    ORDER BY c.created_at DESC
    LIMIT 5
");

// Fetch trending comics
$trendingComics = fetchAll("
    SELECT c.*, GROUP_CONCAT(g.name) as genres,
           (SELECT COUNT(*) FROM reading_history rh WHERE rh.comic_id = c.comic_id 
            AND rh.last_read >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as daily_views
    FROM comics c
    LEFT JOIN comic_genres cg ON c.comic_id = cg.comic_id
    LEFT JOIN genres g ON cg.genre_id = g.genre_id
    GROUP BY c.comic_id
    ORDER BY daily_views DESC, c.rating DESC
    LIMIT 5
");

// Fetch all genres
$genres = fetchAll("SELECT * FROM genres ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DarkVerse - Anime Comic Library</title>
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

        .trending-btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: rgba(244, 44, 29, 0.1);
            color: #F42C1D;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .trending-btn.active {
            background: #F42C1D;
            color: white;
        }

        .trending-btn:hover {
            background: #F42C1D;
            color: white;
        }

        /* Featured Collections hover effect */
        .collection-card:hover .collection-overlay {
            opacity: 0.8;
        }

        .collection-card:hover .collection-title {
            transform: translateY(-10px);
        }

        /* Enhanced mist effect */
        .mist {
            background: radial-gradient(circle at 50% 50%, 
                rgba(174, 25, 27, 0.1) 0%,
                rgba(112, 28, 26, 0.05) 50%,
                transparent 100%);
            mix-blend-mode: screen;
            animation: mistFlow 20s ease-in-out infinite;
        }

        @keyframes mistFlow {
            0%, 100% {
                opacity: 0.3;
                transform: translateY(0) scale(1);
            }
            50% {
                opacity: 0.6;
                transform: translateY(-20px) scale(1.1);
            }
        }
    </style>
</head>
<body class="bg-dark font-main text-gray-200">
    <!-- Navbar -->
    <nav class="fixed top-0 left-0 right-0 bg-dark/95 border-b border-wine/30 z-50">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <a href="index.php" class="text-2xl font-fantasy flame-text">DarkVerse</a>
                
                <!-- Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-300 hover:text-flame">Home</a>
                    <a href="collection.php" class="text-gray-300 hover:text-flame">Collection</a>
                    <a href="genre.php" class="text-gray-300 hover:text-flame">Genres</a>
                    <a href="latest.php" class="text-gray-300 hover:text-flame">Latest</a>
                </div>

                <!-- Search & Auth -->
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <input 
                            type="text" 
                            placeholder="Search comics..." 
                            class="bg-dark/50 border border-wine/30 rounded-lg pl-4 pr-10 py-1 focus:outline-none focus:border-flame w-48"
                        >
                        <button class="absolute right-3 top-1/2 -translate-y-1/2">
                            🔍
                        </button>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- User is logged in -->
                        <div class="relative group">
                            <button class="flex items-center gap-2 hover:text-flame">
                                <img 
                                    src="assets/images/avatars/<?= htmlspecialchars($_SESSION['avatar']) ?>" 
                                    alt="Avatar" 
                                    class="w-8 h-8 rounded-full border border-wine/30"
                                >
                                <span><?= htmlspecialchars($_SESSION['username']) ?></span>
                            </button>
                            <!-- Dropdown Menu -->
                            <div class="absolute right-0 mt-2 w-48 bg-dark border border-wine/30 rounded-lg shadow-lg py-2 hidden group-hover:block">
                                <a href="user/dashboard.php" class="block px-4 py-2 text-gray-300 hover:bg-wine/20 hover:text-flame">
                                    Dashboard
                                </a>
                                <a href="user/profile.php" class="block px-4 py-2 text-gray-300 hover:bg-wine/20 hover:text-flame">
                                    Profile
                                </a>
                                <a href="user/library.php" class="block px-4 py-2 text-gray-300 hover:bg-wine/20 hover:text-flame">
                                    My Library
                                </a>
                                <hr class="my-2 border-wine/30">
                                <a href="logout.php" class="block px-4 py-2 text-flame hover:bg-wine/20">
                                    Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- User is not logged in -->
                        <div class="flex items-center gap-4">
                            <a href="login.php" class="text-gray-300 hover:text-flame">Login</a>
                            <a href="register.php" class="bg-flame text-white px-4 py-1 rounded-lg hover:bg-crimson transition-colors">
                                Sign Up
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="pt-24 pb-12 dark-gradient relative overflow-hidden">
        <div class="container mx-auto px-4 relative z-10">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <div class="md:w-1/2 mb-8 md:mb-0">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4 font-fantasy text-white float">Enter the Dark Realm</h1>
                    <p class="text-lg mb-6 text-gray-400">Discover epic tales and dark adventures.</p>
                    <div class="flex space-x-4">
                        <button class="btn-glow bg-flame text-white px-8 py-3 rounded hover:bg-crimson transition-colors">Start Reading</button>
                        <button class="border border-wine text-gray-300 px-8 py-3 rounded hover:bg-wine/20 transition-colors">Learn More</button>
                    </div>
                </div>
                <div class="md:w-1/2">
                <img 
                    src="assets/images/berserk-guts.jpeg" 
                    alt="Dark warrior Guts from Berserk" 
                    class="rounded shadow-2xl transform hover:scale-105 transition duration-500 w-full h-auto object-cover"
                >
                </div>
            </div>
        </div>
    </section>

    <!-- Hot Comics Section -->
    <section class="py-16 bg-wine/5">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 flame-text text-center font-fantasy">Hot Comics</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <?php foreach ($hotComics as $comic): ?>
                    <a href="comic-detail.php?id=<?= $comic['comic_id'] ?>" 
                       class="card-hover glow-border bg-dark border border-wine/30 rounded-lg overflow-hidden">
                        <div class="relative">
                            <img 
                                src="assets/images/comics/<?= htmlspecialchars($comic['cover_image']) ?>" 
                                alt="<?= htmlspecialchars($comic['title']) ?>" 
                                class="w-full h-64 object-cover"
                            >
                            <span class="absolute top-2 right-2 bg-flame text-white text-xs px-2 py-1 rounded">HOT 🔥</span>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-lg mb-2 text-flame"><?= htmlspecialchars($comic['title']) ?></h3>
                            <p class="text-gray-400 text-sm mb-2"><?= htmlspecialchars($comic['author']) ?></p>
                            <div class="flex items-center justify-between">
                                <?php 
                                if (!empty($comic['genres'])) {
                                    $genreArray = explode(',', $comic['genres']);
                                    $firstGenre = reset($genreArray);
                                }
                                ?>
                                <span class="bg-blood/20 text-flame text-xs px-2 py-1 rounded">
                                    <?= !empty($firstGenre) ? htmlspecialchars($firstGenre) : 'N/A' ?>
                                </span>
                                <span class="text-gray-400 text-sm">
                                    <?= $comic['rating'] === null ? '⭐ N/A' : '⭐ ' . number_format($comic['rating'], 1) ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Latest & Trending Section -->
    <section class="py-16 bg-gradient-to-b from-dark to-wine/20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Latest Comics -->
                <div>
                    <h2 class="text-3xl font-bold mb-6 flame-text font-fantasy">Latest Updates</h2>
                    <div class="space-y-4">
                        <?php foreach ($latestUpdates as $update): ?>
                            <a href="comic-detail.php?id=<?= $update['comic_id'] ?>" 
                               class="flex gap-4 card-hover bg-dark border border-wine/30 rounded-lg p-3">
                                <img 
                                    src="assets/cover/<?= htmlspecialchars($update['cover_image']) ?>" 
                                    alt="<?= htmlspecialchars($update['title']) ?>" 
                                    class="w-24 h-32 object-cover rounded"
                                >
                                <div class="flex-1">
                                    <h3 class="font-semibold text-flame mb-2"><?= htmlspecialchars($update['title']) ?></h3>
                                    <p class="text-gray-400 text-sm mb-2">
                                        <?= $update['chapter_number'] === 'New' ? 'New Release' : 'Chapter ' . $update['chapter_number'] ?>
                                    </p>
                                    <p class="text-gray-500 text-xs">
                                        Added <?= formatDate($update['created_at']) ?>
                                    </p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Trending Comics -->
                <div>
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-3xl font-bold flame-text font-fantasy">Trending Comics</h2>
                        <div class="flex gap-4">
                            <button id="dailyBtn" class="trending-btn active" data-period="daily">Daily</button>
                            <button id="weeklyBtn" class="trending-btn" data-period="weekly">Weekly</button>
                            <button id="monthlyBtn" class="trending-btn" data-period="monthly">Monthly</button>
                        </div>
                    </div>

                    <div id="trendingComics" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                        <?php 
                        try {
                            $trendingComics = getTrendingComics('daily');
                            foreach ($trendingComics as $index => $comic): 
                        ?>
                            <a href="comic-detail.php?id=<?= $comic['comic_id'] ?>" 
                               class="card-hover relative bg-dark border border-wine/30 rounded-lg overflow-hidden">
                                <?php if ($index < 3): ?>
                                    <div class="absolute top-2 left-2 w-8 h-8 bg-flame rounded-full flex items-center justify-center text-white font-bold">
                                        #<?= $index + 1 ?>
                                    </div>
                                <?php endif; ?>
                                <div class="relative aspect-[3/4]">
                                    <img src="assets/cover/<?= htmlspecialchars($comic['cover_image']) ?>" 
                                         alt="<?= htmlspecialchars($comic['title']) ?>"
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold text-flame mb-2"><?= htmlspecialchars($comic['title']) ?></h3>
                                    <div class="flex items-center justify-between text-sm text-gray-400">
                                        <span><?= number_format($comic['period_views'] ?? 0) ?> views</span>
                                    </div>
                                </div>
                            </a>
                        <?php 
                            endforeach;
                        } catch (Exception $e) {
                            error_log("Error loading trending comics: " . $e->getMessage());
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Collections Section -->
    <section class="py-16 bg-dark relative overflow-hidden">
        <div class="mist absolute inset-0"></div>
        <div class="container mx-auto px-4 relative z-10">
            <h2 class="text-3xl font-bold mb-8 flame-text text-center font-fantasy">Featured Collections</h2>
            
            <!-- Collection Categories -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
                <!-- Dark Fantasy Collection -->
                <div class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                    <div class="relative h-48">
                        <img src="assets/images/collections/dark-fantasy.jpg" alt="Dark Fantasy" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark to-transparent"></div>
                        <h3 class="absolute bottom-4 left-4 text-xl font-bold text-white">Dark Fantasy</h3>
                    </div>
                    <div class="p-4">
                        <p class="text-gray-400 mb-4">Epic tales of darkness, demons, and heroic struggles.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-flame">50+ Comics</span>
                            <a href="#" class="text-sm text-flame hover:text-white transition-colors">Explore →</a>
                        </div>
                    </div>
                </div>

                <!-- Horror Collection -->
                <div class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                    <div class="relative h-48">
                        <img src="assets/images/collections/horror.jpg" alt="Horror" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark to-transparent"></div>
                        <h3 class="absolute bottom-4 left-4 text-xl font-bold text-white">Horror</h3>
                    </div>
                    <div class="p-4">
                        <p class="text-gray-400 mb-4">Spine-chilling stories that will keep you awake at night.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-flame">40+ Comics</span>
                            <a href="#" class="text-sm text-flame hover:text-white transition-colors">Explore →</a>
                        </div>
                    </div>
                </div>

                <!-- Gothic Collection -->
                <div class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                    <div class="relative h-48">
                        <img src="assets/images/collections/gothic.jpg" alt="Gothic" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-dark to-transparent"></div>
                        <h3 class="absolute bottom-4 left-4 text-xl font-bold text-white">Gothic</h3>
                    </div>
                    <div class="p-4">
                        <p class="text-gray-400 mb-4">Victorian-era darkness meets supernatural mystery.</p>
                        <div class="flex justify-between items-center">
                            <span class="text-flame">35+ Comics</span>
                            <a href="#" class="text-sm text-flame hover:text-white transition-colors">Explore →</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Featured Series -->
            <div class="text-center mb-8">
                <h3 class="text-2xl font-bold flame-text mb-2 font-fantasy">Featured Series</h3>
                <p class="text-gray-400">Handpicked series that define dark storytelling</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php
                // Example featured series - In production, this would come from your database
                $featuredSeries = [
                    ['title' => 'Crimson Night', 'chapters' => 45, 'rating' => 4.8],
                    ['title' => 'Dark Souls', 'chapters' => 32, 'rating' => 4.7],
                    ['title' => 'Gothic Tales', 'chapters' => 28, 'rating' => 4.9],
                    ['title' => 'Blood Moon', 'chapters' => 56, 'rating' => 4.6],
                    ['title' => 'Shadow Realm', 'chapters' => 39, 'rating' => 4.8],
                    ['title' => 'Eternal Darkness', 'chapters' => 41, 'rating' => 4.7],
                ];

                foreach ($featuredSeries as $series):
                ?>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-3">
                    <div class="aspect-[3/4] mb-2 overflow-hidden rounded">
                        <img src="assets/images/series/placeholder.jpg" alt="<?= htmlspecialchars($series['title']) ?>" 
                             class="w-full h-full object-cover hover:scale-110 transition-transform duration-300">
                    </div>
                    <h4 class="font-semibold text-flame text-sm mb-1"><?= htmlspecialchars($series['title']) ?></h4>
                    <div class="flex justify-between items-center text-xs text-gray-400">
                        <span><?= $series['chapters'] ?> Chapters</span>
                        <span>⭐ <?= number_format($series['rating'], 1) ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Call to Action -->
            <div class="text-center mt-12">
                <a href="#" class="inline-block bg-flame text-white px-8 py-3 rounded-lg hover:bg-crimson transition-colors">
                    View All Collections
                </a>
            </div>
        </div>
    </section>

    <!-- Genres Section -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 flame-text text-center font-fantasy">Dark Genres</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($genres as $genre): ?>
                    <div class="card-hover glow-border bg-dark border border-wine/30 p-6 rounded-lg text-center">
                        <span class="text-flame font-semibold"><?= htmlspecialchars($genre['name']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark border-t border-wine/30 py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4 flame-text font-fantasy">DarkVerse</h3>
                    <p class="text-gray-400">Your portal to dark fantasy</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4 text-flame">Navigation</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-flame transition-colors">Home</a></li>
                        <li><a href="#" class="hover:text-flame transition-colors">Collection</a></li>
                        <li><a href="#" class="hover:text-flame transition-colors">Genre</a></li>
                        <li><a href="#" class="hover:text-flame transition-colors">Latest</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4 text-flame">Support</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-flame transition-colors">FAQ</a></li>
                        <li><a href="#" class="hover:text-flame transition-colors">Contact</a></li>
                        <li><a href="#" class="hover:text-flame transition-colors">Terms</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4 text-flame">Connect</h4>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-flame transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-flame transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-wine/30 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 DarkVerse. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Scroll Reveal
        document.addEventListener('DOMContentLoaded', function() {
            const reveals = document.querySelectorAll('.reveal');

            function revealOnScroll() {
                reveals.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;

                    if (elementTop < window.innerHeight - elementVisible) {
                        element.classList.add('active');
                    }
                });
            }

            window.addEventListener('scroll', revealOnScroll);
            revealOnScroll(); // Initial check

            const heroSection = document.querySelector('.dark-gradient');
            
            // Add red mist effect
            const mist = document.createElement('div');
            mist.className = 'mist';
            heroSection.appendChild(mist);

            // Create floating skull effect
            function createSkull() {
                const skull = document.createElement('div');
                skull.className = 'skull';
                skull.innerHTML = `<svg width="100%" height="100%" viewBox="0 0 24 24" fill="#701C1A" opacity="0.8">
                    <path d="M12 2C6.477 2 2 6.477 2 12c0 3.686 2.11 6.89 5.167 8.444V22h3.666v-1h2.334v1h3.666v-1.556C19.89 18.89 22 15.686 22 12c0-5.523-4.477-10-10-10zm-3.5 16a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm7 0a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm-3.5-5a1 1 0 110-2 1 1 0 010 2z"/>
                </svg>`;
                
                const startX = Math.random() * heroSection.offsetWidth;
                skull.style.left = `${startX}px`;
                skull.style.bottom = '0';
                
                heroSection.appendChild(skull);
                
                skull.addEventListener('animationend', () => {
                    skull.remove();
                });
            }

            // Create flying raven effect
            function createRaven() {
                const raven = document.createElement('div');
                raven.className = 'raven';
                raven.innerHTML = `<svg width="40" height="40" viewBox="0 0 24 24" fill="#482D2E">
                    <path d="M21.4 11.6l-2.2-1.5 1.5-2.2-2.4-.8.5-2.5-2.5.5-.8-2.4-2.2 1.5L11.6 2.6 10.1 5 7.9 3.5l-.8 2.4-2.5-.5.5 2.5-2.4.8 1.5 2.2L2.6 12.4 5 13.9l-1.5 2.2 2.4.8-.5 2.5 2.5-.5.8 2.4 2.2-1.5 1.7 2.1 1.5-2.2 2.2 1.5.8-2.4 2.5.5-.5-2.5 2.4-.8-1.5-2.2 2.1-1.7z"/>
                </svg>`;
                
                const startY = Math.random() * (heroSection.offsetHeight / 2);
                raven.style.top = `${startY}px`;
                
                heroSection.appendChild(raven);
                
                raven.addEventListener('animationend', () => {
                    raven.remove();
                });
            }

            // Create elements periodically
            setInterval(createSkull, 2000);
            setInterval(createRaven, 3000);
        });

        document.addEventListener('DOMContentLoaded', function() {
            const trendingBtns = document.querySelectorAll('.trending-btn');
            
            trendingBtns.forEach(btn => {
                btn.addEventListener('click', async function() {
                    const period = this.dataset.period;
                    
                    // Update active state
                    trendingBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    try {
                        const response = await fetch(`api/trending.php?period=${period}`);
                        if (!response.ok) throw new Error('Network response was not ok');
                        
                        const data = await response.json();
                        if (data.status === 'success' && data.html) {
                            document.getElementById('trendingComics').innerHTML = data.html;
                        } else {
                            console.error('Invalid response format:', data);
                        }
                    } catch (error) {
                        console.error('Error fetching trending comics:', error);
                    }
                });
            });
        });
    </script>
</body>
</html>

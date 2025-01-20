<?php
require_once 'config.php';

// Get all available genres for filter
$genres = fetchAll("SELECT * FROM genres ORDER BY name") ?? [];

// Get search parameters with default values
$search = $_GET['q'] ?? '';
$selectedGenres = isset($_GET['genres']) && is_array($_GET['genres']) ? $_GET['genres'] : [];
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'latest';
$rating = $_GET['rating'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;

// Build the base query with error handling
try {
    $query = "
        SELECT DISTINCT
            c.*,
            GROUP_CONCAT(DISTINCT g.name) as genres,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            COUNT(DISTINCT ch.chapter_id) as chapter_count
        FROM comics c
        LEFT JOIN comic_genres cg ON c.comic_id = cg.comic_id
        LEFT JOIN genres g ON cg.genre_id = g.genre_id
        LEFT JOIN ratings r ON c.comic_id = r.comic_id
        LEFT JOIN chapters ch ON c.comic_id = ch.comic_id
        WHERE 1=1
    ";

    $params = [];

    // Add search condition
    if (!empty($search)) {
        $query .= " AND (c.title LIKE ? OR c.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    // Add genre filter with error handling
    if (!empty($selectedGenres)) {
        $validGenres = array_filter($selectedGenres); // Remove empty values
        if (!empty($validGenres)) {
            $placeholders = str_repeat('?,', count($validGenres) - 1) . '?';
            $query .= " AND c.comic_id IN (
                SELECT comic_id 
                FROM comic_genres cg2 
                JOIN genres g2 ON cg2.genre_id = g2.genre_id 
                WHERE g2.name IN ($placeholders)
                GROUP BY comic_id 
                HAVING COUNT(DISTINCT g2.genre_id) = ?
            )";
            $params = array_merge($params, $validGenres);
            $params[] = count($validGenres);
        }
    }

    // Add status filter
    if (!empty($status)) {
        $query .= " AND c.status = ?";
        $params[] = $status;
    }

    // Add rating filter
    if (!empty($rating)) {
        $query .= " AND (SELECT COALESCE(AVG(rating), 0) FROM ratings WHERE comic_id = c.comic_id) >= ?";
        $params[] = floatval($rating);
    }

    $query .= " GROUP BY c.comic_id";

    // Add sorting with validation
    $validSortOptions = ['latest', 'rating', 'views', 'title'];
    $sort = in_array($sort, $validSortOptions) ? $sort : 'latest';
    
    switch ($sort) {
        case 'rating':
            $query .= " ORDER BY avg_rating DESC, c.updated_at DESC";
            break;
        case 'views':
            $query .= " ORDER BY c.total_views DESC, c.updated_at DESC";
            break;
        case 'title':
            $query .= " ORDER BY c.title ASC";
            break;
        default:
            $query .= " ORDER BY c.updated_at DESC";
    }

    // Get total count with error handling
    $totalQuery = "SELECT COUNT(DISTINCT c.comic_id) as total FROM ($query) as subquery";
    $totalResult = fetchOne($totalQuery, $params);
    $total = $totalResult ? ($totalResult['total'] ?? 0) : 0;
    $totalPages = max(1, ceil($total / $perPage));
    
    // Ensure page is within valid range
    $page = min($page, $totalPages);
    
    // Add pagination
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = ($page - 1) * $perPage;

    // Execute final query with error handling
    $comics = fetchAll($query, $params) ?? [];

} catch (Exception $e) {
    error_log("Collection page error: " . $e->getMessage());
    $comics = [];
    $total = 0;
    $totalPages = 1;
}
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

        .opacity-50 {
            opacity: 0.5;
            pointer-events: none;
            transition: opacity 0.2s;
        }
    </style>
</head>
<body class="bg-dark font-main text-gray-200">
    <!-- Navbar (sama seperti index.php) -->
    <nav class="bg-dark/90 backdrop-blur-md border-b border-wine/30 fixed w-full z-50">
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

    <!-- Collection Header -->
    <section class="pt-24 pb-12 dark-gradient relative overflow-hidden">
        <div class="container mx-auto px-4 relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 font-fantasy text-white text-center">Comic Collection</h1>
            
            <!-- Filter Section -->
            <div class="mb-8 bg-dark border border-wine/30 rounded-lg p-4">
                <form id="filterForm" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Search Input dengan auto-update -->
                        <div>
                            <input type="text" 
                                   name="q" 
                                   value="<?= htmlspecialchars($search) ?>"
                                   class="w-full bg-darker border border-wine/30 rounded p-2 text-gray-300 placeholder-gray-500 focus:border-flame focus:ring-1 focus:ring-flame"
                                   placeholder="Search titles..."
                                   autocomplete="off">
                        </div>

                        <!-- Genre Filter (perbaikan dropdown) -->
                        <div class="relative">
                            <button type="button"
                                    id="genreDropdown"
                                    class="w-full bg-darker border border-wine/30 rounded p-2 text-gray-300 text-left flex justify-between items-center hover:bg-wine/10">
                                <span id="genreText"><?= !empty($selectedGenres) ? count($selectedGenres) . ' genres' : 'All genres' ?></span>
                                <span>▼</span>
                            </button>
                            <div id="genreList" 
                                 class="absolute z-20 w-full mt-1 bg-darker border border-wine/30 rounded-lg p-2 hidden max-h-48 overflow-y-auto">
                                <?php foreach ($genres as $genre): ?>
                                    <label class="flex items-center p-2 hover:bg-wine/20 rounded cursor-pointer">
                                        <input type="checkbox" 
                                               name="genres[]" 
                                               value="<?= htmlspecialchars($genre['name']) ?>"
                                               <?= in_array($genre['name'], $selectedGenres) ? 'checked' : '' ?>
                                               class="mr-2 accent-flame">
                                        <span class="text-gray-300"><?= htmlspecialchars($genre['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Status Dropdown -->
                        <div class="relative">
                            <button type="button"
                                    id="statusDropdown"
                                    class="w-full bg-darker border border-wine/30 rounded p-2 text-gray-300 text-left flex justify-between items-center hover:bg-wine/10">
                                <span id="statusText"><?= !empty($status) ? ucfirst($status) : 'All Status' ?></span>
                                <span>▼</span>
                            </button>
                            <div id="statusList" 
                                 class="absolute z-10 w-full mt-1 bg-darker border border-wine/30 rounded-lg p-2 hidden">
                                <div class="space-y-1">
                                    <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                        <input type="radio" name="status" value="" <?= empty($status) ? 'checked' : '' ?> class="hidden">
                                        <span class="text-gray-300">All Status</span>
                                    </label>
                                    <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                        <input type="radio" name="status" value="ongoing" <?= $status === 'ongoing' ? 'checked' : '' ?> class="hidden">
                                        <span class="text-gray-300">Ongoing</span>
                                    </label>
                                    <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                        <input type="radio" name="status" value="completed" <?= $status === 'completed' ? 'checked' : '' ?> class="hidden">
                                        <span class="text-gray-300">Completed</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Sort & Rating Combined -->
                        <div class="flex gap-2">
                            <!-- Rating Dropdown -->
                            <div class="relative flex-1">
                                <button type="button"
                                        id="ratingDropdown"
                                        class="w-full bg-darker border border-wine/30 rounded p-2 text-gray-300 text-left flex justify-between items-center hover:bg-wine/10">
                                    <span id="ratingText"><?= !empty($rating) ? $rating . '+ ★' : 'Any Rating' ?></span>
                                    <span>▼</span>
                                </button>
                                <div id="ratingList" 
                                     class="absolute z-10 w-full mt-1 bg-darker border border-wine/30 rounded-lg p-2 hidden">
                                    <div class="space-y-1">
                                        <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                            <input type="radio" name="rating" value="" <?= empty($rating) ? 'checked' : '' ?> class="hidden">
                                            <span class="text-gray-300">Any Rating</span>
                                        </label>
                                        <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                            <input type="radio" name="rating" value="4.5" <?= $rating === '4.5' ? 'checked' : '' ?> class="hidden">
                                            <span class="text-gray-300">4.5+ ★</span>
                                        </label>
                                        <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                            <input type="radio" name="rating" value="4" <?= $rating === '4' ? 'checked' : '' ?> class="hidden">
                                            <span class="text-gray-300">4+ ★</span>
                                        </label>
                                        <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                            <input type="radio" name="rating" value="3" <?= $rating === '3' ? 'checked' : '' ?> class="hidden">
                                            <span class="text-gray-300">3+ ★</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Sort Dropdown -->
                            <div class="relative flex-1">
                                <button type="button"
                                        id="sortDropdown"
                                        class="w-full bg-darker border border-wine/30 rounded p-2 text-gray-300 text-left flex justify-between items-center hover:bg-wine/10">
                                    <span id="sortText">
                                        <?php
                                        $sortLabels = [
                                            'latest' => 'Latest Update',
                                            'rating' => 'Rating',
                                            'views' => 'Popularity',
                                            'title' => 'Title'
                                        ];
                                        echo $sortLabels[$sort] ?? 'Latest Update';
                                        ?>
                                    </span>
                                    <span>▼</span>
                                </button>
                                <div id="sortList" 
                                     class="absolute z-10 w-full mt-1 bg-darker border border-wine/30 rounded-lg p-2 hidden">
                                    <div class="space-y-1">
                                        <?php foreach ($sortLabels as $value => $label): ?>
                                            <label class="block p-2 hover:bg-wine/20 rounded cursor-pointer">
                                                <input type="radio" name="sort" value="<?= $value ?>" <?= $sort === $value ? 'checked' : '' ?> class="hidden">
                                                <span class="text-gray-300"><?= $label ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Collection Grid -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <!-- Update the results count display -->
            <div class="text-gray-400 mb-4">
                Found <?= number_format($total) ?> comics
            </div>

            <!-- Update the comics grid to handle empty results -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
                <?php if (!empty($comics)): ?>
                    <?php foreach ($comics as $comic): ?>
                        <div class="card-hover glow-border bg-dark border border-wine/30 rounded-lg overflow-hidden">
                            <div class="relative group">
                                <img 
                                    src="assets/cover/<?= htmlspecialchars($comic['cover_image'] ?? '') ?>" 
                                    alt="<?= htmlspecialchars($comic['title'] ?? 'Comic Cover') ?>" 
                                    class="w-full h-72 object-cover"
                                >
                                <div class="absolute inset-0 bg-gradient-to-t from-dark to-transparent opacity-0 group-hover:opacity-100 transition-opacity flex items-end p-4">
                                    <div class="space-y-2 w-full">
                                        <a href="comic-detail.php?id=<?= $comic['comic_id'] ?? 0 ?>" 
                                           class="block w-full bg-flame text-white text-center py-2 rounded hover:bg-crimson transition-colors">
                                            Read Now
                                        </a>
                                        
                                        <?php if (isLoggedIn()): ?>
                                            <?php 
                                            $isBookmarked = !empty($comic['comic_id']) ? fetchOne(
                                                "SELECT * FROM bookmarks WHERE user_id = ? AND comic_id = ?", 
                                                [$_SESSION['user_id'], $comic['comic_id']]
                                            ) : null;
                                            ?>
                                            <button 
                                                class="bookmark-btn w-full py-2 rounded border border-wine/30 transition-colors
                                                       <?= $isBookmarked ? 'bg-flame/20 text-flame' : 'bg-dark/80 text-white hover:bg-flame/20 hover:text-flame' ?>"
                                                data-comic-id="<?= $comic['comic_id'] ?? 0 ?>">
                                                <span class="bookmark-icon"><?= $isBookmarked ? '★' : '☆' ?></span>
                                                <span class="bookmark-text"><?= $isBookmarked ? 'Bookmarked' : 'Add to Library' ?></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-flame mb-2">
                                    <?= htmlspecialchars($comic['title'] ?? 'Untitled') ?>
                                </h3>
                                <?php if (!empty($comic['genres'])): ?>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <?php 
                                        $comicGenres = explode(',', $comic['genres']);
                                        $firstTwoGenres = array_slice($comicGenres, 0, 2);
                                        foreach ($firstTwoGenres as $genre): 
                                        ?>
                                            <span class="bg-wine/20 text-flame text-xs px-2 py-1 rounded">
                                                <?= htmlspecialchars(trim($genre)) ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-gray-400 text-sm">
                                    <?= number_format($comic['chapter_count'] ?? 0) ?> Chapters
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-12 text-gray-400">
                        No comics found matching your criteria.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Footer (sama seperti index.php) -->
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

    <!-- Add Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 transform translate-y-full opacity-0 transition-all duration-300">
        <div class="flex items-center gap-2 px-6 py-3 rounded-lg bg-dark text-white shadow-lg">
            <span id="toastIcon" class="text-xl"></span>
            <span id="toastMessage"></span>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let searchTimeout;
            
            // Fungsi untuk submit form dengan delay
            function delayedSubmit() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 500); // Delay 500ms setelah user berhenti mengetik
            }

            // Handle search dengan debounce
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput) {
                searchInput.addEventListener('input', delayedSubmit);
                searchInput.addEventListener('keypress', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(searchTimeout);
                        document.getElementById('filterForm').submit();
                    }
                });
            }

            // Genre dropdown dengan auto-submit
            const genreDropdown = document.getElementById('genreDropdown');
            const genreList = document.getElementById('genreList');
            const genreText = document.getElementById('genreText');
            const genreCheckboxes = document.querySelectorAll('input[name="genres[]"]');

            if (genreDropdown && genreList) {
                genreDropdown.addEventListener('click', (e) => {
                    e.stopPropagation();
                    genreList.classList.toggle('hidden');
                    // Close other dropdowns
                    document.querySelectorAll('[id$="List"]').forEach(el => {
                        if (el.id !== 'genreList') el.classList.add('hidden');
                    });
                });

                // Update genre count and submit
                genreCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        const selectedCount = Array.from(genreCheckboxes).filter(cb => cb.checked).length;
                        genreText.textContent = selectedCount > 0 ? `${selectedCount} genres` : 'All genres';
                        delayedSubmit(); // Auto-submit setelah checkbox berubah
                    });
                });
            }

            // Setup dropdown dengan auto-submit
            const setupDropdown = (buttonId, listId, inputName) => {
                const button = document.getElementById(buttonId);
                const list = document.getElementById(listId);
                
                if (!button || !list) return;

                button.addEventListener('click', (e) => {
                    e.stopPropagation();
                    list.classList.toggle('hidden');
                    // Close other dropdowns
                    document.querySelectorAll('[id$="List"]').forEach(el => {
                        if (el.id !== listId) el.classList.add('hidden');
                    });
                });

                // Handle radio selections dengan auto-submit
                if (inputName) {
                    list.querySelectorAll(`input[name="${inputName}"]`).forEach(input => {
                        input.addEventListener('change', () => {
                            const label = input.nextElementSibling.textContent;
                            const textElement = document.getElementById(`${buttonId.replace('Dropdown', 'Text')}`);
                            if (textElement) {
                                textElement.textContent = label;
                            }
                            list.classList.add('hidden');
                            document.getElementById('filterForm').submit(); // Langsung submit saat pilihan berubah
                        });
                    });
                }
            };

            // Setup other dropdowns
            setupDropdown('statusDropdown', 'statusList', 'status');
            setupDropdown('ratingDropdown', 'ratingList', 'rating');
            setupDropdown('sortDropdown', 'sortList', 'sort');

            // Close dropdowns when clicking outside
            document.addEventListener('click', (e) => {
                if (!e.target.closest('[id$="Dropdown"]') && !e.target.closest('[id$="List"]')) {
                    document.querySelectorAll('[id$="List"]').forEach(el => {
                        el.classList.add('hidden');
                    });
                }
            });

            // Optional: Tambahkan loading indicator
            const form = document.getElementById('filterForm');
            if (form) {
                form.addEventListener('submit', () => {
                    // Tambahkan class loading ke container
                    document.querySelector('.grid').classList.add('opacity-50');
                });
            }
        });
    </script>
</body>
</html> 
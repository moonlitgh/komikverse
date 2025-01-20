<?php
require_once 'config.php';

// Fetch all genres with comic count
$genres = fetchAll("
    SELECT 
        g.*,
        COUNT(DISTINCT cg.comic_id) as comic_count,
        GROUP_CONCAT(DISTINCT c.title SEPARATOR '||') as sample_comics,
        GROUP_CONCAT(DISTINCT c.cover_image SEPARATOR '||') as sample_covers
    FROM genres g
    LEFT JOIN comic_genres cg ON g.genre_id = cg.genre_id
    LEFT JOIN comics c ON cg.comic_id = c.comic_id
    GROUP BY g.genre_id
    ORDER BY comic_count DESC
");

// Fetch popular genre combinations
$genreCombinations = fetchAll("
    SELECT 
        g1.name as genre1,
        g2.name as genre2,
        COUNT(*) as combination_count
    FROM comic_genres cg1
    JOIN comic_genres cg2 ON cg1.comic_id = cg2.comic_id AND cg1.genre_id < cg2.genre_id
    JOIN genres g1 ON cg1.genre_id = g1.genre_id
    JOIN genres g2 ON cg2.genre_id = g2.genre_id
    GROUP BY g1.genre_id, g2.genre_id
    HAVING combination_count > 1
    ORDER BY combination_count DESC
    LIMIT 8
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Genres - DarkVerse</title>
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
    </style>
</head>
<body class="bg-dark font-main text-gray-200">
    <!-- Navbar (sama seperti index.php) -->
    <nav class="bg-dark/90 backdrop-blur-md border-b border-wine/30 fixed w-full z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-flame" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                    <span class="text-2xl font-bold font-fantasy flame-text">DarkVerse</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-400 hover:text-flame transition-colors">Home</a>
                    <a href="collection.php" class="text-gray-400 hover:text-flame transition-colors">Collection</a>
                    <a href="genre.php" class="text-gray-400 hover:text-flame transition-colors">Genre</a>
                    <a href="latest.php" class="text-gray-400 hover:text-flame transition-colors">Latest</a>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="btn-glow bg-blood text-white px-6 py-2 rounded hover:bg-crimson transition-colors">Login</button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Genre Header -->
    <section class="pt-24 pb-12 dark-gradient relative overflow-hidden">
        <div class="container mx-auto px-4 relative z-10">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 font-fantasy text-white text-center">Browse by Genre</h1>
            <p class="text-gray-400 text-center max-w-2xl mx-auto">Discover your next favorite comic series through our carefully curated genre categories</p>
        </div>
    </section>

    <!-- Popular Genres -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 flame-text font-fantasy">Popular Genres</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($genres as $genre): 
                    // Handle NULL values for sample comics and covers
                    $sampleComics = !empty($genre['sample_comics']) ? explode('||', $genre['sample_comics']) : [];
                    $sampleCovers = !empty($genre['sample_covers']) ? explode('||', $genre['sample_covers']) : [];
                    
                    // Get up to 3 sample comics/covers
                    $sampleComics = array_slice($sampleComics, 0, 3);
                    $sampleCovers = array_slice($sampleCovers, 0, 3);
                ?>
                    <div class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                        <div class="relative h-48">
                            <!-- Genre Cover Collage -->
                            <div class="absolute inset-0 grid grid-cols-3 gap-1">
                                <?php if (empty($sampleCovers)): ?>
                                    <!-- Default background when no covers available -->
                                    <div class="col-span-3 bg-wine/20"></div>
                                <?php else: ?>
                                    <?php foreach ($sampleCovers as $cover): ?>
                                        <div class="bg-wine/20">
                                            <img 
                                                src="assets/cover/<?= htmlspecialchars($cover) ?>" 
                                                alt="Sample Cover" 
                                                class="w-full h-full object-cover opacity-60"
                                            >
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <!-- Genre Info Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-dark via-dark/80 to-transparent p-4 flex flex-col justify-end">
                                <h3 class="text-2xl font-bold text-flame font-fantasy"><?= htmlspecialchars($genre['name']) ?></h3>
                                <p class="text-gray-400"><?= $genre['comic_count'] ?> Comics</p>
                            </div>
                        </div>
                        <div class="p-4">
                            <p class="text-gray-400 text-sm mb-4"><?= htmlspecialchars($genre['description']) ?></p>
                            <div class="flex flex-wrap gap-2">
                                <?php if (!empty($sampleComics)): ?>
                                    <?php foreach ($sampleComics as $comic): ?>
                                        <span class="text-xs bg-blood/20 text-flame px-2 py-1 rounded">
                                            <?= htmlspecialchars($comic) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400">No comics in this genre yet</span>
                                <?php endif; ?>
                            </div>
                            <a 
                                href="collection.php?genre=<?= urlencode($genre['name']) ?>" 
                                class="block w-full text-center bg-flame text-white py-2 rounded mt-4 hover:bg-crimson transition-colors"
                            >
                                Browse Genre
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Popular Combinations -->
    <section class="py-16 bg-wine/5">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 flame-text font-fantasy">Popular Genre Combinations</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php foreach ($genreCombinations as $combo): ?>
                    <a href="collection.php?genres[]=<?= urlencode($combo['genre1']) ?>&genres[]=<?= urlencode($combo['genre2']) ?>" 
                       class="card-hover bg-dark border border-wine/30 rounded-lg p-4 text-center">
                        <span class="text-flame"><?= htmlspecialchars($combo['genre1']) ?></span>
                        <span class="text-gray-400">+</span>
                        <span class="text-flame"><?= htmlspecialchars($combo['genre2']) ?></span>
                        <span class="text-xs text-gray-400 block mt-2"><?= $combo['combination_count'] ?> Comics</span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Genre Map -->
    <section class="py-16">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold mb-8 flame-text font-fantasy">Genre Map</h2>
            <div class="relative h-[600px] bg-dark border border-wine/30 rounded-lg p-6">
                <!-- Genre map visualization -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 h-full">
                    <?php foreach ($genres as $genre): ?>
                        <div class="flex flex-col items-center justify-center text-center p-4 rounded-lg bg-wine/10 hover:bg-wine/20 transition-colors">
                            <span class="text-flame font-semibold mb-2"><?= htmlspecialchars($genre['name']) ?></span>
                            <span class="text-gray-400 text-sm"><?= $genre['comic_count'] ?> Comics</span>
                        </div>
                    <?php endforeach; ?>
                </div>
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
    </script>
</body>
</html> 
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
            <img src="assets/images/avatar.jpg" alt="User Avatar" class="w-12 h-12 rounded-full border-2 border-flame">
            <div>
                <h3 class="font-semibold text-flame">John Doe</h3>
                <p class="text-xs text-gray-400">Premium Member</p>
            </div>
        </div>

        <!-- Navigation dengan hover effects -->
        <nav class="space-y-2">
            <a href="#overview" class="flex items-center gap-3 p-3 bg-wine/20 text-flame rounded-lg hover:bg-wine/30 transition-all">
                <span>📊</span> Overview
            </a>
            <a href="#library" class="flex items-center gap-3 p-3 hover:bg-wine/20 rounded-lg hover:text-flame transition-all">
                <span>📚</span> My Library
            </a>
            <a href="#reading-history" class="flex items-center gap-3 p-3 hover:bg-wine/20 rounded-lg hover:text-flame transition-all">
                <span>📖</span> Reading History
            </a>
            <a href="#bookmarks" class="flex items-center gap-3 p-3 hover:bg-wine/20 rounded-lg hover:text-flame transition-all">
                <span>🔖</span> Bookmarks
            </a>
            <a href="#collections" class="flex items-center gap-3 p-3 hover:bg-wine/20 rounded-lg hover:text-flame transition-all">
                <span>📂</span> Collections
            </a>
            <a href="#settings" class="flex items-center gap-3 p-3 hover:bg-wine/20 rounded-lg hover:text-flame transition-all">
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
                    <p class="text-2xl font-bold text-flame">247</p>
                </div>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-6">
                    <h3 class="text-gray-400 mb-2">Reading Time</h3>
                    <p class="text-2xl font-bold text-flame">126h</p>
                </div>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-6">
                    <h3 class="text-gray-400 mb-2">Bookmarks</h3>
                    <p class="text-2xl font-bold text-flame">52</p>
                </div>
                <div class="card-hover bg-dark border border-wine/30 rounded-lg p-6">
                    <h3 class="text-gray-400 mb-2">Collections</h3>
                    <p class="text-2xl font-bold text-flame">8</p>
                </div>
            </div>

            <!-- Continue Reading dengan card hover effect -->
            <div class="mb-8">
                <h3 class="text-xl font-bold mb-4 text-flame">Continue Reading</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="card-hover glow-border bg-dark border border-wine/30 rounded-lg overflow-hidden">
                        <div class="flex">
                            <img src="assets/images/comic1.jpg" alt="Comic Cover" class="w-24 h-32 object-cover">
                            <div class="p-4 flex-1">
                                <h4 class="font-semibold text-flame mb-1">Dark Knights</h4>
                                <p class="text-sm text-gray-400 mb-2">Chapter 45</p>
                                <div class="w-full bg-wine/20 rounded-full h-1 mb-2">
                                    <div class="bg-flame h-1 rounded-full" style="width: 75%"></div>
                                </div>
                                <button class="w-full bg-flame text-white py-1 rounded text-sm">Continue</button>
                            </div>
                        </div>
                    </div>
                    <!-- More continue reading cards -->
                </div>
            </div>

            <!-- Reading Activity dengan flame gradient -->
            <div class="bg-dark border border-wine/30 rounded-lg p-6">
                <h3 class="text-xl font-bold mb-4 text-flame">Reading Activity</h3>
                <div class="h-48 flex items-end gap-2">
                    <div class="h-full flex items-end gap-2">
                        <div class="w-8 h-[60%] bg-flame/20 rounded-t"></div>
                        <div class="w-8 h-[80%] bg-flame/40 rounded-t"></div>
                        <div class="w-8 h-[40%] bg-flame/20 rounded-t"></div>
                        <div class="w-8 h-[90%] bg-flame rounded-t"></div>
                        <div class="w-8 h-[30%] bg-flame/20 rounded-t"></div>
                        <div class="w-8 h-[70%] bg-flame/60 rounded-t"></div>
                        <div class="w-8 h-[50%] bg-flame/30 rounded-t"></div>
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-sm text-gray-400">
                    <span>Mon</span>
                    <span>Tue</span>
                    <span>Wed</span>
                    <span>Thu</span>
                    <span>Fri</span>
                    <span>Sat</span>
                    <span>Sun</span>
                </div>
            </div>
        </section>

        <!-- My Library Section -->
        <section id="library" class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-3xl font-bold flame-text font-fantasy">My Library</h2>
                <div class="flex gap-4">
                    <button class="px-4 py-2 bg-dark border border-wine/30 rounded-lg hover:bg-wine/10">
                        Sort by: Recent
                    </button>
                    <button class="px-4 py-2 bg-flame text-white rounded-lg">
                        + Add New
                    </button>
                </div>
            </div>

            <!-- Library Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <div class="card-hover bg-dark border border-wine/30 rounded-lg overflow-hidden">
                    <img src="assets/images/comic2.jpg" alt="Comic Cover" class="w-full h-48 object-cover">
                    <div class="p-3">
                        <h3 class="font-semibold text-sm text-flame mb-1">Blood Moon Saga</h3>
                        <p class="text-xs text-gray-400">Last read: 2 days ago</p>
                    </div>
                </div>
                <!-- More library items -->
            </div>
        </section>

        <!-- Reading History -->
        <section id="reading-history" class="mb-12">
            <h2 class="text-3xl font-bold mb-6 flame-text font-fantasy">Reading History</h2>
            <div class="bg-dark border border-wine/30 rounded-lg">
                <div class="p-4 border-b border-wine/30">
                    <div class="flex items-center gap-4">
                        <img src="assets/images/comic3.jpg" alt="Comic Cover" class="w-16 h-20 object-cover rounded">
                        <div class="flex-1">
                            <h3 class="font-semibold text-flame">Shadow Warriors</h3>
                            <p class="text-sm text-gray-400">Chapter 23: The Dark Path</p>
                            <p class="text-xs text-gray-400 mt-1">Read 3 hours ago</p>
                        </div>
                        <button class="px-4 py-2 bg-wine/20 text-flame rounded-lg hover:bg-wine/30">
                            Read Again
                        </button>
                    </div>
                </div>
                <!-- More history items -->
            </div>
        </section>
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
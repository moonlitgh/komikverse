<?php
require_once 'config.php';
?>

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
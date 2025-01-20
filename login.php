<?php
require_once 'config.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('user/dashboard.php');
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    try {
        // Get user from database
        $user = fetchOne("
            SELECT user_id, username, password, role, avatar, membership_type 
            FROM users 
            WHERE username = ?
        ", [$username]);

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'];
            $_SESSION['membership_type'] = $user['membership_type'];
            
            // Redirect to dashboard
            redirect('user/dashboard.php');
        } else {
            setFlash('error', 'Invalid username or password');
        }
    } catch (Exception $e) {
        setFlash('error', 'An error occurred. Please try again.');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DarkVerse</title>
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
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Background Effects -->
        <div class="absolute inset-0 dark-gradient opacity-90 z-0"></div>
        
        <!-- Floating Skulls -->
        <div class="skull" style="left: 15%; animation-delay: 0s;">💀</div>
        <div class="skull" style="left: 45%; animation-delay: 2s;">💀</div>
        <div class="skull" style="left: 75%; animation-delay: 4s;">💀</div>
        
        <!-- Red Mist -->
        <div class="mist z-10"></div>
        
        <!-- Flying Ravens -->
        <div class="raven" style="animation-delay: 0s;">🦅</div>
        <div class="raven" style="animation-delay: 5s;">🦅</div>
        
        <!-- Blood Drips -->
        <div class="absolute top-0 left-1/4 blood-drip"></div>
        <div class="absolute top-0 left-2/4 blood-drip" style="animation-delay: 1s;"></div>
        <div class="absolute top-0 left-3/4 blood-drip" style="animation-delay: 2s;"></div>

        <!-- Main Content with Glowing Effect -->
        <div class="max-w-md w-full space-y-8 relative z-20 bg-dark/80 p-8 rounded-lg glow-border">
            <!-- Smoke Effects -->
            <div class="absolute -top-10 left-1/2 smoke"></div>
            <div class="absolute -top-10 left-1/3 smoke" style="animation-delay: 1s;"></div>
            <div class="absolute -top-10 left-2/3 smoke" style="animation-delay: 2s;"></div>

            <!-- Fire Background -->
            <div class="absolute inset-0 fire-bg rounded-lg"></div>

            <!-- Content remains the same but add relative z-10 to keep it above effects -->
            <div class="relative z-10">
                <div class="text-center">
                    <a href="index.php">
                        <h1 class="text-4xl font-fantasy flame-text float">DarkVerse</h1>
                    </a>
                    <h2 class="mt-6 text-2xl font-bold text-flame">Sign in to your account</h2>
                </div>

                <?php if ($flash = getFlash()): ?>
                    <div class="bg-blood/20 border border-flame text-flame px-4 py-3 rounded">
                        <?= $flash['message'] ?>
                    </div>
                <?php endif; ?>

                <form class="mt-8 space-y-6 relative" method="POST">
                    <div class="space-y-4">
                        <div class="card-hover">
                            <label for="username" class="text-gray-400">Username</label>
                            <input 
                                id="username" 
                                name="username" 
                                type="text" 
                                required 
                                class="bg-dark/50 border border-wine/30 rounded w-full px-4 py-2 mt-1 focus:outline-none focus:border-flame transition-all duration-300"
                            >
                        </div>
                        <div class="card-hover">
                            <label for="password" class="text-gray-400">Password</label>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required 
                                class="bg-dark/50 border border-wine/30 rounded w-full px-4 py-2 mt-1 focus:outline-none focus:border-flame transition-all duration-300"
                            >
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input 
                                id="remember-me" 
                                name="remember-me" 
                                type="checkbox" 
                                class="h-4 w-4 bg-dark border-wine/30 rounded"
                            >
                            <label for="remember-me" class="ml-2 text-sm text-gray-400">
                                Remember me
                            </label>
                        </div>
                        <a href="#" class="text-sm text-flame hover:text-crimson">
                            Forgot password?
                        </a>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full bg-flame text-white rounded-lg py-3 hover:bg-crimson transition-colors btn-glow"
                    >
                        Sign in
                    </button>

                    <p class="text-center text-sm text-gray-400">
                        Don't have an account? 
                        <a href="register.php" class="text-flame hover:text-crimson">
                            Sign up
                        </a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 
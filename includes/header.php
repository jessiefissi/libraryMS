<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$current_user = getCurrentUser();
$is_admin = $current_user && $current_user['role'] === 'admin';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eef2ff',   /* Lighter shade for backgrounds */
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',  /* Primary brand color */
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        secondary: {
                            50: '#f9fafb',
                            100: '#f3f4f6',
                            200: '#e5e7eb',
                            300: '#d1d5db',
                            400: '#9ca3af',
                            500: '#6b7280', /* Secondary gray for text/borders */
                            600: '#4b5563',
                            700: '#374151',
                            800: '#1f2937',
                            900: '#111827',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen font-sans antialiased">
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <a href="<?php echo APP_URL; ?>" class="flex items-center space-x-2 text-gray-900 hover:text-primary-700 transition duration-200">
                        <i class="fas fa-book-reader text-primary-600 text-2xl"></i>
                        <span class="text-xl font-extrabold tracking-tight"><?php echo APP_NAME; ?></span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-6">
                    <?php if ($current_user): ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo APP_URL; ?>/admin/index.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/index.php') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                            </a>
                            <a href="<?php echo APP_URL; ?>/admin/books/index.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/books/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-book mr-2"></i>Books
                            </a>
                            <a href="<?php echo APP_URL; ?>/admin/users/index.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/users/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-users mr-2"></i>Users
                            </a>
                            <a href="<?php echo APP_URL; ?>/admin/issued-books/index.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/issued-books/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-hand-holding-heart mr-2"></i>Issued Books
                            </a>
                        <?php else: ?>
                            <a href="<?php echo APP_URL; ?>/user/index.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/index.php') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-home mr-2"></i>Dashboard
                            </a>
                            <a href="<?php echo APP_URL; ?>/user/books/browse.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/books/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-search mr-2"></i>Browse Books
                            </a>
                            <a href="<?php echo APP_URL; ?>/user/my-books/current.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/my-books/') !== false ? 'active' : ''; ?>">
                                <i class="fas fa-book-reader mr-2"></i>My Books
                            </a>
                        <?php endif; ?>
                        <div x-data="{ open: false }" @click.away="open = false" class="relative">
                            <button @click="open = !open" type="button" 
                                    class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-opacity-50 rounded-full py-1 pr-2 pl-1 transition duration-200">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user text-primary-600"></i>
                                </div>
                                <span class="font-medium text-sm hidden sm:block"><?php echo htmlspecialchars($current_user['name']); ?></span>
                                <i class="fas fa-chevron-down text-xs text-gray-400" :class="{'rotate-180': open}"></i>
                            </button>
                            <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50 origin-top-right ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <?php if (!$is_admin): ?>
                                    <a href="<?php echo APP_URL; ?>/user/profile.php" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition duration-150">
                                        <i class="fas fa-user-circle mr-2 w-5 text-center"></i>Profile
                                    </a>
                                    <a href="<?php echo APP_URL; ?>/user/fines/index.php" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 transition duration-150">
                                        <i class="fas fa-dollar-sign mr-2 w-5 text-center"></i>Fines
                                    </a>
                                <?php endif; ?>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="<?php echo APP_URL; ?>/auth/logout.php" 
                                   class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50 hover:text-red-700 transition duration-150">
                                    <i class="fas fa-sign-out-alt mr-2 w-5 text-center"></i>Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo APP_URL; ?>/auth/login.php" 
                           class="px-4 py-2 rounded-md text-sm font-medium text-primary-700 bg-primary-100 hover:bg-primary-200 transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            Login
                        </a>
                        <a href="<?php echo APP_URL; ?>/auth/register.php" 
                           class="px-4 py-2 rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 transition duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                            Register
                        </a>
                    <?php endif; ?>
                </div>

                <div class="md:hidden flex items-center">
                    <button type="button" 
                            id="mobile-menu-button"
                            class="mobile-menu-button text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 rounded-md p-2 transition duration-200"
                            aria-controls="mobile-menu" aria-expanded="false">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="md:hidden hidden" id="mobile-menu">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-100 shadow-inner">
                <?php if ($current_user): ?>
                    <?php if ($is_admin): ?>
                        <a href="<?php echo APP_URL; ?>/admin/index.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/index.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt mr-3"></i>Dashboard
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin/books/index.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/books/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-book mr-3"></i>Books
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin/users/index.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/users/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-users mr-3"></i>Users
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin/issued-books/index.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/issued-books/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-hand-holding-heart mr-3"></i>Issued Books
                        </a>
                    <?php else: ?>
                        <a href="<?php echo APP_URL; ?>/user/index.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/index.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-home mr-3"></i>Dashboard
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/books/browse.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/books/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-search mr-3"></i>Browse Books
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/my-books/current.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/my-books/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-book-reader mr-3"></i>My Books
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/profile.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/profile.php') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-user-circle mr-3"></i>Profile
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/fines/index.php" class="mobile-nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/user/fines/') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-dollar-sign mr-3"></i>Fines
                        </a>
                    <?php endif; ?>
                    <div class="border-t border-gray-200 pt-2 mt-2">
                        <a href="<?php echo APP_URL; ?>/auth/logout.php" class="mobile-nav-link text-red-600 hover:bg-red-50 hover:text-red-700">
                            <i class="fas fa-sign-out-alt mr-3"></i>Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/auth/login.php" class="mobile-nav-link">
                        <i class="fas fa-sign-in-alt mr-3"></i>Login
                    </a>
                    <a href="<?php echo APP_URL; ?>/auth/register.php" class="mobile-nav-link">
                        <i class="fas fa-user-plus mr-3"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="fixed top-4 right-4 z-50 w-full max-w-sm" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="flex items-center justify-between p-4 rounded-lg shadow-lg 
                        <?php echo $_SESSION['flash_type'] === 'error' ? 'bg-red-500 text-white' : 'bg-green-500 text-white'; ?>">
                <div class="flex items-center">
                    <i class="text-xl mr-3 fas fa-<?php echo $_SESSION['flash_type'] === 'error' ? 'exclamation-circle' : 'check-circle'; ?>"></i>
                    <span class="font-medium text-sm"><?php echo htmlspecialchars($_SESSION['flash_message']); ?></span>
                </div>
                <button @click="show = false" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>
        <?php 
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>

    <style>
        /* Desktop Navigation Links */
        .nav-link {
            @apply text-gray-700 hover:text-primary-700 px-3 py-2 rounded-md text-sm font-medium transition duration-200 ease-in-out;
        }
        .nav-link.active {
            @apply text-primary-700 bg-primary-50; /* Softer active background */
        }
        .nav-link.active i {
             @apply text-primary-600; /* Primary color for active icon */
        }

        /* Mobile Navigation Links */
        .mobile-nav-link {
            @apply flex items-center px-4 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-50 hover:text-primary-700 transition duration-150 ease-in-out;
        }
        .mobile-nav-link.active {
            @apply bg-primary-50 text-primary-700;
        }
        .mobile-nav-link.active i {
            @apply text-primary-600;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var mobileMenuButton = document.getElementById('mobile-menu-button');
        var mobileMenu = document.getElementById('mobile-menu');

        if (mobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                // Toggle the 'hidden' class to show/hide the menu
                mobileMenu.classList.toggle('hidden');
                
                // Change the icon from bars to times and vice-versa
                const icon = mobileMenuButton.querySelector('i');
                if (mobileMenu.classList.contains('hidden')) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                    mobileMenuButton.setAttribute('aria-expanded', 'false');
                } else {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                    mobileMenuButton.setAttribute('aria-expanded', 'true');
                }
            });

            // Hide mobile menu on resize to desktop size
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) { // Tailwind's 'md' breakpoint
                    if (!mobileMenu.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                        const icon = mobileMenuButton.querySelector('i');
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                        mobileMenuButton.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        }
    });
    </script>
</body>
</html>
<?php
if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/auth.php';

$current_user = getCurrentUser();
$is_admin = $current_user && $current_user['role'] === 'admin';
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        secondary: {
                            50: '#f8fafc',
                            500: '#64748b',
                            600: '#475569',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center">
                    <a href="<?php echo APP_URL; ?>" class="flex items-center space-x-2">
                        <i class="fas fa-book text-primary-600 text-2xl"></i>
                        <span class="text-xl font-bold text-gray-900"><?php echo APP_NAME; ?></span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <?php if ($current_user): ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo APP_URL; ?>/admin/index.php" 
                               class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], '/admin/') !== false ? 'active' : ''; ?>">
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
                                <i class="fas fa-hand-holding mr-2"></i>Issued Books
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
                        
                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" 
                                    class="flex items-center space-x-2 text-gray-700 hover:text-primary-600 focus:outline-none">
                                <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-primary-600"></i>
                                </div>
                                <span class="font-medium"><?php echo htmlspecialchars($current_user['name']); ?></span>
                                <i class="fas fa-chevron-down text-sm"></i>
                            </button>
                            
                            <div x-show="open" 
                                 @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                
                                <?php if (!$is_admin): ?>
                                    <a href="<?php echo APP_URL; ?>/user/profile.php" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-user-edit mr-2"></i>Profile
                                    </a>
                                    <a href="<?php echo APP_URL; ?>/user/fines/index.php" 
                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-dollar-sign mr-2"></i>Fines
                                    </a>
                                <?php endif; ?>
                                
                                <div class="border-t border-gray-100"></div>
                                <a href="<?php echo APP_URL; ?>/auth/logout.php" 
                                   class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo APP_URL; ?>/auth/login.php" 
                           class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition duration-200">
                            Login
                        </a>
                        <a href="<?php echo APP_URL; ?>/auth/register.php" 
                           class="text-primary-600 hover:text-primary-700 font-medium">
                            Register
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button type="button" 
                            class="mobile-menu-button text-gray-600 hover:text-gray-900 focus:outline-none focus:text-gray-900"
                            x-data=""
                            @click="$dispatch('toggle-mobile-menu')">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div class="md:hidden" 
             x-data="{ open: false }" 
             @toggle-mobile-menu.window="open = !open"
             x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2">
            
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t">
                <?php if ($current_user): ?>
                    <?php if ($is_admin): ?>
                        <a href="<?php echo APP_URL; ?>/admin/index.php" class="mobile-nav-link">
                            <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin/books/index.php" class="mobile-nav-link">
                            <i class="fas fa-book mr-2"></i>Books
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin/users/index.php" class="mobile-nav-link">
                            <i class="fas fa-users mr-2"></i>Users
                        </a>
                        <a href="<?php echo APP_URL; ?>/admin/issued-books/index.php" class="mobile-nav-link">
                            <i class="fas fa-hand-holding mr-2"></i>Issued Books
                        </a>
                    <?php else: ?>
                        <a href="<?php echo APP_URL; ?>/user/index.php" class="mobile-nav-link">
                            <i class="fas fa-home mr-2"></i>Dashboard
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/books/browse.php" class="mobile-nav-link">
                            <i class="fas fa-search mr-2"></i>Browse Books
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/my-books/current.php" class="mobile-nav-link">
                            <i class="fas fa-book-reader mr-2"></i>My Books
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/profile.php" class="mobile-nav-link">
                            <i class="fas fa-user-edit mr-2"></i>Profile
                        </a>
                        <a href="<?php echo APP_URL; ?>/user/fines/index.php" class="mobile-nav-link">
                            <i class="fas fa-dollar-sign mr-2"></i>Fines
                        </a>
                    <?php endif; ?>
                    
                    <div class="border-t border-gray-200 pt-2 mt-2">
                        <a href="<?php echo APP_URL; ?>/auth/logout.php" class="mobile-nav-link text-red-600">
                            <i class="fas fa-sign-out-alt mr-2"></i>Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/auth/login.php" class="mobile-nav-link">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="<?php echo APP_URL; ?>/auth/register.php" class="mobile-nav-link">
                        <i class="fas fa-user-plus mr-2"></i>Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="fixed top-20 right-4 z-50" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="bg-<?php echo $_SESSION['flash_type'] === 'error' ? 'red' : 'green'; ?>-500 text-white px-6 py-3 rounded-lg shadow-lg">
                <div class="flex items-center">
                    <i class="fas fa-<?php echo $_SESSION['flash_type'] === 'error' ? 'exclamation-circle' : 'check-circle'; ?> mr-2"></i>
                    <span><?php echo htmlspecialchars($_SESSION['flash_message']); ?></span>
                    <button @click="show = false" class="ml-4 text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php 
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>

    <style>
        .nav-link {
            @apply text-gray-700 hover:text-primary-600 px-3 py-2 rounded-md text-sm font-medium transition duration-200;
        }
        .nav-link.active {
            @apply text-primary-600 bg-primary-50;
        }
        .mobile-nav-link {
            @apply text-gray-700 hover:text-primary-600 hover:bg-gray-50 block px-3 py-2 rounded-md text-base font-medium;
        }
    </style>
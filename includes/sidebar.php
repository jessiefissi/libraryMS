<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_section = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside class="w-64 bg-white shadow-lg h-full fixed left-0 top-16 overflow-y-auto z-30" x-data="{ open: false }" :class="{ 'translate-x-0': open, '-translate-x-full': !open }" class="transform transition-transform duration-300 ease-in-out lg:translate-x-0">
    <div class="p-4">
        <!-- Admin Profile Card -->
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-lg p-4 text-white mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-shield text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold"><?php echo htmlspecialchars(getCurrentUser()['name']); ?></h3>
                    <p class="text-sm opacity-90">Administrator</p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="space-y-2">
            <!-- Dashboard -->
            <a href="<?php echo APP_URL; ?>/admin/index.php" 
               class="sidebar-link <?php echo ($current_page === 'index' && $current_section === 'admin') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>

            <!-- Books Management -->
            <div class="sidebar-section">
                <h4 class="sidebar-section-title">
                    <i class="fas fa-book mr-2"></i>
                    Books Management
                </h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/books/index.php" 
                       class="sidebar-link <?php echo ($current_section === 'books' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>All Books</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/books/add.php" 
                       class="sidebar-link <?php echo ($current_section === 'books' && $current_page === 'add') ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>
                        <span>Add Book</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/categories/index.php" 
                       class="sidebar-link <?php echo ($current_section === 'categories') ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        <span>Categories</span>
                    </a>
                </div>
            </div>

            <!-- Users Management -->
            <div class="sidebar-section">
                <h4 class="sidebar-section-title">
                    <i class="fas fa-users mr-2"></i>
                    Users Management
                </h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/users/index.php" 
                       class="sidebar-link <?php echo ($current_section === 'users' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>All Users</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/users/add.php" 
                       class="sidebar-link <?php echo ($current_section === 'users' && $current_page === 'add') ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>
                        <span>Add User</span>
                    </a>
                </div>
            </div>

            <!-- Issued Books -->
            <div class="sidebar-section">
                <h4 class="sidebar-section-title">
                    <i class="fas fa-hand-holding mr-2"></i>
                    Issued Books
                </h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/index.php" 
                       class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>All Issued</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/issue.php" 
                       class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'issue') ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>
                        <span>Issue Book</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/return.php" 
                       class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'return') ? 'active' : ''; ?>">
                        <i class="fas fa-undo"></i>
                        <span>Return Book</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/history.php" 
                       class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'history') ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>History</span>
                    </a>
                </div>
            </div>

            <!-- Fines Management -->
            <div class="sidebar-section">
                <h4 class="sidebar-section-title">
                    <i class="fas fa-dollar-sign mr-2"></i>
                    Fines Management
                </h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/fines/index.php" 
                       class="sidebar-link <?php echo ($current_section === 'fines' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>All Fines</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/fines/add.php" 
                       class="sidebar-link <?php echo ($current_section === 'fines' && $current_page === 'add') ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>
                        <span>Add Fine</span>
                    </a>
                </div>
            </div>

            <!-- Reports -->
            <div class="sidebar-section">
                <h4 class="sidebar-section-title">
                    <i class="fas fa-chart-bar mr-2"></i>
                    Reports & Analytics
                </h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/reports/index.php" 
                       class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>Overview</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/reports/books-report.php" 
                       class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'books-report') ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        <span>Books Report</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/reports/users-report.php" 
                       class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'users-report') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Users Report</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/reports/financial-report.php" 
                       class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'financial-report') ? 'active' : ''; ?>">
                        <i class="fas fa-money-bill-wave"></i>
                        <span>Financial Report</span>
                    </a>
                </div>
            </div>

            <!-- System Settings -->
            <div class="sidebar-section">
                <h4 class="sidebar-section-title">
                    <i class="fas fa-cog mr-2"></i>
                    System
                </h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/settings.php" 
                       class="sidebar-link <?php echo ($current_page === 'settings') ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i>
                        <span>Settings</span>
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/backup.php" 
                       class="sidebar-link <?php echo ($current_page === 'backup') ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i>
                        <span>Backup</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Quick Stats -->
        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Quick Stats</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Books:</span>
                    <span class="font-medium" id="sidebar-total-books">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Active Users:</span>
                    <span class="font-medium" id="sidebar-active-users">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Issued Today:</span>
                    <span class="font-medium" id="sidebar-issued-today">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Overdue:</span>
                    <span class="font-medium text-red-600" id="sidebar-overdue">-</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Quick Actions</h4>
            <div class="space-y-2">
                <button onclick="window.open('<?php echo APP_URL; ?>/admin/books/add.php', '_blank')" 
                        class="w-full text-left px-3 py-2 text-sm bg-primary-50 text-primary-700 rounded-md hover:bg-primary-100 transition duration-200">
                    <i class="fas fa-plus mr-2"></i>Add New Book
                </button>
                <button onclick="window.open('<?php echo APP_URL; ?>/admin/issued-books/issue.php', '_blank')" 
                        class="w-full text-left px-3 py-2 text-sm bg-green-50 text-green-700 rounded-md hover:bg-green-100 transition duration-200">
                    <i class="fas fa-hand-holding mr-2"></i>Issue Book
                </button>
                <button onclick="window.open('<?php echo APP_URL; ?>/admin/users/add.php', '_blank')" 
                        class="w-full text-left px-3 py-2 text-sm bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition duration-200">
                    <i class="fas fa-user-plus mr-2"></i>Add User
                </button>
            </div>
        </div>
    </div>
</aside>

<!-- Mobile Sidebar Overlay -->
<div class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-20" 
     x-show="$store.sidebar.open" 
     @click="$store.sidebar.open = false"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"></div>

<!-- Mobile Sidebar Toggle Button -->
<button class="lg:hidden fixed top-20 left-4 z-40 bg-white p-2 rounded-md shadow-lg" 
        @click="$store.sidebar.open = !$store.sidebar.open">
    <i class="fas fa-bars text-gray-600"></i>
</button>

<style>
    .sidebar-link {
        @apply flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition duration-200;
    }
    
    .sidebar-link.active {
        @apply bg-primary-100 text-primary-700 border-r-2 border-primary-500;
    }
    
    .sidebar-link i {
        @apply w-5 text-center;
    }
    
    .sidebar-section {
        @apply mb-6;
    }
    
    .sidebar-section-title {
        @apply flex items-center text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 px-3;
    }
</style>

<script>
    // Load sidebar stats
    document.addEventListener('DOMContentLoaded', function() {
        loadSidebarStats();
    });

    function loadSidebarStats() {
        fetch('<?php echo APP_URL; ?>/api/dashboard-stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('sidebar-total-books').textContent = data.stats.total_books || 0;
                    document.getElementById('sidebar-active-users').textContent = data.stats.active_users || 0;
                    document.getElementById('sidebar-issued-today').textContent = data.stats.issued_today || 0;
                    document.getElementById('sidebar-overdue').textContent = data.stats.overdue_books || 0;
                }
            })
            .catch(error => console.error('Error loading sidebar stats:', error));
    }

    // Alpine.js store for sidebar state
    document.addEventListener('alpine:init', () => {
        Alpine.store('sidebar', {
            open: false,
            toggle() {
                this.open = !this.open;
            }
        });
    });
</script>
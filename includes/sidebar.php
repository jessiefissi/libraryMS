<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_section = basename(dirname($_SERVER['PHP_SELF']));
?>

<aside id="sidebar" class="fixed top-0 left-0 h-full w-64 bg-white shadow-xl transform -translate-x-full transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:h-auto lg:shadow-none lg:border-r lg:border-gray-200">
    <div class="p-5 flex flex-col h-full">
        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl p-4 text-white mb-6 flex items-center gap-3 shadow-md">
            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-shield text-2xl"></i>
            </div>
            <div>
                <h3 class="font-bold text-lg leading-tight"><?php echo htmlspecialchars(getCurrentUser()['name']); ?></h3>
                <p class="text-xs opacity-90">Administrator</p>
            </div>
        </div>

        <nav class="flex-1 space-y-2 overflow-y-auto pb-4">
            <a href="<?php echo APP_URL; ?>/admin/index.php" class="sidebar-link <?php echo ($current_page === 'index' && $current_section === 'admin') ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <div class="sidebar-section">
                <h4 class="sidebar-section-title"><i class="fas fa-book mr-2"></i>Books</h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/books/index.php" class="sidebar-link <?php echo ($current_section === 'books' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>All Books
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/books/add.php" class="sidebar-link <?php echo ($current_section === 'books' && $current_page === 'add') ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>Add Book
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/categories/index.php" class="sidebar-link <?php echo ($current_section === 'categories') ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>Categories
                    </a>
                </div>
            </div>
            <div class="sidebar-section">
                <h4 class="sidebar-section-title"><i class="fas fa-users mr-2"></i>Users</h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/users/index.php" class="sidebar-link <?php echo ($current_section === 'users' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>All Users
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/users/add.php" class="sidebar-link <?php echo ($current_section === 'users' && $current_page === 'add') ? 'active' : ''; ?>">
                        <i class="fas fa-user-plus"></i>Add User
                    </a>
                </div>
            </div>
            <div class="sidebar-section">
                <h4 class="sidebar-section-title"><i class="fas fa-hand-holding mr-2"></i>Issued</h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/index.php" class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>All Issued
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/issue.php" class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'issue') ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>Issue Book
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/return.php" class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'return') ? 'active' : ''; ?>">
                        <i class="fas fa-undo"></i>Return Book
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/issued-books/history.php" class="sidebar-link <?php echo ($current_section === 'issued-books' && $current_page === 'history') ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>History
                    </a>
                </div>
            </div>
            <div class="sidebar-section">
                <h4 class="sidebar-section-title"><i class="fas fa-dollar-sign mr-2"></i>Fines</h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/fines/index.php" class="sidebar-link <?php echo ($current_section === 'fines' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>All Fines
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/fines/add.php" class="sidebar-link <?php echo ($current_section === 'fines' && $current_page === 'add') ? 'active' : ''; ?>">
                        <i class="fas fa-plus"></i>Add Fine
                    </a>
                </div>
            </div>
            <div class="sidebar-section">
                <h4 class="sidebar-section-title"><i class="fas fa-chart-bar mr-2"></i>Reports</h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/reports/index.php" class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'index') ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>Overview
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/reports/books-report.php" class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'books-report') ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>Books Report
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/reports/users-report.php" class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'users-report') ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>Users Report
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/reports/financial-report.php" class="sidebar-link <?php echo ($current_section === 'reports' && $current_page === 'financial-report') ? 'active' : ''; ?>">
                        <i class="fas fa-money-bill-wave"></i>Financial Report
                    </a>
                </div>
            </div>
            <div class="sidebar-section">
                <h4 class="sidebar-section-title"><i class="fas fa-cog mr-2"></i>System</h4>
                <div class="ml-4 space-y-1">
                    <a href="<?php echo APP_URL; ?>/admin/settings.php" class="sidebar-link <?php echo ($current_page === 'settings') ? 'active' : ''; ?>">
                        <i class="fas fa-tools"></i>Settings
                    </a>
                    <a href="<?php echo APP_URL; ?>/admin/backup.php" class="sidebar-link <?php echo ($current_page === 'backup') ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i>Backup
                    </a>
                </div>
            </div>
        </nav>

        <div class="mt-auto p-4 bg-gray-50 rounded-lg border border-gray-100">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Quick Stats</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Total Books:</span>
                    <span class="font-bold text-gray-800" id="sidebar-total-books">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Active Users:</span>
                    <span class="font-bold text-gray-800" id="sidebar-active-users">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Issued Today:</span>
                    <span class="font-bold text-gray-800" id="sidebar-issued-today">-</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Overdue:</span>
                    <span class="font-bold text-red-600" id="sidebar-overdue">-</span>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <h4 class="text-sm font-semibold text-gray-700 mb-3">Quick Actions</h4>
            <div class="space-y-2">
                <button onclick="window.open('<?php echo APP_URL; ?>/admin/books/add.php', '_blank')" 
                        class="w-full text-left px-3 py-2 text-sm bg-blue-50 text-blue-700 rounded-md hover:bg-blue-100 transition duration-200 flex items-center justify-center">
                    <i class="fas fa-plus mr-2"></i>Add New Book
                </button>
                <button onclick="window.open('<?php echo APP_URL; ?>/admin/issued-books/issue.php', '_blank')" 
                        class="w-full text-left px-3 py-2 text-sm bg-green-50 text-green-700 rounded-md hover:bg-green-100 transition duration-200 flex items-center justify-center">
                    <i class="fas fa-hand-holding mr-2"></i>Issue Book
                </button>
                <button onclick="window.open('<?php echo APP_URL; ?>/admin/users/add.php', '_blank')" 
                        class="w-full text-left px-3 py-2 text-sm bg-purple-50 text-purple-700 rounded-md hover:bg-purple-100 transition duration-200 flex items-center justify-center">
                    <i class="fas fa-user-plus mr-2"></i>Add User
                </button>
            </div>
        </div>
    </div>
</aside>

<button id="sidebar-toggle" class="fixed top-4 left-4 z-50 p-3 rounded-full bg-white shadow-lg lg:hidden focus:outline-none focus:ring-2 focus:ring-blue-500" aria-label="Open sidebar">
    <i class="fas fa-bars text-gray-700 text-lg"></i>
</button>

<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden lg:hidden"></div>

<script>
// Sidebar toggle for mobile
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebarOverlay = document.getElementById('sidebar-overlay');

function toggleSidebar() {
    sidebar.classList.toggle('-translate-x-full');
    sidebarOverlay.classList.toggle('hidden');
}

if (sidebar && sidebarToggle && sidebarOverlay) {
    sidebarToggle.addEventListener('click', toggleSidebar);
    sidebarOverlay.addEventListener('click', toggleSidebar); // Close sidebar when clicking overlay

    // Hide sidebar on resize to desktop and show toggle on mobile
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
            sidebarToggle.classList.add('hidden'); // Hide toggle button on desktop
        } else {
            sidebarToggle.classList.remove('hidden'); // Show toggle button on mobile
        }
    });

    // Initial check for screen size on load
    if (window.innerWidth < 1024) {
        sidebarToggle.classList.remove('hidden');
    } else {
        sidebarToggle.classList.add('hidden');
    }
}

// Load sidebar stats
document.addEventListener('DOMContentLoaded', function() {
    loadSidebarStats();
});

function loadSidebarStats() {
    fetch('<?php echo APP_URL; ?>/api/dashboard-stats.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('sidebar-total-books').textContent = data.stats.total_books || 0;
                document.getElementById('sidebar-active-users').textContent = data.stats.active_users || 0;
                document.getElementById('sidebar-issued-today').textContent = data.stats.issued_today || 0;
                document.getElementById('sidebar-overdue').textContent = data.stats.overdue_books || 0;
            } else {
                console.error('API returned success: false', data.message);
            }
        })
        .catch(error => console.error('Error loading sidebar stats:', error));
}
</script>

<style>
    /* General sidebar link styling */
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem; /* Increased padding */
        border-radius: 0.625rem; /* Slightly more rounded */
        font-size: 0.9375rem; /* Slightly larger font */
        font-weight: 500;
        color: #4B5563; /* Darker gray for better contrast */
        transition: background 0.2s, color 0.2s, transform 0.2s; /* Added transform for hover effect */
        text-decoration: none;
        position: relative; /* For the active indicator */
    }

    .sidebar-link:hover {
        background: #e0f2fe; /* Lighter blue on hover */
        color: #1d4ed8; /* Darker blue on hover */
        transform: translateX(3px); /* Subtle slide effect on hover */
    }

    /* Active sidebar link styling */
    .sidebar-link.active {
        background: #bfdbfe; /* Stronger blue for active */
        color: #1e40af; /* Even darker blue for active text */
        font-weight: 600; /* Bolder active link */
    }

    .sidebar-link.active::after {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px; /* Thicker active indicator */
        height: 80%; /* Taller active indicator */
        background-color: #3b82f6; /* Blue bar */
        border-radius: 0 4px 4px 0; /* Rounded on one side */
    }

    /* Icon styling within sidebar links */
    .sidebar-link i {
        width: 1.5rem; /* Slightly larger icon area */
        text-align: center;
        color: #6B7280; /* Default icon color */
    }

    .sidebar-link.active i {
        color: #1e40af; /* Active icon color */
    }

    /* Section titles within sidebar */
    .sidebar-section {
        margin-top: 1.5rem; /* More space above sections */
        margin-bottom: 0.75rem; /* Less space below, as links have good spacing */
    }

    .sidebar-section-title {
        display: flex;
        align-items: center;
        font-size: 0.7rem; /* Slightly smaller for subtle header */
        font-weight: 700; /* Bolder */
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.08em; /* Increased letter spacing */
        margin-bottom: 0.75rem; /* More space below title before links */
        padding-left: 1rem; /* Align with link padding */
        padding-right: 1rem;
    }

    /* Mobile-specific adjustments */
    @media (max-width: 1023px) {
        #sidebar {
            top: 0;
            height: 100vh; /* Full viewport height on mobile */
            padding-top: 4rem; /* Space for the toggle button at the top */
        }
    }
</style>
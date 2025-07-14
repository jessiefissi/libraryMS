<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Check if user is logged in and is admin
$auth = new Auth($db);

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../../auth/login.php');
    exit();
}

// Get current date for calculations
$currentDate = date('Y-m-d');

// Get overview statistics
try {
    // Total books
    $stmt = $db->query("SELECT COUNT(*) as total_books FROM books");
    $totalBooks = $stmt->fetch()['total_books'];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
    $totalUsers = $stmt->fetch()['total_users'];
    
    // Currently issued books
    $stmt = $db->query("SELECT COUNT(*) as issued_books FROM issued_books WHERE status = 'issued'");
    $issuedBooks = $stmt->fetch()['issued_books'];
    
    // Total fines
    $stmt = $db->query("SELECT SUM(amount) as total_fines FROM fines");
    $totalFines = $stmt->fetch()['total_fines'] ?: 0;
    
    // Overdue books
    $stmt = $db->prepare("SELECT COUNT(*) as overdue_books FROM issued_books WHERE status = 'issued' AND return_date < ?");
    $stmt->execute([$currentDate]);
    $overdueBooks = $stmt->fetch()['overdue_books'];
    
    // Recent activity (last 30 days)
    $stmt = $db->prepare("SELECT COUNT(*) as recent_issues FROM issued_books WHERE issue_date >= DATE_SUB(?, INTERVAL 30 DAY)");
    $stmt->execute([$currentDate]);
    $recentIssues = $stmt->fetch()['recent_issues'];

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get monthly statistics for chart
try {
    $stmt = $db->prepare("
        SELECT 
            MONTH(issue_date) as month,
            YEAR(issue_date) as year,
            COUNT(*) as issues_count
        FROM issued_books 
        WHERE issue_date >= DATE_SUB(?, INTERVAL 12 MONTH)
        GROUP BY YEAR(issue_date), MONTH(issue_date)
        ORDER BY year, month
    ");
    $stmt->execute([$currentDate]);
    $monthlyStats = $stmt->fetchAll();
} catch (PDOException $e) {
    $monthlyStats = [];
}

// Get popular categories
try {
    $stmt = $db->query("
        SELECT 
            c.name as category_name,
            COUNT(ib.id) as issue_count
        FROM categories c
        LEFT JOIN books b ON c.id = b.category_id
        LEFT JOIN issued_books ib ON b.id = ib.book_id
        GROUP BY c.id, c.name
        ORDER BY issue_count DESC
        LIMIT 5
    ");
    $popularCategories = $stmt->fetchAll();
} catch (PDOException $e) {
    $popularCategories = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Dashboard - Library Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include '../../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="flex-1 p-6">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Reports Dashboard</h1>
                <p class="text-gray-600 mt-2">Comprehensive analytics and reporting for your library</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Books</p>
                            <p class="text-2xl font-bold text-blue-600"><?php echo number_format($totalBooks); ?></p>
                        </div>
                        <div class="bg-blue-100 rounded-full p-3">
                            <i class="fas fa-book text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Users</p>
                            <p class="text-2xl font-bold text-green-600"><?php echo number_format($totalUsers); ?></p>
                        </div>
                        <div class="bg-green-100 rounded-full p-3">
                            <i class="fas fa-users text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Books Issued</p>
                            <p class="text-2xl font-bold text-yellow-600"><?php echo number_format($issuedBooks); ?></p>
                        </div>
                        <div class="bg-yellow-100 rounded-full p-3">
                            <i class="fas fa-hand-holding text-yellow-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Overdue Books</p>
                            <p class="text-2xl font-bold text-red-600"><?php echo number_format($overdueBooks); ?></p>
                        </div>
                        <div class="bg-red-100 rounded-full p-3">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Fines</p>
                            <p class="text-2xl font-bold text-purple-600">$<?php echo number_format($totalFines, 2); ?></p>
                        </div>
                        <div class="bg-purple-100 rounded-full p-3">
                            <i class="fas fa-dollar-sign text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Recent Issues</p>
                            <p class="text-2xl font-bold text-indigo-600"><?php echo number_format($recentIssues); ?></p>
                        </div>
                        <div class="bg-indigo-100 rounded-full p-3">
                            <i class="fas fa-clock text-indigo-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Monthly Issues Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Monthly Book Issues</h2>
                    <canvas id="monthlyIssuesChart"></canvas>
                </div>

                <!-- Popular Categories Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Popular Categories</h2>
                    <canvas id="categoriesChart"></canvas>
                </div>
            </div>

            <!-- Report Links -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="books-report.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Books Report</h3>
                            <p class="text-gray-600">Detailed book analytics and statistics</p>
                        </div>
                        <i class="fas fa-book text-blue-600 text-2xl"></i>
                    </div>
                </a>

                <a href="users-report.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Users Report</h3>
                            <p class="text-gray-600">User activity and engagement metrics</p>
                        </div>
                        <i class="fas fa-users text-green-600 text-2xl"></i>
                    </div>
                </a>

                <a href="financial-report.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Financial Report</h3>
                            <p class="text-gray-600">Fines and revenue analysis</p>
                        </div>
                        <i class="fas fa-dollar-sign text-purple-600 text-2xl"></i>
                    </div>
                </a>
            </div>
        </main>
    </div>

    <script>
        // Monthly Issues Chart
        const monthlyIssuesCtx = document.getElementById('monthlyIssuesChart').getContext('2d');
        const monthlyIssuesChart = new Chart(monthlyIssuesCtx, {
            type: 'line',
            data: {
                labels: [
                    <?php 
                    foreach ($monthlyStats as $stat) {
                        echo "'" . date('M Y', mktime(0, 0, 0, $stat['month'], 1, $stat['year'])) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Book Issues',
                    data: [<?php echo implode(',', array_column($monthlyStats, 'issues_count')); ?>],
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Popular Categories Chart
        const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
        const categoriesChart = new Chart(categoriesCtx, {
            type: 'doughnut',
            data: {
                labels: [
                    <?php 
                    foreach ($popularCategories as $category) {
                        echo "'" . htmlspecialchars($category['category_name']) . "',";
                    }
                    ?>
                ],
                datasets: [{
                    data: [<?php echo implode(',', array_column($popularCategories, 'issue_count')); ?>],
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    </script>
</body>
</html>
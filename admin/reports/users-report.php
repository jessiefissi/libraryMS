<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../../auth/login.php');
    exit();
}

$error = '';

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$user_type = $_GET['user_type'] ?? 'all';

// Get user statistics
$stmt = $db->prepare("
    SELECT 
        u.id,
        u.name,
        u.email,
        u.role,
        COUNT(DISTINCT ib.id) as total_issues,
        COUNT(DISTINCT CASE WHEN ib.status = 'issued' THEN ib.id END) as current_issues,
        COUNT(DISTINCT CASE WHEN ib.status = 'returned' THEN ib.id END) as returned_books,
        COUNT(DISTINCT f.id) as total_fines,
        COALESCE(SUM(f.amount), 0) as fine_amount,
        MAX(ib.issue_date) as last_activity,
        COUNT(DISTINCT CASE WHEN ib.return_date < NOW() AND ib.status = 'issued' THEN ib.id END) as overdue_books
    FROM users u
    LEFT JOIN issued_books ib ON u.id = ib.user_id
    LEFT JOIN fines f ON u.id = f.user_id
    WHERE u.role = 'user'
    " . ($user_type == 'active' ? "AND ib.issue_date BETWEEN ? AND ?" : "") . "
    GROUP BY u.id, u.name, u.email, u.role
    ORDER BY total_issues DESC
");
if (!$stmt) {
    $error = 'Database error: ' . $db->error;
    $users = [];
} else {
    if ($user_type == 'active') {
        $stmt->bind_param('ss', $date_from, $date_to);
        $stmt->execute();
    } else {
        $stmt->execute();
    }
    $result = $stmt->get_result();
    $users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
}

// Get most active users
$stmt = $db->prepare("
    SELECT 
        u.name,
        u.email,
        COUNT(ib.id) as issue_count
    FROM users u
    JOIN issued_books ib ON u.id = ib.user_id
    WHERE ib.issue_date BETWEEN ? AND ?
    GROUP BY u.id, u.name, u.email
    ORDER BY issue_count DESC
    LIMIT 10
");
if (!$stmt) {
    $error = 'Database error: ' . $db->error;
    $mostActiveUsers = [];
} else {
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $mostActiveUsers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
}

// Get daily activity
$stmt = $db->prepare("
    SELECT 
        DATE(ib.issue_date) as activity_date,
        COUNT(DISTINCT ib.user_id) as active_users
    FROM issued_books ib
    WHERE ib.issue_date BETWEEN ? AND ?
    GROUP BY DATE(ib.issue_date)
    ORDER BY activity_date DESC
    LIMIT 30
");
if (!$stmt) {
    $error = 'Database error: ' . $db->error;
    $dailyActivity = [];
} else {
    $stmt->bind_param('ss', $date_from, $date_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $dailyActivity = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
}

// Get summary statistics
$result = $db->query("
    SELECT 
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT ib.user_id) as active_users,
        COUNT(DISTINCT CASE WHEN ib.status = 'issued' THEN ib.user_id END) as users_with_books,
        COUNT(DISTINCT f.user_id) as users_with_fines,
        ROUND(AVG(user_issues.issue_count), 2) as avg_issues_per_user
    FROM users u
    LEFT JOIN issued_books ib ON u.id = ib.user_id
    LEFT JOIN fines f ON u.id = f.user_id
    LEFT JOIN (
        SELECT user_id, COUNT(*) as issue_count
        FROM issued_books
        GROUP BY user_id
    ) user_issues ON u.id = user_issues.user_id
    WHERE u.role = 'user'
");
$summary = $result ? $result->fetch_assoc() : [
    'total_users' => 0,
    'active_users' => 0,
    'users_with_books' => 0,
    'users_with_fines' => 0,
    'avg_issues_per_user' => 0
];

// Get user activity patterns
$result = $db->query("
    SELECT 
        DAYNAME(ib.issue_date) as day_name,
        COUNT(*) as issues_count
    FROM issued_books ib
    WHERE ib.issue_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DAYNAME(ib.issue_date), DAYOFWEEK(ib.issue_date)
    ORDER BY DAYOFWEEK(ib.issue_date)
");
$weeklyActivity = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get users with overdue books
$result = $db->query("
    SELECT 
        u.name,
        u.email,
        COUNT(ib.id) as overdue_count,
        MAX(DATEDIFF(NOW(), ib.return_date)) as max_days_overdue
    FROM users u
    JOIN issued_books ib ON u.id = ib.user_id
    WHERE ib.status = 'issued' AND ib.return_date < NOW()
    GROUP BY u.id, u.name, u.email
    ORDER BY overdue_count DESC, max_days_overdue DESC
");
$overdueUsers = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Report - Library Operations System</title>
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
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Users Report</h1>
                        <p class="text-gray-600 mt-2">User activity and engagement metrics (Library Operations System)
                        </p>
                    </div>
                    <div class="flex space-x-2">
                        <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            <i class="fas fa-print mr-2"></i>Print Report
                        </button>
                        <a href="index.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold mb-4">Filters</h2>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">User Type</label>
                        <select name="user_type" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="all" <?php echo $user_type == 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="active" <?php echo $user_type == 'active' ? 'selected' : ''; ?>>Active Users</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>"
                            class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Total Users</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo number_format($summary['total_users']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Active Users</h3>
                    <p class="text-3xl font-bold text-green-600"><?php echo number_format($summary['active_users']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Users with Books</h3>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($summary['users_with_books']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Users with Fines</h3>
                    <p class="text-3xl font-bold text-red-600"><?php echo number_format($summary['users_with_fines']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Avg. Issues/User</h3>
                    <p class="text-3xl font-bold text-purple-600"><?php echo $summary['avg_issues_per_user']; ?></p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Daily Activity Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Daily Activity</h2>
                    <canvas id="dailyActivityChart"></canvas>
                </div>

                <!-- Weekly Activity Pattern -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Weekly Activity Pattern</h2>
                    <canvas id="weeklyActivityChart"></canvas>
                </div>
            </div>

            <!-- Most Active Users -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Most Active Users</h2>
                    <p class="text-gray-600">Users with highest book issue activity</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($mostActiveUsers as $index => $user): ?>
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                #<?php echo $index + 1; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <?php echo $user['issue_count']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Users with Overdue Books -->
            <?php if (!empty($overdueUsers)): ?>
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-bold text-red-600">Users with Overdue Books</h2>
                        <p class="text-gray-600">Users who have books past their return date</p>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overdue Books</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Max Days Overdue</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($overdueUsers as $user): ?>
                                        <tr>
                                            <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?php echo $user['overdue_count']; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?php echo $user['max_days_overdue']; ?> days
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- All Users Detailed Report -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">All Users Report</h2>
                    <p class="text-gray-600">Complete user activity and statistics</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Issues</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Books</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Returned</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Overdue</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fines</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Activity</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo $user['total_issues']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <?php echo $user['current_issues']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <?php echo $user['returned_books']; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?php if ($user['overdue_books'] > 0): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?php echo $user['overdue_books']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    0
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <?php if ($user['fine_amount'] > 0): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?php echo number_format($user['fine_amount'], 2); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    0.00
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo $user['last_activity'] ? htmlspecialchars(date('Y-m-d', strtotime($user['last_activity']))) : '-'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Chart.js Scripts -->
    <script>
        // Daily Activity Chart
        const dailyActivityCtx = document.getElementById('dailyActivityChart').getContext('2d');
        const dailyActivityChart = new Chart(dailyActivityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($dailyActivity, 'activity_date')); ?>,
                datasets: [{
                    label: 'Active Users',
                    data: <?php echo json_encode(array_column($dailyActivity, 'active_users')); ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.2)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 2,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            tooltipFormat: 'MMM D',
                            displayFormats: {
                                day: 'MMM D'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Date'
                        },
                        ticks: {
                            autoSkip: true,
                            maxTicksLimit: 10
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Active Users'
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    }
                }
            }
        });

        // Weekly Activity Pattern Chart
        const weeklyActivityCtx = document.getElementById('weeklyActivityChart').getContext('2d');
        const weeklyActivityChart = new Chart(weeklyActivityCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($weeklyActivity, 'day_name')); ?>,
                datasets: [{
                    label: 'Issues Count',
                    data: <?php echo json_encode(array_column($weeklyActivity, 'issues_count')); ?>,
                    backgroundColor: 'rgb(34, 197, 94)',
                    borderColor: 'rgb(22, 163, 74)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return `Issues: ${tooltipItem.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Day of the Week'
                        },
                        ticks: {
                            autoSkip: false
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Issues Count'
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>

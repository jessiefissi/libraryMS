<?php
// admin/reports/financial-report.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php'; // Use $db for $db->prepare(), or $conn for raw queries
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

try {
    // Total fines collected
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) as total_collected FROM fines WHERE due_date BETWEEN ? AND ?");
    $stmt->execute([$date_from, $date_to]);
    $total_collected = $stmt->fetchColumn();

    // Outstanding fines
    $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) as outstanding FROM fines WHERE due_date < NOW()");
    $stmt->execute();
    $outstanding = $stmt->fetchColumn();

    // Fines by user
    $stmt = $db->prepare("SELECT u.name, u.email, SUM(f.amount) as user_fines FROM users u JOIN fines f ON u.id = f.user_id WHERE f.due_date BETWEEN ? AND ? GROUP BY u.id, u.name, u.email ORDER BY user_fines DESC LIMIT 10");
    $stmt->execute([$date_from, $date_to]);
    $topUsers = $stmt->fetchAll();

    // Fines by book
    $stmt = $db->prepare("SELECT b.title, SUM(f.amount) as book_fines FROM books b JOIN fines f ON b.id = f.book_id WHERE f.due_date BETWEEN ? AND ? GROUP BY b.id, b.title ORDER BY book_fines DESC LIMIT 10");
    $stmt->execute([$date_from, $date_to]);
    $topBooks = $stmt->fetchAll();

    // Daily fines trend
    $stmt = $db->prepare("SELECT DATE(due_date) as day, SUM(amount) as total FROM fines WHERE due_date BETWEEN ? AND ? GROUP BY day ORDER BY day");
    $stmt->execute([$date_from, $date_to]);
    $dailyTrend = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report - Library Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
<?php include '../../includes/header.php'; ?>
<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">Financial Report</h1>
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="GET" class="mb-6 flex gap-4">
            <div>
                <label>Date From</label>
                <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="border rounded px-2 py-1">
            </div>
            <div>
                <label>Date To</label>
                <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="border rounded px-2 py-1">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Apply</button>
        </form>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold">Total Fines Collected</h3>
                <p class="text-3xl text-green-600 font-bold">$<?php echo number_format($total_collected,2); ?></p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold">Outstanding Fines</h3>
                <p class="text-3xl text-red-600 font-bold">$<?php echo number_format($outstanding,2); ?></p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-2">Top Users by Fines</h3>
                <ul>
                    <?php foreach ($topUsers as $user): ?>
                        <li class="mb-2 flex justify-between">
                            <span><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</span>
                            <span class="font-bold text-red-600">$<?php echo number_format($user['user_fines'],2); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-2">Top Books by Fines</h3>
                <ul>
                    <?php foreach ($topBooks as $book): ?>
                        <li class="mb-2 flex justify-between">
                            <span><?php echo htmlspecialchars($book['title']); ?></span>
                            <span class="font-bold text-blue-600">$<?php echo number_format($book['book_fines'],2); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h3 class="text-lg font-bold mb-4">Daily Fines Trend</h3>
            <canvas id="finesTrendChart"></canvas>
        </div>
    </main>
</div>
<script>
const trendLabels = <?php echo json_encode(array_column($dailyTrend, 'day')); ?>;
const trendData = <?php echo json_encode(array_map('floatval', array_column($dailyTrend, 'total'))); ?>;
new Chart(document.getElementById('finesTrendChart'), {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: 'Fines Collected',
            data: trendData,
            borderColor: 'rgb(37, 99, 235)',
            backgroundColor: 'rgba(37, 99, 235, 0.2)',
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } }
    }
});
</script>
</body>
</html>

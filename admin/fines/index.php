<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check if user is admin
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../../auth/login.php');
    exit;
}

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';

// Pagination settings
$records_per_page = 10;
$current_page = $_GET['page'] ?? 1;
$offset = ($current_page - 1) * $records_per_page;

// Search and filter parameters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'unpaid'; // Default to unpaid fines

// Build query with filters
$where_conditions = ["1=1"];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status_filter === 'paid') {
    $where_conditions[] = "f.paid_date IS NOT NULL";
} elseif ($status_filter === 'unpaid') {
    $where_conditions[] = "f.paid_date IS NULL";
}

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Count total records
$count_query = "SELECT COUNT(*) as total
                FROM fines f
                JOIN users u ON f.user_id = u.id
                JOIN books b ON f.book_id = b.id
                $where_clause";

// Main query
$query = "SELECT f.*, b.title, b.author, u.name as user_name, u.email as user_email
          FROM fines f
          JOIN users u ON f.user_id = u.id
          JOIN books b ON f.book_id = b.id
          $where_clause
          ORDER BY f.due_date DESC
          LIMIT $records_per_page OFFSET $offset";

try {
    // Get total count
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated results
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $fines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_pages = ceil($total_records / $records_per_page);
    
    // Get statistics
    $stats_query = "SELECT 
                    COUNT(*) as total_fines,
                    SUM(CASE WHEN paid_date IS NULL THEN amount ELSE 0 END) as unpaid_amount,
                    SUM(CASE WHEN paid_date IS NOT NULL THEN amount ELSE 0 END) as paid_amount
                    FROM fines";
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $fines = [];
    $total_pages = 0;
    $stats = ['total_fines' => 0, 'unpaid_amount' => 0, 'paid_amount' => 0];
}

$page_title = "Fines Management";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Library Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="flex-1 p-8">
            <div class="max-w-7xl mx-auto">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-dollar-sign text-2xl text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total Fines</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_fines']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-2xl text-red-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Unpaid Amount</p>
                                <p class="text-2xl font-bold text-red-600">$<?php echo number_format($stats['unpaid_amount'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-2xl text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Paid Amount</p>
                                <p class="text-2xl font-bold text-green-600">$<?php echo number_format($stats['paid_amount'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-dollar-sign mr-2"></i>Fines Management
                        </h1>
                        <a href="add.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">
                            <i class="fas fa-plus mr-2"></i>Add Fine
                        </a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Search and Filter Form -->
                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <form method="GET" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                    <input type="text" id="search" name="search" 
                                           value="<?php echo htmlspecialchars($search); ?>"
                                           placeholder="Book title, author, or user..."
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select id="status" name="status" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <option value="">All Status</option>
                                        <option value="unpaid" <?php echo $status_filter === 'unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                                        <option value="paid" <?php echo $status_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    </select>
                                </div>
                                
                                <div class="flex items-end space-x-2">
                                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">
                                        <i class="fas fa-search mr-2"></i>Search
                                    </button>
                                    <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition">
                                        <i class="fas fa-times mr-2"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Results Summary -->
                    <div class="mb-4">
                        <p class="text-sm text-gray-600">
                            Showing <?php echo count($fines); ?> of <?php echo $total_records; ?> fines
                        </p>
                    </div>
                    
                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        User
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Book
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Amount
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Due Date
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($fines)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            <i class="fas fa-inbox text-2xl mb-2"></i>
                                            <p>No fines found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($fines as $fine): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($fine['user_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($fine['user_email']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($fine['title']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        by <?php echo htmlspecialchars($fine['author']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                $<?php echo number_format($fine['amount'], 2); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo date('M j, Y', strtotime($fine['due_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($fine['paid_date']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>Paid
                                                    </span>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        Paid on <?php echo date('M j, Y', strtotime($fine['paid_date'])); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>Unpaid
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <?php if (!$fine['paid_date']): ?>
                                                        <a href="pay.php?id=<?php echo $fine['id']; ?>" 
                                                           class="text-green-600 hover:text-green-900">
                                                            <i class="fas fa-dollar-sign"></i> Pay
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="edit.php?id=<?php echo $fine['id']; ?>" 
                                                       class="text-blue-600 hover:text-blue-900">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                    <a href="delete.php?id=<?php echo $fine['id']; ?>" 
                                                       class="text-red-600 hover:text-red-900"
                                                       onclick="return confirm('Are you sure you want to delete this fine?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="mt-6 flex justify-between items-center">
                            <div class="text-sm text-gray-700">
                                Showing <?php echo (($current_page - 1) * $records_per_page) + 1; ?> to 
                                <?php echo min($current_page * $records_per_page, $total_records); ?> of 
                                <?php echo $total_records; ?> results
                            </div>
                            
                            <div class="flex space-x-2">
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                       class="bg-gray-300 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-400 transition">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                       class="px-3 py-2 rounded-md transition <?php echo $i == $current_page ? 'bg-blue-500 text-white' : 'bg-gray-300 text-gray-700 hover:bg-gray-400'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" 
                                       class="bg-gray-300 text-gray-700 px-3 py-2 rounded-md hover:bg-gray-400 transition">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>
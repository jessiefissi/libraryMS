<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';
$auth = new Auth($db);
// Check if user is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
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
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query with filters
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(b.title LIKE ? OR b.author LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $where_conditions[] = "ib.status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $where_conditions[] = "ib.issue_date >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "ib.issue_date <= ?";
    $params[] = $date_to;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Count total records
$count_query = "SELECT COUNT(*) as total
                FROM issued_books ib
                JOIN books b ON ib.book_id = b.id
                JOIN users u ON ib.user_id = u.id
                $where_clause";

// Main query
$query = "SELECT ib.*, b.title, b.author, u.name as user_name, u.email as user_email,
          CASE 
              WHEN ib.status = 'issued' AND ib.return_date < CURDATE() THEN 'overdue'
              ELSE ib.status
          END as display_status
          FROM issued_books ib
          JOIN books b ON ib.book_id = b.id
          JOIN users u ON ib.user_id = u.id
          $where_clause
          ORDER BY ib.issue_date DESC
          LIMIT $records_per_page OFFSET $offset";

try {
    // Get total count
    $count_stmt = $pdo->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated results
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $issued_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_pages = ceil($total_records / $records_per_page);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    $issued_books = [];
    $total_pages = 0;
}

$page_title = "Borrowing History";
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
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-history mr-2"></i>Borrowing History
                        </h1>
                        <a href="index.php" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Current Issues
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
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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
                                        <option value="issued" <?php echo $status_filter === 'issued' ? 'selected' : ''; ?>>Issued</option>
                                        <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Returned</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                                    <input type="date" id="date_from" name="date_from" 
                                           value="<?php echo htmlspecialchars($date_from); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                                    <input type="date" id="date_to" name="date_to" 
                                           value="<?php echo htmlspecialchars($date_to); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div class="flex items-end space-x-2">
                                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">
                                        <i class="fas fa-search mr-2"></i>Search
                                    </button>
                                    <a href="history.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition">
                                        <i class="fas fa-times mr-2"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Results Summary -->
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">
                            Showing <?php echo count($issued_books); ?> of <?php echo $total_records; ?> results
                        </p>
                    </div>
                    
                    <!-- Issued Books Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white rounded-lg shadow-md">
                            <thead>
                                <tr class="bg-gray-100 text-gray-700">
                                    <th class="py-3 px-4 text-left text-sm font-medium">Book Title</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium">Author</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium">Borrower</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium">Email</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium">Issue Date</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium">Due Date</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium">Status</th>
                                    <th class="py-3 px-4 text-left text-sm font-medium"></th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600">
                                <?php if (empty($issued_books)): ?>
                                    <tr>
                                        <td colspan="8" class="py-4 px-6 text-center text-sm text-gray-500">
                                            No records found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($issued_books as $book): ?>
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($book['title']); ?></td>
                                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($book['author']); ?></td>
                                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($book['user_name']); ?></td>
                                            <td class="py-3 px-4 text-sm"><?php echo htmlspecialchars($book['user_email']); ?></td>
                                            <td class="py-3 px-4 text-sm"><?php echo date('Y-m-d', strtotime($book['issue_date'])); ?></td>
                                            <td class="py-3 px-4 text-sm"><?php echo date('Y-m-d', strtotime($book['due_date'])); ?></td>
                                            <td class="py-3 px-4 text-sm">
                                                <?php if ($book['display_status'] === 'overdue'): ?>
                                                    <span class="text-red-500 font-semibold"><?php echo htmlspecialchars($book['display_status']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-green-500 font-semibold"><?php echo htmlspecialchars($book['display_status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-right">
                                                <a href="view.php?id=<?php echo $book['id']; ?>" class="text-blue-500 hover:text-blue-600 transition">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-4">
                        <nav class="flex justify-between items-center" aria-label="Table navigation">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <a href="#" class="prev text-sm font-medium text-gray-500 hover:text-gray-700">
                                    Previous
                                </a>
                                <a href="#" class="next text-sm font-medium text-gray-500 hover:text-gray-700">
                                    Next
                                </a>
                            </div>
                            <div class="hidden sm:flex sm:flex-1 sm:justify-center">
                                <a href="#" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                                    Previous
                                </a>
                                <span class="px-4 py-2 text-sm text-gray-500">
                                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                                </span>
                                <a href="#" class="px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700">
                                    Next
                                </a>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
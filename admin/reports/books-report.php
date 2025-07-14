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

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

try {
    // Get categories for filter
    $stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
    // Get books statistics
    $stmt = $db->query("
        SELECT 
            b.id,
            b.title,
            b.author,
            b.isbn,
            b.quantity,
            c.name as category_name,
            COUNT(ib.id) as total_issues,
            COUNT(CASE WHEN ib.status = 'issued' THEN 1 END) as current_issues,
            COUNT(CASE WHEN ib.status = 'returned' THEN 1 END) as returned_count,
            MAX(ib.issue_date) as last_issued
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN issued_books ib ON b.id = ib.book_id
        GROUP BY b.id, b.title, b.author, b.isbn, b.quantity, c.name
        ORDER BY total_issues DESC
    ");
    $books = $stmt->fetchAll();
    
    // Get most popular books
    $stmt = $db->prepare("
        SELECT 
            b.title,
            b.author,
            c.name as category_name,
            COUNT(ib.id) as issue_count
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN issued_books ib ON b.id = ib.book_id
        WHERE ib.issue_date BETWEEN ? AND ?
        " . ($category_filter ? "AND c.id = ?" : "") . "
        GROUP BY b.id, b.title, b.author, c.name
        HAVING issue_count > 0
        ORDER BY issue_count DESC
        LIMIT 10
    ");
    
    $params = [$date_from, $date_to];
    if ($category_filter) {
        $params[] = $category_filter;
    }
    $stmt->execute($params);
    $popularBooks = $stmt->fetchAll();
    
    // Get category statistics
    $stmt = $db->prepare("
        SELECT 
            c.name as category_name,
            COUNT(DISTINCT b.id) as total_books,
            COUNT(ib.id) as total_issues,
            COUNT(CASE WHEN ib.status = 'issued' THEN 1 END) as current_issues,
            ROUND(AVG(b.quantity), 2) as avg_quantity
        FROM categories c
        LEFT JOIN books b ON c.id = b.category_id
        LEFT JOIN issued_books ib ON b.id = ib.book_id
        WHERE (ib.issue_date BETWEEN ? AND ?) OR ib.issue_date IS NULL
        GROUP BY c.id, c.name
        ORDER BY total_issues DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $categoryStats = $stmt->fetchAll();
    
    // Get overdue books
    $stmt = $db->prepare("
        SELECT 
            b.title,
            b.author,
            u.name as user_name,
            u.email,
            ib.issue_date,
            ib.return_date,
            DATEDIFF(NOW(), ib.return_date) as days_overdue
        FROM issued_books ib
        JOIN books b ON ib.book_id = b.id
        JOIN users u ON ib.user_id = u.id
        WHERE ib.status = 'issued' AND ib.return_date < NOW()
        ORDER BY days_overdue DESC
    ");
    $stmt->execute();
    $overdueBooks = $stmt->fetchAll();
    
    // Get summary statistics
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT b.id) as total_books,
            SUM(b.quantity) as total_inventory,
            COUNT(DISTINCT c.id) as total_categories,
            COUNT(CASE WHEN ib.status = 'issued' THEN 1 END) as books_issued,
            COUNT(CASE WHEN ib.status = 'returned' THEN 1 END) as books_returned
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN issued_books ib ON b.id = ib.book_id
    ");
    $summary = $stmt->fetch();

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Books Report - Library Management</title>
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
                        <h1 class="text-3xl font-bold text-gray-800">Books Report</h1>
                        <p class="text-gray-600 mt-2">Detailed analytics and statistics for your book collection</p>
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
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category" class="w-full border border-gray-300 rounded-md px-3 py-2">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
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
                    <h3 class="text-lg font-bold text-gray-800">Total Books</h3>
                    <p class="text-3xl font-bold text-blue-600"><?php echo number_format($summary['total_books']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Total Inventory</h3>
                    <p class="text-3xl font-bold text-green-600"><?php echo number_format($summary['total_inventory']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Categories</h3>
                    <p class="text-3xl font-bold text-purple-600"><?php echo number_format($summary['total_categories']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Books Issued</h3>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo number_format($summary['books_issued']); ?></p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-800">Books Returned</h3>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo number_format($summary['books_returned']); ?></p>
                </div>
            </div>

            <!-- Popular Books -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Most Popular Books</h2>
                    <p class="text-gray-600">Based on issue frequency for selected date range</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issues</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($popularBooks as $index => $book): ?>
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                #<?php echo $index + 1; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo htmlspecialchars($book['category_name']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <?php echo $book['issue_count']; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Category Statistics -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Category Statistics</h2>
                    <p class="text-gray-600">Books and issues by category</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Books</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Issues</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Issues</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Avg. Quantity</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($categoryStats as $category): ?>
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo number_format($category['total_books']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo number_format($category['total_issues']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo number_format($category['current_issues']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo $category['avg_quantity']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Overdue Books -->
            <?php if (!empty($overdueBooks)): ?>
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="p-6 border-b">
                        <h2 class="text-xl font-bold">Overdue Books</h2>
                        <p class="text-gray-600">Books that are overdue for return</p>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days Overdue</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($overdueBooks as $book): ?>
                                        <tr>
                                            <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900">
                                                <?php echo htmlspecialchars($book['title']); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars($book['author']); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars($book['user_name']); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars($book['email']); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars(date('Y-m-d', strtotime($book['issue_date']))); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                                <?php echo htmlspecialchars(date('Y-m-d', strtotime($book['return_date']))); ?>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    <?php echo (int)$book['days_overdue']; ?>
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

            <!-- All Books Table -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">All Books Statistics</h2>
                    <p class="text-gray-600">Overview of all books in the library</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ISBN</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Issues</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Issues</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Returned</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Issued</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td class="px-4 py-4 whitespace-nowrap font-medium text-gray-900">
                                            <?php echo htmlspecialchars($book['title']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo htmlspecialchars($book['author']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo htmlspecialchars($book['isbn']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo htmlspecialchars($book['category_name']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo number_format($book['quantity']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo number_format($book['total_issues']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo number_format($book['current_issues']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo number_format($book['returned_count']); ?>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-gray-600">
                                            <?php echo $book['last_issued'] ? htmlspecialchars(date('Y-m-d', strtotime($book['last_issued']))) : '-'; ?>
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
</body>
</html>
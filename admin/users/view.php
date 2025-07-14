<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/auth/login.php');
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    redirect('/admin/users/index.php');
}

$database = new Database();
$db = $database->getConnection();

// Fetch user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('/admin/users/index.php');
}

// Get user statistics
$statsQuery = "SELECT 
    COUNT(CASE WHEN ib.status = 'issued' THEN 1 END) as current_books,
    COUNT(ib.id) as total_borrowed,
    COUNT(CASE WHEN ib.status = 'returned' THEN 1 END) as returned_books,
    COALESCE(SUM(f.amount), 0) as total_fines
    FROM users u
    LEFT JOIN issued_books ib ON u.id = ib.user_id
    LEFT JOIN fines f ON u.id = f.user_id
    WHERE u.id = ?
    GROUP BY u.id";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->bindParam(1, $user_id);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

if (!$stats) {
    $stats = ['current_books' => 0, 'total_borrowed' => 0, 'returned_books' => 0, 'total_fines' => 0];
}

// Get current issued books
$currentBooksQuery = "SELECT b.title, b.author, b.isbn, ib.issue_date, ib.return_date, ib.id as issue_id
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    WHERE ib.user_id = ? AND ib.status = 'issued'
    ORDER BY ib.issue_date DESC";
$currentBooksStmt = $db->prepare($currentBooksQuery);
$currentBooksStmt->bindParam(1, $user_id);
$currentBooksStmt->execute();
$currentBooks = $currentBooksStmt->fetchAll(PDO::FETCH_ASSOC);

// Get borrowing history
$historyQuery = "SELECT b.title, b.author, b.isbn, ib.issue_date, ib.return_date, ib.status
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    WHERE ib.user_id = ?
    ORDER BY ib.issue_date DESC
    LIMIT 10";
$historyStmt = $db->prepare($historyQuery);
$historyStmt->bindParam(1, $user_id);
$historyStmt->execute();
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Get fines
$finesQuery = "SELECT f.amount, f.due_date, b.title, b.author
    FROM fines f
    JOIN books b ON f.book_id = b.id
    WHERE f.user_id = ?
    ORDER BY f.due_date DESC";
$finesStmt = $db->prepare($finesQuery);
$finesStmt->bindParam(1, $user_id);
$finesStmt->execute();
$fines = $finesStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'View User - Admin';
include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="lg:pl-64">
        <div class="p-6">
            <div class="max-w-6xl mx-auto">
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">User Profile</h1>
                        <p class="text-gray-600">View detailed user information and activity</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="/admin/users/edit.php?id=<?php echo $user['id']; ?>" 
                           class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Edit User
                        </a>
                        <a href="/admin/users/index.php" 
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Back to Users
                        </a>
                    </div>
                </div>
                
                <!-- User Information -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="p-6">
                        <div class="flex items-center mb-6">
                            <div class="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-xl font-bold text-blue-600">
                                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                </span>
                            </div>
                            <div class="ml-6">
                                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h2>
                                <p class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Statistics -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Current Books</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['current_books']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-green-100 rounded-lg">
                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Books Returned</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['returned_books']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-yellow-100 rounded-lg">
                                <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Borrowed</p>
                                <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_borrowed']; ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-red-100 rounded-lg">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm text-gray-600">Total Fines</p>
                                <p class="text-2xl font-bold text-gray-900">$<?php echo number_format($stats['total_fines'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Current Books -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Books</h3>
                            <?php if (empty($currentBooks)): ?>
                                <p class="text-gray-500">No books currently borrowed</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($currentBooks as $book): ?>
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($book['title']); ?></h4>
                                            <p class="text-sm text-gray-600">by <?php echo htmlspecialchars($book['author']); ?></p>
                                            <p class="text-xs text-gray-500 mt-1">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></p>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-xs text-gray-500">
                                                    Issued: <?php echo date('M d, Y', strtotime($book['issue_date'])); ?>
                                                </span>
                                                <?php if ($book['return_date'] && strtotime($book['return_date']) < time()): ?>
                                                    <span class="text-xs text-red-600 font-medium">Overdue</span>
                                                <?php elseif ($book['return_date']): ?>
                                                    <span class="text-xs text-blue-600">Due: <?php echo date('M d, Y', strtotime($book['return_date'])); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Fines -->
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Outstanding Fines</h3>
                            <?php if (empty($fines)): ?>
                                <p class="text-gray-500">No outstanding fines</p>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($fines as $fine): ?>
                                        <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                                            <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($fine['title']); ?></h4>
                                            <p class="text-sm text-gray-600">by <?php echo htmlspecialchars($fine['author']); ?></p>
                                            <div class="flex justify-between items-center mt-2">
                                                <span class="text-sm font-medium text-red-600">
                                                    $<?php echo number_format($fine['amount'], 2); ?>
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    Due: <?php echo date('M d, Y', strtotime($fine['due_date'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Borrowing History -->
                <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Borrowing History</h3>
                        <?php if (empty($history)): ?>
                            <p class="text-gray-500">No borrowing history</p>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($history as $record): ?>
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['title']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($record['isbn']); ?></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($record['author']); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo date('M d, Y', strtotime($record['issue_date'])); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <?php echo $record['return_date'] ? date('M d, Y', strtotime($record['return_date'])) : 'Not returned'; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $record['status'] === 'returned' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
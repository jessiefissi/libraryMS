<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';
$auth = new Auth($db);
// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    redirect('/auth/login.php');
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    redirect('/admin/users/index.php');
}

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Fetch user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bindParam(1, $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    redirect('/admin/users/index.php');
}

// Prevent deletion of current admin user
if ($user['id'] == $_SESSION['user_id']) {
    $_SESSION['error_message'] = 'You cannot delete your own account';
    redirect('/admin/users/index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $confirm_delete = $_POST['confirm_delete'] ?? '';
    
    if ($confirm_delete !== 'DELETE') {
        $errors[] = 'Please type "DELETE" to confirm deletion';
    } else {
        try {
            $db->beginTransaction();
            
            // Check if user has any issued books
            $issuedQuery = "SELECT COUNT(*) as count FROM issued_books WHERE user_id = ? AND status = 'issued'";
            $issuedStmt = $db->prepare($issuedQuery);
            $issuedStmt->bindParam(1, $user_id);
            $issuedStmt->execute();
            $issuedCount = $issuedStmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($issuedCount > 0) {
                $errors[] = 'Cannot delete user with active book loans. Please return all books first.';
                $db->rollback();
            } else {
                // Delete related records first
                $deleteFinesQuery = "DELETE FROM fines WHERE user_id = ?";
                $deleteFinesStmt = $db->prepare($deleteFinesQuery);
                $deleteFinesStmt->bindParam(1, $user_id);
                $deleteFinesStmt->execute();
                
                $deleteIssuedQuery = "DELETE FROM issued_books WHERE user_id = ?";
                $deleteIssuedStmt = $db->prepare($deleteIssuedQuery);
                $deleteIssuedStmt->bindParam(1, $user_id);
                $deleteIssuedStmt->execute();
                
                // Delete user
                $deleteUserQuery = "DELETE FROM users WHERE id = ?";
                $deleteUserStmt = $db->prepare($deleteUserQuery);
                $deleteUserStmt->bindParam(1, $user_id);
                
                if ($deleteUserStmt->execute()) {
                    $db->commit();
                    $_SESSION['success_message'] = 'User deleted successfully';
                    redirect('/admin/users/index.php');
                } else {
                    $db->rollback();
                    $errors[] = 'Failed to delete user';
                }
            }
        } catch (PDOException $e) {
            $db->rollback();
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get user statistics
$statsQuery = "SELECT 
    COUNT(CASE WHEN ib.status = 'issued' THEN 1 END) as current_books,
    COUNT(ib.id) as total_borrowed,
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
    $stats = ['current_books' => 0, 'total_borrowed' => 0, 'total_fines' => 0];
}

$pageTitle = 'Delete User - Admin';
include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="lg:pl-64">
        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Delete User</h1>
                    <p class="text-gray-600">This action cannot be undone. Please confirm deletion.</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error:</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- User Information -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">User Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Name</p>
                                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Role</p>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Statistics -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">User Statistics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="p-2 bg-blue-100 rounded-lg">
                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm text-blue-600">Current Books</p>
                                        <p class="text-2xl font-bold text-blue-900"><?php echo $stats['current_books']; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="p-2 bg-green-100 rounded-lg">
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm text-green-600">Total Borrowed</p>
                                        <p class="text-2xl font-bold text-green-900"><?php echo $stats['total_borrowed']; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-red-50 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="p-2 bg-red-100 rounded-lg">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <p class="text-sm text-red-600">Total Fines</p>
                                        <p class="text-2xl font-bold text-red-900">$<?php echo number_format($stats['total_fines'], 2); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Delete Confirmation -->
                <div class="bg-white rounded-lg shadow-sm border border-red-200">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Confirm Deletion</h3>
                            </div
<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    redirect('/auth/login.php');
}

$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    redirect('/admin/users/index.php');
}

// Fetch user details
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $db->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    redirect('/admin/users/index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (!in_array($role, ['admin', 'user'])) {
        $errors[] = 'Invalid role selected';
    }
    
    // Check if email already exists (excluding current user)
    $emailQuery = "SELECT id FROM users WHERE email = ? AND id != ?";
    $emailStmt = $db->prepare($emailQuery);
    $emailStmt->bind_param('si', $email, $user_id);
    $emailStmt->execute();
    $emailStmt->store_result();
    
    if ($emailStmt->num_rows > 0) {
        $errors[] = 'Email already exists';
    }
    $emailStmt->close();
    
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Update with new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateQuery = "UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bind_param('ssssi', $name, $email, $role, $hashedPassword, $user_id);
            } else {
                // Update without changing password
                $updateQuery = "UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bind_param('sssi', $name, $email, $role, $user_id);
            }
            
            if ($updateStmt->execute()) {
                $success = 'User updated successfully';
                // Refresh user data
                $stmt = $db->prepare($query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
            } else {
                $errors[] = 'Failed to update user';
            }
            $updateStmt->close();
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit User - Admin';
include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="lg:pl-64">
        <div class="p-6">
            <div class="max-w-4xl mx-auto">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
                    <p class="text-gray-600">Update user information and permissions</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                                <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="ml-3 text-sm text-green-800"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Full Name *
                                    </label>
                                    <input type="text" 
                                           id="name" 
                                           name="name" 
                                           value="<?php echo htmlspecialchars($user['name']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           required>
                                </div>
                                
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address *
                                    </label>
                                    <input type="email" 
                                           id="email" 
                                           name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           required>
                                </div>
                                
                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                                        Role *
                                    </label>
                                    <select id="role" 
                                            name="role" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                        New Password
                                    </label>
                                    <input type="password" 
                                           id="password" 
                                           name="password" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="Leave blank to keep current password">
                                    <p class="mt-1 text-sm text-gray-500">Leave blank to keep current password</p>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                                <a href="/admin/users/index.php" 
                                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Update User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- User Statistics -->
                <div class="mt-8 bg-white rounded-lg shadow-sm border border-gray-200">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">User Statistics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php
                            // Get user statistics
                            $statsQuery = "SELECT 
                                COUNT(CASE WHEN status = 'issued' THEN 1 END) as current_books,
                                COUNT(*) as total_borrowed,
                                COALESCE(SUM(CASE WHEN f.amount IS NOT NULL THEN f.amount ELSE 0 END), 0) as total_fines
                                FROM issued_books ib
                                LEFT JOIN fines f ON f.user_id = ib.user_id
                                WHERE ib.user_id = ?";
                            $statsStmt = $db->prepare($statsQuery);
                            $statsStmt->bind_param('i', $user_id);
                            $statsStmt->execute();
                            $result = $statsStmt->get_result();
                            $stats = $result->fetch_assoc();
                            $statsStmt->close();
                            if (!$stats) {
                                $stats = ['current_books' => 0, 'total_borrowed' => 0, 'total_fines' => 0];
                            }
                            ?>
                            
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
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
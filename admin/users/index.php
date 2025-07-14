<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Database connection
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

// Check if user is admin
if (!$auth->isAdmin()) {
    header('Location: ../../auth/login.php');
    exit;
}

$success = '';
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $success = 'User deleted successfully!';
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = '';
$searchParams = [];

if ($search) {
    $searchQuery = " WHERE name LIKE ? OR email LIKE ?";
    $searchParams = ["%$search%", "%$search%"];
}

// Get total users count
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users" . $searchQuery);
    $stmt->execute($searchParams);
    $totalUsers = $stmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
} catch (PDOException $e) {
    $totalUsers = 0;
    $totalPages = 0;
}

// Get users
try {
    $stmt = $pdo->prepare("SELECT * FROM users" . $searchQuery . " ORDER BY name LIMIT ? OFFSET ?");
    $params = array_merge($searchParams, [$limit, $offset]);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
}

$page_title = 'Users Management';
include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Users Management</h1>
                    <a href="add.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm">
                        Add New User
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <!-- Search Form -->
                <form method="GET" class="mb-6">
                    <div class="flex items-center space-x-4">
                        <input type="text" 
                               name="search" 
                               placeholder="Search users by name or email..." 
                               class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                            Search
                        </button>
                        <?php if ($search): ?>
                            <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <!-- Users Table -->
                <div class="overflow-x-auto">
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-2 text-left">ID</th>
                                <th class="px-4 py-2 text-left">Name</th>
                                <th class="px-4 py-2 text-left">Email</th>
                                <th class="px-4 py-2 text-left">Role</th>
                                <th class="px-4 py-2 text-left">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">
                                        <?php echo $search ? 'No users found matching your search.' : 'No users found.'; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-2"><?php echo $user['id']; ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $user['role'] == 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="flex space-x-2">
                                                <a href="view.php?id=<?php echo $user['id']; ?>" 
                                                   class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                                    View
                                                </a>
                                                <a href="edit.php?id=<?php echo $user['id']; ?>" 
                                                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">
                                                    Edit
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): // Don't allow deleting own account ?>
                                                    <a href="delete.php?id=<?php echo $user['id']; ?>" 
                                                       class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm"
                                                       onclick="return confirm('Are you sure you want to delete this user?')">
                                                        Delete
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center items-center space-x-2 mt-6">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="<?php echo $i == $page ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?> px-3 py-1 rounded text-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="bg-gray-500 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center text-sm text-gray-600 mt-4">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalUsers); ?> of <?php echo $totalUsers; ?> users
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
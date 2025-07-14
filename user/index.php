<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user stats (books borrowed, fines, etc.)
$stmt = $db->prepare("SELECT COUNT(*) as borrowed FROM issued_books WHERE user_id = ? AND status = 'issued'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$borrowed = $result->fetch_assoc()['borrowed'] ?? 0;

$stmt = $db->prepare("SELECT COUNT(*) as fines FROM fines WHERE user_id = ? AND status = 'unpaid'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$fines = $result->fetch_assoc()['fines'] ?? 0;

$pageTitle = 'User Dashboard';
include '../includes/header.php';
?>
<div class="min-h-screen bg-gray-50">
    <?php include '../includes/sidebar.php'; ?>
    <div class="lg:pl-64">
        <div class="p-6 max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="bg-blue-100 rounded-lg p-6 flex flex-col items-center">
                        <span class="text-3xl font-bold text-blue-700"><?php echo $borrowed; ?></span>
                        <span class="text-gray-700 mt-2">Books Currently Borrowed</span>
                    </div>
                    <div class="bg-red-100 rounded-lg p-6 flex flex-col items-center">
                        <span class="text-3xl font-bold text-red-700"><?php echo $fines; ?></span>
                        <span class="text-gray-700 mt-2">Unpaid Fines</span>
                    </div>
                </div>
                <div class="mt-6 flex space-x-4">
                    <a href="books/browse.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Browse Books</a>
                    <a href="my-books/current.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">My Books</a>
                    <a href="fines/index.php" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">My Fines</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

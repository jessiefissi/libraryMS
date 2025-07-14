<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../../auth/login.php');
    exit;
}

$book_id = $_GET['id'] ?? null;
if (!$book_id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT b.*, c.name as category FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?");
$stmt->bind_param('i', $book_id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    header('Location: index.php');
    exit;
}

$pageTitle = 'Book Details';
include '../../includes/header.php';
?>
<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    <div class="lg:pl-64">
        <div class="p-6 max-w-3xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold mb-4">Book Details</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <img src="../../uploads/book-covers/<?php echo htmlspecialchars($book['cover_image'] ?? ''); ?>" alt="Book Cover" class="w-48 h-64 object-cover rounded border mb-4">
                    </div>
                    <div>
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($book['title']); ?></p>
                        <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?></p>
                        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($book['quantity']); ?></p>
                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($book['description'] ?? '')); ?></p>
                    </div>
                </div>
                <div class="mt-6 flex space-x-4">
                    <a href="edit.php?id=<?php echo $book_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Edit</a>
                    <a href="delete.php?id=<?php echo $book_id; ?>" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                    <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Back to List</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>

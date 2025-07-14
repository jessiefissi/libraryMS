<?php
// user/books/details.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php'; // Use $db for $db->prepare(), or $conn for raw queries
$book_id = $_GET['id'] ?? null;
if (!$book_id) {
    header('Location: browse.php');
    exit();
}
$stmt = $db->prepare("SELECT b.*, c.name as category FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();
if (!$book) {
    header('Location: browse.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Details - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<?php include '../../includes/header.php'; ?>
<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">Book Details</h1>
        <div class="bg-white rounded-lg shadow p-6 max-w-xl">
            <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($book['title']); ?></h2>
            <div class="mb-2 text-gray-700">Author: <?php echo htmlspecialchars($book['author']); ?></div>
            <div class="mb-2 text-gray-500">Category: <?php echo htmlspecialchars($book['category']); ?></div>
            <div class="mb-2 text-gray-500">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></div>
            <div class="mb-2 text-gray-500">Available: <?php echo $book['quantity']; ?></div>
            <?php if (!empty($book['cover_image'])): ?>
                <img src="../../uploads/book-covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover" class="w-40 h-56 object-cover rounded mb-4">
            <?php endif; ?>
            <a href="browse.php" class="inline-block mt-4 bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Back to Browse</a>
        </div>
    </main>
</div>
</body>
</html>

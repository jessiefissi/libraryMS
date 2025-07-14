<?php
// user/my-books/current.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
$auth = new Auth($db);
if (!$auth->isLoggedIn() || $_SESSION['user_role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT ib.*, b.title, b.author, b.isbn, b.category_id, c.name as category, ib.return_date FROM issued_books ib JOIN books b ON ib.book_id = b.id LEFT JOIN categories c ON b.category_id = c.id WHERE ib.user_id = ? AND ib.status = 'issued' ORDER BY ib.issue_date DESC");
$stmt->execute([$user_id]);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Current Books - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<?php include '../../includes/header.php'; ?>
<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">My Currently Borrowed Books</h1>
        <div class="bg-white rounded-lg shadow p-6">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left">Title</th>
                        <th class="px-4 py-2 text-left">Author</th>
                        <th class="px-4 py-2 text-left">Category</th>
                        <th class="px-4 py-2 text-left">ISBN</th>
                        <th class="px-4 py-2 text-left">Issued On</th>
                        <th class="px-4 py-2 text-left">Return By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($book['title']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($book['author']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($book['category']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($book['isbn']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($book['issue_date']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($book['return_date']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($books)): ?>
                        <tr><td colspan="6" class="text-center text-gray-500 py-4">No books currently borrowed.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>

<?php
// user/my-books/renewals.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
$auth = new Auth($db);

if (!$auth->isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch currently issued books
$stmt = $db->prepare("SELECT ib.*, b.title, b.author, b.isbn, c.name as category FROM issued_books ib JOIN books b ON ib.book_id = b.id LEFT JOIN categories c ON b.category_id = c.id WHERE ib.user_id = ? AND ib.status = 'issued' ORDER BY ib.issue_date DESC");
$stmt->execute([$user_id]);
$books = $stmt->fetchAll();

// Handle renewal request (mockup, actual logic may vary)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_id'])) {
    // Here you would update the return_date or add a renewal request in a real system
    $success = 'Renewal request submitted for book ID: ' . intval($_POST['issue_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Renewal Requests - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<?php include '../../includes/header.php'; ?>
<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">Book Renewal Requests</h1>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
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
                        <th class="px-4 py-2 text-left">Action</th>
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
                            <td class="px-4 py-2">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="issue_id" value="<?php echo $book['id']; ?>">
                                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700">Request Renewal</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($books)): ?>
                        <tr><td colspan="7" class="text-center text-gray-500 py-4">No books available for renewal.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>

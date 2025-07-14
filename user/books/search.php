<?php
// user/books/search.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php';
// Use $db for $db->prepare(), or $conn for raw queries

$search = $_GET['q'] ?? '';
$results = [];
if ($search) {
    $stmt = $db->prepare("SELECT b.*, c.name as category FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ? ORDER BY b.title ASC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Books - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<?php include '../../includes/header.php'; ?>
<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">Search Books</h1>
        <form method="GET" class="mb-6 flex gap-4">
            <input type="text" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by title, author, or ISBN" class="border rounded px-3 py-2 w-full max-w-md">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Search</button>
        </form>
        <?php if ($search): ?>
            <div class="mb-4 text-gray-700">Showing results for: <span class="font-bold">"<?php echo htmlspecialchars($search); ?>"</span></div>
        <?php endif; ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($results as $book): ?>
                <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                    <div class="mb-2 font-bold text-lg"><?php echo htmlspecialchars($book['title']); ?></div>
                    <div class="text-gray-600 mb-1">Author: <?php echo htmlspecialchars($book['author']); ?></div>
                    <div class="text-gray-500 text-sm mb-2">Category: <?php echo htmlspecialchars($book['category']); ?></div>
                    <div class="text-gray-500 text-sm mb-2">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></div>
                    <div class="text-gray-500 text-sm mb-2">Available: <?php echo $book['quantity']; ?></div>
                    <a href="details.php?id=<?php echo $book['id']; ?>" class="mt-auto bg-blue-600 text-white px-4 py-2 rounded text-center hover:bg-blue-700">View Details</a>
                </div>
            <?php endforeach; ?>
            <?php if ($search && empty($results)): ?>
                <div class="col-span-full text-center text-gray-500">No books found.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>

<?php
// user/books/browse.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/database.php'; // Use $db for $db->prepare(), or $conn for raw queries
$category_id = $_GET['category_id'] ?? '';
$search = $_GET['search'] ?? '';

$query = "SELECT b.*, c.name as category FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1";
$params = [];
if ($category_id) {
    $query .= " AND b.category_id = ?";
    $params[] = $category_id;
}
if ($search) {
    $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY b.title ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Fetch categories for filter
$catStmt = $db->query("SELECT id, name FROM categories ORDER BY name");
$categories = $catStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Books - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<?php include '../../includes/header.php'; ?>
<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">Browse Books</h1>
        <form method="GET" class="mb-6 flex flex-wrap gap-4 items-end">
            <div>
                <label>Category</label>
                <select name="category_id" class="border rounded px-2 py-1">
                    <option value="">All</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if ($category_id == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="border rounded px-2 py-1">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
        </form>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php foreach ($books as $book): ?>
                <div class="bg-white rounded-lg shadow p-4 flex flex-col">
                    <div class="mb-2 font-bold text-lg"><?php echo htmlspecialchars($book['title']); ?></div>
                    <div class="text-gray-600 mb-1">Author: <?php echo htmlspecialchars($book['author']); ?></div>
                    <div class="text-gray-500 text-sm mb-2">Category: <?php echo htmlspecialchars($book['category']); ?></div>
                    <div class="text-gray-500 text-sm mb-2">ISBN: <?php echo htmlspecialchars($book['isbn']); ?></div>
                    <div class="text-gray-500 text-sm mb-2">Available: <?php echo $book['quantity']; ?></div>
                    <a href="details.php?id=<?php echo $book['id']; ?>" class="mt-auto bg-blue-600 text-white px-4 py-2 rounded text-center hover:bg-blue-700">View Details</a>
                </div>
            <?php endforeach; ?>
            <?php if (empty($books)): ?>
                <div class="col-span-full text-center text-gray-500">No books found.</div>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>

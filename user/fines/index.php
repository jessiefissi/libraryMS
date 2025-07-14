<?php
// user/fines/index.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = getDBConnection();
$user_id = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT f.*, b.title, b.author FROM fines f JOIN books b ON f.book_id = b.id WHERE f.user_id = ? ORDER BY f.due_date DESC");
$stmt->execute([$user_id]);
$fines = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>My Fines - Library</title>
    <script src=\"https://cdn.tailwindcss.com\"></script>
</head>
<body class=\"bg-gray-100\">
<?php include '../../includes/header.php'; ?>
<div class=\"flex\">
    <?php include '../../includes/sidebar.php'; ?>
    <main class=\"flex-1 p-6\">
        <h1 class=\"text-3xl font-bold mb-4\">My Fines</h1>
        <div class=\"bg-white rounded-lg shadow p-6\">
            <table class=\"w-full\">
                <thead>
                    <tr class=\"bg-gray-50\">
                        <th class=\"px-4 py-2 text-left\">Book</th>
                        <th class=\"px-4 py-2 text-left\">Author</th>
                        <th class=\"px-4 py-2 text-left\">Amount</th>
                        <th class=\"px-4 py-2 text-left\">Due Date</th>
                        <th class=\"px-4 py-2 text-left\">Status</th>
                        <th class=\"px-4 py-2 text-left\">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fines as $fine): ?>
                        <tr>
                            <td class=\"px-4 py-2\"><?php echo htmlspecialchars($fine['title']); ?></td>
                            <td class=\"px-4 py-2\"><?php echo htmlspecialchars($fine['author']); ?></td>
                            <td class=\"px-4 py-2\">$<?php echo number_format($fine['amount'],2); ?></td>
                            <td class=\"px-4 py-2\"><?php echo htmlspecialchars($fine['due_date']); ?></td>
                            <td class=\"px-4 py-2\"><?php echo (strtotime($fine['due_date']) < time()) ? '<span class=\"text-red-600 font-bold\">Overdue</span>' : 'Pending'; ?></td>
                            <td class=\"px-4 py-2\">
                                <a href=\"payment.php?id=<?php echo $fine['id']; ?>\" class=\"bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700\">Pay</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($fines)): ?>
                        <tr><td colspan=\"6\" class=\"text-center text-gray-500 py-4\">No fines found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>
</body>
</html>

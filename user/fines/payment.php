<?php
// user/fines/payment.php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ../../auth/login.php');
    exit();
}

$db = getDBConnection();
$user_id = $_SESSION['user_id'];
$fine_id = $_GET['id'] ?? null;

if (!$fine_id) {
    header('Location: index.php');
    exit();
}

$stmt = $db->prepare("SELECT f.*, b.title FROM fines f JOIN books b ON f.book_id = b.id WHERE f.id = ? AND f.user_id = ?");
$stmt->execute([$fine_id, $user_id]);
$fine = $stmt->fetch();
if (!$fine) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // In a real app, integrate payment gateway here
    // For now, just delete the fine as 'paid'
    $stmt = $db->prepare("DELETE FROM fines WHERE id = ? AND user_id = ?");
    $stmt->execute([$fine_id, $user_id]);
    $success = 'Fine paid successfully!';
    header('Location: index.php?paid=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Fine - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<?php include '../../includes/header.php'; ?>
<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">Pay Fine</h1>
        <div class="bg-white rounded-lg shadow p-6 max-w-lg">
            <h2 class="text-xl font-bold mb-2">Book: <?php echo htmlspecialchars($fine['title']); ?></h2>
            <div class="mb-2">Amount: <span class="font-bold text-red-600">$<?php echo number_format($fine['amount'],2); ?></span></div>
            <div class="mb-2">Due Date: <?php echo htmlspecialchars($fine['due_date']); ?></div>
            <form method="POST">
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded mt-4">Pay Now</button>
                <a href="index.php" class="ml-4 text-gray-600 hover:underline">Cancel</a>
            </form>
        </div>
    </main>
</div>
</body>
</html>

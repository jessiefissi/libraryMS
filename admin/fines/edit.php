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

$fine_id = $_GET['id'] ?? null;
if (!$fine_id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT f.*, u.name as user_name, b.title as book_title FROM fines f JOIN users u ON f.user_id = u.id JOIN books b ON f.book_id = b.id WHERE f.id = ?");
$stmt->bind_param('i', $fine_id);
$stmt->execute();
$result = $stmt->get_result();
$fine = $result->fetch_assoc();

if (!$fine) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $status = $_POST['status'] ?? 'unpaid';
    $due_date = $_POST['due_date'] ?? date('Y-m-d');
    $update = $db->prepare("UPDATE fines SET amount = ?, status = ?, due_date = ? WHERE id = ?");
    $update->bind_param('dssi', $amount, $status, $due_date, $fine_id);
    if ($update->execute()) {
        $success = 'Fine updated successfully!';
        header('Location: index.php?success=1');
        exit;
    } else {
        $error = 'Failed to update fine.';
    }
}

$pageTitle = 'Edit Fine';
include '../../includes/header.php';
?>
<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    <div class="lg:pl-64">
        <div class="p-6 max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h1 class="text-2xl font-bold mb-4">Edit Fine</h1>
                <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block font-medium mb-1">User</label>
                        <input type="text" value="<?php echo htmlspecialchars($fine['user_name']); ?>" class="w-full px-3 py-2 border rounded" disabled>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Book</label>
                        <input type="text" value="<?php echo htmlspecialchars($fine['book_title']); ?>" class="w-full px-3 py-2 border rounded" disabled>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Amount ($)</label>
                        <input type="number" name="amount" step="0.01" min="0" value="<?php echo htmlspecialchars($fine['amount']); ?>" class="w-full px-3 py-2 border rounded" required>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border rounded">
                            <option value="unpaid" <?php if ($fine['status'] == 'unpaid') echo 'selected'; ?>>Unpaid</option>
                            <option value="paid" <?php if ($fine['status'] == 'paid') echo 'selected'; ?>>Paid</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-medium mb-1">Due Date</label>
                        <input type="date" name="due_date" value="<?php echo htmlspecialchars($fine['due_date']); ?>" class="w-full px-3 py-2 border rounded" required>
                    </div>
                    <div class="flex space-x-4 mt-4">
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update</button>
                        <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>

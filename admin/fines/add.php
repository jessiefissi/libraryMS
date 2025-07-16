<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
// Check if user is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}

$message = '';
$error = '';

// Get users and books for the form
$users_query = "SELECT * FROM users WHERE role = 'user' ORDER BY name";
$books_query = "SELECT * FROM books ORDER BY title";

try {
    $users_stmt = $db->prepare($users_query);
    if (!$users_stmt) {
        throw new Exception("Failed to prepare users query: " . $db->error);
    }
    $users_stmt->execute();
    $users_result = $users_stmt->get_result();
    $users = $users_result->fetch_all(MYSQLI_ASSOC);

    $books_stmt = $db->prepare($books_query);
    if (!$books_stmt) {
        throw new Exception("Failed to prepare books query: " . $db->error);
    }
    $books_stmt->execute();
    $books_result = $books_stmt->get_result();
    $books = $books_result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Error fetching users or books: " . $e->getMessage();
    $users = [];
    $books = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $book_id = $_POST['book_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // Validate input
    if (empty($user_id) || empty($book_id) || empty($amount)) {
        $error = 'All fields are required.';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = 'Amount must be a positive number.';
    } else {
        $insert_query = "INSERT INTO fines (user_id, book_id, amount, reason, due_date) VALUES (?, ?, ?, ?, CURDATE())";
        $stmt = $db->prepare($insert_query);
        if (!$stmt) {
            $error = "Failed to prepare insert query: " . $db->error;
        } else {
            $stmt->bind_param('iids', $user_id, $book_id, $amount, $reason);
            if ($stmt->execute()) {
                $message = 'Fine added successfully!';
                // Optionally redirect to index.php with message
                header('Location: index.php?message=' . urlencode($message));
                exit;
            } else {
                $error = 'Failed to add fine: ' . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Fine</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <div class="container">
        <h1>Add Fine</h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
            <div class="form-group">
                <label for="user_id">User</label>
                <select name="user_id" id="user_id" required>
                    <option value="">Select a user</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="book_id">Book</label>
                <select name="book_id" id="book_id" required>
                    <option value="">Select a book</option>
                    <?php foreach ($books as $book): ?>
                        <option value="<?= $book['id'] ?>"><?= htmlspecialchars($book['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" name="amount" id="amount" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="reason">Reason</label>
                <textarea name="reason" id="reason" rows="3" required></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add Fine</button>
        </form>

        <p>
            <a href="index.php">Back to Fines List</a>
        </p>
    </div>

    <script src="../../js/scripts.js"></script>
</body>
</html>
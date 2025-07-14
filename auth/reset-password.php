<?php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (!$password || !$confirm) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // In a real app, update the password for the user with the token
        $success = 'Your password has been reset. You may now <a href="login.php" class="text-blue-600 underline">login</a>.';
    }
}

$pageTitle = 'Reset Password';
include '../includes/header.php';
?>
<div class="min-h-screen bg-gray-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-md p-8 max-w-md w-full">
        <h1 class="text-2xl font-bold mb-4">Reset Password</h1>
        <?php if ($error): ?><div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo $success; ?></div><?php endif; ?>
        <form method="POST" class="space-y-4">
            <div>
                <label class="block font-medium mb-1">New Password</label>
                <input type="password" name="password" class="w-full px-3 py-2 border rounded" required>
            </div>
            <div>
                <label class="block font-medium mb-1">Confirm Password</label>
                <input type="password" name="confirm_password" class="w-full px-3 py-2 border rounded" required>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 w-full">Reset Password</button>
        </form>
        <div class="mt-4 text-center">
            <a href="login.php" class="text-blue-600 hover:underline">Back to Login</a>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

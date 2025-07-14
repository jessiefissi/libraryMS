<?php
// user/profile.php
session_start();
require_once '../config/database.php';
require_once '../config/auth.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

$db = getDBConnection();
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $db->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $error = '';
    if (!$name || !$email) {
        $error = 'Name and email are required.';
    } else {
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);
        $user['name'] = $name;
        $user['email'] = $email;
        $success = 'Profile updated successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Library</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<?php include '../includes/header.php'; ?>
<div class="flex">
    <?php include '../includes/sidebar.php'; ?>
    <main class="flex-1 p-6">
        <h1 class="text-3xl font-bold mb-4">My Profile</h1>
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form method="POST" class="bg-white rounded-lg shadow p-6 max-w-lg">
            <div class="mb-4">
                <label class="block mb-1 font-medium">Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block mb-1 font-medium">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Update Profile</button>
        </form>
    </main>
</div>
</body>
</html>

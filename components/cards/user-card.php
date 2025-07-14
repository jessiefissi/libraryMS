<?php
// components/cards/user-card.php
// Usage: include this file and set $user (array) before including
?>
<div class="bg-white rounded-lg shadow p-4 flex flex-col">
    <div class="mb-2 font-bold text-lg"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
    <div class="text-gray-600 mb-1">Email: <?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
    <div class="text-gray-500 text-sm mb-2">Role: <?php echo htmlspecialchars($user['role'] ?? ''); ?></div>
    <?php if (!empty($user['created_at'])): ?>
        <div class="text-gray-500 text-sm mb-2">Joined: <?php echo htmlspecialchars($user['created_at']); ?></div>
    <?php endif; ?>
    <a href="../admin/users/view.php?id=<?php echo $user['id'] ?? ''; ?>" class="mt-auto bg-blue-600 text-white px-4 py-2 rounded text-center hover:bg-blue-700">View Profile</a>
</div>

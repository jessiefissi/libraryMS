<?php
// components/modals/user-profile.php
// Usage: include this file and set $user (array) before including
?>
<div id="userProfileModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-2">User Profile</h2>
        <div class="mb-2">Name: <span class="font-semibold"><?php echo htmlspecialchars($user['name'] ?? ''); ?></span></div>
        <div class="mb-2">Email: <span class="font-semibold"><?php echo htmlspecialchars($user['email'] ?? ''); ?></span></div>
        <div class="mb-2">Role: <span class="font-semibold"><?php echo htmlspecialchars($user['role'] ?? ''); ?></span></div>
        <?php if (!empty($user['created_at'])): ?>
            <div class="mb-2">Joined: <span class="font-semibold"><?php echo htmlspecialchars($user['created_at']); ?></span></div>
        <?php endif; ?>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="document.getElementById('userProfileModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
        </div>
    </div>
</div>
<script>
// To show: document.getElementById('userProfileModal').classList.remove('hidden');
// To hide: document.getElementById('userProfileModal').classList.add('hidden');
</script>

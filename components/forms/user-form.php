<?php
// components/forms/user-form.php
// Usage: include this file and set $user (array, optional) before including
?>
<form method="POST" class="bg-white rounded-lg shadow p-6 max-w-lg">
    <div class="mb-4">
        <label class="block mb-1 font-medium">Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">Role</label>
        <select name="role" class="w-full border rounded px-3 py-2" required>
            <option value="user" <?php if (($user['role'] ?? '') == 'user') echo 'selected'; ?>>User</option>
            <option value="admin" <?php if (($user['role'] ?? '') == 'admin') echo 'selected'; ?>>Admin</option>
        </select>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">Password <?php if (!empty($user)) echo '(leave blank to keep current)'; ?></label>
        <input type="password" name="password" class="w-full border rounded px-3 py-2" <?php if (empty($user)) echo 'required'; ?>>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Save User</button>
</form>

<?php
// components/modals/confirm-delete.php
// Usage: include this file and set $deleteUrl and $itemName before including
?>
<div id="confirmDeleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
        <h2 class="text-xl font-bold mb-4 text-red-600">Confirm Delete</h2>
        <p class="mb-6">Are you sure you want to delete <span class="font-bold"><?php echo htmlspecialchars($itemName ?? 'this item'); ?></span>?</p>
        <div class="flex justify-end gap-2">
            <button onclick="document.getElementById('confirmDeleteModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
            <a href="<?php echo htmlspecialchars($deleteUrl ?? '#'); ?>" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</a>
        </div>
    </div>
</div>
<script>
// To show: document.getElementById('confirmDeleteModal').classList.remove('hidden');
// To hide: document.getElementById('confirmDeleteModal').classList.add('hidden');
</script>

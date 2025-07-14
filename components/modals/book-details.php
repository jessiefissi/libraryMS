<?php
// components/modals/book-details.php
// Usage: include this file and set $book (array) before including
?>
<div id="bookDetailsModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg p-6 max-w-md w-full">
        <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($book['title'] ?? 'Book Details'); ?></h2>
        <div class="mb-2">Author: <span class="font-semibold"><?php echo htmlspecialchars($book['author'] ?? ''); ?></span></div>
        <div class="mb-2">Category: <span class="font-semibold"><?php echo htmlspecialchars($book['category'] ?? ''); ?></span></div>
        <div class="mb-2">ISBN: <span class="font-semibold"><?php echo htmlspecialchars($book['isbn'] ?? ''); ?></span></div>
        <div class="mb-2">Available: <span class="font-semibold"><?php echo $book['quantity'] ?? ''; ?></span></div>
        <?php if (!empty($book['cover_image'])): ?>
            <img src="../../uploads/book-covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover" class="w-32 h-44 object-cover rounded mb-4">
        <?php endif; ?>
        <div class="flex justify-end gap-2 mt-4">
            <button onclick="document.getElementById('bookDetailsModal').classList.add('hidden')" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
        </div>
    </div>
</div>
<script>
// To show: document.getElementById('bookDetailsModal').classList.remove('hidden');
// To hide: document.getElementById('bookDetailsModal').classList.add('hidden');
</script>

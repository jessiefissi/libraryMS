<?php
// components/cards/book-card.php
// Usage: include this file and set $book (array) before including
?>
<div class="bg-white rounded-lg shadow p-4 flex flex-col">
    <div class="mb-2 font-bold text-lg"><?php echo htmlspecialchars($book['title'] ?? ''); ?></div>
    <div class="text-gray-600 mb-1">Author: <?php echo htmlspecialchars($book['author'] ?? ''); ?></div>
    <div class="text-gray-500 text-sm mb-2">Category: <?php echo htmlspecialchars($book['category'] ?? ''); ?></div>
    <div class="text-gray-500 text-sm mb-2">ISBN: <?php echo htmlspecialchars($book['isbn'] ?? ''); ?></div>
    <div class="text-gray-500 text-sm mb-2">Available: <?php echo $book['quantity'] ?? ''; ?></div>
    <a href="details.php?id=<?php echo $book['id'] ?? ''; ?>" class="mt-auto bg-blue-600 text-white px-4 py-2 rounded text-center hover:bg-blue-700">View Details</a>
</div>

<?php
// components/forms/book-form.php
// Usage: include this file and set $book (array, optional) and $categories (array) before including
?>
<form method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6 max-w-lg">
    <div class="mb-4">
        <label class="block mb-1 font-medium">Title</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($book['title'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">Author</label>
        <input type="text" name="author" value="<?php echo htmlspecialchars($book['author'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">ISBN</label>
        <input type="text" name="isbn" value="<?php echo htmlspecialchars($book['isbn'] ?? ''); ?>" class="w-full border rounded px-3 py-2" required>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">Category</label>
        <select name="category_id" class="w-full border rounded px-3 py-2" required>
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php if (!empty($book['category_id']) && $book['category_id'] == $cat['id']) echo 'selected'; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">Quantity</label>
        <input type="number" name="quantity" value="<?php echo htmlspecialchars($book['quantity'] ?? 1); ?>" class="w-full border rounded px-3 py-2" min="1" required>
    </div>
    <div class="mb-4">
        <label class="block mb-1 font-medium">Book Cover</label>
        <input type="file" name="cover_image" class="w-full border rounded px-3 py-2">
        <?php if (!empty($book['cover_image'])): ?>
            <img src="../../uploads/book-covers/<?php echo htmlspecialchars($book['cover_image']); ?>" alt="Book Cover" class="w-24 h-32 object-cover mt-2">
        <?php endif; ?>
    </div>
    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded">Save Book</button>
</form>

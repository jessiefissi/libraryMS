<?php
// components/forms/search-form.php
// Usage: include this file and set $action (string) before including
?>
<form method="GET" action="<?php echo htmlspecialchars($action ?? ''); ?>" class="flex gap-2 mb-4">
    <input type="text" name="q" placeholder="Search..." class="border rounded px-3 py-2 w-full max-w-md">
    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Search</button>
</form>

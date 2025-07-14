<?php
// components/cards/stat-card.php
// Usage: include this file and set $stat (array) before including
?>
<div class="bg-white rounded-lg shadow p-4 flex flex-col items-center">
    <div class="text-2xl font-bold mb-2 text-blue-600"><?php echo htmlspecialchars($stat['value'] ?? ''); ?></div>
    <div class="text-gray-700 font-medium"><?php echo htmlspecialchars($stat['label'] ?? ''); ?></div>
    <?php if (!empty($stat['icon'])): ?>
        <div class="mt-2 text-3xl text-gray-400"><?php echo $stat['icon']; ?></div>
    <?php endif; ?>
</div>

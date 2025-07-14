<?php
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

$auth = new Auth($db);

// Check if user is admin
if (!$auth->isAdmin()) {
    header('Location: ../../auth/login.php');
    exit;
}

$error = '';
$success = '';

// Get category ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Check if category exists and get its name
try {
    $stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $error = 'Error fetching category: ' . $e->getMessage();
}

// Handle deletion
if ($_POST && isset($_POST['confirm_delete'])) {
    try {
        // Check if category has books
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $error = 'Cannot delete category. It has ' . $result['count'] . ' books associated with it.';
        } else {
            // Delete category
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            // Redirect with success message
            header('Location: ' . Auth::baseUrl() . '/admin/categories/index.php?deleted=1');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Error deleting category: ' . $e->getMessage();
    }
}

$page_title = 'Delete Category';
include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="ml-64 p-8">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Delete Category</h1>
                    <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm">
                        Back to Categories
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($category): ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6">
                    <h3 class="font-bold">Warning!</h3>
                    <p>Are you sure you want to delete the category "<strong><?php echo htmlspecialchars($category['name']); ?></strong>"?</p>
                    <p class="mt-2">This action cannot be undone.</p>
                </div>

                <form method="POST" class="space-y-4">
                    <div class="flex space-x-4">
                        <button type="submit" 
                                name="confirm_delete" 
                                value="1"
                                class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-md"
                                onclick="return confirm('Are you absolutely sure you want to delete this category?')">
                            Delete Category
                        </button>
                        <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-md">
                            Cancel
                        </a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
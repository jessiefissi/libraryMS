<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
// Check if user is admin
requireAdmin();

// Handle category actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = trim($_POST['name']);
                $description = trim($_POST['description'] ?? '');
                
                if (!empty($name)) {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO categories (name, description, created_at) VALUES (?, ?, NOW())");
                        $stmt->execute([$name, $description]);
                        $success_message = "Category added successfully!";
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Category with this name already exists!";
                        } else {
                            $error_message = "Error adding category: " . $e->getMessage();
                        }
                    }
                } else {
                    $error_message = "Category name is required!";
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description'] ?? '');
                
                if (!empty($name) && $id > 0) {
                    try {
                        $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $description, $id]);
                        $success_message = "Category updated successfully!";
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error_message = "Category with this name already exists!";
                        } else {
                            $error_message = "Error updating category: " . $e->getMessage();
                        }
                    }
                } else {
                    $error_message = "Invalid category data!";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                if ($id > 0) {
                    try {
                        // Check if category has books
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM books WHERE category_id = ?");
                        $stmt->execute([$id]);
                        $book_count = $stmt->fetchColumn();
                        
                        if ($book_count > 0) {
                            $error_message = "Cannot delete category. It contains {$book_count} book(s). Move or delete books first.";
                        } else {
                            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                            $stmt->execute([$id]);
                            $success_message = "Category deleted successfully!";
                        }
                    } catch (PDOException $e) {
                        $error_message = "Error deleting category: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Invalid category ID!";
                }
                break;
        }
    }
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE c.name LIKE ? OR c.description LIKE ?";
    $search_params = ["%{$search}%", "%{$search}%"];
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) FROM categories c {$search_condition}";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($search_params);
$total_categories = $count_stmt->fetchColumn();
$total_pages = ceil($total_categories / $limit);

// Get categories with book count
$sql = "SELECT c.*, 
        COUNT(b.id) as book_count,
        c.created_at,
        c.updated_at
        FROM categories c 
        LEFT JOIN books b ON c.id = b.category_id 
        {$search_condition}
        GROUP BY c.id 
        ORDER BY c.name ASC 
        LIMIT ? OFFSET ?";

$stmt = $pdo->prepare($sql);
$params = array_merge($search_params, [$limit, $offset]);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT c.id) as total_categories,
    COUNT(DISTINCT b.id) as total_books,
    AVG(book_counts.book_count) as avg_books_per_category
    FROM categories c
    LEFT JOIN books b ON c.id = b.category_id
    LEFT JOIN (
        SELECT category_id, COUNT(*) as book_count 
        FROM books 
        GROUP BY category_id
    ) book_counts ON c.id = book_counts.category_id";

$stats_stmt = $pdo->prepare($stats_sql);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management - Library System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <?php include '../../includes/header.php'; ?>
    
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include '../../includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 ml-64 p-8">
            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Categories Management</h1>
                        <p class="text-gray-600 mt-2">Manage book categories and classifications</p>
                    </div>
                    <button onclick="toggleModal('addCategoryModal')" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg flex items-center space-x-2 transition-colors">
                        <i class="fas fa-plus"></i>
                        <span>Add Category</span>
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if (isset($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($success_message); ?></span>
                    <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative" role="alert">
                    <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
                    <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3" onclick="this.parentElement.style.display='none'">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-list text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Categories</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_categories']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-book text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Books</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total_books']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-chart-bar text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Avg Books/Category</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($stats['avg_books_per_category'] ?? 0, 1); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                        <div class="flex-1 max-w-lg">
                            <form method="GET" class="relative">
                                <input type="text" 
                                       name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search categories..." 
                                       class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <button type="submit" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <i class="fas fa-arrow-right text-gray-400 hover:text-gray-600"></i>
                                </button>
                            </form>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-sm text-gray-600">
                                Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $total_categories); ?> of <?php echo $total_categories; ?> categories
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Books</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-list text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-lg font-medium">No categories found</p>
                                            <p class="text-sm">
                                                <?php echo $search ? 'Try adjusting your search terms.' : 'Get started by adding your first category.'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                        <i class="fas fa-folder text-blue-600"></i>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($category['name']); ?></div>
                                                    <div class="text-sm text-gray-500">ID: <?php echo $category['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($category['description']); ?>">
                                                <?php echo htmlspecialchars($category['description'] ?: 'No description'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo $category['book_count']; ?> book<?php echo $category['book_count'] != 1 ? 's' : ''; ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($category['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex items-center space-x-3">
                                                <button onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-900 transition-colors">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo htmlspecialchars($category['name']); ?>', <?php echo $category['book_count']; ?>)" 
                                                        class="text-red-600 hover:text-red-900 transition-colors">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <p class="text-sm text-gray-700">
                                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start = max(1, $page - 2);
                                $end = min($total_pages, $page + 2);
                                
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="px-3 py-2 text-sm font-medium <?php echo $i == $page ? 'text-blue-600 bg-blue-50 border-blue-300' : 'text-gray-700 bg-white border-gray-300 hover:bg-gray-50'; ?> border rounded-md">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="addCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add New Category</h3>
                    <button onclick="toggleModal('addCategoryModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="add">
                    <div>
                        <label for="add_name" class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                        <input type="text" 
                               id="add_name" 
                               name="name" 
                               required 
                               maxlength="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Enter category name">
                    </div>
                    <div>
                        <label for="add_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="add_description" 
                                  name="description" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Enter category description (optional)"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" 
                                onclick="toggleModal('addCategoryModal')" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div id="editCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Category</h3>
                    <button onclick="toggleModal('editCategoryModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div>
                        <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                        <input type="text" 
                               id="edit_name" 
                               name="name" 
                               required 
                               maxlength="50"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="edit_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="edit_description" 
                                  name="description" 
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="button" 
                                onclick="toggleModal('editCategoryModal')" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                            Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Delete Category</h3>
                    <button onclick="toggleModal('deleteCategoryModal')" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-sm text-gray-600">Are you sure you want to delete this category?</p>
                    <p class="text-sm font-medium text-gray-900 mt-2" id="deleteCategoryName"></p>
                    <p class="text-sm text-red-600 mt-2" id="deleteWarning"></p>
                </div>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="toggleModal('deleteCategoryModal')" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                id="deleteButton"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                            Delete Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.classList.toggle('hidden');
        }

        function editCategory(category) {
            document.getElementById('edit_id').value = category.id;
            document.getElementById('edit_name').value = category.name;
            document.getElementById('edit_description').value = category.description || '';
            toggleModal('editCategoryModal');
        }

        function deleteCategory(id, name, bookCount) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteCategoryName').textContent = name;
            
            const warningElement = document.getElementById('deleteWarning');
            const deleteButton = document.getElementById('deleteButton');
            
            if (bookCount > 0) {
                warningElement.textContent = `This category contains ${bookCount} book${bookCount > 1 ? 's' : ''}. You must move or delete these books first.`;
                deleteButton.disabled = true;
                deleteButton.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                warningElement.textContent = 'This action cannot be undone.';
                deleteButton.disabled = false;
                deleteButton.classList.remove('opacity-50', 'cursor-not-allowed');
            }
            
            toggleModal('deleteCategoryModal');
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('bg-gray-600')) {
                const modals = ['addCategoryModal', 'editCategoryModal', 'deleteCategoryModal'];
                modals.forEach(modalId => {
                    const modal = document.getElementById(modalId);
                    if (!modal.classList.contains('hidden')) {
                        modal.classList.add('hidden');
                    }
                });
            }
        }

        // Auto-hide alert messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
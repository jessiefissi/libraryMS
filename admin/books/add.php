<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check if user is admin
requireAdmin();

$errors = [];
$success = '';

// Get categories for dropdown
$categories = [];
$catResult = $db->query("SELECT * FROM categories ORDER BY name ASC");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

if ($_POST) {
    $title = trim($_POST['title']);
    $author = trim($_POST['author']);
    $isbn = trim($_POST['isbn']);
    $category_id = $_POST['category_id'] ?: null;
    $quantity = (int)$_POST['quantity'];
    
    // Validation
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($author)) {
        $errors[] = 'Author is required';
    }
    
    if (empty($isbn)) {
        $errors[] = 'ISBN is required';
    } elseif (!preg_match('/^[\d-]{10,17}$/', $isbn)) {
        $errors[] = 'Invalid ISBN format';
    }
    
    if ($quantity < 1) {
        $errors[] = 'Quantity must be at least 1';
    }
    
    // Check if ISBN already exists
    if (empty($errors)) {
        $stmt = $db->prepare("SELECT id FROM books WHERE isbn = ?");
        $stmt->bind_param('s', $isbn);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $errors[] = 'A book with this ISBN already exists';
        }
    }
    
    // Insert book if no errors
    if (empty($errors)) {
        $stmt = $db->prepare("INSERT INTO books (title, author, isbn, category_id, quantity) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssi', $title, $author, $isbn, $category_id, $quantity);
        
        if ($stmt->execute()) {
            $success = 'Book added successfully!';
            // Clear form data
            $_POST = [];
            
            // Redirect to books index page
            if (!headers_sent()) {
                header('Location: ' . Auth::baseUrl() . '/admin/books/index.php');
                exit();
            }
        } else {
            $errors[] = 'Failed to add book. Please try again.';
        }
    }
}

$page_title = 'Add New Book';
include '../../includes/header.php';
?>

<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-6">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Add New Book</h1>
                    <p class="text-gray-600">Add a new book to your library collection</p>
                </div>
                <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition duration-200">
                    ← Back to Books
                </a>
            </div>
        </div>
        
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <?php if (!empty($errors)): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    Please fix the following errors:
                                </h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">
                                    <?php echo htmlspecialchars($success); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                Book Title *
                            </label>
                            <input type="text" id="title" name="title" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter book title"
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label for="author" class="block text-sm font-medium text-gray-700">
                                Author *
                            </label>
                            <input type="text" id="author" name="author" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter author name"
                                   value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label for="isbn" class="block text-sm font-medium text-gray-700">
                                ISBN *
                            </label>
                            <input type="text" id="isbn" name="isbn" required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter ISBN (10-17 digits)"
                                   value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700">
                                Category
                            </label>
                            <select id="category_id" name="category_id"
                                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (($_POST['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="quantity" class="block text-sm font-medium text-gray-700">
                                Quantity *
                            </label>
                            <input type="number" id="quantity" name="quantity" required min="1"
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Enter quantity"
                                   value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>">
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <a href="index.php" 
                           class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition duration-200">
                            Cancel
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition duration-200">
                            Add Book
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
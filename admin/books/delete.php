<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check if user is admin
requireAdmin();

$book_id = $_GET['id'] ?? 0;

// Get book data
$stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: index.php');
    exit();
}

// Check if book has any issued copies
$stmt = $pdo->prepare("SELECT COUNT(*) as issued FROM issued_books WHERE book_id = ? AND status = 'issued'");
$stmt->execute([$book_id]);
$issued_count = $stmt->fetch()['issued'];

$error = '';
$success = '';

if ($_POST && isset($_POST['confirm_delete'])) {
    if ($issued_count > 0) {
        $error = 'Cannot delete book with issued copies. Please return all copies first.';
    } else {
        // Delete book
        $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
        if ($stmt->execute([$book_id])) {
            $_SESSION['success'] = 'Book deleted successfully!';
            header('Location: index.php');
            exit();
        } else {
            $error = 'Failed to delete book. Please try again.';
        }
    }
}

$page_title = 'Delete Book';
include '../../includes/header.php';
?>

<div class="flex">
    <?php include '../../includes/sidebar.php'; ?>
    
    <main class="flex-1 p-6">
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Delete Book</h1>
                    <p class="text-gray-600">Confirm book deletion</p>
                </div>
                <a href="index.php" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition duration-200">
                    ← Back to Books
                </a>
            </div>
        </div>
        
        <div class="max-w-2xl">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <?php if ($error): ?>
                    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-red-800">
                                    <?php echo htmlspecialchars($error); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        <div class="flex-shrink-0">
                            <svg class="h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">Delete Book</h3>
                            <p class="text-sm text-gray-600">This action cannot be undone.</p>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-2">Book Details:</h4>
                        <dl class="grid grid-cols-1 gap-x-4 gap-y-2 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Title</dt>
                                <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($book['title']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Author</dt>
                                <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($book['author']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">ISBN</dt>
                                <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($book['isbn']); ?></dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Quantity</dt>
                                <dd class="text-sm text-gray-900"><?php echo $book['quantity']; ?></dd>
                            </div>
                        </dl>
                    </div>
                    
                    <?php if ($issued_count > 0): ?>
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">
                                        Warning: Book has issued copies
                                    </h3>
                                    <p class="mt-1 text-sm text-yellow-700">
                                        This book has <?php echo $issued_count; ?> copies currently issued to users. 
                                        You must return all issued copies before deleting this book.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" class="flex justify-end space-x-3">
                    <a href="index.php" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition duration-200">
                        Cancel
                    </a>
                    <button type="submit" name="confirm_delete" value="1"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition duration-200 <?php echo $issued_count > 0 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                            <?php echo $issued_count > 0 ? 'disabled' : ''; ?>
                            onclick="return confirm('Are you sure you want to delete this book? This action cannot be undone.')">
                        Delete Book
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
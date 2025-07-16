<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
// Check if user is admin
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../../auth/login.php');
    exit;
}

$message = '';
$error = '';

// Get available books and users for the form
$books_query = "SELECT b.*, c.name as category_name, 
                (b.quantity - COALESCE(issued_count.count, 0)) as available_quantity
                FROM books b 
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN (
                    SELECT book_id, COUNT(*) as count 
                    FROM issued_books 
                    WHERE status = 'issued' 
                    GROUP BY book_id
                ) issued_count ON b.id = issued_count.book_id
                HAVING available_quantity > 0
                ORDER BY b.title";

$users_query = "SELECT * FROM users WHERE role = 'user' ORDER BY name";

try {
    $books_stmt = $db->prepare($books_query);
    $books_stmt->execute();
    $result = $books_stmt->get_result();
    $available_books = $result->fetch_all(MYSQLI_ASSOC);
    
    $users_stmt = $db->prepare($users_query);
    $users_stmt->execute();
    $result = $users_stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    $return_date = $_POST['return_date'] ?? '';
    
    // Validate inputs
    if (empty($book_id) || empty($user_id) || empty($return_date)) {
        $error = "All fields are required.";
    } else {
        // Check if book is available
        $check_query = "SELECT b.*, 
                        (b.quantity - COALESCE(issued_count.count, 0)) as available_quantity
                        FROM books b 
                        LEFT JOIN (
                            SELECT book_id, COUNT(*) as count 
                            FROM issued_books 
                            WHERE status = 'issued' 
                            GROUP BY book_id
                        ) issued_count ON b.id = issued_count.book_id
                        WHERE b.id = ?";
        
        try {
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bind_param('i', $book_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $book = $result->fetch_assoc();
            
            if (!$book || $book['available_quantity'] <= 0) {
                $error = "Book is not available for issuing.";
            } else {
                // Check if user already has this book
                $user_has_book_query = "SELECT * FROM issued_books WHERE book_id = ? AND user_id = ? AND status = 'issued'";
                $user_has_book_stmt = $db->prepare($user_has_book_query);
                $user_has_book_stmt->bind_param('ii', $book_id, $user_id);
                $user_has_book_stmt->execute();
                $result = $user_has_book_stmt->get_result();
                if ($result->num_rows > 0) {
                    $error = "User already has this book issued.";
                } else {
                    // Issue the book
                    $issue_query = "INSERT INTO issued_books (book_id, user_id, issue_date, return_date, status) 
                                   VALUES (?, ?, CURDATE(), ?, 'issued')";
                    $issue_stmt = $db->prepare($issue_query);
                    $issue_stmt->bind_param('iis', $book_id, $user_id, $return_date);
                    $issue_stmt->execute();
                    
                    $message = "Book issued successfully!";
                    
                    // Redirect to issued books list
                    header('Location: index.php?message=' . urlencode($message));
                    exit;
                }
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

$page_title = "Issue Book";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Library Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <?php include '../../includes/header.php'; ?>
    
    <div class="flex">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div class="flex-1 p-8">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h1 class="text-2xl font-bold text-gray-800">
                            <i class="fas fa-book-medical mr-2"></i>Issue Book
                        </h1>
                        <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 transition">
                            <i class="fas fa-arrow-left mr-2"></i>Back to List
                        </a>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($message): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="book_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Book *
                                </label>
                                <select id="book_id" name="book_id" required 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Choose a book...</option>
                                    <?php foreach ($available_books as $book): ?>
                                        <option value="<?php echo $book['id']; ?>" 
                                                <?php echo (isset($_POST['book_id']) && $_POST['book_id'] == $book['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($book['title']); ?> - 
                                            <?php echo htmlspecialchars($book['author']); ?>
                                            (Available: <?php echo $book['available_quantity']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="user_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Select User *
                                </label>
                                <select id="user_id" name="user_id" required 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Choose a user...</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>" 
                                                <?php echo (isset($_POST['user_id']) && $_POST['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['name']); ?> - 
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label for="return_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Return Date *
                            </label>
                            <input type="date" id="return_date" name="return_date" required
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   value="<?php echo $_POST['return_date'] ?? date('Y-m-d', strtotime('+14 days')); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="flex justify-end space-x-4">
                            <a href="index.php" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition">
                                Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 transition">
                                <i class="fas fa-book-medical mr-2"></i>Issue Book
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>
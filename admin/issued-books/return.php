<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Database connection
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

// Get issued book ID from URL
$issued_book_id = $_GET['id'] ?? '';

if (empty($issued_book_id)) {
    header('Location: index.php');
    exit;
}

// Get issued book details
$query = "SELECT ib.*, b.title, b.author, u.name as user_name, u.email as user_email
          FROM issued_books ib
          JOIN books b ON ib.book_id = b.id
          JOIN users u ON ib.user_id = u.id
          WHERE ib.id = ? AND ib.status = 'issued'";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$issued_book_id]);
    $issued_book = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$issued_book) {
        header('Location: index.php?error=' . urlencode('Invalid issued book ID or book already returned.'));
        exit;
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate fine if book is overdue
$fine_amount = 0;
$is_overdue = false;
if ($issued_book) {
    $return_date = new DateTime($issued_book['return_date']);
    $today = new DateTime();
    
    if ($today > $return_date) {
        $is_overdue = true;
        $days_overdue = $today->diff($return_date)->days;
        $fine_amount = $days_overdue * FINE_PER_DAY; // Assuming constant defined in constants.php
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual_return_date = $_POST['actual_return_date'] ?? date('Y-m-d');
    $fine_amount_input = $_POST['fine_amount'] ?? 0;
    
    try {
        $pdo->beginTransaction();
        
        // Update issued book status
        $update_query = "UPDATE issued_books SET status = 'returned', return_date = ? WHERE id = ?";
        $update_stmt = $pdo->prepare($update_query);
        $update_stmt->execute([$actual_return_date, $issued_book_id]);
        
        // Add fine if applicable
        if ($fine_amount_input > 0) {
            $fine_query = "INSERT INTO fines (user_id, book_id, amount, due_date) VALUES (?, ?, ?, ?)";
            $fine_stmt = $pdo->prepare($fine_query);
            $fine_stmt->execute([
                $issued_book['user_id'], 
                $issued_book['book_id'], 
                $fine_amount_input, 
                $actual_return_date
            ]);
        }
        
        $pdo->commit();
        
        $message = "Book returned successfully!";
        if ($fine_amount_input > 0) {
            $message .= " Fine of $" . number_format($fine_amount_input, 2) . " has been added.";
        }
        
        header('Location: index.php?message=' . urlencode($message));
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollback();
        $error = "Database error: " . $e->getMessage();
    }
}

$page_title = "Return Book";
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
                            <i class="fas fa-undo mr-2"></i>Return Book
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
                    
                    <?php if ($issued_book): ?>
                        <!-- Book Details -->
                        <div class="bg-gray-50 rounded-lg p-4 mb-6">
                            <h2 class="text-lg font-semibold mb-4">Book Details</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p><strong>Title:</strong> <?php echo htmlspecialchars($issued_book['title']); ?></p>
                                    <p><strong>Author:</strong> <?php echo htmlspecialchars($issued_book['author']); ?></p>
                                    <p><strong>User:</strong> <?php echo htmlspecialchars($issued_book['user_name']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($issued_book['user_email']); ?></p>
                                </div>
                                <div>
                                    <p><strong>Issue Date:</strong> <?php echo date('M j, Y', strtotime($issued_book['issue_date'])); ?></p>
                                    <p><strong>Due Date:</strong> <?php echo date('M j, Y', strtotime($issued_book['return_date'])); ?></p>
                                    <p><strong>Status:</strong> 
                                        <?php if ($is_overdue): ?>
                                            <span class="text-red-600 font-semibold">Overdue</span>
                                        <?php else: ?>
                                            <span class="text-green-600 font-semibold">On Time</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($is_overdue): ?>
                            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <strong>Overdue Notice:</strong> This book is overdue. A fine of $<?php echo number_format($fine_amount, 2); ?> will be applied.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Return Form -->
                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="actual_return_date" class="block text-sm font-medium text-gray-700 mb-2">
                                        Actual Return Date *
                                    </label>
                                    <input type="date" id="actual_return_date" name="actual_return_date" required
                                           value="<?php echo date('Y-m-d'); ?>"
                                           max="<?php echo date('Y-m-d'); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                
                                <div>
                                    <label for="fine_amount" class="block text-sm font-medium text-gray-700 mb-2">
                                        Fine Amount ($)
                                    </label>
                                    <input type="number" id="fine_amount" name="fine_amount" step="0.01" min="0"
                                           value="<?php echo $fine_amount; ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <p class="text-sm text-gray-600 mt-1">Leave as 0 if no fine is applicable</p>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-4">
                                <a href="index.php" class="bg-gray-500 text-white px-6 py-2 rounded-md hover:bg-gray-600 transition">
                                    Cancel
                                </a>
                                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded-md hover:bg-green-600 transition">
                                    <i class="fas fa-undo mr-2"></i>Return Book
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>
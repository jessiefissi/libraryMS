<?php
// includes/functions.php

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function calculateFine($due_date, $fine_per_day = 1.00) {
    $today = new DateTime();
    $due = new DateTime($due_date);
    if ($today > $due) {
        $diff = $today->diff($due);
        return $diff->days * $fine_per_day;
    }
    return 0.00;
}

function getBookStatus($book_id, $db) {
    $stmt = $db->prepare("SELECT quantity, (SELECT COUNT(*) FROM issued_books WHERE book_id = ? AND status = 'issued') as issued_count FROM books WHERE id = ?");
    $stmt->bind_param("ii", $book_id, $book_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $available = $result['quantity'] - $result['issued_count'];
    return $available > 0 ? 'available' : 'unavailable';
}

function getUserStats($user_id, $db) {
    $stats = [];
    
    // Current borrowed books
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM issued_books WHERE user_id = ? AND status = 'issued'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['current_books'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Total books borrowed
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM issued_books WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats['total_books'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Total fines
    $stmt = $db->prepare("SELECT SUM(amount) as total FROM fines WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stats['total_fines'] = $result['total'] ? $result['total'] : 0;
    
    return $stats;
}

function getAdminStats($db) {
    $stats = [];
    
    // Total books
    $result = $db->query("SELECT COUNT(*) as count FROM books");
    $stats['total_books'] = $result->fetch_assoc()['count'];
    
    // Total users
    $result = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    // Currently issued books
    $result = $db->query("SELECT COUNT(*) as count FROM issued_books WHERE status = 'issued'");
    $stats['issued_books'] = $result->fetch_assoc()['count'];
    
    // Overdue books
    $result = $db->query("SELECT COUNT(*) as count FROM issued_books WHERE status = 'issued' AND return_date < CURDATE()");
    $stats['overdue_books'] = $result->fetch_assoc()['count'];
    
    // Total fines
    $result = $db->query("SELECT SUM(amount) as total FROM fines");
    $fines = $result->fetch_assoc();
    $stats['total_fines'] = $fines['total'] ? $fines['total'] : 0;
    
    return $stats;
}

function uploadBookCover($file, $book_id) {
    $target_dir = "../uploads/book-covers/";
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $target_file = $target_dir . "book_" . $book_id . "." . $file_extension;
    
    // Check if file is an image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }
    
    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if (!in_array($file_extension, ["jpg", "jpeg", "png", "gif"])) {
        return false;
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return "book_" . $book_id . "." . $file_extension;
    }
    
    return false;
}

function sendNotification($to, $subject, $message) {
    // Simple mail function - in production, use PHPMailer
    $headers = "From: library@example.com\r\n";
    $headers .= "Reply-To: library@example.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function generateAlert($type, $message) {
    $alertClass = '';
    $icon = '';
    
    switch ($type) {
        case 'success':
            $alertClass = 'bg-green-100 border-green-500 text-green-700';
            $icon = '✓';
            break;
        case 'error':
            $alertClass = 'bg-red-100 border-red-500 text-red-700';
            $icon = '✗';
            break;
        case 'warning':
            $alertClass = 'bg-yellow-100 border-yellow-500 text-yellow-700';
            $icon = '⚠';
            break;
        case 'info':
            $alertClass = 'bg-blue-100 border-blue-500 text-blue-700';
            $icon = 'ℹ';
            break;
    }
    
    return "
    <div class='border-l-4 p-4 mb-4 {$alertClass}' role='alert'>
        <div class='flex'>
            <div class='flex-shrink-0'>
                <span class='text-lg font-bold'>{$icon}</span>
            </div>
            <div class='ml-3'>
                <p class='text-sm'>{$message}</p>
            </div>
        </div>
    </div>";
}
?>
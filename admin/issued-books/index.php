<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('/auth/login.php');
}

$database = new Database();
$db = $database->getConnection();

// Handle search and filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$whereClause = "WHERE 1=1";
$params = [];

if (!empty($search)) {
    $whereClause .= " AND (b.title LIKE ? OR b.author LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if (!empty($status_filter)) {
    $whereClause .= " AND ib.status = ?";
    $params[] = $status_filter;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total 
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    JOIN users u ON ib.user_id = u.id
    $whereClause";
$countStmt = $db->prepare($countQuery);
$countStmt->execute($params);
$totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
$totalPages = ceil($totalRecords / $limit);

// Get issued books
$query = "SELECT ib.*, b.title, b.author, b.isbn, u.name as user_name, u.email as user_email
    FROM issued_books ib
    JOIN books b ON ib.book_id = b.id
    JOIN users u ON ib.user_id = u.id
    $whereClause
    ORDER BY ib.issue_date DESC
    LIMIT ? OFFSET ?";
$stmt = $db->prepare($query);
$stmt->execute(array_merge($params, [$limit, $offset]));
$issuedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "SELECT 
    COUNT(CASE WHEN status = 'issued' THEN 1 END) as active_loans,
    COUNT(CASE WHEN status = 'returned' THEN 1 END) as returned_books,
    COUNT(CASE WHEN status = 'issued' AND return_date < CURDATE() THEN 1 END) as overdue_books
    FROM issued_books";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Issued Books Management - Admin';
include '../../includes/header.php';
?>

<div class="min-h-screen bg-gray-50">
    <?php include '../../includes/sidebar.php'; ?>
    
    <div class="lg:pl-64">
        <div class="p-6">
            <div class="max-w-7xl mx-auto">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900">Issued Books Management</h1>
                    <p class="text-gray-600">Manage book loans and returns</p>
                </div>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <p class="ml-3 text-sm text-green-800"><?php echo $_SESSION['success_message']; ?></p>
                        </div>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24
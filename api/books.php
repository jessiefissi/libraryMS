<?php
// api/books.php
require_once '../config/database.php';
header('Content-Type: application/json');
// Use $db for $db->prepare(), or $conn for raw queries
$search = $_GET['q'] ?? '';
$query = "SELECT b.*, c.name as category FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1";
$params = [];
if ($search) {
    $query .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY b.title ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$books = $stmt->fetchAll();
echo json_encode(['books' => $books]);

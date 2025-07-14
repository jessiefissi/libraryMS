<?php
// api/search.php
require_once '../config/database.php';
// Use $db for $db->prepare(), or $conn for raw queries
header('Content-Type: application/json');
$db = getDBConnection();
$q = $_GET['q'] ?? '';
$results = [];
if ($q) {
    // Search books
    $stmt = $db->prepare("SELECT b.id, b.title, b.author, b.isbn, c.name as category FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?");
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);
    $results['books'] = $stmt->fetchAll();
    // Search users
    $stmt = $db->prepare("SELECT id, name, email FROM users WHERE name LIKE ? OR email LIKE ?");
    $stmt->execute(["%$q%", "%$q%"]);
    $results['users'] = $stmt->fetchAll();
}
echo json_encode($results);

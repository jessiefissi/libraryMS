<?php
// api/users.php
require_once '../config/database.php';
header('Content-Type: application/json');
// Use $db for $db->prepare(), or $conn for raw queries
$search = $_GET['q'] ?? '';
$query = "SELECT id, name, email, role FROM users WHERE 1";
$params = [];
if ($search) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params = ["%$search%", "%$search%"];
}
$query .= " ORDER BY name ASC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
echo json_encode(['users' => $users]);

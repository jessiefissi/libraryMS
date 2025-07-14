<?php
// api/dashboard-stats.php
require_once '../config/database.php';
header('Content-Type: application/json');
// Use $db for $db->prepare(), or $conn for raw queries
$stats = [];
$stats['total_books'] = $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$stats['total_users'] = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$stats['total_issued'] = $db->query("SELECT COUNT(*) FROM issued_books WHERE status = 'issued'")->fetchColumn();
$stats['total_fines'] = $db->query("SELECT COALESCE(SUM(amount),0) FROM fines")->fetchColumn();
echo json_encode($stats);

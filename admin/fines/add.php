<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';
require_once '../../includes/functions.php';


// Check if user is admin

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../auth/login.php');
    exit;
}


$message = '';
$error = '';

// Get users and books for the form
$users_query = "SELECT * FROM users WHERE role = 'user' ORDER BY name";
$books_query = "SELECT * FROM books ORDER BY title";

try {
    $users_stmt = $pdo->prepare($users_query);
    $users_stmt->execute();
    $users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $books_stmt = $pdo->prepare($books_query);
    $books_stmt->execute();
    $books = $books_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error
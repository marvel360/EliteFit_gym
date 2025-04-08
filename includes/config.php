<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'nyonyo1!');
define('DB_NAME', 'elitefit_gym1');

// Base URL
define('BASE_URL', 'http://localhost/elitefit_gym');

// Start session
session_start();

// Connect to database
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check user role
function checkRole($allowedRoles) {
    if (!isLoggedIn() || !in_array($_SESSION['role'], $allowedRoles)) {
        redirect(BASE_URL . '/login.php');
    }
}
?>
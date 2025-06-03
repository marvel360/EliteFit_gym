<?php
// Add this temporarily at the top of config.php:
// Run once, then remove this line
/**
 * EliteFit Gym Configuration File (Stable Version)
 * - Fixed session issues
 * - Backward compatible
 * - Enhanced security without breaking changes
 */

// 1. Environment Setup (Safe Defaults)
define('ENVIRONMENT', 'development'); // Change to 'production' when live
error_reporting(E_ALL);
ini_set('display_errors', ENVIRONMENT === 'development' ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/php-errors.log');

// 2. Database Configuration (No Breaking Changes)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'nyonyo1!');
define('DB_NAME', 'elitefit_gym1');
define('DB_CHARSET', 'utf8mb4');

// 3. Session Fix - Unified Session Handler
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400, // 1 day
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => ENVIRONMENT === 'production', // HTTPS only in production
        'httponly' => true,
        'samesite' => 'Lax' // Balances security and functionality
    ]);

    // Custom session name to avoid conflicts
    session_name('ELITEFIT_SESSID');
    session_start();
}

// 4. Base URL Detection (Works in all environments)
$projectRoot = '/elitefit_gym'; // Change this if your folder name is different
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $projectRoot;
define('BASE_URL', rtrim($base_url, '/'));

// 5. Database Connection (Stable Version)
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true, // Disabled for compatibility
        PDO::ATTR_PERSISTENT => false
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    die("System temporarily unavailable. Please try later.");
}

// 6. Email Configuration (Gmail Compatible)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', 'codexcoder082@gmail.com');
define('SMTP_PASS', 'ngxblhhvslnvcflr');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('MAIL_FROM', 'no-reply@elitefit.com');

// 7. Core Functions (Backward Compatible)
function redirect($url, $statusCode = 303)
{
    header("Location: " . $url, true, $statusCode);
    exit();
}

function isLoggedIn()
{
    return !empty($_SESSION['user_id']); // Works with legacy code
}

function checkRole($allowedRoles)
{
    if (!isLoggedIn() || empty($_SESSION['role']) || !in_array($_SESSION['role'], (array) $allowedRoles)) {
        redirect(BASE_URL . '/login.php');
    }
}

// 8. CSRF Protection (Optional - Uncomment if needed)
/*
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
*/

// 9. Timezone (Adjust to your location)
date_default_timezone_set('Africa/Nairobi');
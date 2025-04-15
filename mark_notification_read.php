<?php
// mark_notification_read.php
require_once 'includes/config.php';

// Validate request
if (!isset($_GET['id']) || !isLoggedIn()) {
    header("HTTP/1.1 400 Bad Request");
    exit();
}

// Update database
try {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 
                          WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['error' => 'Database error']);
}
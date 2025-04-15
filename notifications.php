<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

// Handle account creation
if (isset($_POST['create_account'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);

        if ($stmt->rowCount() > 0) {
            // Add notification
            $title = "New Account Created";
            $message = "A new account has been successfully created for $username.";
            $stmt2 = $pdo->prepare("INSERT INTO notifications (title, message, is_read, created_at) VALUES (?, ?, 0, NOW())");
            $stmt2->execute([$title, $message]);

            // Send email
            $emailData = [
                'to_email' => $email,
                'subject' => 'Account Created Successfully',
                'message' => "Hello $username,\n\nYour account has been successfully created.\n\nThank you for joining us!"
            ];
            sendEmailUsingEmailJS($emailData);
        }
    } catch (PDOException $e) {
        echo "Error creating account: " . $e->getMessage();
    }
}

// Mark notification as read
if (isset($_POST['mark_read'])) {
    $id = intval($_POST['notification_id']);
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?")->execute([$id]);
}

// Delete notification
if (isset($_POST['delete'])) {
    $id = intval($_POST['notification_id']);
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        $emailData = [
            'to_email' => 'user@example.com',
            'subject' => 'Notification Deleted',
            'message' => "The following notification was deleted:\n\nTitle: " . $notification['title'] . "\nMessage: " . $notification['message']
        ];
        sendEmailUsingEmailJS($emailData);
    }

    $pdo->prepare("DELETE FROM notifications WHERE id = ?")->execute([$id]);
}

// EmailJS function
function sendEmailUsingEmailJS($data) {
    $emailJSUrl = 'https://api.emailjs.com/api/v1.0/email/send';
    $serviceID = 'your_service_id';
    $templateID = 'your_template_id';
    $userID = 'your_user_id';

    $payload = json_encode([
        'service_id' => $serviceID,
        'template_id' => $templateID,
        'user_id' => $userID,
        'template_params' => $data
    ]);

    $ch = curl_init($emailJSUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        error_log('Failed to send email using EmailJS');
    }
}

// Fetch notifications
$notifications = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .notification { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
        .unread { background-color: #f9f9f9; }
        .actions { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Notifications</h1>
    <?php if (count($notifications) > 0): ?>
        <?php foreach ($notifications as $row): ?>
            <div class="notification <?= $row['is_read'] ? '' : 'unread'; ?>">
                <p><strong><?= htmlspecialchars($row['title']); ?></strong></p>
                <p><?= htmlspecialchars($row['message']); ?></p>
                <p><small><?= $row['created_at']; ?></small></p>
                <div class="actions">
                    <?php if (!$row['is_read']): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="notification_id" value="<?= $row['user_id']; ?>">
                            <button type="submit" name="mark_read">Mark as Read</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="notification_id" value="<?= $row['user_id']; ?>">
                        <button type="submit" name="delete">Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No notifications found.</p>
    <?php endif; ?>
</body>
</html>

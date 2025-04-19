<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['otp_user_id'])) {
    header("Location: register.php");
    exit();
}

$userId = $_SESSION['otp_user_id'];
$errors = [];
$success = false;

// Get user details
$stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: register.php");
    exit();
}

// Generate new OTP
$otp = rand(100000, 999999);
$otpExpiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

try {
    // Update OTP in database
    $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = ? WHERE user_id = ?");
    $stmt->execute([$otp, $otpExpiry, $userId]);
    
    // Send new OTP email
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@example.com';
        $mail->Password = 'your_email_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        
        // Recipients
        $mail->setFrom('no-reply@elitefitgym.com', 'EliteFit Gym');
        $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'New OTP for EliteFit Gym Account Verification';
        $mail->Body = "
            <h2>Your New OTP</h2>
            <p>Your new OTP for account verification is: <strong>$otp</strong></p>
            <p>This OTP will expire in 30 minutes.</p>
            <p>If you didn't request this, please ignore this email.</p>
        ";
        
        $mail->send();
        $success = true;
    } catch (Exception $e) {
        $errors[] = "OTP email could not be sent. Error: {$mail->ErrorInfo}";
    }
} catch (PDOException $e) {
    $errors[] = "Failed to resend OTP: " . $e->getMessage();
}

if ($success) {
    header("Location: verify_otp.php");
    exit();
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Resend OTP</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        A new OTP has been sent to your email address.
                    </div>
                <?php endif; ?>
                
                <p>Click the button below to receive a new OTP code.</p>
                
                <form method="post">
                    <button type="submit" class="btn btn-primary w-100">Resend OTP</button>
                </form>
                
                <div class="mt-3 text-center">
                    <a href="verify_otp.php">Back to Verification</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
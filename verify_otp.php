<?php
require_once 'includes/config.php';
require_once 'includes/header.php';

$errors = [];

if (!isset($_SESSION['otp_user_id'])) {
    header("Location: register.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $userId = $_SESSION['otp_user_id'];
    
    if (empty($otp)) {
        $errors[] = "OTP is required";
    } else {
        try {
            // Check OTP validity
            $stmt = $pdo->prepare("SELECT otp, otp_expiry FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $errors[] = "Invalid verification request";
            } elseif ($user['otp'] != $otp) {
                $errors[] = "Invalid OTP";
            } elseif (strtotime($user['otp_expiry']) < time()) {
                $errors[] = "OTP has expired";
            } else {
                // Activate account
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1, otp = NULL, otp_expiry = NULL WHERE user_id = ?");
                $stmt->execute([$userId]);
                
                unset($_SESSION['otp_user_id']);
                $_SESSION['success_message'] = "Account verified successfully! You can now login.";
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Verification failed: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Verify Your Email</h3>
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
                
                <p>We've sent a 6-digit OTP to your email address. Please enter it below to verify your account.</p>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="otp" class="form-label">OTP Code</label>
                        <input type="text" class="form-control" id="otp" name="otp" required maxlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Verify Account</button>
                </form>
                
                <div class="mt-3 text-center">
                    <a href="resend_otp.php">Resend OTP</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
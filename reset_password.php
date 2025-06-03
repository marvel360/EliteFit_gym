<?php
declare(strict_types=1);
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Load database configuration
require_once 'includes/config.php';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$message = '';
$message_type = '';
$email = $_GET['email'] ?? '';
$show_otp_form = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF protection
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new RuntimeException('Invalid CSRF token');
        }

        $email = $_POST['email'] ?? '';
        $otp = $_POST['otp'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }

        // If OTP is being submitted
        if (isset($_POST['verify_otp'])) {
            // Verify OTP
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email AND otp = :otp AND otp_expiry > NOW() AND is_active = 1 LIMIT 1");
            $stmt->execute([':email' => $email, ':otp' => $otp]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new RuntimeException('Invalid or expired OTP');
            }

            // OTP is valid, show password reset form
            $_SESSION['otp_verified'] = true;
            $_SESSION['reset_email'] = $email;
            $show_otp_form = false;
            $message = 'Please enter your new password';
            $message_type = 'success';

        } elseif (isset($_POST['reset_password'])) {
            // Verify session
            if (empty($_SESSION['otp_verified']) || $_SESSION['reset_email'] !== $email) {
                throw new RuntimeException('OTP verification required');
            }

            // Validate passwords
            if (empty($password) || empty($confirm_password)) {
                throw new InvalidArgumentException('All fields are required');
            }

            if ($password !== $confirm_password) {
                throw new InvalidArgumentException('Passwords do not match');
            }

            if (strlen($password) < 8) {
                throw new InvalidArgumentException('Password must be at least 8 characters');
            }

            // Update password and clear OTP
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = :password, otp = NULL, otp_expiry = NULL WHERE email = :email");
            $updateStmt->execute([':password' => $hashed_password, ':email' => $email]);

            // Clear session
            unset($_SESSION['otp_verified']);
            unset($_SESSION['reset_email']);

            $message = 'Your password has been reset successfully!';
            $message_type = 'success';
            header("Refresh: 3; url=login.php"); // Redirect after 3 seconds
        }

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $message = 'A database error occurred';
        $message_type = 'error';
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $message = $e->getMessage();
        $message_type = 'error';
    }
} elseif (isset($_GET['email'])) {
    $email = $_GET['email'];
    $show_otp_form = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | EliteFit</title>
    <link href="/assets/css/styles.css" rel="stylesheet">
    <style>
        /* Same styles as forgot_password.php */
        .auth-container {
            max-width: 420px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #1e1e1e;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid #4caf50;
            color: #4caf50;
        }
        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid #f44336;
            color: #f44336;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #252525;
            border: 1px solid #333;
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 1rem;
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background-color: #c11325;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
        }
        .auth-links {
            margin-top: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }
        .auth-links a {
            color: #a0a0a0;
            text-decoration: none;
        }
        .otp-input {
            letter-spacing: 2px;
            font-size: 1.2rem;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1><img src="/assets/images/elitefit-logo.png" alt="EliteFit"></h1>
        <h2>Reset Password</h2>

        <!-- <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?> -->

        <?php if ($message_type === 'success' && isset($_POST['reset_password'])): ?>
            <p>You will be redirected to the login page shortly...</p>
        <?php elseif ($show_otp_form || (isset($_POST['verify_otp']) && $message_type === 'error')): ?>
            <!-- OTP Verification Form -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                
                <div class="form-group">
                    <label for="otp">Enter 6-digit OTP</label>
                    <input type="text" id="otp" name="otp" class="form-control otp-input" required 
                           pattern="\d{6}" maxlength="6" placeholder="123456">
                </div>

                <button type="submit" name="verify_otp" class="btn btn-primary">Verify OTP</button>
            </form>

            <div class="auth-links">
                <a href="forgot_password.php">Resend OTP</a>
            </div>
        <?php elseif (!isset($_SESSION['otp_verified'])): ?>
            <div class="alert alert-error">
                OTP verification required. Please start the password reset process from the <a href="forgot_password.php">forgot password page</a>.
            </div>
        <?php else: ?>
            <!-- Password Reset Form -->
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-control" required 
                           minlength="8" placeholder="At least 8 characters">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required 
                           minlength="8" placeholder="Confirm your password">
                </div>

                <button type="submit" name="reset_password" class="btn btn-primary">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="auth-links">
            <a href="login.php">Back to Login</a>
        </div>
    </div>

    <script>
        // Auto-advance OTP input
        document.getElementById('otp')?.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
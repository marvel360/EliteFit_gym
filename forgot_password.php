<?php
declare(strict_types=1);
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Load dependencies
try {
    require_once 'includes/config.php'; // Should contain $pdo initialization
    require_once 'vendor/autoload.php'; // For PHPMailer
} catch (Throwable $e) {
    die("Error loading dependencies: " . $e->getMessage());
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Initialize response
$response = [
    'status' => 'error',
    'message' => 'An error occurred',
    'debug' => ''
];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Process request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new RuntimeException('Invalid CSRF token');
        }

        // Validate email
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Please provide a valid email address');
        }

        // Check user existence
        $stmt = $pdo->prepare("SELECT user_id, first_name FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
        if (!$stmt) {
            throw new RuntimeException('Failed to prepare database query');
        }

        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate 6-digit OTP
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Store OTP in database
            $updateStmt = $pdo->prepare("UPDATE users SET otp = :otp, otp_expiry = :expiry WHERE user_id = :id");
            if (!$updateStmt) {
                throw new RuntimeException('Failed to prepare update statement');
            }

            $updateStmt->execute([
                ':otp' => $otp,
                ':expiry' => $expiry,
                ':id' => $user['user_id']
            ]);

            // Send email using PHPMailer
            $mail = new PHPMailer(true);

            try {
                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'codexcoder082@gmail.com'; // Use environment variable
                $mail->Password = 'ngxblhhvslnvcflr' ; // Use environment variable
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output if needed

                // Recipients
                $mail->setFrom('no-reply@elitefit.com', 'EliteFit');
                $mail->addAddress($email, $user['first_name'] ?? 'User');

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Password Reset OTP';
                $mail->Body = "
                    <h2>Password Reset Request</h2>
                    <p>Hello " . htmlspecialchars($user['first_name'] ?? 'User') . ",</p>
                    <p>Your OTP for password reset is: <strong>$otp</strong></p>
                    <p>This OTP is valid for 15 minutes.</p>
                    <p>If you didn't request this, please ignore this email.</p>
                ";

                $mail->send(); // [FIXED] Removed duplicate send()

            } catch (Exception $e) {
                throw new RuntimeException('Failed to send OTP email: ' . $mail->ErrorInfo);
            }

        } // [FIXED] Added missing closing brace for if ($user)

        // Always return success to prevent email enumeration
        $response = [
            'status' => 'success',
            'message' => 'If an account exists with this email, an OTP has been sent'
        ];

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage(), 3, __DIR__ . '/logs/error.log'); // [FIXED] Move inside catch
        $response['message'] = 'A database error occurred';
        $response['debug'] = $e->getMessage();
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage(), 3, __DIR__ . '/logs/error.log'); // [FIXED] Move inside catch
        $response['message'] = $e->getMessage();
        $response['debug'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | EliteFit</title>
    <link href="/assets/css/styles.css" rel="stylesheet">
    <style>
        /* [UNCHANGED] CSS */
        .auth-container {
            max-width: 420px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #1e1e1e;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            color: white;
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
        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            font-size: 0.8rem;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1>EliteFit</h1>
        <h2>Forgot Password</h2>

        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $response['status'] === 'error'): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($response['message']) ?>
                <?php if (isset($_GET['debug'])): ?>
                    <div class="debug-info">
                        <?= nl2br(htmlspecialchars($response['debug'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $response['status'] === 'success'): ?>
            <div class="alert alert-success"><?= htmlspecialchars($response['message']) ?></div>
            <div class="alert">
                <p>Check your email for the OTP and then 
                    <a href="reset_password.php?email=<?= urlencode($email ?? '') ?>">click here to reset your password</a>.
                </p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required class="form-control" 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>

            <button type="submit" class="btn btn-primary">Send OTP</button>
        </form>

        <div class="auth-links">
            <a href="/login.php">Back to Login</a>
        </div>
    </div>
</body>
</html>

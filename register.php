<?php
require_once 'includes/config.php'; // Still needed for DB constants
require_once 'includes/header.php';

// Load Composer's autoloader
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$errors = [];
$success = false;

// Database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Input filtering
    $username = trim(htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $firstName = trim(htmlspecialchars($_POST['first_name'] ?? '', ENT_QUOTES, 'UTF-8'));
    $lastName = trim(htmlspecialchars($_POST['last_name'] ?? '', ENT_QUOTES, 'UTF-8'));

    // Validate inputs
    $errors = validateRegistration($pdo, $username, $email, $password, $confirmPassword, $firstName, $lastName);

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Generate OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));
            
            // Insert user
            $stmt = $pdo->prepare("INSERT INTO users 
                (username, password, email, role, first_name, last_name, is_active, otp, otp_expiry) 
                VALUES (?, ?, ?, 'member', ?, ?, 0, ?, ?)");
            
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt->execute([
                $username, 
                $hashedPassword, 
                $email, 
                $firstName, 
                $lastName, 
                $otp, 
                $otpExpiry
            ]);
            
            $userId = $pdo->lastInsertId();
            
            // Insert member profile
            $stmt = $pdo->prepare("INSERT INTO member_profiles (user_id, experience_level) VALUES (?, 'beginner')");
            $stmt->execute([$userId]);
            
            // Send verification email
            sendVerificationEmail($email, $firstName, $lastName, $otp);
            
            $pdo->commit();
            
            $_SESSION['otp_user_id'] = $userId;
            header("Location: verify_otp.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}

/**
 * Validate registration inputs
 */
function validateRegistration(PDO $pdo, string $username, string $email, string $password, 
                            string $confirmPassword, string $firstName, string $lastName): array {
    $errors = [];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters (letters, numbers, underscores)";
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/', $password)) {
        $errors[] = "Password must be at least 8 characters with uppercase, lowercase, number, and special character";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // Validate names
    if (empty($firstName)) {
        $errors[] = "First name is required";
    } elseif (!preg_match('/^[\p{L} \'-]{2,50}$/u', $firstName)) {
        $errors[] = "Invalid first name format";
    }
    
    if (empty($lastName)) {
        $errors[] = "Last name is required";
    } elseif (!preg_match('/^[\p{L} \'-]{2,50}$/u', $lastName)) {
        $errors[] = "Invalid last name format";
    }
    
    // Check if username/email exists
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Username or email already exists";
    }
    
    return $errors;
}

/**
 * Send verification email with hardcoded SMTP credentials
 */
function sendVerificationEmail(string $toEmail, string $firstName, string $lastName, string $otp): void {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings - HARDCODED VALUES
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';                  // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'codexcoder082@gmail.com';                  // Your email
        $mail->Password   = 'ngxblhhvslnvcflr';               // Your email password or app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;    // TLS encryption
        $mail->Port       = 587;                               // TLS port
        
        // Enable debugging
        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = function($str, $level) {
            file_put_contents('smtp_debug.log', "$level: $str\n", FILE_APPEND);
        };

        // Recipients
        $mail->setFrom('no-reply@elitefit.com', 'EliteFit Gym');      // From address
        $mail->addAddress($toEmail, "$firstName $lastName");   // Recipient
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your EliteFit Gym Account';
        $mail->Body    = "
            <h2>Welcome to EliteFit Gym, $firstName!</h2>
            <p>Your verification code is: <strong>$otp</strong></p>
            <p>This code will expire in 30 minutes.</p>
        ";
        
        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        throw new Exception("Email could not be sent. Please try again later.");
    }
}
?>

<!-- Rest of your HTML form remains exactly the same -->

<?php require_once 'includes/footer.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-center">Register for EliteFit Gym</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="post" id="registrationForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="form-text text-muted">
                            Must be at least 8 characters with uppercase, lowercase, number and special character
                        </small>
                        <div class="password-strength mt-2">
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small class="text-muted" id="password-strength-text">Password strength</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                <div class="mt-3 text-center">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Client-side password strength meter
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthMeter = document.querySelector('.progress-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    // Reset
    strengthMeter.style.width = '0%';
    strengthMeter.classList.remove('bg-danger', 'bg-warning', 'bg-info', 'bg-success');
    
    if (password.length === 0) {
        strengthText.textContent = 'Password strength';
        return;
    }
    
    // Calculate strength
    let strength = 0;
    
    // Length
    if (password.length >= 8) strength += 1;
    if (password.length >= 12) strength += 1;
    
    // Complexity
    if (/[A-Z]/.test(password)) strength += 1;
    if (/[a-z]/.test(password)) strength += 1;
    if (/\d/.test(password)) strength += 1;
    if (/[\W_]/.test(password)) strength += 1;
    
    // Update UI
    let width = 0;
    let color = '';
    let text = '';
    
    if (strength <= 2) {
        width = 25;
        color = 'bg-danger';
        text = 'Weak';
    } else if (strength <= 4) {
        width = 50;
        color = 'bg-warning';
        text = 'Moderate';
    } else if (strength <= 6) {
        width = 75;
        color = 'bg-info';
        text = 'Strong';
    } else {
        width = 100;
        color = 'bg-success';
        text = 'Very Strong';
    }
    
    strengthMeter.style.width = `${width}%`;
    strengthMeter.classList.add(color);
    strengthText.textContent = `Password strength: ${text}`;
});
</script>
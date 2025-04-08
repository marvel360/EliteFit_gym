<?php
require_once '../includes/config.php';
checkRole(['admin']);
$pageTitle = "Manage Users";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_user'])) {
        $userId = intval($_POST['user_id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $role = trim($_POST['role']);
        
        // Validate inputs
        $errors = [];
        if (empty($username)) $errors[] = "Username is required";
        if (empty($email)) $errors[] = "Email is required";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
        if (empty($firstName)) $errors[] = "First name is required";
        if (empty($lastName)) $errors[] = "Last name is required";
        if (empty($role)) $errors[] = "Role is required";
        
        if (empty($errors)) {
            // Check if username or email already exists for another user
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
            $stmt->execute([$username, $email, $userId]);
            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = "Username or email already exists for another user";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, role = ? WHERE user_id = ?");
                if ($stmt->execute([$username, $email, $firstName, $lastName, $role, $userId])) {
                    $_SESSION['success'] = "User updated successfully";
                    redirect("manage_users.php?view=$userId");
                } else {
                    $_SESSION['error'] = "Failed to update user";
                }
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    } elseif (isset($_POST['update_member_profile'])) {
        $userId = intval($_POST['user_id']);
        $fitnessGoals = trim($_POST['fitness_goals']);
        $experienceLevel = trim($_POST['experience_level']);
        $preferredWorkouts = isset($_POST['preferred_workouts']) ? implode(',', $_POST['preferred_workouts']) : '';
        $medicalNotes = trim($_POST['medical_notes']);
        
        $stmt = $pdo->prepare("UPDATE member_profiles SET fitness_goals = ?, experience_level = ?, preferred_workouts = ?, medical_notes = ? WHERE user_id = ?");
        if ($stmt->execute([$fitnessGoals, $experienceLevel, $preferredWorkouts, $medicalNotes, $userId])) {
            $_SESSION['success'] = "Member profile updated successfully";
            redirect("manage_users.php?view=$userId");
        } else {
            $_SESSION['error'] = "Failed to update member profile";
        }
    } elseif (isset($_POST['reset_password'])) {
        $userId = intval($_POST['user_id']);
        $newPassword = password_hash('elitefit123', PASSWORD_DEFAULT); // Default password
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        if ($stmt->execute([$newPassword, $userId])) {
            $_SESSION['success'] = "Password reset successfully to 'elitefit123'";
            redirect("manage_users.php?view=$userId");
        } else {
            $_SESSION['error'] = "Failed to reset password";
        }
    }
}

// View specific user
$viewUser = null;
$memberProfile = null;
if (isset($_GET['view'])) {
    $userId = intval($_GET['view']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $viewUser = $stmt->fetch();
    
    if ($viewUser && $viewUser['role'] === 'member') {
        $stmt = $pdo->prepare("SELECT * FROM member_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $memberProfile = $stmt->fetch();
    }
}

// Get all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY role, first_name, last_name");
$stmt->execute();
$users = $stmt->fetchAll();

require_once '../includes/header.php';

// Display messages
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
    echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
    unset($_SESSION['success']);
}
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>All Users</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php foreach ($users as $user): ?>
                        <a href="manage_users.php?view=<?php echo $user['user_id']; ?>" 
                           class="list-group-item list-group-item-action d-flex justify-content-between align-items-center 
                                  <?php echo $viewUser && $viewUser['user_id'] == $user['user_id'] ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            <span class="badge 
                                <?php echo $user['role'] === 'admin' ? 'bg-danger' : 
                                      ($user['role'] === 'trainer' ? 'bg-success' : 
                                      ($user['role'] === 'equipment' ? 'bg-warning text-dark' : 'bg-info')); ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <?php if ($viewUser): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
                    <h5>User Details</h5>
                    <span class="badge 
                        <?php echo $viewUser['role'] === 'admin' ? 'bg-danger' : 
                              ($viewUser['role'] === 'trainer' ? 'bg-success' : 
                              ($viewUser['role'] === 'equipment' ? 'bg-warning text-dark' : 'bg-primary')); ?>">
                        <?php echo ucfirst($viewUser['role']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="user_id" value="<?php echo $viewUser['user_id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($viewUser['username']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($viewUser['email']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($viewUser['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($viewUser['last_name']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="admin" <?php echo $viewUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="trainer" <?php echo $viewUser['role'] === 'trainer' ? 'selected' : ''; ?>>Trainer</option>
                                <option value="equipment" <?php echo $viewUser['role'] === 'equipment' ? 'selected' : ''; ?>>Equipment Manager</option>
                                <option value="member" <?php echo $viewUser['role'] === 'member' ? 'selected' : ''; ?>>Member</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                            <button type="submit" name="reset_password" class="btn btn-outline-danger" 
                                    onclick="return confirm('Are you sure you want to reset this user\\'s password?')">
                                Reset Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if ($viewUser['role'] === 'member' && $memberProfile): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5>Member Profile</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="user_id" value="<?php echo $viewUser['user_id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Fitness Goals</label>
                                <textarea class="form-control" name="fitness_goals" rows="3"><?php echo htmlspecialchars($memberProfile['fitness_goals'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Experience Level</label>
                                <select class="form-select" name="experience_level">
                                    <option value="beginner" <?php echo ($memberProfile['experience_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                                    <option value="intermediate" <?php echo ($memberProfile['experience_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                    <option value="advanced" <?php echo ($memberProfile['experience_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preferred Workouts</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="strength" 
                                        <?php echo strpos($memberProfile['preferred_workouts'] ?? '', 'strength') !== false ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Strength Training</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="cardio" 
                                        <?php echo strpos($memberProfile['preferred_workouts'] ?? '', 'cardio') !== false ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Cardio</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="flexibility" 
                                        <?php echo strpos($memberProfile['preferred_workouts'] ?? '', 'flexibility') !== false ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Flexibility</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="balance" 
                                        <?php echo strpos($memberProfile['preferred_workouts'] ?? '', 'balance') !== false ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Balance</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="endurance" 
                                        <?php echo strpos($memberProfile['preferred_workouts'] ?? '', 'endurance') !== false ? 'checked' : ''; ?>>
                                    <label class="form-check-label">Endurance</label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Medical Notes</label>
                                <textarea class="form-control" name="medical_notes" rows="2"><?php echo htmlspecialchars($memberProfile['medical_notes'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_member_profile" class="btn btn-success">Update Profile</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Manage Users</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Select a user from the list to view or edit their details.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
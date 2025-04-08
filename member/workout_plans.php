<?php
require_once '../includes/config.php';
checkRole(['member']);
$pageTitle = "Workout Plans";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_plan'])) {
        // Create new plan request
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $trainerId = intval($_POST['trainer_id']);
        
        if (empty($title)) {
            $_SESSION['error'] = "Plan title is required";
        } else {
            $stmt = $pdo->prepare("INSERT INTO workout_plans (member_id, trainer_id, title, description, status) VALUES (?, ?, ?, ?, 'pending')");
            if ($stmt->execute([$_SESSION['user_id'], $trainerId, $title, $description])) {
                $_SESSION['success'] = "Workout plan request submitted successfully";
                
                // Create notification for trainer
                $planId = $pdo->lastInsertId();
                $message = "New workout plan request from " . $_SESSION['first_name'] . " " . $_SESSION['last_name'];
                $link = "../trainer/manage_plans.php?view=$planId";
                
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'New Plan Request', ?, ?)");
                $stmt->execute([$trainerId, $message, $link]);
                
                redirect("workout_plans.php");
            } else {
                $_SESSION['error'] = "Failed to create workout plan";
            }
        }
    } elseif (isset($_POST['update_profile'])) {
        // Update member profile
        $fitnessGoals = trim($_POST['fitness_goals']);
        $experienceLevel = trim($_POST['experience_level']);
        $preferredWorkouts = isset($_POST['preferred_workouts']) ? implode(',', $_POST['preferred_workouts']) : '';
        $medicalNotes = trim($_POST['medical_notes']);
        
        $stmt = $pdo->prepare("UPDATE member_profiles SET fitness_goals = ?, experience_level = ?, preferred_workouts = ?, medical_notes = ? WHERE user_id = ?");
        if ($stmt->execute([$fitnessGoals, $experienceLevel, $preferredWorkouts, $medicalNotes, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Profile updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update profile";
        }
        redirect("workout_plans.php");
    }
}

// View specific plan
$viewPlan = null;
if (isset($_GET['view'])) {
    $planId = intval($_GET['view']);
    $stmt = $pdo->prepare("SELECT wp.*, u.first_name, u.last_name 
                           FROM workout_plans wp 
                           JOIN users u ON wp.trainer_id = u.user_id 
                           WHERE wp.plan_id = ? AND wp.member_id = ?");
    $stmt->execute([$planId, $_SESSION['user_id']]);
    $viewPlan = $stmt->fetch();
    
    if ($viewPlan) {
        // Get exercises for this plan
        $stmt = $pdo->prepare("SELECT pe.*, e.name as equipment_name 
                               FROM plan_exercises pe 
                               LEFT JOIN equipment e ON pe.equipment_id = e.equipment_id 
                               WHERE pe.plan_id = ? 
                               ORDER BY pe.day_of_week, pe.exercise_id");
        $stmt->execute([$planId]);
        $exercises = $stmt->fetchAll();
    }
}

// Get member profile
$stmt = $pdo->prepare("SELECT * FROM member_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

// Get all trainers
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name FROM users WHERE role = 'trainer'");
$stmt->execute();
$trainers = $stmt->fetchAll();

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
                <h5>My Fitness Profile</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Fitness Goals</label>
                        <textarea class="form-control" name="fitness_goals" rows="3"><?php echo htmlspecialchars($profile['fitness_goals'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Experience Level</label>
                        <select class="form-select" name="experience_level">
                            <option value="beginner" <?php echo ($profile['experience_level'] ?? '') === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
                            <option value="intermediate" <?php echo ($profile['experience_level'] ?? '') === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                            <option value="advanced" <?php echo ($profile['experience_level'] ?? '') === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preferred Workouts</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="strength" 
                                <?php echo strpos($profile['preferred_workouts'] ?? '', 'strength') !== false ? 'checked' : ''; ?>>
                            <label class="form-check-label">Strength Training</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="cardio" 
                                <?php echo strpos($profile['preferred_workouts'] ?? '', 'cardio') !== false ? 'checked' : ''; ?>>
                            <label class="form-check-label">Cardio</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="flexibility" 
                                <?php echo strpos($profile['preferred_workouts'] ?? '', 'flexibility') !== false ? 'checked' : ''; ?>>
                            <label class="form-check-label">Flexibility</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="balance" 
                                <?php echo strpos($profile['preferred_workouts'] ?? '', 'balance') !== false ? 'checked' : ''; ?>>
                            <label class="form-check-label">Balance</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="preferred_workouts[]" value="endurance" 
                                <?php echo strpos($profile['preferred_workouts'] ?? '', 'endurance') !== false ? 'checked' : ''; ?>>
                            <label class="form-check-label">Endurance</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medical Notes</label>
                        <textarea class="form-control" name="medical_notes" rows="2"><?php echo htmlspecialchars($profile['medical_notes'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary w-100">Update Profile</button>
                </form>
            </div>
        </div>
        
        <?php if (!$viewPlan): ?>
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>Request New Workout Plan</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Plan Title</label>
                            <input type="text" class="form-control" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trainer</label>
                            <select class="form-select" name="trainer_id" required>
                                <option value="">Select Trainer</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <option value="<?php echo $trainer['user_id']; ?>">
                                        <?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="create_plan" class="btn btn-success w-100">Request Plan</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-md-8">
        <?php if ($viewPlan): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-info text-white">
                    <h5><?php echo htmlspecialchars($viewPlan['title']); ?></h5>
                    <div>
                        <span class="badge 
                            <?php echo $viewPlan['status'] === 'approved' ? 'bg-success' : 
                                  ($viewPlan['status'] === 'pending' ? 'bg-warning text-dark' : 
                                  ($viewPlan['status'] === 'rejected' ? 'bg-danger' : 'bg-secondary')); ?>">
                            <?php echo ucfirst($viewPlan['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6>Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($viewPlan['description'])); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Created By</h6>
                        <p><?php echo htmlspecialchars($viewPlan['first_name'] . ' ' . $viewPlan['last_name']); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Workout Schedule</h6>
                            <?php if ($viewPlan['status'] === 'approved'): ?>
                                <a href="schedule.php?plan_id=<?php echo $viewPlan['plan_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-calendar-plus"></i> Schedule Session
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (empty($exercises)): ?>
                            <div class="alert alert-info">
                                No exercises have been added to this plan yet.
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="workoutAccordion">
                                <?php 
                                $days = [
                                    'monday' => 'Monday',
                                    'tuesday' => 'Tuesday',
                                    'wednesday' => 'Wednesday',
                                    'thursday' => 'Thursday',
                                    'friday' => 'Friday',
                                    'saturday' => 'Saturday',
                                    'sunday' => 'Sunday'
                                ];
                                
                                foreach ($days as $dayKey => $dayName): 
                                    $dayExercises = array_filter($exercises, function($ex) use ($dayKey) {
                                        return $ex['day_of_week'] === $dayKey;
                                    });
                                    
                                    if (!empty($dayExercises)):
                                ?>
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="heading<?php echo ucfirst($dayKey); ?>">
                                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo ucfirst($dayKey); ?>">
                                                <?php echo $dayName; ?>
                                            </button>
                                        </h2>
                                        <div id="collapse<?php echo ucfirst($dayKey); ?>" class="accordion-collapse collapse show" aria-labelledby="heading<?php echo ucfirst($dayKey); ?>">
                                            <div class="accordion-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Exercise</th>
                                                                <th>Sets</th>
                                                                <th>Reps</th>
                                                                <th>Duration</th>
                                                                <th>Intensity</th>
                                                                <th>Equipment</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($dayExercises as $ex): ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($ex['name']); ?></td>
                                                                    <td><?php echo $ex['sets'] ? htmlspecialchars($ex['sets']) : '-'; ?></td>
                                                                    <td><?php echo $ex['reps'] ? htmlspecialchars($ex['reps']) : '-'; ?></td>
                                                                    <td><?php echo $ex['duration_minutes'] ? htmlspecialchars($ex['duration_minutes']) . ' min' : '-'; ?></td>
                                                                    <td>
                                                                        <?php if ($ex['intensity']): ?>
                                                                            <span class="badge 
                                                                                <?php echo $ex['intensity'] === 'low' ? 'bg-success' : 
                                                                                      ($ex['intensity'] === 'medium' ? 'bg-warning text-dark' : 'bg-danger'); ?>">
                                                                                <?php echo ucfirst($ex['intensity']); ?>
                                                                            </span>
                                                                        <?php else: ?>
                                                                            -
                                                                        <?php endif; ?>
                                                                    </td>
                                                                    <td><?php echo $ex['equipment_name'] ? htmlspecialchars($ex['equipment_name']) : 'None'; ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="workout_plans.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Plans
                        </a>
                        
                        <?php if ($viewPlan['status'] === 'pending'): ?>
                            <div>
                                <button class="btn btn-success me-2">Accept Plan</button>
                                <button class="btn btn-outline-danger">Request Changes</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>My Workout Plans</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT wp.*, u.first_name, u.last_name 
                                           FROM workout_plans wp 
                                           JOIN users u ON wp.trainer_id = u.user_id 
                                           WHERE wp.member_id = ? 
                                           ORDER BY wp.created_at DESC");
                    $stmt->execute([$_SESSION['user_id']]);
                    $plans = $stmt->fetchAll();
                    
                    if (empty($plans)): ?>
                        <div class="alert alert-info">
                            You don't have any workout plans yet. Create your first one using the form on the left.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Trainer</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($plans as $plan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($plan['title']); ?></td>
                                            <td><?php echo htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php echo $plan['status'] === 'approved' ? 'bg-success' : 
                                                          ($plan['status'] === 'pending' ? 'bg-warning text-dark' : 
                                                          ($plan['status'] === 'rejected' ? 'bg-danger' : 'bg-secondary')); ?>">
                                                    <?php echo ucfirst($plan['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($plan['created_at'])); ?></td>
                                            <td>
                                                <a href="workout_plans.php?view=<?php echo $plan['plan_id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
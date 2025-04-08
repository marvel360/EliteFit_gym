<?php
require_once '../includes/config.php';
checkRole(['trainer']);
$pageTitle = "Manage Workout Plans";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_plan'])) {
        $planId = intval($_POST['plan_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $status = trim($_POST['status']);
        
        $stmt = $pdo->prepare("UPDATE workout_plans SET title = ?, description = ?, status = ? WHERE plan_id = ? AND trainer_id = ?");
        if ($stmt->execute([$title, $description, $status, $planId, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Workout plan updated successfully";
            
            // Create notification for member
            $stmt = $pdo->prepare("SELECT member_id FROM workout_plans WHERE plan_id = ?");
            $stmt->execute([$planId]);
            $memberId = $stmt->fetchColumn();
            
            $message = "Your workout plan has been " . $status;
            $link = "../member/workout_plans.php?view=$planId";
            
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'Plan Updated', ?, ?)");
            $stmt->execute([$memberId, $message, $link]);
            
            redirect("manage_plans.php?view=$planId");
        } else {
            $_SESSION['error'] = "Failed to update workout plan";
        }
    } elseif (isset($_POST['add_exercise'])) {
        $planId = intval($_POST['plan_id']);
        $name = trim($_POST['name']);
        $equipmentId = !empty($_POST['equipment_id']) ? intval($_POST['equipment_id']) : null;
        $sets = !empty($_POST['sets']) ? intval($_POST['sets']) : null;
        $reps = !empty($_POST['reps']) ? intval($_POST['reps']) : null;
        $duration = !empty($_POST['duration_minutes']) ? intval($_POST['duration_minutes']) : null;
        $intensity = !empty($_POST['intensity']) ? trim($_POST['intensity']) : null;
        $dayOfWeek = trim($_POST['day_of_week']);
        $notes = trim($_POST['notes']);
        
        $stmt = $pdo->prepare("INSERT INTO plan_exercises 
                              (plan_id, equipment_id, name, sets, reps, duration_minutes, intensity, day_of_week, notes) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$planId, $equipmentId, $name, $sets, $reps, $duration, $intensity, $dayOfWeek, $notes])) {
            $_SESSION['success'] = "Exercise added successfully";
            redirect("manage_plans.php?view=$planId");
        } else {
            $_SESSION['error'] = "Failed to add exercise";
        }
    } elseif (isset($_POST['delete_exercise'])) {
        $exerciseId = intval($_POST['exercise_id']);
        $planId = intval($_POST['plan_id']);
        
        $stmt = $pdo->prepare("DELETE FROM plan_exercises WHERE exercise_id = ? AND plan_id = ?");
        if ($stmt->execute([$exerciseId, $planId])) {
            $_SESSION['success'] = "Exercise deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete exercise";
        }
        redirect("manage_plans.php?view=$planId");
    }
}

// View specific plan
$viewPlan = null;
if (isset($_GET['view'])) {
    $planId = intval($_GET['view']);
    $stmt = $pdo->prepare("SELECT wp.*, u.first_name, u.last_name 
                           FROM workout_plans wp 
                           JOIN users u ON wp.member_id = u.user_id 
                           WHERE wp.plan_id = ? AND wp.trainer_id = ?");
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

// Get all equipment
$stmt = $pdo->prepare("SELECT * FROM equipment WHERE status = 'available' ORDER BY name");
$stmt->execute();
$equipment = $stmt->fetchAll();

// Get all plans
$stmt = $pdo->prepare("SELECT wp.*, u.first_name, u.last_name 
                       FROM workout_plans wp 
                       JOIN users u ON wp.member_id = u.user_id 
                       WHERE wp.trainer_id = ? 
                       ORDER BY wp.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$plans = $stmt->fetchAll();

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
                <h5>My Workout Plans</h5>
            </div>
            <div class="card-body">
                <?php if (empty($plans)): ?>
                    <div class="alert alert-info">
                        You haven't created any workout plans yet.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($plans as $plan): ?>
                            <a href="manage_plans.php?view=<?php echo $plan['plan_id']; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center 
                                      <?php echo $viewPlan && $viewPlan['plan_id'] == $plan['plan_id'] ? 'active' : ''; ?>">
                                <?php echo htmlspecialchars($plan['title']); ?>
                                <span class="badge 
                                    <?php echo $plan['status'] === 'approved' ? 'bg-success' : 
                                          ($plan['status'] === 'pending' ? 'bg-warning text-dark' : 
                                          ($plan['status'] === 'rejected' ? 'bg-danger' : 'bg-secondary')); ?>">
                                    <?php echo ucfirst($plan['status']); ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
                    <form method="post">
                        <input type="hidden" name="plan_id" value="<?php echo $viewPlan['plan_id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Plan Title</label>
                            <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($viewPlan['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($viewPlan['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending" <?php echo $viewPlan['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $viewPlan['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $viewPlan['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($viewPlan['first_name'] . ' ' . $viewPlan['last_name']); ?>" readonly>
                        </div>
                        
                        <button type="submit" name="update_plan" class="btn btn-primary">Update Plan</button>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5>Add New Exercise</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="plan_id" value="<?php echo $viewPlan['plan_id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Exercise Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Equipment</label>
                                <select class="form-select" name="equipment_id">
                                    <option value="">None</option>
                                    <?php foreach ($equipment as $eq): ?>
                                        <option value="<?php echo $eq['equipment_id']; ?>">
                                            <?php echo htmlspecialchars($eq['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Sets</label>
                                <input type="number" class="form-control" name="sets" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Reps</label>
                                <input type="number" class="form-control" name="reps" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Duration (min)</label>
                                <input type="number" class="form-control" name="duration_minutes" min="1">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Intensity</label>
                                <select class="form-select" name="intensity">
                                    <option value="">Select</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Day of Week</label>
                                <select class="form-select" name="day_of_week" required>
                                    <option value="monday">Monday</option>
                                    <option value="tuesday">Tuesday</option>
                                    <option value="wednesday">Wednesday</option>
                                    <option value="thursday">Thursday</option>
                                    <option value="friday">Friday</option>
                                    <option value="saturday">Saturday</option>
                                    <option value="sunday">Sunday</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes</label>
                                <input type="text" class="form-control" name="notes">
                            </div>
                        </div>
                        
                        <button type="submit" name="add_exercise" class="btn btn-success">Add Exercise</button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Workout Exercises</h5>
                </div>
                <div class="card-body">
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
                                                            <th>Actions</th>
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
                                                                <td>
                                                                    <form method="post" style="display:inline;">
                                                                        <input type="hidden" name="plan_id" value="<?php echo $viewPlan['plan_id']; ?>">
                                                                        <input type="hidden" name="exercise_id" value="<?php echo $ex['exercise_id']; ?>">
                                                                        <button type="submit" name="delete_exercise" class="btn btn-sm btn-outline-danger" 
                                                                                onclick="return confirm('Are you sure you want to delete this exercise?')">
                                                                            Delete
                                                                        </button>
                                                                    </form>
                                                                </td>
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
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Manage Workout Plans</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        Select a workout plan from the list to view or edit it.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
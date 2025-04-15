<?php
require_once '../includes/config.php';
checkRole(['trainer','admin']);
$pageTitle = "Schedule Sessions";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_session'])) {
        $planId = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : null;
        $trainerId = intval($_POST['trainer_id']);
        $sessionDate = trim($_POST['session_date']);
        $startTime = trim($_POST['start_time']);
        $endTime = trim($_POST['end_time']);
        $notes = trim($_POST['notes']);
        
        // Validate inputs
        $errors = [];
        if (empty($sessionDate)) $errors[] = "Session date is required";
        if (empty($startTime)) $errors[] = "Start time is required";
        if (empty($endTime)) $errors[] = "End time is required";
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Insert session
                $stmt = $pdo->prepare("INSERT INTO scheduled_sessions 
                                       (member_id, trainer_id, plan_id, session_date, start_time, end_time, notes) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $trainerId,
                    $planId,
                    $sessionDate,
                    $startTime,
                    $endTime,
                    $notes
                ]);
                
                // Create notification for trainer
                $sessionId = $pdo->lastInsertId();
                $message = "New session scheduled by " . $_SESSION['first_name'] . " " . $_SESSION['last_name'];
                $link = "../trainer/dashboard.php";
                
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, link) VALUES (?, 'New Session Scheduled', ?, ?)");
                $stmt->execute([$trainerId, $message, $link]);
                
                $pdo->commit();
                $_SESSION['success'] = "Session scheduled successfully";
                redirect("../member/schedule.php");
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "Failed to schedule session: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    } elseif (isset($_POST['cancel_session'])) {
        $sessionId = intval($_POST['session_id']);
        
        $stmt = $pdo->prepare("UPDATE scheduled_sessions SET status = 'cancelled' WHERE session_id = ? AND member_id = ?");
        if ($stmt->execute([$sessionId, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Session cancelled successfully";
        } else {
            $_SESSION['error'] = "Failed to cancel session";
        }
        redirect("../member/schedule.php");
    }
}

// Get specific plan if provided
$planId = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : null;
$plan = null;
if ($planId) {
    $stmt = $pdo->prepare("SELECT wp.*, u.first_name, u.last_name 
                           FROM workout_plans wp 
                           JOIN users u ON wp.trainer_id = u.user_id 
                           WHERE wp.plan_id = ? AND wp.member_id = ? AND wp.status = 'approved'");
    $stmt->execute([$planId, $_SESSION['user_id']]);
    $plan = $stmt->fetch();
}

// Get all trainers
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name FROM users WHERE role = 'trainer'");
$stmt->execute();
$trainers = $stmt->fetchAll();

// Get all approved plans with trainers
$stmt = $pdo->prepare("SELECT wp.plan_id, wp.title, u.user_id as trainer_id, u.first_name, u.last_name 
                       FROM workout_plans wp 
                       JOIN users u ON wp.trainer_id = u.user_id 
                       WHERE wp.member_id = ? AND wp.status = 'approved'");
$stmt->execute([$_SESSION['user_id']]);
$approvedPlans = $stmt->fetchAll();

// Get all scheduled sessions
$stmt = $pdo->prepare("SELECT ss.*, u.first_name, u.last_name, wp.title as plan_title 
                       FROM scheduled_sessions ss 
                       JOIN users u ON ss.trainer_id = u.user_id 
                       LEFT JOIN workout_plans wp ON ss.plan_id = wp.plan_id 
                       WHERE ss.member_id = ? 
                       ORDER BY ss.session_date DESC, ss.start_time DESC");
$stmt->execute([$_SESSION['user_id']]);
$sessions = $stmt->fetchAll();

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
    <div class="col-md-5">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>Schedule New Session</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if ($plan): ?>
                        <input type="hidden" name="plan_id" value="<?php echo $plan['plan_id']; ?>">
                        <div class="mb-3">
                            <label class="form-label">Plan</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($plan['title']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Trainer</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo htmlspecialchars($plan['first_name'] . ' ' . $plan['last_name']); ?>" readonly>
                            <input type="hidden" name="trainer_id" value="<?php echo $plan['trainer_id']; ?>">
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label">Workout Plan (Optional)</label>
                            <select class="form-select" name="plan_id">
                                <option value="">No specific plan</option>
                                <?php foreach ($approvedPlans as $ap): ?>
                                    <option value="<?php echo $ap['plan_id']; ?>">
                                        <?php echo htmlspecialchars($ap['title'] . ' (' . $ap['first_name'] . ' ' . $ap['last_name'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Session Date</label>
                        <input type="date" class="form-control" name="session_date" required 
                               min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="col">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" name="schedule_session" class="btn btn-primary w-100">Schedule Session</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5>My Scheduled Sessions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($sessions)): ?>
                    <div class="alert alert-info">
                        You don't have any scheduled sessions yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Trainer</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sessions as $session): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($session['session_date'])); ?></td>
                                        <td>
                                            <?php echo date('g:i A', strtotime($session['start_time'])) . ' - ' . 
                                                  date('g:i A', strtotime($session['end_time'])); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></td>
                                        <td><?php echo $session['plan_title'] ? htmlspecialchars($session['plan_title']) : '-'; ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php echo $session['status'] === 'scheduled' ? 'bg-primary' : 
                                                      ($session['status'] === 'completed' ? 'bg-success' : 'bg-secondary'); ?>">
                                                <?php echo ucfirst($session['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($session['status'] === 'scheduled'): ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="session_id" value="<?php echo $session['session_id']; ?>">
                                                    <button type="submit" name="cancel_session" class="btn btn-sm btn-outline-danger" 
                                                            onclick="return confirm('Are you sure you want to cancel this session?')">
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../includes/config.php';
checkRole(['member']);
$pageTitle = "Member Dashboard";

// Get member profile
$stmt = $pdo->prepare("SELECT mp.* FROM member_profiles mp JOIN users u ON mp.user_id = u.user_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();

// Get workout plans
$stmt = $pdo->prepare("SELECT wp.*, u.first_name, u.last_name 
                       FROM workout_plans wp 
                       JOIN users u ON wp.trainer_id = u.user_id 
                       WHERE wp.member_id = ? 
                       ORDER BY wp.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$plans = $stmt->fetchAll();

// Get upcoming sessions
$stmt = $pdo->prepare("SELECT ss.*, u.first_name, u.last_name 
                       FROM scheduled_sessions ss 
                       JOIN users u ON ss.trainer_id = u.user_id 
                       WHERE ss.member_id = ? AND ss.session_date >= CURDATE() 
                       ORDER BY ss.session_date, ss.start_time 
                       LIMIT 3");
$stmt->execute([$_SESSION['user_id']]);
$upcomingSessions = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row">
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>My Profile</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="avatar-placeholder bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 100px; height: 100px; font-size: 2rem;">
                        <?php echo substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1); ?>
                    </div>
                    <h4 class="mt-2"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
                </div>
                <div class="mb-2">
                    <strong>Experience Level:</strong> 
                    <span class="badge bg-info"><?php echo htmlspecialchars(ucfirst($profile['experience_level'])); ?></span>
                </div>
                <div class="mb-2">
                    <strong>Fitness Goals:</strong>
                    <p><?php echo htmlspecialchars($profile['fitness_goals'] ?? 'Not specified'); ?></p>
                </div>
                <div class="mb-2">
                    <strong>Preferred Workouts:</strong>
                    <div>
                        <?php 
                        $preferred = explode(',', $profile['preferred_workouts'] ?? '');
                        foreach ($preferred as $workout) {
                            if (!empty(trim($workout))) {
                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(ucfirst(trim($workout))) . '</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
                <!-- <a href="#" class="btn btn-sm btn-outline-primary w-100 mt-2">Edit Profile</a> -->
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>My Workout Plans</h5>
            </div>
            <div class="card-body">
                <?php if (empty($plans)): ?>
                    <div class="alert alert-info">
                        You don't have any workout plans yet. <a href="workout_plans.php">Create one now</a>.
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
                    <div class="text-end">
                        <a href="workout_plans.php" class="btn btn-primary">View All Plans</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary  text-white">
                <h5>Upcoming Sessions</h5>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingSessions)): ?>
                    <div class="alert alert-info">
                        You don't have any upcoming sessions. <a href="schedule.php">Schedule one now</a>.
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($upcomingSessions as $session): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Session with <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></h6>
                                    <small><?php echo date('M j, Y', strtotime($session['session_date'])); ?></small>
                                </div>
                                <p class="mb-1">
                                    <i class="far fa-clock"></i> 
                                    <?php echo date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time'])); ?>
                                </p>
                                <?php if (!empty($session['notes'])): ?>
                                    <small class="text-muted">Notes: <?php echo htmlspecialchars($session['notes']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-end mt-3">
                        <a href="schedule.php" class="btn btn-info">View All Sessions</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../includes/config.php';
checkRole(['admin']);
$pageTitle = "Admin Dashboard";

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_members FROM users WHERE role = 'member'");
$totalMembers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as total_trainers FROM users WHERE role = 'trainer'");
$totalTrainers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as active_plans FROM workout_plans WHERE status = 'approved'");
$activePlans = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as equipment_count FROM equipment");
$equipmentCount = $stmt->fetchColumn();

// Get recent members
$stmt = $pdo->query("SELECT user_id, username, first_name, last_name, created_at 
                     FROM users 
                     WHERE role = 'member' 
                     ORDER BY created_at DESC 
                     LIMIT 5");
$recentMembers = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Members</h5>
                <h1 class="display-4"><?php echo $totalMembers; ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Trainers</h5>
                <h1 class="display-4"><?php echo $totalTrainers; ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Active Plans</h5>
                <h1 class="display-4"><?php echo $activePlans; ?></h1>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title">Equipment</h5>
                <h1 class="display-4"><?php echo $equipmentCount; ?></h1>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5>Recent Members</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentMembers)): ?>
                    <div class="alert alert-info">
                        No members have registered yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentMembers as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['username']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
                                        <td>
                                            <a href="manage_users.php?view=<?php echo $member['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
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
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="manage_users.php" class="btn btn-outline-primary">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="../equipment/manage_equipment.php" class="btn btn-outline-success">
                        <i class="fas fa-dumbbell"></i> Manage Equipment
                    </a>
                    <a href="#" class="btn btn-outline-info">
                        <i class="fas fa-chart-line"></i> View Reports
                    </a>
                    <a href="#" class="btn btn-outline-warning">
                        <i class="fas fa-cog"></i> System Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
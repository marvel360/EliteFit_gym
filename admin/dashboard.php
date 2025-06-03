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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="../assets/css/styles.css">
</head>

<body>


    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <i class="fas fa-crown"></i> <span>EliteFit Admin</span>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="../equipment/dashboard.php"><i class="fas fa-dumbbell"></i> Equipment</a>
                <a href="../trainer/dashboard.php"><i class="fas fa-calendar-alt"></i> Sessions</a>
                <!-- <a href="#"><i class="fas fa-chart-line"></i> Analytics</a> -->

            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header class="admin-header">
                <h1>Admin Dashboard</h1>
                <div class="admin-profile">
                    <img src="https://ui-avatars.com/api/?name=Admin&background=1e3c72&color=fff" alt="Admin">
                    <span>Administrator</span>
                </div>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card bg-accent">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $totalMembers; ?></h3>
                        <p>Total Members</p>
                    </div>
                </div>
                <div class="stat-card bg-accent">
                    <div class="stat-icon"><i class="fas fa-user-tie"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $totalTrainers; ?></h3>
                        <p>Total Trainers</p>
                    </div>
                </div>
                <div class="stat-card bg-accent">
                    <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $activePlans; ?></h3>
                        <p>Active Plans</p>
                    </div>
                </div>
                <div class="stat-card bg-accent">
                    <div class="stat-icon"><i class="fas fa-dumbbell"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $equipmentCount; ?></h3>
                        <p>Equipment</p>
                    </div>
                </div>
            </div>

            <!-- Recent Members Table -->
            <div class="recent-activity">
                <h2>Recent Members</h2>
                <div class="activity-list">
                    <?php if (empty($recentMembers)): ?>
                        <div class="alert alert-info">No members have registered yet.</div>
                    <?php else: ?>
                        <?php foreach ($recentMembers as $member): ?>
                            <div class="activity-item">
                                <div class="activity-icon primary">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="activity-details">
                                    <p><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                        (<?php echo htmlspecialchars($member['username']); ?>)</p>
                                    <small>Joined on <?php echo date('M j, Y', strtotime($member['created_at'])); ?></small>
                                </div>
                                <div class="ms-auto">
                                    <a href="manage_users.php?view=<?php echo $member['user_id']; ?>"
                                        class="btn btn-sm btn-outline-primary">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="manage_users.php" class="action-btn"><i class="fas fa-users"></i><span>Manage
                            Users</span></a>
                    <a href="../equipment/dashboard.php" class="action-btn"><i class="fas fa-dumbbell"></i><span>Manage
                            Equipment</span></a>
                    <!-- <a href="#" class="action-btn"><i class="fas fa-chart-line"></i><span>View Reports</span></a> -->
                </div>
            </div>

            <!-- Download Reports -->
            <div class="quick-actions">
                <h2>Download Reports</h2>
                <div class="action-buttons">
                    <a href="download_report.php?format=pdf" class="action-btn"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="download_report.php?format=docx" class="action-btn"><i class="fas fa-file-word"></i>
                        DOCX</a>
                    <a href="download_report.php?format=xlsx" class="action-btn"><i class="fas fa-file-excel"></i>
                        XLSX</a>
                </div>
            </div>
        </main>
    </div>

    <?php require_once '../includes/footer.php'; ?>
    <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>

</html>
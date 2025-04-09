<?php
// Start session and check authentication
session_start();
require_once 'auth.php'; // Your authentication script
require_once 'db.php';   // Database connection

// Redirect unauthorized users
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role from session
$userRole = $_SESSION['user_role'];
$userId = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Schedule - EliteFit</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="schedule-container">
        <h1>Class Schedule</h1>
        
        <!-- Trainer/Admin Controls -->
        <?php if ($userRole === 'trainer' || $userRole === 'admin'): ?>
            <div class="schedule-actions">
                <button id="add-class-btn" class="btn">+ Add New Class</button>
            </div>
        <?php endif; ?>

        <!-- Schedule Table -->
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Class</th>
                    <th>Trainer</th>
                    <th>Duration</th>
                    <th>Slots</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch classes from database
                $query = "SELECT c.*, t.name AS trainer_name 
                          FROM classes c
                          JOIN trainers t ON c.trainer_id = t.id
                          WHERE c.date >= CURDATE()
                          ORDER BY c.date, c.start_time";
                
                $result = mysqli_query($conn, $query);

                while ($class = mysqli_fetch_assoc($result)):
                    $classFull = ($class['registered_members'] >= $class['max_members']);
                ?>
                <tr class="<?= $classFull ? 'full' : '' ?>">
                    <td><?= date('H:i', strtotime($class['start_time'])) ?> - <?= date('H:i', strtotime($class['end_time'])) ?></td>
                    <td><?= htmlspecialchars($class['class_name']) ?></td>
                    <td><?= htmlspecialchars($class['trainer_name']) ?></td>
                    <td><?= $class['duration'] ?> mins</td>
                    <td><?= $class['registered_members'] ?>/<?= $class['max_members'] ?></td>
                    <td>
                        <?php if ($userRole === 'member' && !$classFull): ?>
                            <button class="btn-book" data-class-id="<?= $class['id'] ?>">Book</button>
                        <?php elseif ($userRole === 'trainer' || $userRole === 'admin'): ?>
                            <button class="btn-edit" data-class-id="<?= $class['id'] ?>">Edit</button>
                            <button class="btn-delete" data-class-id="<?= $class['id'] ?>">Delete</button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </main>

    <!-- Add/Edit Class Modal (for trainers/admins) -->
    <?php if ($userRole === 'trainer' || $userRole === 'admin'): ?>
        <div id="class-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2 id="modal-title">Add New Class</h2>
                <form id="class-form">
                    <input type="hidden" id="class-id">
                    <div class="form-group">
                        <label for="class-name">Class Name</label>
                        <input type="text" id="class-name" required>
                    </div>
                    <!-- Additional form fields -->
                    <button type="submit" class="btn">Save</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="js/schedule.js"></script>
</body>
</html>
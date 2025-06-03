<?php
require_once '../includes/config.php';
checkRole(['trainer','member']);
$pageTitle = "Schedule Sessions";

/**
 * Check if user has too many upcoming sessions with the same trainer
 */
function hasTooManySessionsWithTrainer($pdo, $userId, $trainerId, $maxSessions = 3) {
    $stmt = $pdo->prepare("SELECT COUNT(*) 
                          FROM scheduled_sessions 
                          WHERE member_id = ? 
                          AND trainer_id = ? 
                          AND status = 'scheduled' 
                          AND session_date >= CURDATE()");
    $stmt->execute([$userId, $trainerId]);
    $count = $stmt->fetchColumn();
    return $count >= $maxSessions;
}

/**
 * Check if trainer is already booked at the requested time
 */
function isTrainerBooked($pdo, $trainerId, $sessionDate, $startTime, $endTime, $excludeSessionId = null) {
    $sql = "SELECT COUNT(*) 
            FROM scheduled_sessions 
            WHERE trainer_id = ? 
            AND session_date = ? 
            AND status = 'scheduled'
            AND (
                (start_time < ? AND end_time > ?) OR  -- New session starts during existing
                (start_time < ? AND end_time > ?) OR  -- New session ends during existing
                (start_time >= ? AND end_time <= ?)   -- New session completely within existing
            )";
    
    $params = [
        $trainerId, 
        $sessionDate,
        $endTime, $startTime,   // For first condition
        $endTime, $startTime,   // For second condition
        $startTime, $endTime    // For third condition
    ];
    
    if ($excludeSessionId) {
        $sql .= " AND session_id != ?";
        $params[] = $excludeSessionId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() > 0;
}

/**
 * Get available time slots for a trainer on a specific date
 */
function getAvailableTimeSlots($pdo, $trainerId, $sessionDate) {
    // Define working hours (9 AM to 6 PM)
    $workingHoursStart = '09:00:00';
    $workingHoursEnd = '18:00:00';
    
    // Get trainer's booked slots for the day
    $stmt = $pdo->prepare("SELECT start_time, end_time 
                          FROM scheduled_sessions 
                          WHERE trainer_id = ? 
                          AND session_date = ? 
                          AND status = 'scheduled'
                          ORDER BY start_time");
    $stmt->execute([$trainerId, $sessionDate]);
    $bookedSlots = $stmt->fetchAll();
    
    // Generate all possible slots (30-minute intervals)
    $allSlots = [];
    $current = strtotime($workingHoursStart);
    $end = strtotime($workingHoursEnd);
    
    while ($current < $end) {
        $slotStart = date('H:i:s', $current);
        $slotEnd = date('H:i:s', $current + 1800); // 30 minutes
        $allSlots[] = ['start' => $slotStart, 'end' => $slotEnd];
        $current += 1800;
    }
    
    // Remove booked slots
    $availableSlots = $allSlots;
    
    foreach ($bookedSlots as $booked) {
        $bookedStart = strtotime($booked['start_time']);
        $bookedEnd = strtotime($booked['end_time']);
        
        $availableSlots = array_filter($availableSlots, function($slot) use ($bookedStart, $bookedEnd) {
            $slotStart = strtotime($slot['start']);
            $slotEnd = strtotime($slot['end']);
            return !($slotStart < $bookedEnd && $slotEnd > $bookedStart);
        });
    }
    
    return array_values($availableSlots);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['schedule_session'])) {
        $planId = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : null;
        $trainerId = intval($_POST['trainer_id']);
        $sessionDate = trim($_POST['session_date']);
        $startTime = trim($_POST['start_time']);
        $endTime = trim($_POST['end_time']);
        $notes = trim($_POST['notes']);
        
        // Convert day-month-year to year-month-day for database
        $sessionDateParts = explode('-', $sessionDate);
        if (count($sessionDateParts) === 3) {
            $sessionDateFormatted = $sessionDateParts[2].'-'.$sessionDateParts[1].'-'.$sessionDateParts[0];
        } else {
            $sessionDateFormatted = date('Y-m-d', strtotime($sessionDate));
        }
        
        // Validate inputs
        $errors = [];
        if (empty($sessionDate)) $errors[] = "Session date is required";
        if (empty($startTime)) $errors[] = "Start time is required";
        if (empty($endTime)) $errors[] = "End time is required";
        
        // Validate time format and logic
        if (strtotime($startTime) >= strtotime($endTime)) {
            $errors[] = "End time must be after start time";
        }
        
        // Check if session is in the past
        $currentDateTime = new DateTime();
        $sessionDateTime = new DateTime($sessionDateFormatted . ' ' . $startTime);
        if ($sessionDateTime < $currentDateTime) {
            $errors[] = "Cannot schedule sessions in the past";
        }
        
        // Check trainer availability constraints
        if (empty($errors)) {
            if (hasTooManySessionsWithTrainer($pdo, $_SESSION['user_id'], $trainerId)) {
                $errors[] = "You already have 3 or more upcoming sessions with this trainer. Please schedule with another trainer or wait until some sessions are completed.";
            }
            
            if (isTrainerBooked($pdo, $trainerId, $sessionDateFormatted, $startTime, $endTime)) {
                $errors[] = "This trainer is already booked during the requested time slot. Please choose a different time.";
            }
        }
        
        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                
                // Insert session
                $stmt = $pdo->prepare("INSERT INTO scheduled_sessions 
                                       (member_id, trainer_id, plan_id, session_date, start_time, end_time, notes, status) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled')");
                $stmt->execute([
                    $_SESSION['user_id'],
                    $trainerId,
                    $planId,
                    $sessionDateFormatted,
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

// Get available slots if trainer and date are selected
$availableSlots = [];
$selectedTrainerId = null;
$selectedDate = null;

if (isset($_GET['trainer_id']) && isset($_GET['session_date'])) {
    $selectedTrainerId = intval($_GET['trainer_id']);
    // Convert day-month-year to year-month-day for database query
    $dateParts = explode('-', $_GET['session_date']);
    if (count($dateParts) === 3) {
        $selectedDate = $dateParts[2].'-'.$dateParts[1].'-'.$dateParts[0];
    } else {
        $selectedDate = date('Y-m-d', strtotime($_GET['session_date']));
    }
    $availableSlots = getAvailableTimeSlots($pdo, $selectedTrainerId, $selectedDate);
}

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
                <form method="post" id="scheduleForm">
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
                            <input type="hidden" name="trainer_id" id="trainer_id" value="<?php echo $plan['trainer_id']; ?>">
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
                            <select class="form-select" name="trainer_id" id="trainer_id" required>
                                <option value="">Select Trainer</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <option value="<?php echo $trainer['user_id']; ?>" <?php echo ($selectedTrainerId == $trainer['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($trainer['first_name'] . ' ' . $trainer['last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                         <label class="form-label">Session Date (DD-MM-YYYY)</label>
                         <input type="text" class="form-control datepicker-dmy" name="session_date" id="session_date" required 
                                placeholder="dd-mm-yyyy" 
                                value="<?php echo isset($_GET['session_date']) ? htmlspecialchars($_GET['session_date']) : ''; ?>"
                                pattern="\d{2}-\d{2}-\d{4}">
                    </div>
                    
                    <?php if (!empty($availableSlots)): ?>
                        <div class="mb-3">
                            <label class="form-label">Available Time Slots</label>
                            <div class="available-slots">
                                <?php foreach ($availableSlots as $slot): ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm slot-btn mb-1" 
                                            data-start="<?php echo date('H:i', strtotime($slot['start'])); ?>"
                                            data-end="<?php echo date('H:i', strtotime($slot['end'])); ?>">
                                        <?php echo date('g:i A', strtotime($slot['start'])); ?> - <?php echo date('g:i A', strtotime($slot['end'])); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time" id="start_time" required>
                        </div>
                        <div class="col">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time" id="end_time" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" name="schedule_session" class="btn btn-primary w-100">Schedule Session</button>
                </form>
                
                <?php if (!$plan): ?>
                    <div class="mt-3">
                        <button id="checkAvailability" class="btn btn-outline-secondary w-100">Check Available Slots</button>
                    </div>
                <?php endif; ?>
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
                                        <td><?php echo date('d-m-Y', strtotime($session['session_date'])); ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check availability button
    const checkAvailabilityBtn = document.getElementById('checkAvailability');
    if (checkAvailabilityBtn) {
        checkAvailabilityBtn.addEventListener('click', function() {
            const trainerId = document.getElementById('trainer_id').value;
            const sessionDate = document.getElementById('session_date').value;
            
            if (!trainerId || !sessionDate) {
                alert('Please select both a trainer and a date');
                return;
            }
            
            // Validate date format
            if (!/^\d{2}-\d{2}-\d{4}$/.test(sessionDate)) {
                alert('Please enter date in DD-MM-YYYY format');
                return;
            }
            
            window.location.href = `?trainer_id=${trainerId}&session_date=${sessionDate}`;
        });
    }
    
    // Slot selection buttons
    document.querySelectorAll('.slot-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('start_time').value = this.dataset.start;
            document.getElementById('end_time').value = this.dataset.end;
        });
    });
    
    // Validate end time is after start time
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    
    if (startTimeInput && endTimeInput) {
        endTimeInput.addEventListener('change', function() {
            if (startTimeInput.value && endTimeInput.value) {
                if (startTimeInput.value >= endTimeInput.value) {
                    alert('End time must be after start time');
                    endTimeInput.value = '';
                }
            }
        });
    }
    
    // Date validation
    const dateInput = document.getElementById('session_date');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            const datePattern = /^\d{2}-\d{2}-\d{4}$/;
            if (!datePattern.test(this.value)) {
                alert('Please enter date in DD-MM-YYYY format');
                this.value = '';
                return;
            }
            
            // Check if date is in the past
            const parts = this.value.split('-');
            const inputDate = new Date(parts[2], parts[1] - 1, parts[0]);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (inputDate < today) {
                alert('Cannot schedule sessions in the past');
                this.value = '';
            }
        });
    }
    
    // Auto-submit form when trainer and date are selected for a plan
    <?php if ($plan): ?>
        const sessionDateInput = document.getElementById('session_date');
        if (sessionDateInput) {
            sessionDateInput.addEventListener('change', function() {
                const trainerId = document.getElementById('trainer_id').value;
                const sessionDate = this.value;
                
                if (trainerId && sessionDate) {
                    window.location.href = `?plan_id=<?php echo $planId; ?>&trainer_id=${trainerId}&session_date=${sessionDate}`;
                }
            });
        }
    <?php endif; ?>
});
</script>

<?php require_once '../includes/footer.php'; ?>
<?php
require_once '../includes/config.php';
checkRole(['equipment' , 'admin']);
$pageTitle = "Equipment Dashboard";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_equipment'])) {
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        
        if (empty($name)) {
            $_SESSION['error'] = "Equipment name is required";
        } else {
            $stmt = $pdo->prepare("INSERT INTO equipment (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $_SESSION['success'] = "Equipment added successfully";
                redirect("dashboard.php");
            } else {
                $_SESSION['error'] = "Failed to add equipment";
            }
        }
    } elseif (isset($_POST['update_equipment'])) {
        $equipmentId = intval($_POST['equipment_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $status = trim($_POST['status']);
        $lastMaintenance = !empty($_POST['last_maintenance']) ? trim($_POST['last_maintenance']) : null;
        
        if (empty($name)) {
            $_SESSION['error'] = "Equipment name is required";
        } else {
            $stmt = $pdo->prepare("UPDATE equipment SET name = ?, description = ?, status = ?, last_maintenance = ? WHERE equipment_id = ?");
            if ($stmt->execute([$name, $description, $status, $lastMaintenance, $equipmentId])) {
                $_SESSION['success'] = "Equipment updated successfully";
                redirect("dashboard.php");
            } else {
                $_SESSION['error'] = "Failed to update equipment";
            }
        }
    }
}

// Get all equipment
$stmt = $pdo->prepare("SELECT * FROM equipment ORDER BY name");
$stmt->execute();
$equipment = $stmt->fetchAll();

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
                <h5>Add New Equipment</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Equipment Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_equipment" class="btn btn-primary w-100">Add Equipment</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5>Gym Equipment</h5>
            </div>
            <div class="card-body">
                <?php if (empty($equipment)): ?>
                    <div class="alert alert-info">
                        No equipment has been added yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Last Maintenance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($equipment as $eq): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($eq['name']); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php echo $eq['status'] === 'available' ? 'bg-success' : 
                                                      ($eq['status'] === 'maintenance' ? 'bg-warning text-dark' : 'bg-secondary'); ?>">
                                                <?php echo ucfirst($eq['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $eq['last_maintenance'] ? date('M j, Y', strtotime($eq['last_maintenance'])) : 'Never'; ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEquipmentModal<?php echo $eq['equipment_id']; ?>">
                                                Edit
                                            </button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Equipment Modal -->
                                    <div class="modal fade" id="editEquipmentModal<?php echo $eq['equipment_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Equipment</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="post">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="equipment_id" value="<?php echo $eq['equipment_id']; ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Equipment Name</label>
                                                            <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($eq['name']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Description</label>
                                                            <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($eq['description']); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select class="form-select" name="status" required>
                                                                <option value="available" <?php echo $eq['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                                                <option value="maintenance" <?php echo $eq['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                                                <option value="out_of_service" <?php echo $eq['status'] === 'out_of_service' ? 'selected' : ''; ?>>Out of Service</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Last Maintenance Date</label>
                                                            <input type="date" class="form-control" name="last_maintenance" value="<?php echo $eq['last_maintenance'] ? date('Y-m-d', strtotime($eq['last_maintenance'])) : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="update_equipment" class="btn btn-primary">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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
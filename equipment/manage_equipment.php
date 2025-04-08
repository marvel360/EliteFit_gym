<?php
require_once '../includes/config.php';
checkRole(['equipment']); // Restrict to equipment managers only
$pageTitle = "Manage Equipment";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_equipment'])) {
        // Add new equipment
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $status = 'available'; // Default status

        if (empty($name)) {
            $_SESSION['error'] = "Equipment name is required";
        } else {
            $stmt = $pdo->prepare("INSERT INTO equipment (name, description, status) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $description, $status])) {
                $_SESSION['success'] = "Equipment added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add equipment";
            }
        }
        redirect("manage_equipment.php");
    } 
    elseif (isset($_POST['update_equipment'])) {
        // Update existing equipment
        $equipmentId = intval($_POST['equipment_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $status = trim($_POST['status']);
        $lastMaintenance = !empty($_POST['last_maintenance']) ? $_POST['last_maintenance'] : null;

        if (empty($name)) {
            $_SESSION['error'] = "Equipment name is required";
        } else {
            $stmt = $pdo->prepare("UPDATE equipment SET name = ?, description = ?, status = ?, last_maintenance = ? WHERE equipment_id = ?");
            if ($stmt->execute([$name, $description, $status, $lastMaintenance, $equipmentId])) {
                $_SESSION['success'] = "Equipment updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to update equipment";
            }
        }
        redirect("manage_equipment.php");
    }
}

// Fetch all equipment
$stmt = $pdo->query("SELECT * FROM equipment ORDER BY name");
$equipment = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">
                <i class="fas fa-dumbbell"></i> Manage Gym Equipment
            </h4>
        </div>
        <div class="card-body">
            <!-- Add New Equipment Form -->
            <div class="mb-4">
                <button class="btn btn-success" data-b
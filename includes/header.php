<?php
// Define BASE_URL if not already defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/elitefit_gym');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EliteFit Gym - <?php echo $pageTitle ?? 'Custom Gym Planning Portal'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;600;700&family=Oswald:wght@400;500;600&display=swap"
        rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>">EliteFit <span>Gym</span></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if ($_SESSION['role'] === 'member'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/member/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/member/workout_plans.php">Workout Plans</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/member/schedule.php">Schedule Sessions</a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'trainer'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/trainer/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/trainer/manage_plans.php">Manage Plans</a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'equipment'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/equipment/dashboard.php">Dashboard</a>
                            </li>
                            <!-- <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/equipment/manage_equipment.php">Manage Equipment</a>
                            </li> -->
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/manage_users.php">Manage Users</a>
                            </li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/notifications.php"><i
                                    class="fas fa-bell"></i></a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                                data-bs-toggle="dropdown">
                                <?php echo $_SESSION['first_name']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <!-- <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li> -->
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <style>
                            body {
                                background-image: url('assets/images/home.jpg');
                                background-size: cover;
                                background-position: center;
                                /* filter: blur(10px); */
                                position: relative;
                                -webkit-display: flex;
                                top: 0;
                                left: 0;
                                width: 100%;
                                height: 100%;
                                /* overflow: hidden; */
                                z-index: 0;

                            }
                        </style>
                        <a href="<?php echo BASE_URL; ?>/login.php" class="btn">Login</a>
                        <a href="<?php echo BASE_URL; ?>/register.php" class="btn">Join Now</a>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4"></div>
    </div>
    <?php
    // secureRedirect function
    function secureRedirect($url)
    {
        // Check if the URL is valid
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            header("Location: " . $url);
            exit();
        } else {
            // Handle the error - invalid URL
            echo "Invalid URL.";
            exit();
        }
    }

    // Usage
// secureRedirect('YOUR_URL_HERE');
    ?>
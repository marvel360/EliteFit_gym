<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
?>

<div class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4 fw-bold">Welcome to EliteFit Gym</h1>
                <p class="lead">Your personalized fitness journey starts here with our Custom Gym Planning Portal.</p>
                <?php if (!isLoggedIn()): ?>
                    <div class="d-flex gap-3 mt-4">
                        <a href="register.php" class="btn btn-light btn-lg px-4">Join Now</a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-4">Login</a>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-light btn-lg px-4 mt-4">
                        Go to Dashboard
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <img src="assets/images/gym-hero.jpg" alt="Fitness" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<div class="features-section py-5">
    <div class="container">
        <h2 class="text-center mb-5">Why Choose EliteFit Gym?</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon bg-primary bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-dumbbell fs-4"></i>
                        </div>
                        <h4 class="card-title">Personalized Workouts</h4>
                        <p class="card-text">Get customized workout plans tailored to your fitness goals and experience level.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon bg-success bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-tie fs-4"></i>
                        </div>
                        <h4 class="card-title">Expert Trainers</h4>
                        <p class="card-text">Work with our certified trainers who will guide and motivate you throughout your journey.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="feature-icon bg-info bg-gradient text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <i class="fas fa-chart-line fs-4"></i>
                        </div>
                        <h4 class="card-title">Progress Tracking</h4>
                        <p class="card-text">Monitor your progress with our advanced analytics and stay motivated to reach your goals.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="testimonials-section bg-light py-5">
    <div class="container">
        <h2 class="text-center mb-5">What Our Members Say</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-quote-left text-muted fs-1"></i>
                        </div>
                        <p class="card-text">EliteFit Gym transformed my fitness journey. The personalized plans and expert trainers helped me achieve results I never thought possible.</p>
                        <div class="d-flex align-items-center mt-4">
                            <img src="assets/images/user1.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                            <div>
                                <h6 class="mb-0">Sarah Johnson</h6>
                                <small class="text-muted">Member since 2020</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-quote-left text-muted fs-1"></i>
                        </div>
                        <p class="card-text">The custom planning portal is a game-changer. I can schedule sessions, track my progress, and communicate with my trainer all in one place.</p>
                        <div class="d-flex align-items-center mt-4">
                            <img src="assets/images/user2.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                            <div>
                                <h6 class="mb-0">Michael Chen</h6>
                                <small class="text-muted">Member since 2021</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-quote-left text-muted fs-1"></i>
                        </div>
                        <p class="card-text">As a busy professional, I appreciate how EliteFit adapts to my schedule while keeping me accountable to my fitness goals.</p>
                        <div class="d-flex align-items-center mt-4">
                            <img src="assets/images/user3.jpg" class="rounded-circle me-3" width="50" height="50" alt="User">
                            <div>
                                <h6 class="mb-0">David Rodriguez</h6>
                                <small class="text-muted">Member since 2019</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="cta-section py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="mb-4">Ready to Start Your Fitness Journey?</h2>
        <p class="lead mb-4">Join EliteFit Gym today and experience the difference of personalized fitness planning.</p>
        <?php if (!isLoggedIn()): ?>
            <a href="register.php" class="btn btn-light btn-lg px-5">Get Started</a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>/<?php echo $_SESSION['role']; ?>/dashboard.php" class="btn btn-light btn-lg px-5">
                Go to Dashboard
            </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
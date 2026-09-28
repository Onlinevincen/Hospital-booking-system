<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<div class="hero text-center mb-5">
    <span class="badge rounded-pill px-3 py-2 mb-3">
        <i class="bi bi-stars me-1"></i>Trusted by patients nationwide
    </span>
    <h1 class="display-6 mb-3 text-white">Quality Healthcare, <span style="color:#93c5fd;">Made Simple</span></h1>
    <p class="lead mx-auto" style="max-width:560px;">
        Book appointments with our doctors, manage your medical records,
        and view prescriptions and reports — all in one place.
    </p>
    <?php if (!isLoggedIn()): ?>
        <div class="mt-4">
            <a href="register.php" class="btn btn-blue btn-lg px-4"><i class="bi bi-calendar-plus me-2"></i>Get Started</a>
            <a href="login.php" class="btn btn-pink btn-lg px-4 ms-2">Login</a>
        </div>
    <?php else: ?>
        <p class="mt-3 fs-5 text-white">Welcome back, <strong><?= sanitize($_SESSION['full_name']) ?></strong>!</p>
        <a href="<?= currentUserRole() ?>/dashboard.php" class="btn btn-teal btn-lg px-4 mt-2">Go to Dashboard</a>
    <?php endif; ?>
</div>

<!-- Stats bar -->
<div class="stats-bar py-4 mb-5">
    <div class="row text-center g-4">
        <div class="col-6 col-md-3">
            <div class="stat-number">50+</div>
            <div class="stat-label">Doctors</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-number">12k+</div>
            <div class="stat-label">Patients Served</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-number">15</div>
            <div class="stat-label">Specialties</div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-number">4.9/5</div>
            <div class="stat-label">Patient Rating</div>
        </div>
    </div>
</div>

<!-- Features -->
<div class="text-center mb-4">
    <h2 class="h3">Everything you need, in one place</h2>
    <p class="text-muted">A complete platform connecting patients and doctors.</p>
</div>
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card card-blue card-hover h-100 p-4 border-0 shadow-sm">
            <span class="feature-icon icon-blue mb-3"><i class="bi bi-calendar-check"></i></span>
            <h3 class="h6">Easy Appointment Booking</h3>
            <p class="text-muted small mb-0">Find the right doctor and book a slot that fits your schedule in minutes.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-purple card-hover h-100 p-4 border-0 shadow-sm">
            <span class="feature-icon icon-purple mb-3"><i class="bi bi-file-earmark-medical"></i></span>
            <h3 class="h6">Digital Medical Records</h3>
            <p class="text-muted small mb-0">Your full medical history, prescriptions, and reports — always accessible.</p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-teal card-hover h-100 p-4 border-0 shadow-sm">
            <span class="feature-icon icon-teal mb-3"><i class="bi bi-shield-check"></i></span>
            <h3 class="h6">Secure & Private</h3>
            <p class="text-muted small mb-0">Your data is protected with industry-standard security practices.</p>
        </div>
    </div>
</div>

<!-- How it works -->
<div class="text-center mb-4">
    <h2 class="h3">How It Works</h2>
    <p class="text-muted">Get started in three simple steps.</p>
</div>
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="d-flex align-items-start gap-3">
            <span class="step-number flex-shrink-0">1</span>
            <div>
                <h3 class="h6 mb-1">Create an Account</h3>
                <p class="text-muted small mb-0">Register as a patient in under a minute.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="d-flex align-items-start gap-3">
            <span class="step-number flex-shrink-0" style="background:linear-gradient(135deg,#7c3aed,#db2777);">2</span>
            <div>
                <h3 class="h6 mb-1">Choose a Doctor & Time</h3>
                <p class="text-muted small mb-0">Browse doctors by specialty and pick an open slot.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="d-flex align-items-start gap-3">
            <span class="step-number flex-shrink-0" style="background:linear-gradient(135deg,#0d9488,#2563eb);">3</span>
            <div>
                <h3 class="h6 mb-1">Get Care & Track Records</h3>
                <p class="text-muted small mb-0">Attend your appointment, then view prescriptions and reports online.</p>
            </div>
        </div>
    </div>
</div>

<!-- CTA -->
<?php if (!isLoggedIn()): ?>
<div class="cta-banner text-center mb-5">
    <h2 class="text-white h3 mb-2">Ready to take control of your healthcare?</h2>
    <p class="text-white-50 mb-4">Join thousands of patients managing their appointments and records online.</p>
    <a href="register.php" class="btn btn-pink btn-lg px-4">Create Your Free Account</a>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

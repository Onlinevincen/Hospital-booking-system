<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$errors = [];
$oldEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($oldEmail === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    }

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$oldEmail]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Invalid email or password.';
        } elseif (!$user['is_active']) {
            $errors[] = 'Your account has been deactivated. Please contact the hospital.';
        } else {
            loginUser($user);
            redirectToDashboard();
        }
    }
}

$pageTitle = 'Login - ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="row g-0 shadow-sm w-100 align-items-stretch">
        <div class="col-lg-5 d-none d-lg-flex auth-panel">
            <div class="panel-content">
                <div class="d-flex align-items-center gap-2 mb-5">
                    <span class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
                    <span class="fw-bold fs-5"><?= sanitize(APP_NAME) ?></span>
                </div>
                <h2 class="text-white mb-3">Your health records, always within reach.</h2>
                <p class="text-white-50">Log in to view upcoming appointments, past prescriptions, and lab reports — anytime, anywhere.</p>
            </div>
            <div class="panel-content">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-shield-check fs-5"></i>
                    <span class="small">Bank-level data encryption</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history fs-5"></i>
                    <span class="small">24/7 access to your records</span>
                </div>
            </div>
        </div>

        <div class="col-lg-7 auth-form-side d-flex align-items-center">
            <div class="mx-auto w-100" style="max-width:400px;">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);">
                        <i class="bi bi-person-circle text-white fs-2"></i>
                    </div>
                    <h2 class="h3 mb-1">Welcome Back</h2>
                    <p class="text-muted">Log in to your account to continue.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= sanitize($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="login.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= sanitize($oldEmail) ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mt-2"><i class="bi bi-box-arrow-in-right me-2"></i>Login</button>
                </form>

                <p class="text-center text-muted small mt-4 mb-0">
                    Don't have an account? <a href="register.php" class="text-decoration-none fw-semibold">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

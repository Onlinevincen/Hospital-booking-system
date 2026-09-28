<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    redirectToDashboard();
}

$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'dob' => '', 'gender' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email']     = trim($_POST['email'] ?? '');
    $old['phone']     = trim($_POST['phone'] ?? '');
    $old['dob']       = trim($_POST['dob'] ?? '');
    $old['gender']    = trim($_POST['gender'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirmPassword  = $_POST['confirm_password'] ?? '';

    if ($old['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }
    if (!isValidEmail($old['email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!isStrongPassword($password)) {
        $errors[] = 'Password must be at least 8 characters and include a letter and a number.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)'
            );
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$old['full_name'], $old['email'], $passwordHash, 'patient', $old['phone']]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO patients (user_id, date_of_birth, gender) VALUES (?, ?, ?)'
            );
            $stmt->execute([
                $userId,
                $old['dob'] !== '' ? $old['dob'] : null,
                $old['gender'] !== '' ? $old['gender'] : null,
            ]);

            $pdo->commit();

            setFlash('success', 'Registration successful! Please log in.');
            header('Location: ' . BASE_URL . '/login.php');
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Registration failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Register - ' . APP_NAME;
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="row g-0 shadow-sm w-100 align-items-stretch">
        <div class="col-lg-5 d-none d-lg-flex auth-panel" style="background-image: linear-gradient(160deg, rgba(29,78,216,0.92) 0%, rgba(15,23,42,0.94) 100%), url('https://picsum.photos/seed/newpatient/900/1400');">
            <div class="panel-content">
                <div class="d-flex align-items-center gap-2 mb-5">
                    <span class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
                    <span class="fw-bold fs-5"><?= sanitize(APP_NAME) ?></span>
                </div>
                <h2 class="text-white mb-3">Join thousands managing their health online.</h2>
                <p class="text-white-50">Create your patient account to book appointments, view prescriptions, and keep your medical history in one secure place.</p>
            </div>
            <div class="panel-content row auth-panel-stat">
                <div class="col-4">
                    <div class="fw-bold fs-4">50+</div>
                    <div class="small text-white-50">Doctors</div>
                </div>
                <div class="col-4">
                    <div class="fw-bold fs-4">12k+</div>
                    <div class="small text-white-50">Patients</div>
                </div>
                <div class="col-4">
                    <div class="fw-bold fs-4">4.9<i class="bi bi-star-fill small ms-1"></i></div>
                    <div class="small text-white-50">Rating</div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 auth-form-side">
            <div class="mx-auto" style="max-width:440px;">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; background: linear-gradient(135deg, #ec4899 0%, #db2777 100%);">
                        <i class="bi bi-person-plus text-white fs-2"></i>
                    </div>
                    <h2 class="h3 mb-1">Create Patient Account</h2>
                    <p class="text-muted">Fill in your details to get started.</p>
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

                <form method="POST" action="register.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= sanitize($old['full_name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= sanitize($old['email']) ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?= sanitize($old['phone']) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" value="<?= sanitize($old['dob']) ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender">
                            <option value="">-- Select --</option>
                            <option value="male" <?= $old['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $old['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= $old['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mt-2"><i class="bi bi-person-plus me-2"></i>Create Account</button>
                </form>

                <p class="text-center text-muted small mt-4 mb-0">
                    Already have an account? <a href="login.php" class="text-decoration-none fw-semibold">Log in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

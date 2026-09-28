<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['doctor']);

$pdo = getDBConnection();
$userId = currentUserId();
$errors = [];

$stmt = $pdo->prepare(
    "SELECT u.full_name, u.email, u.phone, d.* FROM users u
     JOIN doctors d ON u.user_id = d.user_id WHERE u.user_id = ?"
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$departments = $pdo->query('SELECT * FROM departments WHERE is_active = 1 ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $departmentId = (int)($_POST['department_id'] ?? 0) ?: null;
    $specialization = trim($_POST['specialization'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $fee = trim($_POST['consultation_fee'] ?? '0');
    $bio = trim($_POST['bio'] ?? '');

    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($specialization === '') $errors[] = 'Specialization is required.';
    if (!is_numeric($fee) || $fee < 0) $errors[] = 'Consultation fee must be a valid number.';

    if (empty($errors)) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?');
        $stmt->execute([$fullName, $phone, $userId]);

        $stmt = $pdo->prepare(
            'UPDATE doctors SET department_id = ?, specialization = ?, qualification = ?, consultation_fee = ?, bio = ? WHERE user_id = ?'
        );
        $stmt->execute([$departmentId, $specialization, $qualification, $fee, $bio, $userId]);
        $pdo->commit();

        $_SESSION['full_name'] = $fullName;
        setFlash('success', 'Profile updated successfully.');
        header('Location: ' . BASE_URL . '/doctor/profile.php');
        exit;
    }
}

$pageTitle = 'My Profile';
$activeNav = 'profile';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card p-4" style="max-width:700px;">
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" value="<?= sanitize($profile['full_name']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Email (cannot be changed)</label>
                <input type="email" class="form-control" value="<?= sanitize($profile['email']) ?>" disabled>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?= sanitize($profile['phone'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['department_id'] ?>" <?= (int)$profile['department_id'] === (int)$dept['department_id'] ? 'selected' : '' ?>>
                            <?= sanitize($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Specialization</label>
                <input type="text" name="specialization" class="form-control" value="<?= sanitize($profile['specialization']) ?>" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Consultation Fee ($)</label>
                <input type="number" step="0.01" min="0" name="consultation_fee" class="form-control" value="<?= sanitize((string)$profile['consultation_fee']) ?>">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Qualification</label>
            <input type="text" name="qualification" class="form-control" value="<?= sanitize($profile['qualification'] ?? '') ?>" placeholder="e.g. MBBS, MD (Cardiology)">
        </div>

        <div class="mb-3">
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-control" rows="4"><?= sanitize($profile['bio'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="btn btn-purple px-4"><i class="bi bi-save me-2"></i>Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['patient']);

$pdo = getDBConnection();
$userId = currentUserId();
$errors = [];

$stmt = $pdo->prepare(
    "SELECT u.full_name, u.email, u.phone, p.* FROM users u
     JOIN patients p ON u.user_id = p.user_id WHERE u.user_id = ?"
);
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dob = trim($_POST['date_of_birth'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bloodGroup = trim($_POST['blood_group'] ?? '');
    $emergencyName = trim($_POST['emergency_contact_name'] ?? '');
    $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Full name is required.';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?');
        $stmt->execute([$fullName, $phone, $userId]);

        $stmt = $pdo->prepare(
            'UPDATE patients SET date_of_birth = ?, gender = ?, address = ?, blood_group = ?,
             emergency_contact_name = ?, emergency_contact_phone = ? WHERE user_id = ?'
        );
        $stmt->execute([
            $dob !== '' ? $dob : null, $gender !== '' ? $gender : null, $address, $bloodGroup,
            $emergencyName, $emergencyPhone, $userId
        ]);
        $pdo->commit();

        $_SESSION['full_name'] = $fullName;
        setFlash('success', 'Profile updated successfully.');
        header('Location: ' . BASE_URL . '/patient/profile.php');
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
                <label class="form-label">Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control" value="<?= sanitize($profile['date_of_birth'] ?? '') ?>">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">-- Select --</option>
                    <?php foreach (['male', 'female', 'other'] as $g): ?>
                        <option value="<?= $g ?>" <?= ($profile['gender'] ?? '') === $g ? 'selected' : '' ?>><?= ucfirst($g) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Blood Group</label>
                <input type="text" name="blood_group" class="form-control" value="<?= sanitize($profile['blood_group'] ?? '') ?>" placeholder="e.g. O+">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" value="<?= sanitize($profile['address'] ?? '') ?>">
        </div>

        <hr class="my-4">
        <h2 class="h6 mb-3">Emergency Contact</h2>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Contact Name</label>
                <input type="text" name="emergency_contact_name" class="form-control" value="<?= sanitize($profile['emergency_contact_name'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Contact Phone</label>
                <input type="text" name="emergency_contact_phone" class="form-control" value="<?= sanitize($profile['emergency_contact_phone'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-purple px-4"><i class="bi bi-save me-2"></i>Save Changes</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$pdo = getDBConnection();
$errors = [];
$old = ['full_name' => '', 'email' => '', 'phone' => '', 'specialization' => '', 'department_id' => ''];

// ---- Add doctor (creates both users + doctors rows) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_doctor'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $old['full_name'] = trim($_POST['full_name'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['phone'] = trim($_POST['phone'] ?? '');
    $old['specialization'] = trim($_POST['specialization'] ?? '');
    $old['department_id'] = $_POST['department_id'] ?? '';
    $fee = trim($_POST['consultation_fee'] ?? '0');
    $tempPassword = $_POST['temp_password'] ?? '';

    if ($old['full_name'] === '') $errors[] = 'Full name is required.';
    if (!isValidEmail($old['email'])) $errors[] = 'Please enter a valid email address.';
    if ($old['specialization'] === '') $errors[] = 'Specialization is required.';
    if (!isStrongPassword($tempPassword)) $errors[] = 'Temporary password must be at least 8 characters with a letter and a number.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->execute([$old['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with this email already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role, phone) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$old['full_name'], $old['email'], password_hash($tempPassword, PASSWORD_DEFAULT), 'doctor', $old['phone']]);
            $newUserId = $pdo->lastInsertId();

            $stmt = $pdo->prepare(
                'INSERT INTO doctors (user_id, department_id, specialization, consultation_fee) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$newUserId, $old['department_id'] ?: null, $old['specialization'], $fee]);

            $pdo->commit();
            setFlash('success', 'Doctor account created for ' . $old['full_name'] . '. Share the temporary password with them securely.');
            header('Location: ' . BASE_URL . '/admin/doctors.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Doctor creation failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong creating the doctor account.';
        }
    }
}

// ---- Toggle doctor active/inactive ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_user_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE user_id = ? AND role = "doctor"');
        $stmt->execute([(int)$_POST['toggle_user_id']]);
        setFlash('success', 'Doctor status updated.');
    }
    header('Location: ' . BASE_URL . '/admin/doctors.php');
    exit;
}

$departments = $pdo->query('SELECT * FROM departments ORDER BY name')->fetchAll();

$doctors = $pdo->query(
    "SELECT d.*, u.full_name, u.email, u.phone, u.is_active, u.user_id, dep.name AS department_name
     FROM doctors d
     JOIN users u ON d.user_id = u.user_id
     LEFT JOIN departments dep ON d.department_id = dep.department_id
     ORDER BY u.full_name"
)->fetchAll();

$pageTitle = 'Manage Doctors';
$activeNav = 'doctors';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card p-4">
            <h2 class="h6 mb-3">Add New Doctor</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="add_doctor" value="1">

                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= sanitize($old['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= sanitize($old['email']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= sanitize($old['phone']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>"><?= sanitize($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="<?= sanitize($old['specialization']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Consultation Fee ($)</label>
                    <input type="number" step="0.01" min="0" name="consultation_fee" class="form-control" value="0.00">
                </div>
                <div class="mb-3">
                    <label class="form-label">Temporary Password</label>
                    <input type="text" name="temp_password" class="form-control" placeholder="Min 8 chars, letter + number" required>
                    <div class="form-text">Share this with the doctor so they can log in and change it.</div>
                </div>
                <button type="submit" class="btn btn-purple w-100"><i class="bi bi-person-plus me-2"></i>Create Doctor Account</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-0">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Name</th><th>Department</th><th>Specialization</th><th>Email</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($doctors as $doc): ?>
                    <tr>
                        <td class="fw-semibold">Dr. <?= sanitize($doc['full_name']) ?></td>
                        <td><?= sanitize($doc['department_name'] ?? '—') ?></td>
                        <td><?= sanitize($doc['specialization']) ?></td>
                        <td class="small text-muted"><?= sanitize($doc['email']) ?></td>
                        <td>
                            <span class="badge <?= $doc['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>">
                                <?= $doc['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="toggle_user_id" value="<?= $doc['user_id'] ?>">
                                <button class="btn btn-sm <?= $doc['is_active'] ? 'btn-red' : 'btn-green' ?>"><?= $doc['is_active'] ? 'Deactivate' : 'Activate' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$pdo = getDBConnection();
$errors = [];

// ---- Add department ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        $errors[] = 'Department name is required.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO departments (name, description) VALUES (?, ?)');
            $stmt->execute([$name, $description]);
            setFlash('success', 'Department added.');
            header('Location: ' . BASE_URL . '/admin/departments.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'A department with that name already exists.';
        }
    }
}

// ---- Toggle active/inactive ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_department_id'])) {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $stmt = $pdo->prepare('UPDATE departments SET is_active = NOT is_active WHERE department_id = ?');
        $stmt->execute([(int)$_POST['toggle_department_id']]);
        setFlash('success', 'Department status updated.');
    }
    header('Location: ' . BASE_URL . '/admin/departments.php');
    exit;
}

$departments = $pdo->query(
    "SELECT d.*, (SELECT COUNT(*) FROM doctors doc WHERE doc.department_id = d.department_id) AS doctor_count
     FROM departments d ORDER BY d.name"
)->fetchAll();

$pageTitle = 'Manage Departments';
$activeNav = 'departments';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card p-4">
            <h2 class="h6 mb-3">Add Department</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="add_department" value="1">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-teal w-100"><i class="bi bi-plus-lg me-2"></i>Add Department</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-0">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Name</th><th>Description</th><th>Doctors</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td class="fw-semibold"><?= sanitize($dept['name']) ?></td>
                        <td class="small text-muted"><?= sanitize($dept['description'] ?: '—') ?></td>
                        <td><?= (int)$dept['doctor_count'] ?></td>
                        <td>
                            <span class="badge <?= $dept['is_active'] ? 'badge-completed' : 'badge-cancelled' ?>">
                                <?= $dept['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="toggle_department_id" value="<?= $dept['department_id'] ?>">
                                <button class="btn btn-sm <?= $dept['is_active'] ? 'btn-red' : 'btn-green' ?>"><?= $dept['is_active'] ? 'Deactivate' : 'Activate' ?></button>
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

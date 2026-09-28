<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['doctor']);

$pdo = getDBConnection();
$doctorId = getDoctorIdByUserId($pdo, currentUserId());

// ---- Handle status change actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'], $_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request. Please try again.');
        header('Location: ' . BASE_URL . '/doctor/appointments.php');
        exit;
    }

    $apptId = (int)$_POST['appointment_id'];
    $action = $_POST['action'];

    // Map allowed actions to allowed FROM statuses -> TO status,
    // so a doctor can't e.g. "complete" an already-cancelled appointment.
    $transitions = [
        'confirm'  => ['from' => ['pending'],            'to' => 'confirmed'],
        'cancel'   => ['from' => ['pending', 'confirmed'], 'to' => 'cancelled'],
        'complete' => ['from' => ['confirmed'],           'to' => 'completed'],
    ];

    if (isset($transitions[$action])) {
        $placeholders = implode(',', array_fill(0, count($transitions[$action]['from']), '?'));
        $params = array_merge([$transitions[$action]['to']], [$apptId, $doctorId], $transitions[$action]['from']);
        $stmt = $pdo->prepare(
            "UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ? AND status IN ($placeholders)"
        );
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            setFlash('success', 'Appointment ' . $transitions[$action]['to'] . '.');
            if ($action === 'complete') {
                header('Location: ' . BASE_URL . '/doctor/medical_record.php?appointment_id=' . $apptId);
                exit;
            }
        } else {
            setFlash('error', 'Could not update appointment (it may already have a different status).');
        }
    }

    header('Location: ' . BASE_URL . '/doctor/appointments.php');
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT a.*, u.full_name AS patient_name,
        (SELECT COUNT(*) FROM medical_records mr WHERE mr.appointment_id = a.appointment_id) AS has_record
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON p.user_id = u.user_id
        WHERE a.doctor_id = ?";
$params = [$doctorId];
if (in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'Appointments';
$activeNav = 'appointments';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="btn-group mb-3">
    <?php foreach (['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label): ?>
        <a href="?status=<?= $key ?>" class="btn btn-sm <?= $statusFilter === $key ? 'btn-purple' : 'btn-outline-secondary' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if (empty($appointments)): ?>
    <div class="card p-4 text-center text-muted">No appointments found.</div>
<?php else: ?>
    <div class="card p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Patient</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td><?= sanitize($appt['patient_name']) ?></td>
                    <td><?= formatDate($appt['appointment_date']) ?></td>
                    <td><?= formatTime($appt['appointment_time']) ?></td>
                    <td class="small text-muted"><?= sanitize($appt['reason_for_visit'] ?: '—') ?></td>
                    <td><span class="badge <?= statusBadgeClass($appt['status']) ?>"><?= ucfirst($appt['status']) ?></span></td>
                    <td>
                        <div class="d-flex gap-1 flex-wrap">
                        <?php if ($appt['status'] === 'pending'): ?>
                            <form method="POST"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button class="btn btn-sm btn-green">Confirm</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button class="btn btn-sm btn-red">Cancel</button>
                            </form>
                        <?php elseif ($appt['status'] === 'confirmed'): ?>
                            <form method="POST"><input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                <input type="hidden" name="action" value="complete">
                                <button class="btn btn-sm btn-teal">Complete Visit</button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="appointment_id" value="<?= $appt['appointment_id'] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button class="btn btn-sm btn-red">Cancel</button>
                            </form>
                        <?php elseif ($appt['status'] === 'completed'): ?>
                            <a href="medical_record.php?appointment_id=<?= $appt['appointment_id'] ?>" class="btn btn-sm btn-blue">
                                <?= $appt['has_record'] ? 'View Record' : 'Add Record' ?>
                            </a>
                        <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

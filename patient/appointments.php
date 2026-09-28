<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['patient']);

$pdo = getDBConnection();
$patientId = getPatientIdByUserId($pdo, currentUserId());

// ---- Handle cancel action ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment_id'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request. Please try again.');
    } else {
        $apptId = (int)$_POST['cancel_appointment_id'];
        // Only allow cancelling your OWN appointment, and only if not already completed
        $stmt = $pdo->prepare(
            "UPDATE appointments SET status = 'cancelled'
             WHERE appointment_id = ? AND patient_id = ? AND status IN ('pending','confirmed')"
        );
        $stmt->execute([$apptId, $patientId]);
        setFlash('success', 'Appointment cancelled.');
    }
    header('Location: ' . BASE_URL . '/patient/appointments.php');
    exit;
}

$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT a.*, u.full_name AS doctor_name, d.specialization
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        WHERE a.patient_id = ?";
$params = [$patientId];
if (in_array($statusFilter, ['pending', 'confirmed', 'completed', 'cancelled'], true)) {
    $sql .= " AND a.status = ?";
    $params[] = $statusFilter;
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$pageTitle = 'My Appointments';
$activeNav = 'appointments';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="btn-group">
        <?php foreach (['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label): ?>
            <a href="?status=<?= $key ?>" class="btn btn-sm <?= $statusFilter === $key ? 'btn-blue' : 'btn-outline-secondary' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </div>
    <a href="book_appointment.php" class="btn btn-blue btn-sm"><i class="bi bi-plus-lg me-1"></i>Book New</a>
</div>

<?php if (empty($appointments)): ?>
    <div class="card p-4 text-center text-muted">No appointments found.</div>
<?php else: ?>
    <div class="card p-0">
        <table class="table mb-0 align-middle">
            <thead>
                <tr><th>Doctor</th><th>Specialization</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $appt): ?>
                <tr>
                    <td>Dr. <?= sanitize($appt['doctor_name']) ?></td>
                    <td><?= sanitize($appt['specialization']) ?></td>
                    <td><?= formatDate($appt['appointment_date']) ?></td>
                    <td><?= formatTime($appt['appointment_time']) ?></td>
                    <td class="small text-muted"><?= sanitize($appt['reason_for_visit'] ?: '—') ?></td>
                    <td><span class="badge <?= statusBadgeClass($appt['status']) ?>"><?= ucfirst($appt['status']) ?></span></td>
                    <td>
                        <?php if (in_array($appt['status'], ['pending', 'confirmed'], true)): ?>
                            <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
                                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                                <input type="hidden" name="cancel_appointment_id" value="<?= $appt['appointment_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

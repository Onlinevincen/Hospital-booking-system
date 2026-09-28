<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['doctor']);

$pdo = getDBConnection();
$doctorId = getDoctorIdByUserId($pdo, currentUserId());

// Today's appointments
$stmt = $pdo->prepare(
    "SELECT a.*, u.full_name AS patient_name
     FROM appointments a
     JOIN patients p ON a.patient_id = p.patient_id
     JOIN users u ON p.user_id = u.user_id
     WHERE a.doctor_id = ? AND a.appointment_date = CURDATE() AND a.status IN ('pending','confirmed')
     ORDER BY a.appointment_time"
);
$stmt->execute([$doctorId]);
$today = $stmt->fetchAll();

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'");
$stmt->execute([$doctorId]);
$pendingCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = CURDATE() AND status IN ('pending','confirmed')");
$stmt->execute([$doctorId]);
$todayCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM appointments WHERE doctor_id = ?");
$stmt->execute([$doctorId]);
$patientCount = (int)$stmt->fetchColumn();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card stat-card-orange d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <div class="stat-number"><?= $pendingCount ?></div>
                <div class="stat-label">Pending Requests</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-card-blue d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-calendar-day"></i></span>
            <div>
                <div class="stat-number"><?= $todayCount ?></div>
                <div class="stat-label">Today's Appointments</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-card-green d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-people"></i></span>
            <div>
                <div class="stat-number"><?= $patientCount ?></div>
                <div class="stat-label">Total Patients Seen</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Today's Schedule</h2>
    <a href="appointments.php" class="small">View all appointments &rarr;</a>
</div>

<?php if (empty($today)): ?>
    <div class="card p-4 text-center text-muted">No appointments scheduled for today.</div>
<?php else: ?>
    <div class="card p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($today as $appt): ?>
                <tr>
                    <td><?= formatTime($appt['appointment_time']) ?></td>
                    <td><?= sanitize($appt['patient_name']) ?></td>
                    <td class="small text-muted"><?= sanitize($appt['reason_for_visit'] ?: '—') ?></td>
                    <td><span class="badge <?= statusBadgeClass($appt['status']) ?>"><?= ucfirst($appt['status']) ?></span></td>
                    <td><a href="appointments.php" class="small">Manage</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

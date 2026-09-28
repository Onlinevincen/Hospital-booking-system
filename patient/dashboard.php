<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['patient']);

$pdo = getDBConnection();
$patientId = getPatientIdByUserId($pdo, currentUserId());

// Upcoming appointments (today or later, not cancelled)
$stmt = $pdo->prepare(
    "SELECT a.*, u.full_name AS doctor_name, d.specialization
     FROM appointments a
     JOIN doctors d ON a.doctor_id = d.doctor_id
     JOIN users u ON d.user_id = u.user_id
     WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status IN ('pending','confirmed')
     ORDER BY a.appointment_date, a.appointment_time
     LIMIT 5"
);
$stmt->execute([$patientId]);
$upcoming = $stmt->fetchAll();

// Quick stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status IN ('pending','confirmed')");
$stmt->execute([$patientId]);
$upcomingCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE patient_id = ? AND status = 'completed'");
$stmt->execute([$patientId]);
$completedCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM medical_records WHERE patient_id = ?");
$stmt->execute([$patientId]);
$recordsCount = (int)$stmt->fetchColumn();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card stat-card-blue d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-calendar-week"></i></span>
            <div>
                <div class="stat-number"><?= $upcomingCount ?></div>
                <div class="stat-label">Upcoming Appointments</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-card-green d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-check-circle"></i></span>
            <div>
                <div class="stat-number"><?= $completedCount ?></div>
                <div class="stat-label">Completed Visits</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card stat-card-orange d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-file-earmark-medical"></i></span>
            <div>
                <div class="stat-number"><?= $recordsCount ?></div>
                <div class="stat-label">Medical Records</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Upcoming Appointments</h2>
    <a href="book_appointment.php" class="btn btn-blue btn-sm"><i class="bi bi-plus-lg me-1"></i>Book New</a>
</div>

<?php if (empty($upcoming)): ?>
    <div class="card p-4 text-center text-muted">
        <i class="bi bi-calendar-x fs-2 mb-2"></i>
        <p class="mb-2">You have no upcoming appointments.</p>
        <a href="book_appointment.php" class="btn btn-primary btn-sm">Book an Appointment</a>
    </div>
<?php else: ?>
    <div class="card p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Doctor</th><th>Specialization</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($upcoming as $appt): ?>
                <tr>
                    <td>Dr. <?= sanitize($appt['doctor_name']) ?></td>
                    <td><?= sanitize($appt['specialization']) ?></td>
                    <td><?= formatDate($appt['appointment_date']) ?></td>
                    <td><?= formatTime($appt['appointment_time']) ?></td>
                    <td><span class="badge <?= statusBadgeClass($appt['status']) ?>"><?= ucfirst($appt['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="text-end mt-2">
        <a href="appointments.php" class="small">View all appointments &rarr;</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$pdo = getDBConnection();

$doctorCount = (int)$pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$patientCount = (int)$pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$departmentCount = (int)$pdo->query("SELECT COUNT(*) FROM departments WHERE is_active = 1")->fetchColumn();
$appointmentCount = (int)$pdo->query("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()")->fetchColumn();

$recentAppointments = $pdo->query(
    "SELECT a.*, up.full_name AS patient_name, ud.full_name AS doctor_name
     FROM appointments a
     JOIN patients p ON a.patient_id = p.patient_id JOIN users up ON p.user_id = up.user_id
     JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users ud ON d.user_id = ud.user_id
     ORDER BY a.created_at DESC LIMIT 8"
)->fetchAll();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card stat-card-blue d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-person-badge"></i></span>
            <div><div class="stat-number"><?= $doctorCount ?></div><div class="stat-label">Doctors</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card stat-card-green d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-people"></i></span>
            <div><div class="stat-number"><?= $patientCount ?></div><div class="stat-label">Patients</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card stat-card-orange d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-diagram-3"></i></span>
            <div><div class="stat-number"><?= $departmentCount ?></div><div class="stat-label">Departments</div></div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card stat-card-purple d-flex align-items-center gap-3">
            <span class="stat-icon"><i class="bi bi-calendar-day"></i></span>
            <div><div class="stat-number"><?= $appointmentCount ?></div><div class="stat-label">Today's Appointments</div></div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0">Recent Appointments</h2>
</div>
<?php if (empty($recentAppointments)): ?>
    <div class="card p-4 text-center text-muted">No appointments yet.</div>
<?php else: ?>
    <div class="card p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>Patient</th><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recentAppointments as $appt): ?>
                <tr>
                    <td><?= sanitize($appt['patient_name']) ?></td>
                    <td>Dr. <?= sanitize($appt['doctor_name']) ?></td>
                    <td><?= formatDate($appt['appointment_date']) ?></td>
                    <td><?= formatTime($appt['appointment_time']) ?></td>
                    <td><span class="badge <?= statusBadgeClass($appt['status']) ?>"><?= ucfirst($appt['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

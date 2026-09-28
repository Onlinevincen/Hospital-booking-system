<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['doctor']);

$pdo = getDBConnection();
$doctorId = getDoctorIdByUserId($pdo, currentUserId());

$appointmentId = (int)($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);

// Verify this appointment belongs to this doctor and is completed
$stmt = $pdo->prepare(
    "SELECT a.*, u.full_name AS patient_name, a.patient_id
     FROM appointments a
     JOIN patients p ON a.patient_id = p.patient_id
     JOIN users u ON p.user_id = u.user_id
     WHERE a.appointment_id = ? AND a.doctor_id = ? AND a.status = 'completed'"
);
$stmt->execute([$appointmentId, $doctorId]);
$appointment = $stmt->fetch();

if (!$appointment) {
    setFlash('error', 'Appointment not found or not yet completed.');
    header('Location: ' . BASE_URL . '/doctor/appointments.php');
    exit;
}

// Does a record already exist for this appointment?
$stmt = $pdo->prepare('SELECT * FROM medical_records WHERE appointment_id = ?');
$stmt->execute([$appointmentId]);
$existingRecord = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existingRecord) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $symptoms = trim($_POST['symptoms'] ?? '');
    $treatment = trim($_POST['treatment_notes'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $allergies = trim($_POST['allergies'] ?? '');

    if ($diagnosis === '') {
        $errors[] = 'Diagnosis is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'INSERT INTO medical_records (patient_id, doctor_id, appointment_id, diagnosis, symptoms, treatment_notes, prescription, allergies)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $appointment['patient_id'], $doctorId, $appointmentId,
            $diagnosis, $symptoms, $treatment, $prescription, $allergies
        ]);

        setFlash('success', 'Medical record saved.');
        header('Location: ' . BASE_URL . '/doctor/appointments.php');
        exit;
    }
}

$pageTitle = $existingRecord ? 'View Medical Record' : 'Create Medical Record';
$activeNav = 'appointments';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<div class="card p-4" style="max-width:700px;">
    <div class="mb-3 pb-3 border-bottom">
        <div class="text-muted small">Patient</div>
        <div class="fw-semibold"><?= sanitize($appointment['patient_name']) ?></div>
        <div class="text-muted small mt-1"><?= formatDate($appointment['appointment_date']) ?> at <?= formatTime($appointment['appointment_time']) ?></div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if ($existingRecord): ?>
        <!-- Read-only view: records are not editable once saved, to preserve medical record integrity -->
        <div class="mb-3">
            <div class="text-muted small">Diagnosis</div>
            <div><?= sanitize($existingRecord['diagnosis']) ?></div>
        </div>
        <?php foreach (['symptoms' => 'Symptoms', 'treatment_notes' => 'Treatment Notes', 'prescription' => 'Prescription', 'allergies' => 'Allergies'] as $field => $label): ?>
            <?php if ($existingRecord[$field]): ?>
                <div class="mb-3">
                    <div class="text-muted small"><?= $label ?></div>
                    <div><?= nl2br(sanitize($existingRecord[$field])) ?></div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        <a href="appointments.php" class="btn btn-blue btn-sm">&larr; Back to Appointments</a>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="appointment_id" value="<?= $appointmentId ?>">

            <div class="mb-3">
                <label class="form-label">Diagnosis *</label>
                <input type="text" name="diagnosis" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Symptoms</label>
                <textarea name="symptoms" class="form-control" rows="2"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Treatment Notes</label>
                <textarea name="treatment_notes" class="form-control" rows="3"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Prescription</label>
                <textarea name="prescription" class="form-control" rows="3" placeholder="e.g. Amoxicillin 500mg — 3x daily for 7 days"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Known Allergies</label>
                <input type="text" name="allergies" class="form-control">
            </div>

            <button type="submit" class="btn btn-green px-4"><i class="bi bi-save me-2"></i>Save Medical Record</button>
            <a href="appointments.php" class="btn btn-outline-secondary">Cancel</a>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

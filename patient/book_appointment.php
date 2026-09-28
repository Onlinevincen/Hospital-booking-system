<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['patient']);

$pdo = getDBConnection();
$patientId = getPatientIdByUserId($pdo, currentUserId());

$errors = [];

// ---- Read selections from query string (each step reloads the page) ----
$departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$doctorId     = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : null;
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// ---- Handle booking submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    $postDoctorId = (int)($_POST['doctor_id'] ?? 0);
    $postDate     = $_POST['appointment_date'] ?? '';
    $postTime     = $_POST['appointment_time'] ?? '';
    $reason       = trim($_POST['reason_for_visit'] ?? '');

    if (empty($errors)) {
        // Re-validate the slot is still available (prevents race conditions / stale forms)
        $available = getAvailableSlots($pdo, $postDoctorId, $postDate);
        if (!in_array($postTime, $available, true)) {
            $errors[] = 'Sorry, that time slot is no longer available. Please pick another.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason_for_visit, status)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$patientId, $postDoctorId, $postDate, $postTime, $reason, 'pending']);

            setFlash('success', 'Appointment requested! You will be notified once the doctor confirms it.');
            header('Location: ' . BASE_URL . '/patient/appointments.php');
            exit;
        } catch (PDOException $e) {
            // Unique key violation = someone else grabbed the slot first
            error_log('Booking failed: ' . $e->getMessage());
            $errors[] = 'That slot was just booked by someone else. Please choose another time.';
        }
    }

    // Keep selections so the form re-renders at the right step
    $departmentId = $departmentId ?? null;
    $doctorId = $postDoctorId;
    $selectedDate = $postDate;
}

// ---- Data for the wizard ----
$departments = $pdo->query('SELECT * FROM departments WHERE is_active = 1 ORDER BY name')->fetchAll();

$doctors = [];
if ($departmentId) {
    $stmt = $pdo->prepare(
        "SELECT d.doctor_id, d.specialization, d.consultation_fee, u.full_name
         FROM doctors d JOIN users u ON d.user_id = u.user_id
         WHERE d.department_id = ? AND u.is_active = 1 ORDER BY u.full_name"
    );
    $stmt->execute([$departmentId]);
    $doctors = $stmt->fetchAll();
}

$selectedDoctor = null;
$slots = [];
if ($doctorId) {
    $stmt = $pdo->prepare(
        "SELECT d.*, u.full_name FROM doctors d JOIN users u ON d.user_id = u.user_id WHERE d.doctor_id = ?"
    );
    $stmt->execute([$doctorId]);
    $selectedDoctor = $stmt->fetch();
    if ($selectedDoctor) {
        $slots = getAvailableSlots($pdo, $doctorId, $selectedDate);
    }
}

$pageTitle = 'Book Appointment';
$activeNav = 'book';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?><div><?= sanitize($error) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Step 1: Department -->
<div class="card p-4 mb-3">
    <h2 class="h6 mb-3"><span class="step-number me-2">1</span>Choose a Department</h2>
    <div class="row g-2">
        <?php foreach ($departments as $dept): ?>
            <div class="col-md-3 col-6">
                <a href="?department_id=<?= $dept['department_id'] ?>"
                   class="btn <?= $departmentId === (int)$dept['department_id'] ? 'btn-blue' : 'btn-outline-secondary' ?> w-100 text-start py-2">
                    <?= sanitize($dept['name']) ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Step 2: Doctor -->
<?php if ($departmentId): ?>
<div class="card p-4 mb-3">
    <h2 class="h6 mb-3"><span class="step-number me-2">2</span>Choose a Doctor</h2>
    <?php if (empty($doctors)): ?>
        <p class="text-muted mb-0">No doctors currently available in this department.</p>
    <?php else: ?>
        <div class="row g-2">
            <?php foreach ($doctors as $doc): ?>
                <div class="col-md-4">
                    <a href="?department_id=<?= $departmentId ?>&doctor_id=<?= $doc['doctor_id'] ?>"
                       class="text-decoration-none">
                        <div class="card p-3 h-100 <?= $doctorId === (int)$doc['doctor_id'] ? 'border-primary' : '' ?>">
                            <div class="fw-semibold text-dark">Dr. <?= sanitize($doc['full_name']) ?></div>
                            <div class="text-muted small"><?= sanitize($doc['specialization']) ?></div>
                            <div class="small mt-1">Fee: $<?= number_format((float)$doc['consultation_fee'], 2) ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Step 3: Date & Time -->
<?php if ($selectedDoctor): ?>
<div class="card p-4 mb-3">
    <h2 class="h6 mb-3"><span class="step-number me-2">3</span>Choose Date & Time</h2>

    <form method="GET" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="department_id" value="<?= $departmentId ?>">
        <input type="hidden" name="doctor_id" value="<?= $doctorId ?>">
        <div class="col-auto">
            <label class="form-label">Date</label>
            <input type="date" name="date" class="form-control" value="<?= sanitize($selectedDate) ?>" min="<?= date('Y-m-d') ?>" onchange="this.form.submit()">
        </div>
    </form>

    <?php if (empty($slots)): ?>
        <p class="text-muted mb-0"><i class="bi bi-info-circle me-1"></i>No available slots on this date. Try a different day.</p>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="doctor_id" value="<?= $doctorId ?>">
            <input type="hidden" name="appointment_date" value="<?= sanitize($selectedDate) ?>">

            <div class="row g-2 mb-3">
                <?php foreach ($slots as $slot): ?>
                    <div class="col-auto">
                        <input type="radio" class="btn-check" name="appointment_time" id="slot_<?= $slot ?>" value="<?= $slot ?>" required>
                        <label class="btn btn-outline-primary btn-sm" for="slot_<?= $slot ?>"><?= formatTime($slot) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mb-3" style="max-width:500px;">
                <label class="form-label">Reason for Visit (optional)</label>
                <textarea name="reason_for_visit" class="form-control" rows="2" placeholder="Briefly describe your symptoms or reason for the visit"></textarea>
            </div>

            <button type="submit" class="btn btn-green"><i class="bi bi-check2-circle me-1"></i>Confirm Booking</button>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

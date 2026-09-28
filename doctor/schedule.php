<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['doctor']);

$pdo = getDBConnection();
$doctorId = getDoctorIdByUserId($pdo, currentUserId());

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission. Please try again.';
    }

    if (empty($errors)) {
        foreach ($days as $day) {
            $enabled = isset($_POST['enabled'][$day]);
            $start = $_POST['start_time'][$day] ?? '';
            $end = $_POST['end_time'][$day] ?? '';
            $duration = (int)($_POST['duration'][$day] ?? 30);

            if ($enabled && $start !== '' && $end !== '' && $start < $end) {
                // Upsert: create the day's schedule, or update it if it already exists
                $stmt = $pdo->prepare(
                    'INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, slot_duration_minutes, is_active)
                     VALUES (?, ?, ?, ?, ?, 1)
                     ON DUPLICATE KEY UPDATE start_time = VALUES(start_time), end_time = VALUES(end_time),
                     slot_duration_minutes = VALUES(slot_duration_minutes), is_active = 1'
                );
                $stmt->execute([$doctorId, $day, $start, $end, $duration]);
            } else {
                // Not enabled (or incomplete) — remove any existing schedule for that day
                $stmt = $pdo->prepare('DELETE FROM doctor_schedules WHERE doctor_id = ? AND day_of_week = ?');
                $stmt->execute([$doctorId, $day]);
            }
        }

        setFlash('success', 'Your weekly schedule has been updated.');
        header('Location: ' . BASE_URL . '/doctor/schedule.php');
        exit;
    }
}

// Load current schedule keyed by day
$stmt = $pdo->prepare('SELECT * FROM doctor_schedules WHERE doctor_id = ?');
$stmt->execute([$doctorId]);
$existing = [];
foreach ($stmt->fetchAll() as $row) {
    $existing[$row['day_of_week']] = $row;
}

$pageTitle = 'My Schedule';
$activeNav = 'schedule';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e): ?><div><?= sanitize($e) ?></div><?php endforeach; ?></div>
<?php endif; ?>

<div class="card p-4">
    <p class="text-muted small mb-4">Set your weekly working hours. Patients will only be able to book slots within these windows. Un-check a day to mark yourself unavailable.</p>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

        <table class="table align-middle">
            <thead><tr><th style="width:140px;">Day</th><th>Start Time</th><th>End Time</th><th>Slot Length</th></tr></thead>
            <tbody>
            <?php foreach ($days as $day): ?>
                <?php $row = $existing[$day] ?? null; ?>
                <tr>
                    <td>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="enabled[<?= $day ?>]" id="en_<?= $day ?>" <?= $row ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold" for="en_<?= $day ?>"><?= $day ?></label>
                        </div>
                    </td>
                    <td><input type="time" class="form-control form-control-sm" name="start_time[<?= $day ?>]" value="<?= $row['start_time'] ?? '09:00' ?>"></td>
                    <td><input type="time" class="form-control form-control-sm" name="end_time[<?= $day ?>]" value="<?= $row['end_time'] ?? '17:00' ?>"></td>
                    <td>
                        <select class="form-select form-select-sm" name="duration[<?= $day ?>]" style="max-width:130px;">
                            <?php foreach ([15, 20, 30, 45, 60] as $mins): ?>
                                <option value="<?= $mins ?>" <?= ((int)($row['slot_duration_minutes'] ?? 30)) === $mins ? 'selected' : '' ?>><?= $mins ?> min</option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-teal px-4 mt-2"><i class="bi bi-save me-2"></i>Save Schedule</button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

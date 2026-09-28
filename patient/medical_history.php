<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['patient']);

$pdo = getDBConnection();
$patientId = getPatientIdByUserId($pdo, currentUserId());

$stmt = $pdo->prepare(
    "SELECT mr.*, u.full_name AS doctor_name, d.specialization
     FROM medical_records mr
     LEFT JOIN doctors d ON mr.doctor_id = d.doctor_id
     LEFT JOIN users u ON d.user_id = u.user_id
     WHERE mr.patient_id = ?
     ORDER BY mr.recorded_at DESC"
);
$stmt->execute([$patientId]);
$records = $stmt->fetchAll();

$pageTitle = 'Medical History';
$activeNav = 'history';
require_once __DIR__ . '/../includes/dashboard_header.php';
?>

<?php if (empty($records)): ?>
    <div class="card p-4 text-center text-muted">
        <i class="bi bi-file-earmark-medical fs-2 mb-2"></i>
        <p class="mb-0">No medical records yet. Records will appear here after your doctor visits.</p>
    </div>
<?php else: ?>
    <?php foreach ($records as $rec): ?>
        <div class="card p-4 mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div>
                    <h2 class="h6 mb-1"><?= sanitize($rec['diagnosis'] ?: 'General Consultation') ?></h2>
                    <div class="text-muted small">
                        <?= $rec['doctor_name'] ? 'Dr. ' . sanitize($rec['doctor_name']) . ' — ' . sanitize($rec['specialization']) : 'Doctor unassigned' ?>
                    </div>
                </div>
                <span class="text-muted small"><?= date('M j, Y g:i A', strtotime($rec['recorded_at'])) ?></span>
            </div>

            <?php if ($rec['symptoms']): ?>
                <div class="mb-2"><strong class="small">Symptoms:</strong> <span class="small"><?= nl2br(sanitize($rec['symptoms'])) ?></span></div>
            <?php endif; ?>
            <?php if ($rec['treatment_notes']): ?>
                <div class="mb-2"><strong class="small">Treatment Notes:</strong> <span class="small"><?= nl2br(sanitize($rec['treatment_notes'])) ?></span></div>
            <?php endif; ?>
            <?php if ($rec['prescription']): ?>
                <div class="mb-2"><strong class="small">Prescription:</strong> <span class="small"><?= nl2br(sanitize($rec['prescription'])) ?></span></div>
            <?php endif; ?>
            <?php if ($rec['allergies']): ?>
                <div class="mb-0"><strong class="small">Allergies:</strong> <span class="small"><?= sanitize($rec['allergies']) ?></span></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/dashboard_footer.php'; ?>

<?php
/**
 * General Helper Functions
 * -----------------------------------------------------
 */

/**
 * Clean user input for safe display (prevents XSS when echoed into HTML).
 */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Basic email format check.
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Password strength check: at least 8 chars, one letter, one number.
 */
function isStrongPassword(string $password): bool
{
    return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

/**
 * Store a one-time flash message (e.g. "Registration successful!")
 * that survives exactly one redirect, then disappears.
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format a TIME value ("14:30:00") as "2:30 PM".
 */
function formatTime(string $time): string
{
    return date('g:i A', strtotime($time));
}

/**
 * Format a DATE value ("2026-08-20") as "Thu, Aug 20 2026".
 */
function formatDate(string $date): string
{
    return date('D, M j Y', strtotime($date));
}

/**
 * Bootstrap badge class for an appointment status.
 */
function statusBadgeClass(string $status): string
{
    return match ($status) {
        'pending'   => 'badge-pending',
        'confirmed' => 'badge-confirmed',
        'completed' => 'badge-completed',
        'cancelled' => 'badge-cancelled',
        default     => 'bg-secondary',
    };
}

/**
 * Look up a patient's patient_id from their user_id (session id).
 */
function getPatientIdByUserId(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare('SELECT patient_id FROM patients WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['patient_id'] : null;
}

/**
 * Look up a doctor's doctor_id from their user_id (session id).
 */
function getDoctorIdByUserId(PDO $pdo, int $userId): ?int
{
    $stmt = $pdo->prepare('SELECT doctor_id FROM doctors WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ? (int)$row['doctor_id'] : null;
}

/**
 * Build the list of bookable time slots for a doctor on a given date.
 * Looks up the doctor's recurring weekly schedule for that day of week,
 * generates slots at the configured interval, then removes any slot
 * that's already booked (status != cancelled).
 *
 * Returns an array of "HH:MM:SS" strings.
 */
function getAvailableSlots(PDO $pdo, int $doctorId, string $date): array
{
    $dayOfWeek = date('l', strtotime($date)); // e.g. "Monday"

    // Don't allow booking in the past
    if (strtotime($date) < strtotime(date('Y-m-d'))) {
        return [];
    }

    $stmt = $pdo->prepare(
        'SELECT start_time, end_time, slot_duration_minutes FROM doctor_schedules
         WHERE doctor_id = ? AND day_of_week = ? AND is_active = 1'
    );
    $stmt->execute([$doctorId, $dayOfWeek]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        return []; // doctor doesn't work this day
    }

    // Generate all possible slots between start and end time
    $slots = [];
    $current = strtotime($date . ' ' . $schedule['start_time']);
    $end     = strtotime($date . ' ' . $schedule['end_time']);
    $intervalSeconds = (int)$schedule['slot_duration_minutes'] * 60;

    while ($current < $end) {
        // If booking for today, skip slots already in the past
        if (!($date === date('Y-m-d') && $current <= time())) {
            $slots[] = date('H:i:s', $current);
        }
        $current += $intervalSeconds;
    }

    if (empty($slots)) {
        return [];
    }

    // Remove slots already booked (pending or confirmed)
    $stmt = $pdo->prepare(
        "SELECT appointment_time FROM appointments
         WHERE doctor_id = ? AND appointment_date = ? AND status IN ('pending','confirmed')"
    );
    $stmt->execute([$doctorId, $date]);
    $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return array_values(array_diff($slots, $booked));
}

<?php
/**
 * Authentication Helpers
 * -----------------------------------------------------
 * Handles login state, role checks, and page protection.
 * Include this AFTER config.php on any page that needs it.
 */

/**
 * Is anyone logged in at all?
 */
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

/**
 * Get the role of the currently logged-in user, or null.
 */
function currentUserRole(): ?string
{
    return $_SESSION['role'] ?? null;
}

/**
 * Get the currently logged-in user's id, or null.
 */
function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Block access unless the user is logged in.
 * Call at the very top of any protected page.
 */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Block access unless the user is logged in AND has one of the allowed roles.
 * Example: requireRole(['doctor']); or requireRole(['admin', 'doctor']);
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    if (!in_array(currentUserRole(), $allowedRoles, true)) {
        http_response_code(403);
        die('Access denied. You do not have permission to view this page.');
    }
}

/**
 * Log a user in: sets session variables.
 * Called after verifying email + password in login.php
 */
function loginUser(array $user): void
{
    // Regenerate session ID on login to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];
}

/**
 * Log the current user out completely.
 */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie('PHPSESSID', '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * Generate a CSRF token for a form, storing it in the session.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify a submitted CSRF token matches the session's token.
 */
function verifyCsrfToken(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect a logged-in user to their correct dashboard based on role.
 * Useful right after login, or if a logged-in user visits login.php again.
 */
function redirectToDashboard(): void
{
    switch (currentUserRole()) {
        case 'patient':
            header('Location: ' . BASE_URL . '/patient/dashboard.php');
            break;
        case 'doctor':
            header('Location: ' . BASE_URL . '/doctor/dashboard.php');
            break;
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/login.php');
    }
    exit;
}

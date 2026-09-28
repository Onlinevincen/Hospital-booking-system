<?php
/**
 * Shared page header.
 * Expects $pageTitle to optionally be set before including this file.
 */
$pageTitle = $pageTitle ?? APP_NAME;
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= sanitize(APP_NAME) ?> — book appointments, manage medical records, and view prescriptions online.">
    <title><?= sanitize($pageTitle) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Font: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom CSS (brand overrides on top of Bootstrap) -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="<?= BASE_URL ?>/index.php">
            <span class="brand-icon"><i class="bi bi-heart-pulse-fill"></i></span>
            <span><?= sanitize(APP_NAME) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navMain">
            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item text-muted small me-lg-3">
                        <i class="bi bi-person-circle me-1"></i>Hi, <?= sanitize($_SESSION['full_name']) ?>
                    </li>
                    <?php if (currentUserRole() === 'patient'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/patient/dashboard.php">Dashboard</a></li>
                    <?php elseif (currentUserRole() === 'doctor'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/doctor/dashboard.php">Dashboard</a></li>
                    <?php elseif (currentUserRole() === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="btn btn-outline-secondary btn-sm px-3" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/login.php">Login</a></li>
                    <li class="nav-item"><a class="btn btn-blue btn-sm px-3" href="<?= BASE_URL ?>/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show d-flex align-items-center gap-2">
            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
            <div><?= sanitize($flash['message']) ?></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

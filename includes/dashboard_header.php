<?php
/**
 * Shared dashboard layout (sidebar + topbar).
 * Used by all patient/doctor/admin dashboard pages.
 *
 * Expects, set BEFORE include:
 *   $pageTitle  - browser tab title
 *   $activeNav  - key of the current nav item, to highlight it
 */
$pageTitle = $pageTitle ?? APP_NAME;
$activeNav = $activeNav ?? '';
$flash = getFlash();
$role = currentUserRole();

// Nav items per role: key => [icon, label, href relative to BASE_URL]
$navItems = [
    'patient' => [
        'dashboard'   => ['bi-speedometer2', 'Dashboard', '/patient/dashboard.php'],
        'book'        => ['bi-calendar-plus', 'Book Appointment', '/patient/book_appointment.php'],
        'appointments'=> ['bi-calendar-week', 'My Appointments', '/patient/appointments.php'],
        'history'     => ['bi-file-earmark-medical', 'Medical History', '/patient/medical_history.php'],
        'profile'     => ['bi-person', 'Profile', '/patient/profile.php'],
        'chatbot'     => ['bi-chat-dots', 'Chat Assistant', '#'],
    ],
    'doctor' => [
        'dashboard'   => ['bi-speedometer2', 'Dashboard', '/doctor/dashboard.php'],
        'appointments'=> ['bi-calendar-week', 'Appointments', '/doctor/appointments.php'],
        'schedule'    => ['bi-clock-history', 'My Schedule', '/doctor/schedule.php'],
        'profile'     => ['bi-person', 'Profile', '/doctor/profile.php'],
    ],
    'admin' => [
        'dashboard'   => ['bi-speedometer2', 'Dashboard', '/admin/dashboard.php'],
        'doctors'     => ['bi-person-badge', 'Manage Doctors', '/admin/doctors.php'],
        'departments' => ['bi-diagram-3', 'Manage Departments', '/admin/departments.php'],
    ],
];
$items = $navItems[$role] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="dash-wrapper">
    <aside class="dash-sidebar" id="dashSidebar">
        <div class="brand">
            <a href="<?= BASE_URL ?>/index.php" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="brand-icon" style="background: linear-gradient(135deg, #2563eb 0%, #7c3aed 100%);"><i class="bi bi-heart-pulse-fill"></i></span>
                <span class="fw-bold text-white"><?= sanitize(APP_NAME) ?></span>
            </a>
        </div>
        <nav class="py-2">
            <div class="nav-section-label"><?= ucfirst($role) ?> Menu</div>
            <?php foreach ($items as $key => $item): ?>
                <?php if ($key === 'chatbot'): ?>
                    <!-- Chatbot special item with onclick -->
                    <a href="javascript:void(0);" class="nav-link <?= $activeNav === $key ? 'active' : '' ?>" onclick="toggleChat(); return false;">
                        <i class="bi <?= $item[0] ?>"></i> <?= sanitize($item[1]) ?>
                    </a>
                <?php else: ?>
                    <!-- Regular navigation item -->
                    <a href="<?= BASE_URL . $item[2] ?>" class="nav-link <?= $activeNav === $key ? 'active' : '' ?>">
                        <i class="bi <?= $item[0] ?>"></i> <?= sanitize($item[1]) ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="nav-section-label">Account</div>
            <a href="<?= BASE_URL ?>/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </aside>

    <div class="dash-main">
        <div class="dash-topbar">
            <button class="btn btn-sm btn-outline-secondary d-md-none" onclick="document.getElementById('dashSidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <h1 class="h5 mb-0"><?= sanitize($pageTitle) ?></h1>
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-person-circle fs-5 text-muted"></i>
                <span class="small text-muted"><?= sanitize($_SESSION['full_name']) ?></span>
            </div>
        </div>
        <div class="dash-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show d-flex align-items-center gap-2">
                    <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
                    <div><?= sanitize($flash['message']) ?></div>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($role === 'patient'): ?>
            <!-- Chatbot Assistant (embedded in header for all patient pages) -->
            <div class="card chatbot-card" id="chatbot-card" style="display: none; position: fixed; bottom: 80px; right: 20px; width: 350px; max-width: 90vw; z-index: 1000; border: 2px solid #007bff; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Booking Assistant</h5>
                    <button type="button" class="btn-close btn-close-white" onclick="toggleChat()"></button>
                </div>
                <div class="card-body">
                    <div id="chat-messages" class="chat-messages mb-3" style="height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.375rem; padding: 1rem; background-color: #f8f9fa;">
                        <div class="chat-message bot-message">
                            <div class="message-content">
                                <strong>Assistant:</strong> Hello <?= sanitize($_SESSION['full_name'] ?? 'there') ?>! I can help you book appointments, check your schedule, or answer questions about our departments. How can I assist you today?
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-2">
                        <input type="text" id="chat-input" class="form-control" placeholder="Type your message here..." onkeypress="if(event.key === 'Enter') sendMessage()">
                        <button class="btn btn-primary" onclick="sendMessage()"><i class="bi bi-send"></i> Send</button>
                    </div>
                    <div class="text-center">
                        <a href="tel:+233595498738" class="btn btn-outline-success btn-sm w-100">
                            <i class="bi bi-telephone-fill me-1"></i> Speak to an Assistant
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Button (appears when chat is open on mobile) -->
            <button id="chat-menu-btn" class="chat-menu-btn" onclick="toggleChatForMobile()">
                <i class="bi bi-list"></i> Menu
            </button>

            <style>
            .chat-toggle-btn {
                position: fixed;
                bottom: 20px;
                right: 20px;
                width: 60px;
                height: 60px;
                border-radius: 50%;
                background: linear-gradient(135deg, #007bff, #0056b3);
                color: white;
                border: none;
                font-size: 24px;
                cursor: pointer;
                z-index: 1000;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                transition: transform 0.2s, box-shadow 0.2s;
            }
            .chat-toggle-btn:hover {
                transform: scale(1.1);
                box-shadow: 0 6px 16px rgba(0,0,0,0.4);
            }
            .chat-toggle-btn:active {
                transform: scale(0.95);
            }
            .chat-menu-btn {
                position: fixed;
                top: 70px;
                left: 20px;
                z-index: 1001;
                background: #1e293b;
                color: white;
                border: none;
                padding: 10px 15px;
                border-radius: 8px;
                cursor: pointer;
                display: none;
                box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            }
            .chat-menu-btn:hover {
                background: #334155;
            }
            /* Mobile-specific chat behavior */
            @media (max-width: 768px) {
                .dash-sidebar.sidebar-hidden {
                    display: none !important;
                }
                .chat-menu-btn.chat-visible {
                    display: block !important;
                }
            }
            .chat-messages {
                background-color: #f8f9fa;
            }
            .chat-message {
                margin-bottom: 1rem;
                padding: 0.75rem;
                border-radius: 0.5rem;
                max-width: 80%;
            }
            .bot-message {
                background-color: #e3f2fd;
                margin-right: auto;
            }
            .user-message {
                background-color: #f1f8e9;
                margin-left: auto;
            }
            .message-content {
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            </style>

            <script>
            // Get patient info for current page
            const patientId = <?php 
                if (isset($patientId)) {
                    echo $patientId;
                } else {
                    // Try to get patient ID from session
                    $pdo = getDBConnection();
                    $userId = currentUserId();
                    $patientId = getPatientIdByUserId($pdo, $userId);
                    echo $patientId ?? 0;
                }
            ?>;
            const patientName = <?= json_encode($_SESSION['full_name'] ?? 'Guest') ?>;
            const sessionId = 'session-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            
            // Debug: Log to console
            console.log('Chatbot Debug Info:');
            console.log('Patient ID:', patientId);
            console.log('Patient Name:', patientName);
            console.log('Session ID:', sessionId);
            console.log('Current URL:', window.location.href);
            
            // Use PHP proxy for Python service (works with ngrok)
            const apiBaseUrl = window.location.protocol + '//' + window.location.hostname + '/city_hospital/api/chat_proxy.php';

            function toggleChat() {
                const chatCard = document.getElementById('chatbot-card');
                const toggleBtn = document.getElementById('chat-toggle-btn');
                const sidebar = document.getElementById('dashSidebar');
                const menuBtn = document.getElementById('chat-menu-btn');
                
                if (chatCard.style.display === 'none' || chatCard.style.display === '') {
                    chatCard.style.display = 'block';
                    if (toggleBtn) toggleBtn.style.display = 'none';
                    
                    // Mobile: hide sidebar and show menu button
                    if (window.innerWidth <= 768) {
                        sidebar.classList.add('sidebar-hidden');
                        if (menuBtn) menuBtn.classList.add('chat-visible');
                    }
                } else {
                    chatCard.style.display = 'none';
                    if (toggleBtn) toggleBtn.style.display = 'block';
                    
                    // Mobile: show sidebar and hide menu button
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('sidebar-hidden');
                        if (menuBtn) menuBtn.classList.remove('chat-visible');
                    }
                }
            }

            function toggleChatForMobile() {
                const chatCard = document.getElementById('chatbot-card');
                const sidebar = document.getElementById('dashSidebar');
                const menuBtn = document.getElementById('chat-menu-btn');
                
                // Close chat and show sidebar
                chatCard.style.display = 'none';
                sidebar.classList.remove('sidebar-hidden');
                if (menuBtn) menuBtn.classList.remove('chat-visible');
            }

            function sendMessage() {
                const input = document.getElementById('chat-input');
                const message = input.value.trim();
                
                if (!message) return;
                
                addMessage(message, 'user');
                input.value = '';
                
                fetch(apiBaseUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        message: message,
                        patient_id: patientId,
                        patient_name: patientName
                    })
                })
                .then(response => response.json())
                .then(data => {
                    addMessage(data.reply, 'bot');
                    if (data.options && data.options.length > 0) {
                        showOptions(data.options);
                    }
                })
                .catch(error => {
                    addMessage('Sorry, I encountered an error. Please try again.', 'bot');
                    console.error('Error:', error);
                });
            }

            function addMessage(text, sender) {
                const chatMessages = document.getElementById('chat-messages');
                const messageDiv = document.createElement('div');
                messageDiv.className = `chat-message ${sender}-message`;
                const label = sender === 'bot' ? 'Assistant:' : 'You:';
                messageDiv.innerHTML = `<div class="message-content"><strong>${label}</strong> ${text}</div>`;
                chatMessages.appendChild(messageDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function showOptions(options) {
                const chatMessages = document.getElementById('chat-messages');
                const optionsDiv = document.createElement('div');
                optionsDiv.className = 'chat-options';
                optionsDiv.style.marginTop = '0.5rem';
                options.forEach(option => {
                    const button = document.createElement('button');
                    button.className = 'btn btn-sm btn-outline-primary me-2 mb-2';
                    button.textContent = option;
                    button.onclick = () => {
                        document.getElementById('chat-input').value = option;
                        sendMessage();
                    };
                    optionsDiv.appendChild(button);
                });
                chatMessages.appendChild(optionsDiv);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            </script>
            <?php endif; ?>

<?php
// Ensure session is started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/csrf.php';

// Initialize variables for login
$login_error = '';
$success_message = '';
$email_or_phone = '';
$login_success = false;

// Initialize variables for registration
$reg_errors = [];
$full_name = '';
$username = '';
$email = '';
$phone = '';
$gender = '';
$register_success = false;

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    require_once 'config.php';
    $email_or_phone = trim($_POST['email_or_phone']);
    $password = trim($_POST['password']);

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $login_error = 'Session expired, please try again.';
    } elseif (empty($email_or_phone) || empty($password)) {
        $login_error = 'Please fill in all fields.';
    } else {
        $sql = "SELECT id, username, email, password FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $email_or_phone, $email_or_phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $login_success = true; // Trigger success notification
                $_SESSION['show_login_success'] = true; // Set session flag for success notification
            } else {
                $login_error = 'Invalid password.';
            }
        } else {
            $login_error = 'No account found with that email or username.';
        }
        $stmt->close();
        // NOTE: do NOT close $conn here — header.php is included by pages
        // (event.php, index.php, ticket.php, etc.) that still need the
        // connection for queries that run after this include.
    }
}

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_submit'])) {
    require_once 'config.php';
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $reg_errors[] = 'Session expired, please try again.';
    }

    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $reg_errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $reg_errors[] = 'Invalid email format.';
    }

    if ($password !== $confirm_password) {
        $reg_errors[] = 'Passwords do not match.';
    }

    if (strlen($password) < 6) {
        $reg_errors[] = 'Password must be at least 6 characters long.';
    }

    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $reg_errors[] = 'Username or email already exists.';
    }
    $stmt->close();

    if (empty($reg_errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            $register_success = true; // Trigger success notification
            $_SESSION['show_register_success'] = true; // Set session flag for success notification
            $success_message = 'Registration successful! Please login.';
        } else {
            $reg_errors[] = 'Registration failed. Please try again.';
        }
        $stmt->close();
        // NOTE: do NOT close $conn here — same reason as the login block above.
    }
}
?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }

    body {
        background-color: #fafafa;
        color: #333;
        line-height: 1.6;
    }

    .container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    header {
        padding: 20px 0;
        background: linear-gradient(135deg, #000000, #1E40AF);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        position: sticky;
        top: 0;
        z-index: 100;
        animation: fadeIn 1s ease-in-out;
    }

    .header-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .logo {
        font-weight: 700;
        font-size: 24px;
    }

    .logo .preu {
        color: #FF0000;
    }

    .logo .tix {
        color: #0000FF;
    }

    .profile-section {
        display: flex;
        align-items: center;
    }

    .profile-image {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 8px;
        border: 2px solid #fff;
    }

    .user-name {
        color: #fff;
        font-weight: 500;
        font-size: 14px;
        margin-right: 15px;
    }

    .logout-btn {
        display: inline-block;
        text-decoration: none;
        color: #fff;
        font-weight: 500;
        font-size: 14px;
        background-color: rgba(255, 0, 0, 0.2);
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #FF0000;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .logout-btn:hover {
        background-color: #FF0000;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    nav ul {
        display: flex;
        list-style: none;
        align-items: center;
    }

    nav ul li {
        margin-left: 30px;
        position: relative;
    }

    nav ul li a {
        text-decoration: none;
        color: #fff;
        font-weight: 500;
        font-size: 16px;
        transition: color 0.3s ease;
    }

    nav ul li a:hover {
        color: #FF0000;
    }

    nav ul li a::after {
        content: '';
        position: absolute;
        width: 0;
        height: 2px;
        background-color: #FF0000;
        bottom: -5px;
        left: 50%;
        transform: translateX(-50%);
        transition: width 0.3s ease;
    }

    nav ul li a:hover::after {
        width: 100%;
    }

    .nav-btn.active {
        background: #fff;
        color: #0066ff;
        padding: 8px 20px;
        border-radius: 4px;
        font-weight: 500;
        text-decoration: none;
    }

    .nav-btn.active::after {
        display: none;
    }

    .nav-btn.active:hover {
        background: #fff;
        color: #0055dd;
    }

    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        font-size: 24px;
        color: #fff;
        cursor: pointer;
    }

    @keyframes fadeIn {
        0% { opacity: 0; transform: translateY(-20px); }
        100% { opacity: 1; transform: translateY(0); }
    }

    /* ===== Modal Styles (redesigned, brand colors preserved) ===== */
    :root {
        --pt-red: #FF0000;
        --pt-blue: #0000FF;
        --pt-blue-dark: #1E40AF;
        --pt-blue-mid: #0066ff;
    }

    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(10, 10, 20, 0.6);
        backdrop-filter: blur(3px);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        padding: 16px;
        animation: fadeIn 0.25s ease-out;
    }

    .auth-card {
        background-color: #fff;
        border-radius: 18px;
        width: 100%;
        max-width: 880px;
        max-height: 92vh;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        position: relative;
        overflow: hidden;
        display: flex;
        animation: modalPop 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    @keyframes modalPop {
        0% { opacity: 0; transform: scale(0.94) translateY(10px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }

    .auth-card .close {
        position: absolute;
        top: 14px;
        right: 18px;
        font-size: 26px;
        line-height: 1;
        cursor: pointer;
        color: #fff;
        z-index: 5;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        transition: background 0.2s ease;
    }

    .auth-card .close:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Decorative brand panel (kept red/blue identity) */
    .auth-aside {
        display: flex;
        flex-direction: column;
        justify-content: center;
        width: 38%;
        min-width: 260px;
        padding: 40px 32px;
        color: #fff;
        position: relative;
        overflow: hidden;
        background: linear-gradient(155deg, var(--pt-red) 0%, #7a0033 42%, #000000 55%, var(--pt-blue-dark) 78%, var(--pt-blue) 100%);
    }

    .auth-aside::before,
    .auth-aside::after {
        content: '';
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .auth-aside::before {
        width: 220px;
        height: 220px;
        top: -80px;
        right: -80px;
    }

    .auth-aside::after {
        width: 160px;
        height: 160px;
        bottom: -60px;
        left: -50px;
        background: rgba(255, 255, 255, 0.06);
    }

    .auth-aside .auth-logo {
        position: relative;
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 14px;
        letter-spacing: 0.5px;
    }

    .auth-aside .auth-logo .preu { color: #fff; }
    .auth-aside .auth-logo .tix { color: #FFD9D9; }

    .auth-aside p {
        position: relative;
        font-size: 15px;
        opacity: 0.9;
        line-height: 1.6;
    }

    .auth-aside .auth-tagline {
        position: relative;
        margin-top: 26px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        font-size: 13.5px;
        opacity: 0.95;
    }

    .auth-aside .auth-tagline span {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .auth-aside .auth-tagline i {
        width: 20px;
        text-align: center;
    }

    /* Form side */
    .auth-main {
        flex: 1;
        padding: 38px 40px;
        overflow-y: auto;
        max-height: 92vh;
    }

    .auth-tabs {
        display: flex;
        gap: 6px;
        background: #f1f2f6;
        border-radius: 10px;
        padding: 4px;
        margin-bottom: 26px;
    }

    .auth-tabs button {
        flex: 1;
        border: none;
        background: transparent;
        padding: 10px 0;
        font-size: 14.5px;
        font-weight: 600;
        color: #666;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.25s ease;
    }

    .auth-tabs button.active {
        background: linear-gradient(135deg, var(--pt-blue-mid), #000000);
        color: #fff;
        box-shadow: 0 4px 10px rgba(0, 102, 255, 0.25);
    }

    /* Success Notification Overlay */
    .success-notification {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 2000;
        justify-content: center;
        align-items: center;
    }

    .success-notification-content {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px;
        text-align: center;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .success-notification-content .checkmark {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: block;
        stroke: #4CAF50;
        stroke-width: 2;
        stroke-miterlimit: 10;
        box-shadow: inset 0px 0px 0px #4CAF50;
        animation: fill 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
    }

    .success-notification-content .checkmark__circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #4CAF50;
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }

    .success-notification-content .checkmark__check {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        stroke-width: 2;
        stroke: #4CAF50;
        fill: none;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }

    .success-notification-content p {
        margin-top: 20px;
        font-size: 18px;
        color: #333;
        font-weight: 500;
    }

    @keyframes stroke {
        100% { stroke-dashoffset: 0; }
    }

    @keyframes scale {
        0%, 100% { transform: none; }
        50% { transform: scale3d(1.1, 1.1, 1); }
    }

    @keyframes fill {
        100% { box-shadow: inset 0px 0px 0px 30px #4CAF50; }
    }

    /* Shared form field styles (login + register) */
    .auth-main .title {
        font-size: 22px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #111;
    }

    .auth-main .subtitle {
        font-size: 13.5px;
        color: #888;
        margin-bottom: 20px;
    }

    .field-group {
        margin-bottom: 16px;
    }

    .field-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 6px;
    }

    .field-input {
        position: relative;
        display: flex;
        align-items: center;
    }

    .field-input i.field-icon {
        position: absolute;
        left: 14px;
        color: #a9adb8;
        font-size: 15px;
        pointer-events: none;
    }

    .field-input input {
        width: 100%;
        height: 46px;
        padding: 0 14px 0 40px;
        border-radius: 10px;
        border: 1.5px solid #e4e6ec;
        font-size: 14.5px;
        background: #fafbfd;
        outline: none;
        transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    }

    .field-input input:focus {
        border-color: var(--pt-blue-mid);
        background: #fff;
        box-shadow: 0 0 0 3px rgba(0, 102, 255, 0.12);
    }

    .field-input .toggle-pass {
        position: absolute;
        right: 14px;
        color: #a9adb8;
        cursor: pointer;
        font-size: 14px;
        background: none;
        border: none;
    }

    .field-input .toggle-pass:hover { color: #666; }

    .field-row-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 14px;
    }

    .pass-strength {
        height: 4px;
        border-radius: 3px;
        background: #eceef3;
        margin-top: 8px;
        overflow: hidden;
    }

    .pass-strength-bar {
        height: 100%;
        width: 0%;
        border-radius: 3px;
        transition: width 0.25s ease, background 0.25s ease;
        background: var(--pt-red);
    }

    .pass-strength-label {
        font-size: 11.5px;
        color: #999;
        margin-top: 4px;
        display: block;
    }

    .field-hint {
        margin-top: 10px;
        text-align: right;
    }

    .field-hint a {
        color: var(--pt-blue-mid);
        font-size: 13px;
        text-decoration: none;
    }

    .field-hint a:hover { text-decoration: underline; }

    .auth-submit {
        width: 100%;
        height: 46px;
        border: none;
        border-radius: 10px;
        color: #fff;
        font-size: 15px;
        font-weight: 600;
        letter-spacing: 0.3px;
        cursor: pointer;
        margin-top: 18px;
        background: linear-gradient(120deg, var(--pt-red), var(--pt-blue-mid));
        background-size: 200% auto;
        transition: background-position 0.4s ease, transform 0.15s ease, box-shadow 0.2s ease;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .auth-submit:hover {
        background-position: right center;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .auth-submit:active { transform: translateY(0); }

    .auth-switch {
        text-align: center;
        margin-top: 18px;
        font-size: 13.5px;
        color: #777;
    }

    .auth-switch a {
        color: var(--pt-blue-mid);
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
    }

    .auth-switch a:hover { text-decoration: underline; }

    /* Gender selector, redesigned as chips */
    .gender-chip-group .gender-title {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #444;
        margin-bottom: 8px;
    }

    .gender-chip-group input[type="radio"] { display: none; }

    .gender-chip-group .category {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .gender-chip-group .category label {
        padding: 8px 16px;
        border-radius: 20px;
        border: 1.5px solid #e4e6ec;
        font-size: 13px;
        color: #666;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .gender-chip-group #dot-1:checked ~ .category label[for="dot-1"],
    .gender-chip-group #dot-2:checked ~ .category label[for="dot-2"],
    .gender-chip-group #dot-3:checked ~ .category label[for="dot-3"] {
        background: linear-gradient(135deg, var(--pt-blue-mid), #000000);
        border-color: transparent;
        color: #fff;
    }

    .error, .success {
        font-size: 13.5px;
        margin-top: 4px;
        margin-bottom: 10px;
        text-align: left;
        padding: 10px 12px;
        border-radius: 8px;
    }

    .error { color: #b3261e; background: #fdecea; }
    .success { color: #1e7d34; background: #e9f7ec; }

    @media (min-width: 769px) {
        nav ul {
            justify-content: flex-end;
        }
    }

    @media (max-width: 768px) {
        nav ul {
            display: none;
            position: absolute;
            top: 70px;
            left: 0;
            width: 100%;
            background-color: #1E40AF;
            flex-direction: column;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        nav ul.active {
            display: flex;
        }

        nav ul li {
            margin: 15px 0;
        }

        nav ul li a {
            color: #fff;
        }

        nav ul li a:hover {
            color: #FF0000;
        }

        .nav-btn.active {
            background: transparent;
            color: #fff;
            padding: 0;
        }

        .nav-btn.active:hover {
            color: #FF0000;
        }

        .mobile-menu-btn {
            display: block;
        }

        .logo {
            font-size: 20px;
        }

        .profile-section {
            flex-direction: column;
            align-items: center;
        }

        .profile-image {
            margin-right: 0;
            margin-bottom: 5px;
        }

        .user-name {
            font-size: 12px;
            margin-right: 0;
            margin-bottom: 5px;
        }

        .logout-btn {
            font-size: 12px;
            padding: 4px 10px;
        }

        .field-row-2 {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .auth-card {
            flex-direction: column;
            max-height: 94vh;
        }

        .auth-aside {
            width: 100%;
            min-width: 0;
            padding: 26px 28px 20px;
        }

        .auth-aside p,
        .auth-aside .auth-tagline {
            display: none;
        }

        .auth-main {
            padding: 26px 24px 30px;
            max-height: none;
        }
    }
</style>
<link rel="stylesheet" href="assets/css/theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
<header>
    <div class="container header-container">
        <div class="logo">
            <span class="preu">Preu</span><span class="tix">Tix</span>
        </div>
        <nav>
            <button class="mobile-menu-btn">☰</button>
            <ul id="nav-menu">
                <li><a href="index.php" class="nav-btn">Home</a></li>
                <li><a href="event.php" class="nav-btn">Events</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="history.php" class="nav-btn">History</a>
                    <?php else: ?>
                        <a href="#" class="nav-btn" onclick="alert('You must register and login to your account.'); return false;">History</a>
                    <?php endif; ?>
                </li>
                <li class="profile-section">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <img src="<?php echo htmlspecialchars(isset($_SESSION['profile_picture']) && !empty($_SESSION['profile_picture']) ? 'Uploads/profile/' . $_SESSION['profile_picture'] : 'assets/profile.png'); ?>" alt="Profile" class="profile-image">
                        <span class="user-name">Hello, <?php echo htmlspecialchars($_SESSION['username'] ?? $_SESSION['user_id']); ?></span>
                        <a href="logout.php" class="logout-btn">Log Out</a>
                    <?php else: ?>
                        <a href="#" class="nav-btn" onclick="openModal('login')">Login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </div>
</header>

<!-- Modal -->
<div id="authModal" class="modal">
    <div class="auth-card">
        <span class="close" onclick="closeModal()">×</span>

        <!-- Brand aside (keeps PreuTix red/blue identity) -->
        <div class="auth-aside">
            <div class="auth-logo"><span class="preu">Preu</span><span class="tix">Tix</span></div>
            <p>Platform resmi tiket event kampus President University. Gabung untuk pengalaman beli tiket yang lebih cepat dan aman.</p>
            <div class="auth-tagline">
                <span><i class="fas fa-bolt"></i> Checkout cepat & e-ticket instan</span>
                <span><i class="fas fa-shield-alt"></i> Data dan pembayaran aman</span>
                <span><i class="fas fa-history"></i> Riwayat tiket tersimpan rapi</span>
            </div>
        </div>

        <!-- Form side -->
        <div class="auth-main">
            <div class="auth-tabs">
                <button type="button" id="tabLogin" class="active" onclick="switchForm('login')">Login</button>
                <button type="button" id="tabRegister" onclick="switchForm('register')">Register</button>
            </div>

            <!-- Login Form -->
            <div id="loginForm" class="login-form">
                <div class="title">Selamat datang kembali</div>
                <div class="subtitle">Masuk untuk melanjutkan pembelian tiket kamu.</div>
                <?php if (!empty($login_error)): ?>
                    <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message) && !$login_success): ?>
                    <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <form action="" method="POST" autocomplete="off">
                    <input type="hidden" name="login_submit" value="1">
                    <?php csrf_field(); ?>
                    <div class="field-group">
                        <label for="login_email_or_phone">Email atau Username</label>
                        <div class="field-input">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" id="login_email_or_phone" name="email_or_phone" placeholder="Masukkan email atau username" value="<?php echo htmlspecialchars($email_or_phone); ?>" required />
                        </div>
                    </div>
                    <div class="field-group">
                        <label for="login_password">Password</label>
                        <div class="field-input">
                            <i class="fas fa-lock field-icon"></i>
                            <input type="password" id="login_password" name="password" placeholder="Masukkan password" required />
                            <button type="button" class="toggle-pass" onclick="togglePassword('login_password', this)"><i class="fas fa-eye"></i></button>
                        </div>
                        <div class="field-hint"><a href="forgot_password.php">Lupa password?</a></div>
                    </div>
                    <button type="submit" class="auth-submit">Login</button>
                    <div class="auth-switch">Belum punya akun? <a onclick="switchForm('register')">Daftar sekarang</a></div>
                </form>
            </div>

            <!-- Registration Form -->
            <div id="registerForm" class="register-form" style="display: none;">
                <div class="title">Buat akun baru</div>
                <div class="subtitle">Isi data berikut untuk mulai memesan tiket event.</div>
                <?php if (!empty($reg_errors)): ?>
                    <div class="error">
                        <?php foreach ($reg_errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="" method="POST" autocomplete="off">
                    <input type="hidden" name="register_submit" value="1">
                    <?php csrf_field(); ?>
                    <div class="field-row-2">
                        <div class="field-group">
                            <label for="reg_full_name">Nama Lengkap</label>
                            <div class="field-input">
                                <i class="fas fa-id-card field-icon"></i>
                                <input type="text" id="reg_full_name" name="full_name" placeholder="Nama lengkap" value="<?php echo htmlspecialchars($full_name); ?>" required>
                            </div>
                        </div>
                        <div class="field-group">
                            <label for="reg_username">Username</label>
                            <div class="field-input">
                                <i class="fas fa-at field-icon"></i>
                                <input type="text" id="reg_username" name="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="field-row-2">
                        <div class="field-group">
                            <label for="reg_email">Email</label>
                            <div class="field-input">
                                <i class="fas fa-envelope field-icon"></i>
                                <input type="email" id="reg_email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                        </div>
                        <div class="field-group">
                            <label for="reg_phone">No. HP</label>
                            <div class="field-input">
                                <i class="fas fa-phone field-icon"></i>
                                <input type="text" id="reg_phone" name="phone" placeholder="Nomor HP" value="<?php echo htmlspecialchars($phone); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="field-row-2">
                        <div class="field-group">
                            <label for="reg_password">Password</label>
                            <div class="field-input">
                                <i class="fas fa-lock field-icon"></i>
                                <input type="password" id="reg_password" name="password" placeholder="Password" required oninput="updatePasswordStrength(this.value)">
                                <button type="button" class="toggle-pass" onclick="togglePassword('reg_password', this)"><i class="fas fa-eye"></i></button>
                            </div>
                            <div class="pass-strength"><div class="pass-strength-bar" id="passStrengthBar"></div></div>
                            <span class="pass-strength-label" id="passStrengthLabel">Minimal 6 karakter</span>
                        </div>
                        <div class="field-group">
                            <label for="reg_confirm_password">Konfirmasi Password</label>
                            <div class="field-input">
                                <i class="fas fa-lock field-icon"></i>
                                <input type="password" id="reg_confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                                <button type="button" class="toggle-pass" onclick="togglePassword('reg_confirm_password', this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                    </div>
                    <div class="field-group gender-chip-group">
                        <input type="radio" name="gender" id="dot-1" value="Male" <?php echo $gender == 'Male' ? 'checked' : ''; ?>>
                        <input type="radio" name="gender" id="dot-2" value="Female" <?php echo $gender == 'Female' ? 'checked' : ''; ?>>
                        <input type="radio" name="gender" id="dot-3" value="Prefer not to say" <?php echo $gender == 'Prefer not to say' ? 'checked' : ''; ?>>
                        <span class="gender-title">Gender</span>
                        <div class="category">
                            <label for="dot-1">Male</label>
                            <label for="dot-2">Female</label>
                            <label for="dot-3">Prefer not to say</label>
                        </div>
                    </div>
                    <button type="submit" class="auth-submit">Register</button>
                    <div class="auth-switch">Sudah punya akun? <a onclick="switchForm('login')">Login sekarang</a></div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Success Notification Overlay -->
<div id="successNotification" class="success-notification">
    <div class="success-notification-content">
        <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
            <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
        </svg>
        <p id="successMessage"></p>
    </div>
</div>

<script>
// Mobile Menu Toggle
document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
    document.getElementById('nav-menu').classList.toggle('active');
});

// Navigation active state
const navLinks = document.querySelectorAll('.nav-btn');
navLinks.forEach(link => {
    link.addEventListener('click', function() {
        navLinks.forEach(nav => nav.classList.remove('active'));
        this.classList.add('active');
    });
});

const currentLocation = window.location.href;
navLinks.forEach(link => {
    if (link.href === currentLocation) {
        link.classList.add('active');
    }
});

// Modal Functions
function openModal(form) {
    document.getElementById('authModal').style.display = 'flex';
    switchForm(form);
}

function closeModal() {
    document.getElementById('authModal').style.display = 'none';
    switchForm('login'); // Reset to login form
}

function switchForm(form) {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const tabLogin = document.getElementById('tabLogin');
    const tabRegister = document.getElementById('tabRegister');
    loginForm.style.display = form === 'login' ? 'block' : 'none';
    registerForm.style.display = form === 'register' ? 'block' : 'none';
    if (tabLogin && tabRegister) {
        tabLogin.classList.toggle('active', form === 'login');
        tabRegister.classList.toggle('active', form === 'register');
    }
}

// Show/hide password
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Simple password strength meter (visual guidance only, not a security gate)
function updatePasswordStrength(value) {
    const bar = document.getElementById('passStrengthBar');
    const label = document.getElementById('passStrengthLabel');
    if (!bar || !label) return;

    let score = 0;
    if (value.length >= 6) score++;
    if (value.length >= 10) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;

    const levels = [
        { width: '0%', color: '#eceef3', text: 'Minimal 6 karakter' },
        { width: '20%', color: '#FF0000', text: 'Sangat lemah' },
        { width: '40%', color: '#ff7a00', text: 'Lemah' },
        { width: '60%', color: '#ffb800', text: 'Cukup' },
        { width: '80%', color: '#0066ff', text: 'Kuat' },
        { width: '100%', color: '#1e7d34', text: 'Sangat kuat' },
    ];

    const level = value.length === 0 ? levels[0] : levels[Math.min(score, 5)];
    bar.style.width = level.width;
    bar.style.background = level.color;
    label.textContent = level.text;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('authModal');
    if (event.target === modal) {
        closeModal();
    }
};

// Show Success Notification
function showSuccessNotification(message) {
    const notification = document.getElementById('successNotification');
    const messageElement = document.getElementById('successMessage');
    messageElement.textContent = message;
    notification.style.display = 'flex';
    setTimeout(() => {
        notification.style.display = 'none';
    }, 1000); // Display for 1 second
}

// Check for success flags on page load
window.onload = function() {
    <?php if (isset($_SESSION['show_login_success']) && $_SESSION['show_login_success']): ?>
        showSuccessNotification('Login Successful!');
        <?php unset($_SESSION['show_login_success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['show_register_success']) && $_SESSION['show_register_success']): ?>
        showSuccessNotification('Registration Successful!');
        <?php unset($_SESSION['show_register_success']); ?>
    <?php endif; ?>
};

// Handle success actions after form submission
<?php if ($login_success): ?>
    closeModal();
    setTimeout(() => {
        window.location.href = 'index.php';
    }, 6000); // Redirect after 1 second
<?php endif; ?>

<?php if ($register_success): ?>
    closeModal();
    setTimeout(() => {
        switchForm('login');
    }, 6000); // Switch to login form after 1 second
<?php endif; ?>
</script>
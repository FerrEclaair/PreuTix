<?php
// Start the session
session_start();

// Include database configuration
require_once 'config.php';

// Initialize variables for registration
$reg_errors = [];
$full_name = '';
$username = '';
$email = '';
$phone = '';
$gender = '';

// Initialize variables for newsletter
$newsletter_message = '';
$newsletter_error = false;

// Check if user is premium
$is_premium_user = false;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT premium FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $is_premium_user = $user['premium'] == 1;
    }
    $stmt->close();
}

// Process newsletter subscription
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['newsletter_subscribe'])) {
    $newsletter_email = trim($_POST['newsletter_email']);
    
    if (empty($newsletter_email)) {
        $newsletter_message = 'Please enter an email address.';
        $newsletter_error = true;
    } elseif (!filter_var($newsletter_email, FILTER_VALIDATE_EMAIL)) {
        $newsletter_message = 'Invalid email format.';
        $newsletter_error = true;
    } else {
        // Check if email already exists
        $sql = "SELECT id FROM newsletter_subscribers WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $newsletter_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $newsletter_message = 'This email is already subscribed.';
            $newsletter_error = true;
        } else {
            // Insert new subscriber
            $sql = "INSERT INTO newsletter_subscribers (email) VALUES (?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $newsletter_email);
            if ($stmt->execute()) {
                $newsletter_message = 'Thank you for subscribing!';
                $newsletter_error = false;
            } else {
                $newsletter_message = 'Failed to subscribe. Please try again.';
                $newsletter_error = true;
            }
        }
        $stmt->close();
    }
}

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

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

    if (empty($reg_errors)) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $reg_errors[] = 'Username or email already exists.';
        }
        $stmt->close();
    }

    if (empty($reg_errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $username, $email, $hashed_password);
        if ($stmt->execute()) {
            echo '<script>alert("Registration successful! Please log in."); document.getElementById("register-modal").style.display = "none"; document.getElementById("login-modal").style.display = "flex";</script>';
        } else {
            $reg_errors[] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}

// Initialize variables for login
$login_error = '';
$email_or_phone = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email_or_phone = trim($_POST['email_or_phone']);
    $password = trim($_POST['password']);

    if (empty($email_or_phone) || empty($password)) {
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
                echo '<script>window.location.href = "index.php";</script>'; // Redirect to index after login
            } else {
                $login_error = 'Invalid password.';
            }
        } else {
            $login_error = 'No account found with that email or username.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preutix - Your Event Ticketing Platform at President University</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        /* Reset and Base Styles */
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
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Hero Section */
        .hero {
            padding: 40px 0; /* Reduced from 60px to 40px for a subtler lift */
            position: relative;
            min-height: 400px;
            background-color: #fff;
            display: flex;
            align-items: center; /* Reverted to center for vertical alignment */
            justify-content: center;
        }

        .hero-content {
            display: flex;
            align-items: center; /* Reverted to center for vertical alignment */
            justify-content: space-between;
            width: 100%;
            gap: 30px;
        }

        .hero-left {
            flex: 1;
            padding-right: 15px;
        }

        .hero-left .subtitle {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 8px;
        }

        .hero-left .headline {
            font-size: 2.2rem;
            font-weight: 700;
            color: #111;
            margin-bottom: 15px;
        }

        .hero-left .description {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 15px;
        }

        .hero-left .btn-explore {
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: #fff;
            padding: 12px 26px;
            border-radius: var(--pt-radius-sm, 8px);
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            box-shadow: var(--pt-shadow-sm, 0 2px 8px rgba(0,0,0,0.1));
            transition: background-position 0.4s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hero-left .btn-explore:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: var(--pt-shadow-md, 0 8px 20px rgba(0,0,0,0.15));
        }

        .hero-center {
            flex: 1;
            position: relative;
            text-align: center;
        }

        .hero-center .feature-image {
            max-width: 100%;
            border-radius: var(--pt-radius-lg, 18px);
            overflow: hidden;
            position: relative;
            border: none;
            box-shadow: var(--pt-shadow-lg, 0 16px 40px rgba(0, 0, 0, 0.16));
        }

        .hero-center .feature-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        /* Features Section */
        .features {
            padding: 60px 0;
            background-color: white;
        }

        .section-title {
            font-size: 2.2rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 40px;
            color: #111;
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 64px;
            height: 4px;
            margin: 14px auto 0;
            border-radius: 999px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
        }

        .features-grid {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 30px;
        }

        .feature-card {
            flex: 1;
            min-width: 250px;
            background-color: #fff;
            padding: 30px 25px;
            border-radius: var(--pt-radius-md, 12px);
            text-align: center;
            box-shadow: var(--pt-shadow-sm, 0 2px 8px rgba(0,0,0,0.06));
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--pt-shadow-lg, 0 16px 40px rgba(0, 0, 0, 0.14));
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: #fff;
            font-size: 24px;
            transition: transform 0.3s;
        }

        .feature-card:hover .feature-icon {
            transform: rotate(360deg);
        }

        .feature-title {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #111;
        }

        .feature-desc {
            color: #666;
            font-size: 0.95rem;
        }

        /* About Section */
        .about {
            padding: 70px 0;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
        }

        .about-content {
            flex: 1;
            min-width: 300px;
            padding-left: 40px;
        }

        .about-image {
            flex: 1;
            min-width: 300px;
            border-radius: 12px;
            overflow: hidden;
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .about h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: #111;
        }

        .about p {
            margin-bottom: 20px;
            color: #555;
        }

        .btn-secondary {
            display: inline-block;
            padding: 11px 26px;
            background-color: transparent;
            color: var(--pt-blue-mid, #0066ff);
            border: 2px solid var(--pt-blue-mid, #0066ff);
            border-radius: var(--pt-radius-sm, 8px);
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            border-color: transparent;
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--pt-shadow-sm, 0 2px 8px rgba(0,0,0,0.1));
        }

        /* Events Section */
        .events {
            padding: 70px 0;
            background-color: #fafafa;
        }

        .event-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .event-card {
            background-color: white;
            border-radius: var(--pt-radius-md, 12px);
            overflow: hidden;
            box-shadow: var(--pt-shadow-sm, 0 5px 15px rgba(0, 0, 0, 0.08));
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .event-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--pt-shadow-lg, 0 16px 40px rgba(0, 0, 0, 0.16));
        }

        .event-img {
            height: 180px;
            overflow: hidden;
        }

        .event-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .event-card:hover .event-img img {
            transform: scale(1.1);
        }

        .event-content {
            padding: 20px;
        }

        .event-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #111;
        }

        .event-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        /* Impact & Testimonials Section */
        .impact-testimonials {
            padding: 70px 0;
            background-color: #fafafa;
        }

        .impact-testimonials-container {
            display: flex;
            flex-direction: column;
            gap: 50px;
        }

        .stats-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .stat-item {
            flex: 1;
            min-width: 150px;
            padding: 20px;
            transition: transform 0.3s;
        }

        .stat-item:hover {
            transform: scale(1.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #555;
            font-size: 1rem;
        }

        .testimonial {
            background-color: white;
            padding: 30px;
            border-radius: var(--pt-radius-md, 12px);
            box-shadow: var(--pt-shadow-sm, 0 5px 15px rgba(0, 0, 0, 0.05));
            display: flex;
            align-items: flex-start;
            margin-bottom: 30px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .testimonial:hover {
            transform: translateY(-4px);
            box-shadow: var(--pt-shadow-md, 0 8px 24px rgba(0,0,0,0.1));
        }

        .testimonial-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
            border: 3px solid #eef1ff;
        }

        .testimonial-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .testimonial-content {
            flex: 1;
        }

        .testimonial-text {
            font-style: italic;
            color: #555;
            margin-bottom: 15px;
        }

        .testimonial-name {
            font-weight: 600;
            color: #111;
        }

        .testimonial-position {
            color: #777;
            font-size: 0.9rem;
        }

        /* Footer Section */
        .footer-section {
            background: linear-gradient(180deg, #f0f5ff 0%, #eef2fb 100%);
            padding: 60px 0;
        }

        .newsletter-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 0 20px;
            margin-bottom: 40px;
            gap: 20px;
        }

        .newsletter-content {
            flex: 1;
            min-width: 250px;
        }

        .newsletter-content h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #111;
        }

        .newsletter-content p {
            color: #555;
            margin-bottom: 15px;
        }

        .newsletter-form {
            flex: 1;
            min-width: 300px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .newsletter-form form {
            display: flex;
            width: 100%;
            max-width: 400px;
        }

        .newsletter-input {
            flex: 1;
            padding: 12px 15px;
            border: 1.5px solid #dde1ec;
            border-right: none;
            border-radius: var(--pt-radius-sm, 8px) 0 0 var(--pt-radius-sm, 8px);
            font-size: 1rem;
            outline: none;
            background: #fff;
            transition: border-color 0.3s;
        }

        .newsletter-input:focus {
            border-color: var(--pt-blue-mid, #0066ff);
        }

        .newsletter-btn {
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            border: none;
            padding: 12px 22px;
            border-radius: 0 var(--pt-radius-sm, 8px) var(--pt-radius-sm, 8px) 0;
            font-weight: 600;
            cursor: pointer;
            transition: background-position 0.4s ease, transform 0.3s ease;
        }

        .newsletter-btn:hover {
            background-position: right center;
            transform: translateY(-2px);
        }

        .newsletter-notification {
            display: none;
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-size: 0.9rem;
            text-align: center;
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }

        .newsletter-notification.success {
            background-color: #e6ffe6;
            color: #006400;
            border: 1px solid #006400;
        }

        .newsletter-notification.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }

        footer {
            padding: 40px 0;
            background-color: #f0f5ff;
        }

        .footer-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 30px;
        }

        .footer-content {
            flex: 1;
            min-width: 250px;
        }

        .footer-content h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #111;
        }

        .footer-content p {
            color: #555;
            margin-bottom: 15px;
        }

        .footer-links {
            flex: 1;
            min-width: 150px;
        }

        .footer-links h4 {
            font-size: 1.2rem;
            margin-bottom: 15px;
            color: #111;
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links ul li {
            margin-bottom: 10px;
        }

        .footer-links ul li a {
            color: #555;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links ul li a:hover {
            color: #0066ff;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 25px 30px;
            border-radius: 5px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
            max-width: 700px;
            width: 90%;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content .title {
            font-size: 25px;
            font-weight: 500;
            position: relative;
        }

        .modal-content .title::before {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            height: 2.5px;
            width: 60px;
            border-radius: 5px;
            background: linear-gradient(135deg, #0066ff, #000000);
        }

        .modal-content .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            color: #333;
        }

        .modal-content p {
            margin-bottom: 20px;
            color: #666;
        }

        .modal-content .btn-action {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0066ff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
        }

        .modal-content .btn-action:hover {
            background-color: #0055dd;
        }

        .content form .user-details {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            margin: 20px 0 12px 0;
        }

        form .user-details .input-box {
            margin-bottom: 15px;
            width: calc(100% / 2 - 20px);
        }

        form .input-box span.details {
            display: block;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .user-details .input-box input {
            height: 45px;
            width: 100%;
            outline: none;
            font-size: 16px;
            border-radius: 5px;
            padding-left: 15px;
            border: 1px solid #ccc;
            border-bottom-width: 2px;
            transition: all 0.3s ease;
        }

        .user-details .input-box input:focus,
        .user-details .input-box input:valid {
            border-color: #0066ff;
        }

        form .gender-details .gender-title {
            font-size: 20px;
            font-weight: 500;
        }

        form .category {
            display: flex;
            width: 80%;
            margin: 14px 0;
            justify-content: space-between;
        }

        form .category label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        form .category label .dot {
            height: 18px;
            width: 18px;
            border-radius: 50%;
            margin-right: 10px;
            background: #d9d9d9;
            border: 5px solid transparent;
            transition: all 0.3s ease;
        }

        #dot-1:checked~.category label .one,
        #dot-2:checked~.category label .two,
        #dot-3:checked~.category label .three {
            background: #0066ff;
            border-color: #d9d9d9;
        }

        form input[type="radio"] {
            display: none;
        }

        form .button {
            height: 45px;
            margin: 35px 0;
        }

        form .button input {
            height: 100%;
            width: 100%;
            border-radius: 5px;
            border: none;
            color: #fff;
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #0066ff, #000000);
        }

        form .button input:hover {
            background: linear-gradient(-135deg, #0066ff, #000000);
        }

        .error {
            color: red;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }

        /* Login Modal Styles */
        .login-modal .row {
            height: 60px;
            margin-top: 15px;
            position: relative;
        }

        .login-modal .row input {
            height: 100%;
            width: 100%;
            outline: none;
            padding-left: 70px;
            border-radius: 5px;
            border: 1px solid lightgrey;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .login-modal .row input:focus {
            border-color: #0066ff;
        }

        .login-modal .row input::placeholder {
            color: #999;
        }

        .login-modal .row i {
            position: absolute;
            width: 55px;
            height: 100%;
            color: #fff;
            font-size: 22px;
            background: linear-gradient(135deg, #0066ff, #000000);
            border: 1px solid #0066ff;
            border-radius: 5px 0 0 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-modal .pass a {
            color: #0066ff;
            font-size: 17px;
            text-decoration: none;
        }

        .login-modal .pass a:hover {
            text-decoration: underline;
        }

        .login-modal .button input {
            margin-top: 20px;
            color: #fff;
            font-size: 20px;
            font-weight: 500;
            padding-left: 0px;
            background: linear-gradient(135deg, #0066ff, #000000);
            border: 1px solid #0066ff;
            cursor: pointer;
        }

        .login-modal .button input:hover {
            background: linear-gradient(135deg, #0066ff, #000000);
        }

        .login-modal .signup-link {
            text-align: center;
            margin-top: 45px;
            font-size: 17px;
        }

        .login-modal .signup-link a {
            color: #0066ff;
            text-decoration: none;
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .hero-content {
                flex-direction: column;
                text-align: center;
            }
            .hero-left, .hero-right {
                padding: 0;
            }
            .hero-center {
                margin: 15px 0;
            }
        }

        @media (max-width: 768px) {
            .hero .headline {
                font-size: 1.8rem;
            }
            .section-title {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 584px) {
            form .user-details .input-box {
                margin-bottom: 15px;
                width: 100%;
            }
            form .category {
                width: 100%;
            }
            .content form .user-details {
                max-height: 300px;
                overflow-y: scroll;
            }
            .user-details::-webkit-scrollbar {
                width: 5px;
            }
        }

        @media (max-width: 576px) {
            .newsletter-form form {
                flex-direction: column;
                align-items: stretch;
            }
            .newsletter-input {
                border-radius: 6px;
                border-right: 1px solid #ddd;
                margin-bottom: 10px;
            }
            .newsletter-btn {
                border-radius: 6px;
                padding: 12px;
            }
            .newsletter-notification {
                margin-left: 0;
            }
            .hero .headline {
                font-size: 1.3rem;
            }
            .hero .description {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 459px) {
            .modal-content .content .category {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Include Header -->
    <?php include 'header.php'; ?>

    <!-- Premium Modal -->
    <div class="modal" id="premium-modal">
        <div class="modal-content">
            <span class="close" id="close-premium-modal">×</span>
            <h2>Premium Membership</h2>
            <?php if (!isset($_SESSION['username'])): ?>
                <p>Please login to access Premium features.</p>
                <a href="#" class="btn-action" id="login-from-modal">Login</a>
            <?php elseif ($is_premium_user): ?>
                <p>You are already a Premium member! Enjoy exclusive features like promoting your events in the Hero Section.</p>
                <a href="advertise.php" class="btn-action">Promote an Event</a>
            <?php else: ?>
                <p>Upgrade to Premium to unlock exclusive features, such as promoting your events in the Hero Section for maximum visibility!</p>
                <p>Please contact our admin at support@preutix.com to upgrade your account to Premium.</p>
                <a href="mailto:support@preutix.com" class="btn-action">Contact Admin</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <div class="hero-left">
                    <div class="subtitle">Ticketing</div>
                    <div class="headline">Connect, Celebrate, Create – The Future of Events!</div>
                    <p class="description">Life is full of experiences waiting to be discovered. With our platform, you can easily find and attend events that match your interests, connect with like-minded individuals, and create lasting memories. Start your journey here!.</p>
                    <a href="event.php" class="btn-explore">Explore</a>
                </div>
                <div class="hero-center">
                    <div class="feature-image">
                        <img src="assets/Night PUCC.heic" alt="Featured Event">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Your Gateway to Seamless Events</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3 class="feature-title">Instant Booking</h3>
                    <p class="feature-desc">Find and book tickets. Our platform ensures quick and easy transactions.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📱</div>
                    <h3 class="feature-title">Event Discovery</h3>
                    <p class="feature-desc">Discover local and global events that match your interests and preferences.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🎫</div>
                    <h3 class="feature-title">Seamless Ticketing</h3>
                    <p class="feature-desc">Digital tickets delivered instantly to your device, no printing required.</p>
                </div>
            </div>
            <div class="text-center" style="margin-top: 40px; text-align: center;">
                <a href="#footer" class="btn-primary" style="display: inline-block; width: auto;">Learn More</a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <div class="about">
                <div class="about-image">
                    <img src="assets/PUCC.heic" alt="Event planning meeting">
                </div>
                <div class="about-content">
                    <h2>We Are the Ultimate Ticketing Platform for Unforgettable Events!</h2>
                    <p>Preutix has revolutionized the way people experience events, offering a comprehensive platform that enables seamless ticket purchasing, management, and access. Our cutting-edge technology ensures security, while providing event organizers with powerful tools.</p>
                    <p>Founded in 2024, we've already helped thousands of events achieve success through our innovative platform.</p>
                    <a href="#footer" class="btn-secondary">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Events Section -->
    <section class="events">
        <div class="container">
            <h2 class="section-title">Explore the Moments Beyond</h2>
            <div class="event-cards">
                <?php
                $sql = "SELECT * FROM events LIMIT 3";
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    ?>
                    <div class="event-card">
                        <div class="event-img">
                            <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                        </div>
                        <div class="event-content">
                            <h3 class="event-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                            <p class="event-details"><?php echo htmlspecialchars($row['details']); ?></p>
                            <a href="event.php?id=<?php echo $row['id']; ?>" class="btn-secondary">Get Tickets</a>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </section>

    <!-- Impact & Testimonials Section -->
    <section class="impact-testimonials">
        <div class="container">
            <h2 class="section-title">Impact & Testimonials</h2>
            <div class="impact-testimonials-container">
                <div class="stats-container">
                    <div class="stat-item">
                        <div class="stat-number" id="stat-events">200</div>
                        <div class="stat-label">Events</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="stat-organizers">500</div>
                        <div class="stat-label">Organizers</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="stat-tickets">100000</div>
                        <div class="stat-label">Tickets Sold</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" id="stat-users">1000</div>
                        <div class="stat-label">Happy Users</div>
                    </div>
                </div>
                <div class="testimonials-content">
                    <?php
                    $sql = "SELECT * FROM testimonials LIMIT 2";
                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <div class="testimonial">
                            <div class="testimonial-avatar">
                                <img src="<?php echo htmlspecialchars($row['avatar']); ?>" alt="User avatar">
                            </div>
                            <div class="testimonial-content">
                                <p class="testimonial-text">"<?php echo htmlspecialchars($row['text']); ?>"</p>
                                <div class="testimonial-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="testimonial-position"><?php echo htmlspecialchars($row['position']); ?></div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer Section with Newsletter -->
    <section class="footer-section" id="footer">
        <div class="container">
            <div class="newsletter">
                <div class="newsletter-container">
                    <div class="newsletter-content">
                        <h3>Sign up to our newsletter</h3>
                        <p>Get the latest updates on events and exclusive offers</p>
                    </div>
                    <div class="newsletter-form">
                        <form id="newsletter-form" method="POST">
                            <input type="hidden" name="newsletter_subscribe" value="1">
                            <input type="email" class="newsletter-input" name="newsletter_email" placeholder="Your email address" required>
                            <button type="submit" class="newsletter-btn">Subscribe</button>
                        </form>
                        <div class="newsletter-notification" id="newsletter-notification" style="display: <?php echo !empty($newsletter_message) ? 'block' : 'none'; ?>;" class="<?php echo $newsletter_error ? 'error' : 'success'; ?>">
                            <?php echo htmlspecialchars($newsletter_message); ?>
                        </div>
                    </div>
                </div>
            </div>
            <footer>
                <div class="footer-container">
                    <div class="footer-content">
                        <h3>Preutix</h3>
                        <p>Your ultimate event ticketing platform. Seamless booking, instant delivery, and unforgettable experiences.</p>
                        <p>© 2025 Preutix. All rights reserved.</p>
                    </div>
                    <div class="footer-links">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="event.php">Events</a></li>
                            <li><a href="#footer">About</a></li>
                        </ul>
                    </div>
                </div>
            </footer>
        </div>
    </section>

    <!-- Register Modal -->
    <div id="register-modal" class="modal">
        <div class="modal-content">
            <span class="close">×</span>
            <div class="title">Registration</div>
            <div class="content">
                <?php if (!empty($reg_errors)): ?>
                    <div class="error">
                        <?php foreach ($reg_errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <form action="" method="POST">
                    <input type="hidden" name="register" value="1">
                    <div class="user-details">
                        <div class="input-box">
                            <span class="details">Full Name</span>
                            <input type="text" name="full_name" placeholder="Enter your name" value="<?php echo htmlspecialchars($full_name); ?>" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Username</span>
                            <input type="text" name="username" placeholder="Enter your username" value="<?php echo htmlspecialchars($username); ?>" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Email</span>
                            <input type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Phone Number</span>
                            <input type="text" name="phone" placeholder="Enter your number" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Password</span>
                            <input type="password" name="password" placeholder="Enter your password" required>
                        </div>
                        <div class="input-box">
                            <span class="details">Confirm Password</span>
                            <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                        </div>
                    </div>
                    <div class="gender-details">
                        <input type="radio" name="gender" id="dot-1" value="Male" <?php echo $gender == 'Male' ? 'checked' : ''; ?>>
                        <input type="radio" name="gender" id="dot-2" value="Female" <?php echo $gender == 'Female' ? 'checked' : ''; ?>>
                        <input type="radio" name="gender" id="dot-3" value="Prefer not to say" <?php echo $gender == 'Prefer not to say' ? 'checked' : ''; ?>>
                        <span class="gender-title">Gender</span>
                        <div class="category">
                            <label for="dot-1">
                                <span class="dot one"></span>
                                <span class="gender">Male</span>
                            </label>
                            <label for="dot-2">
                                <span class="dot two"></span>
                                <span class="gender">Female</span>
                            </label>
                            <label for="dot-3">
                                <span class="dot three"></span>
                                <span class="gender">Prefer not to say</span>
                            </label>
                        </div>
                    </div>
                    <div class="button">
                        <input type="submit" value="Register">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="modal login-modal">
        <div class="modal-content">
            <span class="close">×</span>
            <div class="title"><span>Login Form</span></div>
            <form action="" method="POST">
                <div class="row">
                    <i class="fas fa-user"></i>
                    <input type="text" name="email_or_phone" placeholder="Email or Phone" value="<?php echo htmlspecialchars($email_or_phone); ?>" required />
                </div>
                <div class="row">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required />
                </div>
                <?php if (!empty($login_error)): ?>
                    <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
                <?php endif; ?>
                <div class="pass"><a href="forgot_password.php">Forgot password?</a></div>
                <div class="row button">
                    <input type="submit" name="login" value="Login" />
                </div>
                <div class="signup-link">Not a member? <a href="#" id="switch-to-register">Register now</a></div>
            </form>
        </div>
    </div>

    <script>
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

        // Mobile Menu Toggle
        document.querySelector('.mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });

        // Modal Functionality
        const registerModal = document.getElementById('register-modal');
        const loginModal = document.getElementById('login-modal');
        const registerBtn = document.querySelector('.nav-btn[href="signup.php"]');
        const loginBtn = document.querySelector('.nav-btn[href="signup.php"]'); // Temporary, as header may not have direct login-btn
        const closeBtns = document.getElementsByClassName('close');
        const switchToRegister = document.getElementById('switch-to-register');

        registerBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            registerModal.style.display = 'flex';
        });
        loginBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            loginModal.style.display = 'flex';
        });

        switchToRegister?.addEventListener('click', (e) => {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'flex';
        });

        Array.from(closeBtns).forEach(btn => {
            btn.addEventListener('click', () => {
                registerModal.style.display = 'none';
                loginModal.style.display = 'none';
                premiumModal.style.display = 'none';
            });
        });

        window.addEventListener('click', (e) => {
            if (e.target === registerModal) {
                registerModal.style.display = 'none';
            }
            if (e.target === loginModal) {
                loginModal.style.display = 'none';
            }
            if (e.target === premiumModal) {
                premiumModal.style.display = 'none';
            }
        });

        // Premium Modal Logic
        const premiumBtn = document.getElementById('premium-btn');
        const premiumModal = document.getElementById('premium-modal');
        const closePremiumModal = document.getElementById('close-premium-modal');
        const loginFromModal = document.getElementById('login-from-modal');

        premiumBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            premiumModal.style.display = 'flex';
        });

        closePremiumModal?.addEventListener('click', function() {
            premiumModal.style.display = 'none';
        });

        loginFromModal?.addEventListener('click', function(e) {
            e.preventDefault();
            premiumModal.style.display = 'none';
            loginModal.style.display = 'flex';
        });

        // Newsletter Form Submission
        document.getElementById('newsletter-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const notification = document.getElementById('newsletter-notification');
            
            try {
                const response = await fetch('index.php', {
                    method: 'POST',
                    body: new FormData(form)
                });
                
                const text = await response.text();
                // Extract message from response (since it's a full HTML page)
                const parser = new DOMParser();
                const doc = parser.parseFromString(text, 'text/html');
                const messageElement = doc.querySelector('.newsletter-notification');
                
                if (messageElement) {
                    notification.textContent = messageElement.textContent;
                    notification.style.display = 'block';
                    notification.className = 'newsletter-notification ' + 
                        (messageElement.classList.contains('error') ? 'error' : 'success');
                    
                    // Clear input on success
                    if (!messageElement.classList.contains('error')) {
                        form.reset();
                    }
                    
                    // Auto-hide notification after 3 seconds
                    setTimeout(() => {
                        notification.style.display = 'none';
                    }, 3000);
                }
            } catch (error) {
                notification.textContent = 'An error occurred. Please try again.';
                notification.className = 'newsletter-notification error';
                notification.style.display = 'block';
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
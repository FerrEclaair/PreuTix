<?php
// Start the session
session_start();
require_once __DIR__ . '/csrf.php';

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include PHPMailer classes manually
$phpmailer_path = __DIR__ . '/PHPMailer-master/src';
if (!file_exists($phpmailer_path . '/PHPMailer.php')) {
    die('Error: PHPMailer not found at ' . $phpmailer_path . '/PHPMailer.php. Please ensure the PHPMailer files are in the correct directory.');
}
require $phpmailer_path . '/Exception.php';
require $phpmailer_path . '/PHPMailer.php';
require $phpmailer_path . '/SMTP.php';

// Use PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include database configuration
if (!file_exists('config.php')) {
    die('Error: config.php not found. Please ensure it exists in the same directory.');
}
require_once 'config.php';

// Verify database connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Initialize variables
$email = '';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Validate CSRF + email
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $error_message = 'Session expired, please try again.';
    } elseif (empty($email)) {
        $error_message = 'Please enter your email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } else {
        // Check if email exists in users table
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $error_message = 'Database error. Please try again later.';
            error_log('Prepare failed: ' . $conn->error);
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Store token in password_resets table
                $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE token = ?, expires_at = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $error_message = 'Database error. Please try again later.';
                    error_log('Prepare failed: ' . $conn->error);
                } else {
                    $stmt->bind_param('sssss', $email, $token, $expires_at, $token, $expires_at);
                    if ($stmt->execute()) {
                        // Send reset email using PHPMailer
                        $mail = new PHPMailer(true);
                        try {
                            // SMTP settings
                            $mail->isSMTP();
                            $mail->Host = 'smtp.mailtrap.io'; // Replace with your SMTP host
                            $mail->SMTPAuth = true;
                            $mail->Username = 'yose1304@gmail.com'; // Replace with your SMTP email
                            $mail->Password = '1234'; // Replace with your app-specific password
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = 587;

                            // Email settings
                            $mail->setFrom('no-reply@preutix.com', 'Preutix');
                            $mail->addAddress($email);
                            $mail->isHTML(true);
                            $mail->Subject = 'Password Reset Request';
                            $reset_link = "http://localhost/php_preutix/reset_password.php?token=" . $token; // Replace with your domain
                            $mail->Body = "
                                <h2>Password Reset Request</h2>
                                <p>You requested to reset your password. Click the link below to set a new password:</p>
                                <p><a href='$reset_link'>Reset Password</a></p>
                                <p>This link will expire in 1 hour.</p>
                                <p>If you did not request this, ignore this email.</p>
                            ";
                            $mail->AltBody = "You requested to reset your password. Visit this link to set a new password: $reset_link\nThis link will expire in 1 hour.";

                            $mail->send();
                            $success_message = 'A password reset link has been sent to your email.';
                        } catch (Exception $e) {
                            $error_message = 'Failed to send email. Error: ' . htmlspecialchars($mail->ErrorInfo);
                            error_log('PHPMailer error: ' . $mail->ErrorInfo);
                        }
                    } else {
                        $error_message = 'Failed to generate reset token.';
                        error_log('Insert failed: ' . $stmt->error);
                    }
                }
            } else {
                $error_message = 'No account found with that email.';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Preutix</title>
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
        }

        .body-kontainer {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            padding: 15px;
            background: #fafafa;
            overflow: hidden;
        }

        .wrapper {
            max-width: 500px;
            width: 100%;
            background: #fff;
            border-radius: 5px;
            box-shadow: 0px 4px 10px 1px rgba(0, 0, 0, 0.1);
        }

        .wrapper .title {
            height: 120px;
            background: linear-gradient(135deg, #0066ff, #000000);
            border-radius: 5px 5px 0 0;
            color: #fff;
            font-size: 30px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wrapper form {
            padding: 25px 35px;
        }

        .wrapper form .row {
            height: 60px;
            margin-top: 15px;
            position: relative;
        }

        .wrapper form .row input {
            height: 100%;
            width: 100%;
            outline: none;
            padding-left: 70px;
            border-radius: 5px;
            border: 1px solid lightgrey;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        form .row input:focus {
            border-color: #0066ff;
        }

        form .row input::placeholder {
            color: #999;
        }

        .wrapper form .row i {
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

        .wrapper form .button input {
            margin-top: 20px;
            color: #fff;
            font-size: 20px;
            font-weight: 500;
            padding-left: 0px;
            background: linear-gradient(135deg, #0066ff, #000000);
            border: 1px solid #0066ff;
            cursor: pointer;
            width: 100%;
        }

        form .button input:hover {
            background: linear-gradient(135deg, #0055dd, #000000);
        }

        .wrapper form .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 17px;
        }

        .wrapper form .login-link a {
            color: #0066ff;
            text-decoration: none;
        }

        .wrapper form .login-link a:hover {
            text-decoration: underline;
        }

        .error, .success {
            font-size: 16px;
            margin-top: 10px;
            text-align: center;
        }

        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
    <div class="body-kontainer">
        <div class="wrapper">
            <div class="title"><span>Forgot Password</span></div>
            <form action="forgot_password.php" method="POST">
                <?php csrf_field(); ?>
                <div class="row">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required />
                </div>
                <?php if (!empty($error_message)): ?>
                    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)): ?>
                    <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <div class="row button">
                    <input type="submit" value="Send Reset Link" />
                </div>
                <div class="login-link">Back to <a href="signup.php">Login</a></div>
            </form>
        </div>
    </div>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>
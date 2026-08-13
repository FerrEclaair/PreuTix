<?php
// Start the session
session_start();

// Include database configuration
require_once 'config.php';

// Initialize variables
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$new_password = '';
$confirm_password = '';
$error_message = '';
$success_message = '';
$token_valid = false;

// Generate CSRF token for form submission
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Validate token on page load
if (empty($token)) {
    $error_message = 'No token provided.';
} else {
    $sql = "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $error_message = 'Database error. Please try again later.';
        error_log('Prepare failed: ' . $conn->error);
    } else {
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $token_valid = true;
        } else {
            $error_message = 'Invalid or expired token.';
        }
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $token_valid) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = 'CSRF token validation failed.';
    } else {
        $new_password = trim($_POST['new_password']);
        $confirm_password = trim($_POST['confirm_password']);
        $token = trim($_POST['token']);

        // Validate passwords
        if (empty($new_password) || empty($confirm_password)) {
            $error_message = 'Please fill in all fields.';
        } elseif ($new_password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($new_password) < 8) {
            $error_message = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Za-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
            $error_message = 'Password must contain at least one letter and one number.';
        } else {
            // Verify token again (in case of race conditions)
            $sql = "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW()";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error_message = 'Database error. Please try again later.';
                error_log('Prepare failed: ' . $conn->error);
            } else {
                $stmt->bind_param('s', $token);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $email = $row['email'];

                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password = ? WHERE email = ?";
                    $stmt = $conn->prepare($sql);
                    if (!$stmt) {
                        $error_message = 'Database error. Please try again later.';
                        error_log('Prepare failed: ' . $conn->error);
                    } else {
                        $stmt->bind_param('ss', $hashed_password, $email);
                        if ($stmt->execute()) {
                            // Delete the used token
                            $sql = "DELETE FROM password_resets WHERE token = ?";
                            $stmt = $conn->prepare($sql);
                            if (!$stmt) {
                                error_log('Prepare failed: ' . $conn->error);
                            } else {
                                $stmt->bind_param('s', $token);
                                $stmt->execute();
                            }

                            // Store success message in session and redirect
                            $_SESSION['success_message'] = 'Your password has been reset. You can now log in.';
                            header('Location: signup.php');
                            exit();
                        } else {
                            $error_message = 'Failed to update password. Please try again.';
                            error_log('Update failed: ' . $stmt->error);
                        }
                    }
                } else {
                    $error_message = 'Invalid or expired token.';
                }
                $stmt->close();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Preutix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
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

        .wrapper .message {
            padding: 25px 35px;
            text-align: center;
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
            <div class="title"><span>Reset Password</span></div>
            <?php if (!empty($error_message) && !$token_valid): ?>
                <div class="message">
                    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                    <div class="login-link">Back to <a href="signup.php">Login</a></div>
                </div>
            <?php else: ?>
                <form action="reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                    <div class="row">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" placeholder="New Password" required />
                    </div>
                    <div class="row">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required />
                    </div>
                    <?php if (!empty($error_message)): ?>
                        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                    <div class="row button">
                        <input type="submit" value="Reset Password" />
                    </div>
                    <div class="login-link">Back to <a href="signup.php">Login</a></div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>
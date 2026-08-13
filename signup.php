<?php
// Start the session
session_start();
require_once __DIR__ . '/csrf.php';

// Include database configuration
require_once 'config.php';

// Initialize variables
$login_error = '';
$success_message = '';
$email_or_phone = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_or_phone = trim($_POST['email_or_phone']);
    $password = trim($_POST['password']);

    // Validate input
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $login_error = 'Session expired, please try again.';
    } elseif (empty($email_or_phone) || empty($password)) {
        $login_error = 'Please fill in all fields.';
    } else {
        // Check if user exists
        $sql = "SELECT id, username, email, password FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $email_or_phone, $email_or_phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                // Redirect to home page
                header('Location: index.php');
                exit();
            } else {
                $login_error = 'Invalid password.';
            }
        } else {
            $login_error = 'No account found with that email or username.';
        }
        $stmt->close();
    }
}

// Check for success message from reset_password.php
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Preutix</title>
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

        .wrapper form .pass {
            margin-top: 12px;
        }

        .wrapper form .pass a {
            color: #0066ff;
            font-size: 17px;
            text-decoration: none;
        }

        .wrapper form .pass a:hover {
            text-decoration: underline;
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
        }

        form .button input:hover {
            background: linear-gradient(135deg, #0066ff, #000000);
        }

        .wrapper form .signup-link {
            text-align: center;
            margin-top: 45px;
            font-size: 17px;
        }

        .wrapper form .signup-link a {
            color: #0066ff;
            text-decoration: none;
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
    <!-- Header -->
    <?php include 'header.php'; ?>

    <div class="body-kontainer">
        <div class="wrapper">
            <div class="title"><span>Login Form</span></div>
            <form action="signup.php" method="POST">
                <?php csrf_field(); ?>
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
                <?php if (!empty($success_message)): ?>
                    <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                <div class="pass"><a href="forgot_password.php">Forgot password?</a></div>
                <div class="row button">
                    <input type="submit" value="Login" />
                </div>
                <div class="signup-link">Not a member? <a href="registration.php">Register now</a></div>
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
        document.querySelector('.mobile-menu-btn').addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });
    </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>
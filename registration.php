<?php
// Start the session
session_start();
require_once __DIR__ . '/csrf.php';

// Include database configuration
require_once 'config.php';

// Initialize variables
$errors = [];
$full_name = '';
$username = '';
$email = '';
$phone = '';
$gender = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $gender = isset($_POST['gender']) ? trim($_POST['gender']) : '';

    // Validation
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Session expired, please try again.';
    }

    if (empty($full_name) || empty($username) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $errors[] = 'All fields are required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    // Check if username or email already exists
    $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $errors[] = 'Username or email already exists.';
    }
    $stmt->close();

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sss', $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            // Redirect to login page on successful registration
            header('Location: signup.php');
            exit();
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration - Preutix</title>
    <link rel="stylesheet" href="assets/css/theme.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap');
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 10px;
            background: #fff;
        }

        .container {
            max-width: 700px;
            width: 100%;
            background-color: #fff;
            padding: 25px 30px;
            border-radius: 5px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.15);
            margin: 100px 0px 0px 400px;
        }
        .container .title {
            font-size: 25px;
            font-weight: 500;
            position: relative;
        }
        .container .title::before {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            height: 2.5px;
            width: 60px;
            border-radius: 5px;
            background: linear-gradient(135deg, #0066ff, #000000);
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
        /* Responsive media query code for mobile devices */
        @media(max-width: 584px) {
            .container {
                max-width: 100%;
            }
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
        @media(max-width: 459px) {
            .container .content .category {
                flex-direction: column;
            }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="container">
        <div class="title">Registration</div>
        <div class="content">
            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form action="registration.php" method="POST">
                <?php csrf_field(); ?>
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
</body>
</html>
<?php
// Close database connection
$conn->close();
?>
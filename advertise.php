<?php
// Start the session
session_start();
require_once __DIR__ . '/csrf.php';

// Include database configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Handle event submission
$add_event_errors = [];
$add_event_success = '';
$title = '';
$description = '';
$details = '';
$price = '';
$event_category = '';
$location = 'President University';
$image = '';

// Check if the events table has the required columns, and add them if missing
$required_columns = [
    'description' => 'TEXT',
    'category' => 'VARCHAR(50)',
    'created_by' => 'INT'
];
foreach ($required_columns as $column => $type) {
    $column_check = $conn->query("SHOW COLUMNS FROM events LIKE '$column'");
    if ($column_check->num_rows == 0) {
        $alter_query = "ALTER TABLE events ADD COLUMN $column $type";
        if (!$conn->query($alter_query)) {
            $add_event_errors[] = "Failed to add column $column to events table: " . $conn->error;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_event'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $details = trim($_POST['details']);
    $price = trim($_POST['price']);
    $event_category = trim($_POST['category']);
    $location = trim($_POST['location']);

    // Validation
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $add_event_errors[] = 'Session expired, please try again.';
    }

    if (empty($title) || empty($description) || empty($details) || empty($price) || empty($event_category) || empty($location)) {
        $add_event_errors[] = 'All fields are required.';
    }

    if (!is_numeric($price) || $price <= 0) {
        $add_event_errors[] = 'Price must be a positive number.';
    }

    // Image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = 'assets/uploads/';
        $image_name = time() . '_' . basename($_FILES['image']['name']);
        $image_path = $upload_dir . $image_name;

        // Ensure upload directory exists and is writable
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                $add_event_errors[] = 'Failed to create upload directory.';
            }
        }
        if (!is_writable($upload_dir)) {
            $add_event_errors[] = 'Upload directory is not writable. Please set permissions (e.g., chmod 755 assets/uploads).';
        }

        if (empty($add_event_errors)) {
            // Validate image
            $image_type = mime_content_type($_FILES['image']['tmp_name']);
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($image_type, $allowed_types)) {
                $add_event_errors[] = 'Only JPEG, PNG, and GIF images are allowed.';
            }

            if ($_FILES['image']['size'] > 5 * 1024 * 1024) { // 5MB limit
                $add_event_errors[] = 'Image size must not exceed 5MB.';
            }

            if (empty($add_event_errors)) {
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_path)) {
                    $add_event_errors[] = 'Failed to upload image.';
                } else {
                    $image = $image_path;
                }
            }
        }
    } else {
        $add_event_errors[] = 'Please upload an event image.';
    }

    // Insert into database if no errors
    if (empty($add_event_errors)) {
        $sql = "INSERT INTO events (title, description, details, price, category, location, image, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $add_event_errors[] = 'Database error: ' . $conn->error;
        } else {
            $stmt->bind_param('sssdsssi', $title, $description, $details, $price, $event_category, $location, $image, $_SESSION['user_id']);
            if ($stmt->execute()) {
                $add_event_success = 'Event added successfully! It will be reviewed and published soon.';
                $title = $description = $details = $price = $event_category = $location = $image = '';
            } else {
                $add_event_errors[] = 'Failed to add event: ' . $stmt->error;
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
    <title>Advertise Your Event - Preutix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/theme.css">
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
            padding: 50px 20px;
        }

        .advertise-form {
            max-width: 640px;
            margin: 0 auto;
            padding: 40px;
            background-color: #fff;
            border-radius: var(--pt-radius-lg, 18px);
            box-shadow: var(--pt-shadow-md, 0 8px 24px rgba(0,0,0,0.1));
            text-align: center;
        }

        .advertise-form h2 {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: #111;
        }

        .advertise-form p.advertise-sub {
            color: #666;
            margin-bottom: 24px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 15px;
            text-align: left;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13.5px;
            color: #444;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--pt-border, #e4e6ec);
            border-radius: var(--pt-radius-sm, 8px);
            font-size: 15px;
            background: #fafbfd;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--pt-blue-mid, #0066ff);
            background: #fff;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group input[type="file"] {
            padding: 8px;
            background: #fff;
        }

        .form-group input[type="submit"] {
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: var(--pt-radius-sm, 8px);
            padding: 13px;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: background-position 0.4s ease, transform 0.3s ease;
        }

        .form-group input[type="submit"]:hover {
            background-position: right center;
            transform: translateY(-2px);
        }

        /* Add Event Button */
        .add-event-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 30px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            text-decoration: none;
            border-radius: var(--pt-radius-pill, 999px);
            margin: 12px 0 0;
            font-weight: 700;
            text-align: center;
            box-shadow: var(--pt-shadow-sm, 0 2px 8px rgba(0,0,0,0.1));
            transition: background-position 0.4s ease, transform 0.3s ease, box-shadow 0.3s ease;
        }

        .add-event-btn:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: var(--pt-shadow-md, 0 8px 20px rgba(0,0,0,0.15));
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(10, 10, 20, 0.55);
            backdrop-filter: blur(3px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }

        .modal-content {
            background-color: #fff;
            padding: 30px 34px;
            border-radius: var(--pt-radius-lg, 18px);
            box-shadow: var(--pt-shadow-lg, 0 16px 40px rgba(0, 0, 0, 0.2));
            max-width: 600px;
            width: 90%;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            text-align: left;
        }

        .modal-content .title {
            font-size: 24px;
            font-weight: 700;
            position: relative;
            margin-bottom: 24px;
        }

        .modal-content .title::before {
            content: "";
            position: absolute;
            left: 0;
            bottom: -8px;
            height: 3px;
            width: 60px;
            border-radius: 999px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
        }

        .modal-content .close {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 22px;
            cursor: pointer;
            color: #999;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease, color 0.2s ease;
        }

        .modal-content .close:hover {
            background: #f1f2f6;
            color: #333;
        }

        .modal-content form .form-group {
            margin-bottom: 15px;
        }

        .modal-content form .form-group label {
            display: block;
            font-weight: 600;
            font-size: 13.5px;
            color: #444;
            margin-bottom: 6px;
        }

        .modal-content form .form-group input,
        .modal-content form .form-group textarea,
        .modal-content form .form-group select {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--pt-border, #e4e6ec);
            border-radius: var(--pt-radius-sm, 8px);
            font-size: 15px;
            background: #fafbfd;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .modal-content form .form-group input:focus,
        .modal-content form .form-group textarea:focus,
        .modal-content form .form-group select:focus {
            border-color: var(--pt-blue-mid, #0066ff);
            background: #fff;
        }

        .modal-content form .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .modal-content form .form-group input[type="file"] {
            padding: 8px;
            background: #fff;
        }

        .modal-content form .button input {
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: var(--pt-radius-sm, 8px);
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: background-position 0.4s ease, transform 0.3s ease;
        }

        .modal-content form .button input:hover {
            background-position: right center;
            transform: translateY(-2px);
        }

        .error, .success {
            font-size: 13.5px;
            margin-bottom: 15px;
            text-align: left;
            padding: 10px 14px;
            border-radius: var(--pt-radius-sm, 8px);
        }

        .error { color: #b3261e; background: #fdecea; }
        .success { color: #1e7d34; background: #e9f7ec; }

        @media (max-width: 576px) {
            .advertise-form {
                padding: 15px;
            }

            .advertise-form h2 {
                font-size: 20px;
            }

            .form-group label,
            .form-group input,
            .form-group textarea,
            .form-group select {
                font-size: 14px;
            }

            .modal-content {
                padding: 15px;
            }

            .modal-content .title {
                font-size: 20px;
            }

            .modal-content form .form-group label,
            .modal-content form .form-group input,
            .modal-content form .form-group textarea,
            .modal-content form .form-group select {
                font-size: 14px;
            }

            .add-event-btn {
                width: 100%;
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="advertise-form">
            <h2>Advertise Your Event at President University</h2>
            <a href="#" class="add-event-btn" id="add-event-btn">Add Event (Major Association)</a>
        </div>

        <div id="add-event-modal" class="modal">
            <div class="modal-content">
                <span class="close">×</span>
                <div class="title">Add Event for Major Association</div>
                <?php if (!empty($add_event_errors)): ?>
                    <div class="error">
                        <?php foreach ($add_event_errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if ($add_event_success): ?>
                    <div class="success"><?php echo htmlspecialchars($add_event_success); ?></div>
                <?php endif; ?>
                <form action="advertise.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_event" value="1">
                    <?php csrf_field(); ?>
                    <div class="form-group">
                        <label for="title">Event Title</label>
                        <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" required><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="details">Details (e.g., Date, Time)</label>
                        <textarea name="details" id="details" required><?php echo htmlspecialchars($details); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="price">Price per Ticket (USD)</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" value="<?php echo htmlspecialchars($price); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <select name="category" id="category" required>
                            <option value="" <?php echo $event_category == '' ? 'selected' : ''; ?>>Select Category</option>
                            <option value="Concerts" <?php echo $event_category == 'Concerts' ? 'selected' : ''; ?>>Concerts</option>
                            <option value="Workshops" <?php echo $event_category == 'Workshops' ? 'selected' : ''; ?>>Workshops</option>
                            <option value="Seminars" <?php echo $event_category == 'Seminars' ? 'selected' : ''; ?>>Seminars</option>
                            <option value="Sports" <?php echo $event_category == 'Sports' ? 'selected' : ''; ?>>Sports</option>
                            <option value="Cultural" <?php echo $event_category == 'Cultural' ? 'selected' : ''; ?>>Cultural</option>
                            <option value="Other" <?php echo $event_category == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($location); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="image">Event Image</label>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" required>
                    </div>
                    <div class="button">
                        <input type="submit" value="Add Event">
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        const addEventModal = document.getElementById('add-event-modal');
        const addEventBtn = document.getElementById('add-event-btn');
        const closeBtns = document.getElementsByClassName('close');

        addEventBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            addEventModal.style.display = 'flex';
        });

        Array.from(closeBtns).forEach(btn => {
            btn.addEventListener('click', () => {
                addEventModal.style.display = 'none';
            });
        });

        window.addEventListener('click', (e) => {
            if (e.target === addEventModal) {
                addEventModal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
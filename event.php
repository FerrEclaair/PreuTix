<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();
require_once __DIR__ . '/csrf.php';

// Include database configuration
if (!file_exists('config.php')) {
    die('Error: config.php not found. Please ensure it exists in the same directory.');
}
require_once 'config.php';

// Verify database connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Conversion rate: 1 USD = 15,000 IDR
$conversion_rate = 15000;

// Initialize variables for newsletter
$newsletter_message = '';
$newsletter_error = false;

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

// Handle search and filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

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
    if (!isset($_SESSION['user_id'])) {
        $add_event_errors[] = 'You must be logged in to add an event.';
    } elseif (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $add_event_errors[] = 'Session expired, please try again.';
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $details = trim($_POST['details']);
        $price = trim($_POST['price']);
        $event_category = trim($_POST['category']);
        $location = trim($_POST['location']);

        // Validation
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
}

// Logout handling
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo '<script>location.reload();</script>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events at President University - Preutix</title>
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

        /* Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Event Section Header */
        .event-header {
            padding: 48px 30px;
            text-align: center;
            background: var(--pt-gradient-header, linear-gradient(135deg, #000000, #1E40AF));
            border-radius: var(--pt-radius-lg, 18px);
            margin-top: 30px;
            box-shadow: var(--pt-shadow-md, 0 8px 24px rgba(0,0,0,0.12));
            position: relative;
            overflow: hidden;
        }

        .event-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF0000, #0000FF);
        }

        .event-header h1 {
            font-size: 2.3rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 24px;
        }

        /* Search Section */
        .search-form {
            display: flex;
            gap: 10px;
            max-width: 600px;
            margin: 0 auto;
            margin-bottom: 20px;
        }

        .search-form input[type="text"] {
            flex: 1;
            padding: 13px 16px;
            border: none;
            border-radius: var(--pt-radius-sm, 8px);
            font-size: 16px;
            outline: none;
            box-shadow: var(--pt-shadow-sm, 0 2px 8px rgba(0,0,0,0.08));
            transition: box-shadow 0.3s;
        }

        .search-form input[type="text"]:focus {
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
        }

        .search-form button {
            padding: 13px 26px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: var(--pt-radius-sm, 8px);
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background-position 0.4s ease, transform 0.3s ease;
        }

        .search-form button:hover {
            background-position: right center;
            transform: translateY(-2px);
        }

        /* Add Event Button (CTA) */
        .add-event-cta {
            display: flex;
            justify-content: center;
            margin-top: 6px;
        }

        .add-event-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 30px;
            background: #fff;
            color: var(--pt-blue-mid, #0066ff);
            text-decoration: none;
            border-radius: var(--pt-radius-pill, 999px);
            font-size: 1rem;
            font-weight: 700;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            border: none;
        }

        .add-event-btn i {
            font-size: 1.1rem;
        }

        .add-event-btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--pt-shadow-md, 0 8px 24px rgba(0, 0, 0, 0.2));
        }

        /* Event Cards */
        .event-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 20px;
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

        .btn-primary {
            display: inline-block;
            padding: 9px 22px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            text-decoration: none;
            border-radius: var(--pt-radius-sm, 8px);
            margin-top: 10px;
            font-weight: 600;
            transition: background-position 0.4s ease, transform 0.3s ease;
        }

        .btn-primary:hover {
            background-position: right center;
            transform: translateY(-2px);
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
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
        }

        .error { color: red; }
        .success { color: green; }

        .no-events {
            text-align: center;
            color: #666;
            font-size: 1.1rem;
            margin-top: 40px;
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
            background-color: #e6ffed;
            color: #2e7d32;
            border: 1px solid #2e7d32;
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

        /* Responsive Styles */
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            .search-form input[type="text"] {
                width: 100%;
            }
            .search-form button {
                width: 100%;
            }
            .event-header h1 {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .event-cards {
                grid-template-columns: 1fr;
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
                font-size: 1rem;
                padding: 10px 20px;
            }
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
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Add Event Modal -->
    <div class="modal" id="add-event-modal">
        <div class="modal-content">
            <span class="close" id="close-add-event-modal">×</span>
            <div class="title">Add New Event</div>
            <?php if (!empty($add_event_errors)): ?>
                <div class="error">
                    <?php foreach ($add_event_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($add_event_success)): ?>
                <div class="success"><?php echo htmlspecialchars($add_event_success); ?></div>
            <?php endif; ?>
            <form action="event.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="add_event" value="1">
                <?php csrf_field(); ?>
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($title); ?>" required>
                </div>
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" required><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="details">Details</label>
                    <textarea name="details" id="details" required><?php echo htmlspecialchars($details); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="price">Price (USD)</label>
                    <input type="number" name="price" id="price" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
                </div>
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="" disabled <?php echo empty($event_category) ? 'selected' : ''; ?>>Select a category</option>
                        <option value="Music" <?php echo $event_category == 'Music' ? 'selected' : ''; ?>>Music</option>
                        <option value="Sports" <?php echo $event_category == 'Sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="Workshop" <?php echo $event_category == 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                        <option value="Conference" <?php echo $event_category == 'Conference' ? 'selected' : ''; ?>>Conference</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" value="<?php echo htmlspecialchars($location); ?>" required>
                </div>
                <div class="form-group">
                    <label for="image">Event Image</label>
                    <input type="file" name="image" id="image" accept="image/*" required>
                </div>
                <div class="button">
                    <input type="submit" value="Add Event">
                </div>
            </form>
        </div>
    </div>

    <div class="container">
        <div class="event-header">
            <h1>Events at President University</h1>
            <div class="search-form">
                <input type="text" id="search-input" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
                <button onclick="applyFilters()">Search</button>
            </div>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="add-event-cta">
                    <button class="add-event-btn" id="add-event-btn">
                        <i class="fas fa-plus-circle"></i> Create New Event
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <div class="event-cards">
            <?php
            // Check if the events table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'events'");
            if ($table_check->num_rows == 0) {
                echo '<p class="no-events">Error: The events table does not exist in the database. Please create it with the required columns.</p>';
            } else {
                // Build the SQL query with search and category filters
                $sql = "SELECT id, title, details, price, image FROM events WHERE location LIKE '%President University%'";
                $params = [];
                $types = '';

                if (!empty($search)) {
                    $sql .= " AND (title LIKE ? OR description LIKE ?)";
                    $search_param = "%$search%";
                    $params[] = $search_param;
                    $params[] = $search_param;
                    $types .= 'ss';
                }

                if (!empty($category)) {
                    $sql .= " AND category = ?";
                    $params[] = $category;
                    $types .= 's';
                }

                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            ?>
                            <div class="event-card">
                                <div class="event-img">
                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['title']); ?>">
                                </div>
                                <div class="event-content">
                                    <h3 class="event-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                    <p class="event-details"><?php echo htmlspecialchars($row['details']); ?></p>
                                    <p class="event-details">Price: Rp <?php echo number_format($row['price'] * $conversion_rate, 0, ',', '.'); ?></p>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <a href="ticket.php?id=<?php echo $row['id']; ?>" class="btn-primary">Get Tickets</a>
                                    <?php else: ?>
                                        <a href="#" class="btn-primary" onclick="showLoginWarning()">Get Tickets</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="no-events">No events found matching your criteria.</p>';
                    }
                    $stmt->close();
                } else {
                    echo '<p class="no-events">Error preparing query: ' . $conn->error . '</p>';
                }
            }
            ?>
        </div>
    </div>

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
                        <div class="newsletter-notification" id="newsletter-notification">
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

    <script>
        // Search filter function
        function applyFilters() {
            const search = document.getElementById('search-input').value;
            let url = 'event.php?';
            if (search) {
                url += 'search=' + encodeURIComponent(search);
            }
            window.location.href = url;
        }

        // Show warning for unauthenticated users
        function showLoginWarning() {
            alert('You have to register and login to your account');
            window.location.href = 'signup.php';
        }

        // Add Event Modal Logic
        const addEventBtn = document.getElementById('add-event-btn');
        const addEventModal = document.getElementById('add-event-modal');
        const closeAddEventModal = document.getElementById('close-add-event-modal');

        addEventBtn?.addEventListener('click', function(e) {
            e.preventDefault();
            addEventModal.style.display = 'flex';
        });

        closeAddEventModal.addEventListener('click', function() {
            addEventModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === addEventModal) {
                addEventModal.style.display = 'none';
            }
        });

        // Newsletter Form Submission
        document.getElementById('newsletter-form')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const notification = document.getElementById('newsletter-notification');
            
            try {
                const response = await fetch('event.php', {
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
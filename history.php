<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session
session_start();

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

// Logout handling
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo '<script>location.reload();</script>';
}

// Fetch user ticket history
$history = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT e.title, e.details, t.purchase_date, t.quantity, t.total_price, e.image, t.id AS ticket_id 
            FROM tickets t 
            JOIN events e ON t.event_id = e.id 
            WHERE t.user_id = ? 
            ORDER BY t.purchase_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket History - Preutix</title>
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

        /* Header Section */
        .history-header {
            padding: 40px 30px;
            text-align: center;
            background: var(--pt-gradient-header, linear-gradient(135deg, #000000, #1E40AF));
            border-radius: var(--pt-radius-lg, 18px);
            margin-top: 30px;
            box-shadow: var(--pt-shadow-md, 0 8px 24px rgba(0,0,0,0.12));
            position: relative;
            overflow: hidden;
        }

        .history-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF0000, #0000FF);
        }

        .history-header h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #fff;
        }

        /* History Cards */
        .history-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }

        .history-card {
            background-color: white;
            border-radius: var(--pt-radius-md, 12px);
            overflow: hidden;
            box-shadow: var(--pt-shadow-sm, 0 5px 15px rgba(0, 0, 0, 0.08));
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .history-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--pt-shadow-lg, 0 16px 40px rgba(0, 0, 0, 0.16));
        }

        .history-img {
            height: 180px;
            overflow: hidden;
        }

        .history-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }

        .history-card:hover .history-img img {
            transform: scale(1.1);
        }

        .history-content {
            padding: 20px;
        }

        .history-title {
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #111;
            font-weight: 700;
        }

        .history-details {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .btn-invoice {
            display: inline-block;
            padding: 9px 22px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            text-decoration: none;
            border-radius: var(--pt-radius-sm, 8px);
            margin-top: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-position 0.4s ease, transform 0.3s ease;
        }

        .btn-invoice:hover {
            background-position: right center;
            transform: translateY(-2px);
        }

        .no-history {
            text-align: center;
            color: #666;
            font-size: 1.05rem;
            margin-top: 30px;
            padding: 50px 20px;
            background-color: white;
            border-radius: var(--pt-radius-lg, 18px);
            box-shadow: var(--pt-shadow-sm, 0 2px 8px rgba(0,0,0,0.06));
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
        }

        .newsletter-input {
            flex: 1;
            padding: 12px 15px;
            border: 1.5px solid #dde1ec;
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
            padding: 0 22px;
            border-radius: 0 var(--pt-radius-sm, 8px) var(--pt-radius-sm, 8px) 0;
            font-weight: 600;
            cursor: pointer;
            transition: background-position 0.4s ease, transform 0.3s ease;
        }

        .newsletter-btn:hover {
            background-position: right center;
            transform: translateY(-2px);
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
            .history-header h1 {
                font-size: 2rem;
            }
            .history-cards {
                grid-template-columns: 1fr;
            }
            .newsletter-container {
                flex-direction: column;
                text-align: center;
            }
            .newsletter-form {
                flex-direction: column;
                width: 100%;
            }
            .newsletter-input {
                border-radius: 6px;
                margin-bottom: 10px;
            }
            .newsletter-btn {
                width: 100%;
                border-radius: 6px;
            }
        }

        @media (max-width: 576px) {
            .no-history {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="history-header">
            <h1>Ticket History</h1>
        </div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="history-cards">
                <?php if (!empty($history)): ?>
                    <?php foreach ($history as $entry): ?>
                        <div class="history-card">
                            <div class="history-img">
                                <img src="<?php echo htmlspecialchars($entry['image'] ?? 'assets/default-image.jpg'); ?>" alt="<?php echo htmlspecialchars($entry['title']); ?>">
                            </div>
                            <div class="history-content">
                                <h3 class="history-title"><?php echo htmlspecialchars($entry['title']); ?></h3>
                                <p class="history-details">Purchased: <?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($entry['purchase_date']))); ?></p>
                                <p class="history-details">Quantity: <?php echo htmlspecialchars($entry['quantity']); ?></p>
                                <p class="history-details">Total: Rp <?php echo number_format($entry['total_price'] * $conversion_rate, 0, ',', '.'); ?></p>
                                <a href="generate_invoice.php?ticket_id=<?php echo $entry['ticket_id']; ?>" class="btn-invoice" target="_blank">Generate Invoice</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-history">No ticket history found.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p class="no-history">Please log in to view your ticket history.</p>
        <?php endif; ?>
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
                        <input type="email" class="newsletter-input" placeholder="Your email address">
                        <button class="newsletter-btn">Subscribe</button>
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
        // Mobile menu toggle (handled by header.php script)
        document.querySelector('.mobile-menu-btn')?.addEventListener('click', function() {
            document.getElementById('nav-menu').classList.toggle('active');
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>
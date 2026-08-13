<?php
// Start the session
session_start();
require_once __DIR__ . '/csrf.php';

// Include database configuration
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: signup.php');
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Check if event_id is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: event.php');
    exit();
}

$event_id = (int)$_GET['id'];

// Conversion rate: 1 USD = 15,000 IDR
$conversion_rate = 15000;

// Fetch event details
$sql = "SELECT id, title, description, image, price FROM events WHERE id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Database error: ' . $conn->error);
}
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: event.php');
    exit();
}

$event = $result->fetch_assoc();
$stmt->close();

// Process ticket purchase
$errors = [];
$quantity = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy_tickets'])) {
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

    // Validation
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        $errors[] = "Session expired, please refresh the page and try again.";
    }

    if ($quantity <= 0) {
        $errors[] = "Please enter a valid number of tickets.";
    }

    // Simulate payment validation (in a real app, integrate with a payment gateway)
    $card_number = isset($_POST['card_number']) ? trim($_POST['card_number']) : '';
    $expiry = isset($_POST['expiry']) ? trim($_POST['expiry']) : '';
    $cvv = isset($_POST['cvv']) ? trim($_POST['cvv']) : '';

    if (empty($card_number) || empty($expiry) || empty($cvv)) {
        $errors[] = "All payment fields are required.";
    } elseif (!preg_match('/^\d{16}$/', $card_number)) {
        $errors[] = "Invalid card number. Must be 16 digits.";
    } elseif (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiry)) {
        $errors[] = "Invalid expiry date. Use MM/YY format.";
    } elseif (!preg_match('/^\d{3}$/', $cvv)) {
        $errors[] = "Invalid CVV. Must be 3 digits.";
    }

    if (empty($errors)) {
        // Calculate total price in USD for database storage
        $total_price_usd = $quantity * $event['price'];

        // Generate invoice number (INV-YYYY-XXXX)
        $year = date('Y');
        $sql_count = "SELECT COUNT(*) as count FROM tickets WHERE invoice_number LIKE 'INV-$year-%'";
        $result_count = $conn->query($sql_count);
        if (!$result_count) {
            $errors[] = "Database error: " . $conn->error;
        } else {
            $row_count = $result_count->fetch_assoc();
            $order_number = $row_count['count'] + 1;
            $invoice_number = sprintf("INV-%s-%04d", $year, $order_number);

            // Insert into tickets
            $sql = "INSERT INTO tickets (user_id, event_id, quantity, total_price, invoice_number, purchase_date) VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $errors[] = "Prepare failed: " . $conn->error;
            } else {
                $stmt->bind_param('iiids', $user_id, $event_id, $quantity, $total_price_usd, $invoice_number);
                if ($stmt->execute()) {
                    header('Location: history.php');
                    exit();
                } else {
                    $errors[] = "Failed to process your order: " . $stmt->error;
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
    <title>Get Tickets - Preutix</title>
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

        /* Page hero */
        .ticket-hero {
            background: var(--pt-gradient-header, linear-gradient(135deg, #000000, #1E40AF));
            padding: 40px 0;
            text-align: center;
            color: #fff;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }

        .ticket-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #FF0000, #0000FF);
        }

        .ticket-hero h1 {
            font-size: 2rem;
            font-weight: 800;
        }

        /* Ticket Form */
        .ticket-form {
            max-width: 620px;
            margin: 0 auto 60px;
            padding: 34px;
            background-color: #fff;
            border-radius: var(--pt-radius-lg, 18px);
            box-shadow: var(--pt-shadow-md, 0 8px 24px rgba(0,0,0,0.1));
        }

        .ticket-form img {
            width: 100%;
            max-width: 100%;
            height: 220px;
            object-fit: cover;
            margin-bottom: 22px;
            border-radius: var(--pt-radius-md, 12px);
        }

        .ticket-form h2 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #111;
        }

        .ticket-form p {
            margin-bottom: 15px;
            color: #555;
        }

        .ticket-form label {
            display: block;
            font-weight: 600;
            font-size: 13.5px;
            color: #444;
            margin-bottom: 8px;
        }

        .ticket-form input[type="number"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 15px;
            border: 1.5px solid var(--pt-border, #e4e6ec);
            border-radius: var(--pt-radius-sm, 8px);
            font-size: 16px;
            background: #fafbfd;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .ticket-form input[type="number"]:focus {
            border-color: var(--pt-blue-mid, #0066ff);
            background: #fff;
        }

        .ticket-form input[type="submit"] {
            width: 100%;
            padding: 13px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            background-size: 200% auto;
            color: white;
            border: none;
            border-radius: var(--pt-radius-sm, 8px);
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            transition: background-position 0.4s ease, transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: var(--pt-shadow-sm, 0 2px 8px rgba(0,0,0,0.1));
        }

        .ticket-form input[type="submit"]:hover {
            background-position: right center;
            transform: translateY(-2px);
            box-shadow: var(--pt-shadow-md, 0 8px 20px rgba(0,0,0,0.15));
        }

        .error {
            color: #b3261e;
            background: #fdecea;
            padding: 10px 14px;
            border-radius: var(--pt-radius-sm, 8px);
            margin-bottom: 15px;
            font-size: 13.5px;
        }

        #total-price {
            display: inline-block;
            font-weight: 700;
            font-size: 1.15rem;
            margin-bottom: 15px;
            background: var(--pt-gradient-brand, linear-gradient(120deg, #FF0000, #0066ff));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Modal Styles (Payment) */
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
            padding: 30px 32px;
            border-radius: var(--pt-radius-lg, 18px);
            box-shadow: var(--pt-shadow-lg, 0 16px 40px rgba(0, 0, 0, 0.2));
            max-width: 460px;
            width: 90%;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content .title {
            font-size: 22px;
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

        .modal-content form .form-group input {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid var(--pt-border, #e4e6ec);
            border-radius: var(--pt-radius-sm, 8px);
            font-size: 15px;
            background: #fafbfd;
            outline: none;
            transition: border-color 0.2s ease, background 0.2s ease;
        }

        .modal-content form .form-group input:focus {
            border-color: var(--pt-blue-mid, #0066ff);
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

        .modal-error {
            color: #b3261e;
            font-size: 12.5px;
            margin-top: 5px;
            display: none;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .ticket-form {
                padding: 24px 20px;
            }
            .ticket-form img {
                height: 180px;
            }
            .modal-content {
                padding: 22px 20px;
            }
            .modal-content .title {
                font-size: 20px;
            }
            .modal-content form .form-group input {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <div class="ticket-hero">
        <div class="container">
            <h1>Get Tickets</h1>
        </div>
    </div>

    <div class="container">
        <div class="ticket-form">
            <img src="<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
            <h2><?php echo htmlspecialchars($event['title']); ?></h2>
            <p><?php echo htmlspecialchars($event['description']); ?></p>
            <p><strong>Price per Ticket:</strong> <?php echo 'Rp ' . number_format($event['price'] * $conversion_rate, 0, ',', '.'); ?></p>

            <?php if (!empty($errors)): ?>
                <div class="error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form id="ticket-form" method="POST" action="ticket.php?id=<?php echo $event_id; ?>">
                <input type="hidden" name="buy_tickets" value="1">
                <?php csrf_field(); ?>
                <label for="quantity">Number of Tickets:</label>
                <input type="number" name="quantity" id="quantity" min="1" value="<?php echo $quantity ?: 1; ?>" required>
                <p><strong>Total Price:</strong> <span id="total-price"><?php echo 'Rp ' . number_format(($event['price'] * $conversion_rate) * ($quantity ?: 1), 0, ',', '.'); ?></span></p>
                <input type="submit" value="Buy Tickets" id="buy-tickets-btn">
            </form>
        </div>
    </div>

    <!-- Payment Modal -->
    <div class="modal" id="payment-modal">
        <div class="modal-content">
            <span class="close" id="close-payment-modal">×</span>
            <div class="title">Payment Details</div>
            <form id="payment-form">
                <div class="form-group">
                    <label for="card-number">Card Number</label>
                    <input type="text" id="card-number" placeholder="1234 5678 9012 3456" maxlength="19" required>
                    <div class="modal-error" id="card-number-error">Invalid card number. Must be 16 digits.</div>
                </div>
                <div class="form-group">
                    <label for="expiry">Expiry Date (MM/YY)</label>
                    <input type="text" id="expiry" placeholder="MM/YY" maxlength="5" required>
                    <div class="modal-error" id="expiry-error">Invalid expiry date. Use MM/YY format.</div>
                </div>
                <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" placeholder="123" maxlength="3" required>
                    <div class="modal-error" id="cvv-error">Invalid CVV. Must be 3 digits.</div>
                </div>
                <div class="button">
                    <input type="submit" value="Pay Now">
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script>
        // Total Price Update Script
        (function() {
            const quantityInput = document.getElementById('quantity');
            const totalPriceSpan = document.getElementById('total-price');
            const pricePerTicket = parseFloat(<?php echo json_encode($event['price'] * $conversion_rate); ?>);

            if (!quantityInput || !totalPriceSpan) {
                console.error('Error: quantityInput or totalPriceSpan not found');
                return;
            }

            function updateTotalPrice() {
                const quantity = Math.max(1, parseInt(quantityInput.value) || 1); // Ensure at least 1
                const totalPrice = quantity * pricePerTicket;
                totalPriceSpan.textContent = 'Rp ' + totalPrice.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            updateTotalPrice();
            quantityInput.addEventListener('input', updateTotalPrice);
            quantityInput.addEventListener('change', updateTotalPrice);
        })();

        // Payment Modal Logic
        const ticketForm = document.getElementById('ticket-form');
        const buyTicketsBtn = document.getElementById('buy-tickets-btn');
        const paymentModal = document.getElementById('payment-modal');
        const closePaymentModal = document.getElementById('close-payment-modal');
        const paymentForm = document.getElementById('payment-form');
        const cardNumberInput = document.getElementById('card-number');
        const expiryInput = document.getElementById('expiry');
        const cvvInput = document.getElementById('cvv');
        const cardNumberError = document.getElementById('card-number-error');
        const expiryError = document.getElementById('expiry-error');
        const cvvError = document.getElementById('cvv-error');

        // Show payment modal on Buy Tickets click
        buyTicketsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity <= 0) {
                alert('Please enter a valid number of tickets.');
                return;
            }
            paymentModal.style.display = 'flex';
        });

        // Close payment modal
        closePaymentModal.addEventListener('click', function() {
            paymentModal.style.display = 'none';
            resetPaymentForm();
        });

        window.addEventListener('click', (e) => {
            if (e.target === paymentModal) {
                paymentModal.style.display = 'none';
                resetPaymentForm();
            }
        });

        // Payment form submission
        paymentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            let hasError = false;

            // Validate card number            
            const cardNumber = cardNumberInput.value.replace(/\s/g, '');
            if (!/^\d{16}$/.test(cardNumber)) {
                cardNumberError.style.display = 'block';
                hasError = true;
            } else {
                cardNumberError.style.display = 'none';
            }

            // Validate expiry
            const expiry = expiryInput.value;
            if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) {
                expiryError.style.display = 'block';
                hasError = true;
            } else {
                expiryError.style.display = 'none';
            }

            // Validate CVV
            const cvv = cvvInput.value;
            if (!/^\d{3}$/.test(cvv)) {
                cvvError.style.display = 'block';
                hasError = true;
            } else {
                cvvError.style.display = 'none';
            }

            if (!hasError) {
                // Add payment details to ticket form and submit
                const hiddenCardNumber = document.createElement('input');
                hiddenCardNumber.type = 'hidden';
                hiddenCardNumber.name = 'card_number';
                hiddenCardNumber.value = cardNumber;
                ticketForm.appendChild(hiddenCardNumber);

                const hiddenExpiry = document.createElement('input');
                hiddenExpiry.type = 'hidden';
                hiddenExpiry.name = 'expiry';
                hiddenExpiry.value = expiry;
                ticketForm.appendChild(hiddenExpiry);

                const hiddenCvv = document.createElement('input');
                hiddenCvv.type = 'hidden';
                hiddenCvv.name = 'cvv';
                hiddenCvv.value = cvv;
                ticketForm.appendChild(hiddenCvv);

                ticketForm.submit();
            }
        });

        // Format card number input (add spaces every 4 digits)
        cardNumberInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = value;
        });

        // Format expiry input (add slash after MM)
        expiryInput.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });

        // Restrict CVV to digits only
        cvvInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });

        // Reset payment form
        function resetPaymentForm() {
            paymentForm.reset();
            cardNumberError.style.display = 'none';
            expiryError.style.display = 'none';
            cvvError.style.display = 'none';
        }
    </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>
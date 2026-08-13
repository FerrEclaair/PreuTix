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

// Include TCPDF library
require_once 'C:\xampp\htdocs\php_PreUtix\TCPDF-main\tcpdf.php';

// Conversion rate: 1 USD = 15,000 IDR
$conversion_rate = 15000;

// Check if ticket_id is provided and user is logged in
if (!isset($_GET['ticket_id']) || !isset($_SESSION['user_id'])) {
    die('Invalid request or user not logged in.');
}

$ticket_id = (int)$_GET['ticket_id'];
$user_id = $_SESSION['user_id'];

// Fetch ticket details
$sql = "SELECT e.title, e.details, e.location, t.purchase_date, t.quantity, t.total_price, e.image, u.username, u.email 
        FROM tickets t 
        JOIN events e ON t.event_id = e.id 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ? AND t.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $ticket_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('Ticket not found or you do not have permission to access this ticket.');
}

$ticket = $result->fetch_assoc();
$stmt->close();

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Preutix');
$pdf->SetAuthor('Preutix');
$pdf->SetTitle('Invoice - ' . $ticket['title']);
$pdf->SetSubject('Event Ticket Invoice');
$pdf->SetKeywords('Invoice, Ticket, Preutix, Event');

// Set default header data
$pdf->SetHeaderData('', 0, 'Preutix Invoice', 'Your Event Ticketing Platform');

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 12);

// Invoice Title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'INVOICE', 0, 1, 'C');
$pdf->Ln(5);

// Invoice Details
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Invoice for: ' . htmlspecialchars($ticket['username']), 0, 1);
$pdf->Cell(0, 10, 'Email: ' . htmlspecialchars($ticket['email']), 0, 1);
$pdf->Cell(0, 10, 'Invoice Date: ' . date('Y-m-d'), 0, 1);
$pdf->Ln(10);

// Event Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Event Details', 0, 1);
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Event: ' . htmlspecialchars($ticket['title']), 0, 1);
$pdf->Cell(0, 10, 'Details: ' . htmlspecialchars($ticket['details']), 0, 1);
$pdf->Cell(0, 10, 'Location: ' . htmlspecialchars($ticket['location']), 0, 1);
$pdf->Cell(0, 10, 'Purchase Date: ' . date('Y-m-d H:i:s', strtotime($ticket['purchase_date'])), 0, 1);
$pdf->Ln(10);

// Payment Details
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Payment Details', 0, 1);
$pdf->SetFont('helvetica', '', 12);

// Table Header
$pdf->SetFillColor(200, 220, 255);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(30, 10, 'Quantity', 1, 0, 'C', 1);
$pdf->Cell(60, 10, 'Description', 1, 0, 'C', 1);
$pdf->Cell(50, 10, 'Unit Price (USD)', 1, 0, 'C', 1);
$pdf->Cell(50, 10, 'Total (IDR)', 1, 1, 'C', 1);

// Table Row
$pdf->SetFont('helvetica', '', 12);
$unit_price = $ticket['total_price'] / $ticket['quantity'];
$total_idr = $ticket['total_price'] * $conversion_rate;
$pdf->Cell(30, 10, $ticket['quantity'], 1, 0, 'C');
$pdf->Cell(60, 10, 'Ticket for ' . htmlspecialchars($ticket['title']), 1, 0);
$pdf->Cell(50, 10, '$' . number_format($unit_price, 2), 1, 0, 'C');
$pdf->Cell(50, 10, 'Rp ' . number_format($total_idr, 0, ',', '.'), 1, 1, 'C');

// Total
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(140, 10, 'Total', 1, 0, 'R');
$pdf->Cell(50, 10, 'Rp ' . number_format($total_idr, 0, ',', '.'), 1, 1, 'C');

$pdf->Ln(10);

// Footer Note
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 10, 'Thank you for choosing Preutix! Contact support@preutix.com for any inquiries.', 0, 1);

// Output the PDF
$pdf->Output('invoice_' . $ticket_id . '.pdf', 'I');

// Close database connection
$conn->close();
?>
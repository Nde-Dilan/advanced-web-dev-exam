<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$booking_id = $_GET['booking_id'] ?? 0;
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    header('Location: ../booking-history.php');
    exit;
}

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT b.*, 
               GROUP_CONCAT(e.title SEPARATOR ', ') as event_titles,
               SUM(bi.quantity) as total_tickets
        FROM bookings b
        LEFT JOIN booking_items bi ON b.id = bi.booking_id
        LEFT JOIN events e ON bi.event_id = e.id
        WHERE b.id = ? AND (b.user_id = ? OR ? = 1)
        GROUP BY b.id
    ");
    
    // Check if user is admin or owner of booking
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    $stmt->execute([$booking_id, $user_id, $is_admin ? 1 : 0]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header('Location: ../booking-history.php');
        exit;
    }
    
    // Get booking items with event details
    $stmt = $pdo->prepare("
        SELECT bi.*, e.title, e.event_date, e.event_time, e.venue, e.location, e.organizer
        FROM booking_items bi
        JOIN events e ON bi.event_id = e.id
        WHERE bi.booking_id = ?
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$booking_id]);
    $booking_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for PDF-like text download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="tickets_' . $booking['booking_reference'] . '.txt"');
    
    // Generate ticket content
    echo generateTicketContent($booking, $booking_items);
    
} catch (PDOException $e) {
    error_log("Ticket download error: " . $e->getMessage());
    header('Location: ../booking-history.php?error=download_failed');
    exit;
}

function generateTicketContent($booking, $booking_items) {
    $content = "
╔══════════════════════════════════════════════════════════════════════════════╗
║                              EVENTBOOK TICKETS                              ║
╚══════════════════════════════════════════════════════════════════════════════╝

BOOKING CONFIRMATION
══════════════════════════════════════════════════════════════════════════════

Booking Reference: {$booking['booking_reference']}
Booking Date: " . date('F j, Y g:i A', strtotime($booking['created_at'])) . "
Status: " . strtoupper($booking['booking_status']) . "

ATTENDEE INFORMATION
══════════════════════════════════════════════════════════════════════════════

Name: {$booking['first_name']} {$booking['last_name']}
Email: {$booking['email']}
Phone: {$booking['phone']}

Billing Address:
{$booking['address']}
{$booking['city']}, {$booking['state']} {$booking['zip']}

EVENT TICKETS
══════════════════════════════════════════════════════════════════════════════
";

    foreach ($booking_items as $index => $item) {
        $ticket_number = $index + 1;
        $event_date = date('F j, Y', strtotime($item['event_date']));
        $event_time = date('g:i A', strtotime($item['event_time']));
        
        $content .= "
TICKET #{$ticket_number}
──────────────────────────────────────────────────────────────────────────────

Event: {$item['title']}
Date: {$event_date}
Time: {$event_time}
Venue: {$item['venue']}
Location: {$item['location']}
Organizer: " . ($item['organizer'] ?: 'EventBook') . "

Quantity: {$item['quantity']} ticket(s)
Price per ticket: $" . number_format($item['price'], 2) . "
Subtotal: $" . number_format($item['subtotal'], 2) . "

";
    }

    $content .= "
PAYMENT SUMMARY
══════════════════════════════════════════════════════════════════════════════

Subtotal: $" . number_format($booking['total_amount'] - $booking['service_fee'], 2) . "
Service Fee: $" . number_format($booking['service_fee'], 2) . "
Total Amount: $" . number_format($booking['total_amount'], 2) . "

Payment Status: " . strtoupper($booking['payment_status']) . "

IMPORTANT INFORMATION
══════════════════════════════════════════════════════════════════════════════

• Please arrive at the venue at least 30 minutes before the event start time
• Bring a valid photo ID along with this ticket
• This ticket is non-transferable and non-refundable
• For questions, contact the event organizer or EventBook support
• Keep this ticket safe - you will need it for entry

TERMS & CONDITIONS
══════════════════════════════════════════════════════════════════════════════

By purchasing this ticket, you agree to the event terms and conditions.
Ticket holders assume all risks associated with attendance.
EventBook reserves the right to refuse entry for any reason.

Event organizers reserve the right to make changes to the event schedule,
venue, or performers without prior notice.

Lost or stolen tickets cannot be replaced.

For support, visit: eventbook.com/support
Email: support@eventbook.com

Thank you for choosing EventBook!

╔══════════════════════════════════════════════════════════════════════════════╗
║        This is your official ticket - Please present at venue entry         ║
╚══════════════════════════════════════════════════════════════════════════════╝

Generated on: " . date('F j, Y g:i A') . "
Booking Reference: {$booking['booking_reference']}
";

    return $content;
}
?>

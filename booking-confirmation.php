<?php
session_start();
require_once 'config/database.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;

if (!$booking_id || !isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Get booking details
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               GROUP_CONCAT(
                   CONCAT(bi.quantity, 'x ', e.title, ' (', e.event_date, ')') 
                   SEPARATOR '\n'
               ) as items_summary
        FROM bookings b
        LEFT JOIN booking_items bi ON b.id = bi.booking_id
        LEFT JOIN events e ON bi.event_id = e.id
        WHERE b.id = ? AND b.user_id = ?
        GROUP BY b.id
    ");
    $stmt->execute([$booking_id, $_SESSION['user_id']]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        header('Location: index.php');
        exit;
    }
    
    // Get detailed booking items
    $stmt = $pdo->prepare("
        SELECT bi.*, e.title, e.event_date, e.event_time, e.venue, e.location
        FROM booking_items bi
        JOIN events e ON bi.event_id = e.id
        WHERE bi.booking_id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Message -->
            <div class="text-center mb-4">
                <div class="display-1 text-success mb-3">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 class="text-success">Booking Confirmed!</h2>
                <p class="lead">Thank you for your booking. Your tickets have been confirmed.</p>
            </div>
            
            <!-- Booking Details -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-ticket-alt"></i> Booking Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Booking Reference:</strong><br>
                            <span class="h5 text-primary"><?php echo htmlspecialchars($booking['booking_reference']); ?></span></p>
                            
                            <p><strong>Booking Date:</strong><br>
                            <?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></p>
                            
                            <p><strong>Status:</strong><br>
                            <span class="badge bg-success">Confirmed</span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Total Amount:</strong><br>
                            <span class="h5 text-success">$<?php echo number_format($booking['total_amount'], 2); ?></span></p>
                            
                            <p><strong>Payment Status:</strong><br>
                            <span class="badge bg-success">Completed</span></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendee Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Attendee Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong><br>
                            <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                            
                            <p><strong>Email:</strong><br>
                            <?php echo htmlspecialchars($booking['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Phone:</strong><br>
                            <?php echo htmlspecialchars($booking['phone']); ?></p>
                            
                            <p><strong>Address:</strong><br>
                            <?php echo htmlspecialchars($booking['address']); ?><br>
                            <?php echo htmlspecialchars($booking['city'] . ', ' . $booking['state'] . ' ' . $booking['zip']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booked Events -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Your Events</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($booking_items as $item): ?>
                        <div class="row mb-3 pb-3 border-bottom">
                            <div class="col-md-8">
                                <h6><?php echo htmlspecialchars($item['title']); ?></h6>
                                <p class="mb-1">
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo date('M j, Y', strtotime($item['event_date'])); ?> at 
                                    <?php echo date('g:i A', strtotime($item['event_time'])); ?>
                                </p>
                                <p class="mb-0 text-muted">
                                    <i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($item['venue']); ?>, <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <p class="mb-1"><strong>Quantity:</strong> <?php echo $item['quantity']; ?></p>
                                <p class="mb-0"><strong>Subtotal:</strong> $<?php echo number_format($item['subtotal'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Total -->
                    <div class="row mt-3">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="text-end">
                                <p class="mb-1">Subtotal: $<?php echo number_format($booking['total_amount'] - $booking['service_fee'], 2); ?></p>
                                <p class="mb-1">Service Fee: $<?php echo number_format($booking['service_fee'], 2); ?></p>
                                <hr>
                                <p class="h5 text-success"><strong>Total: $<?php echo number_format($booking['total_amount'], 2); ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="text-center mb-4">
                <button class="btn btn-primary me-2" onclick="downloadTickets()">
                    <i class="fas fa-download"></i> Download Tickets
                </button>
                <button class="btn btn-outline-primary me-2" onclick="generateQR()">
                    <i class="fas fa-qrcode"></i> Generate QR Code
                </button>
                <a href="booking-history.php" class="btn btn-outline-secondary">
                    <i class="fas fa-history"></i> View All Bookings
                </a>
            </div>
            
            <!-- Important Notice -->
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Important Information:</h6>
                <ul class="mb-0">
                    <li>A confirmation email has been sent to <?php echo htmlspecialchars($booking['email']); ?></li>
                    <li>Please bring a valid ID and this booking reference to the venue</li>
                    <li>Tickets are non-refundable but may be transferable (check event terms)</li>
                    <li>Arrive at least 30 minutes before the event start time</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Modal -->
<div class="modal fade" id="qrModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-code" class="mb-3"></div>
                <p class="text-muted">Scan this QR code at the venue for quick entry</p>
            </div>
        </div>
    </div>
</div>

 
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>

<script>
function downloadTickets() {
    // Check if libraries are available
    if (typeof window.jspdf === 'undefined') {
        alert('PDF library not loaded. Please refresh the page and try again.');
        return;
    }
    
    if (typeof QRious === 'undefined') {
        alert('QR Code library not loaded. Please refresh the page and try again.');
        return;
    }
    
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF();
    
    // Ticket data
    const ticketData = {
        reference: '<?php echo $booking['booking_reference']; ?>',
        name: '<?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>',
        total: '<?php echo number_format($booking['total_amount'], 2); ?>',
        date: '<?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?>',
        email: '<?php echo htmlspecialchars($booking['email']); ?>',
        phone: '<?php echo htmlspecialchars($booking['phone']); ?>'
    };
    
    // Events data
    const events = [
        <?php foreach ($booking_items as $item): ?>{
            title: '<?php echo addslashes(htmlspecialchars($item['title'])); ?>',
            date: '<?php echo date('M j, Y', strtotime($item['event_date'])); ?>',
            time: '<?php echo date('g:i A', strtotime($item['event_time'])); ?>',
            venue: '<?php echo addslashes(htmlspecialchars($item['venue'])); ?>',
            location: '<?php echo addslashes(htmlspecialchars($item['location'])); ?>',
            quantity: <?php echo $item['quantity']; ?>,
            subtotal: '<?php echo number_format($item['subtotal'], 2); ?>'
        },
        <?php endforeach; ?>
    ];
    
    // Generate QR Code using QRious
    try {
        const qr = new QRious({
            value: JSON.stringify({
                booking_reference: ticketData.reference,
                attendee: ticketData.name,
                total: ticketData.total
            }),
            size: 100
        });
        
        const qrUrl = qr.toDataURL();
        generatePDF(doc, ticketData, events, qrUrl);
        
    } catch (e) {
        console.error('QR generation error:', e);
        generatePDF(doc, ticketData, events, null);
    }
}

function generatePDF(doc, ticketData, events, qrUrl) {
    // PDF Design
    const pageWidth = doc.internal.pageSize.width;
    const pageHeight = doc.internal.pageSize.height;
    
    // Header Background
    doc.setFillColor(52, 152, 219); // Blue background
    doc.rect(0, 0, pageWidth, 40, 'F');
    
    // Company Logo/Title
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(24);
    doc.setFont('helvetica', 'bold');
    doc.text('EventBook', 20, 25);
    
    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Event Ticket', 20, 32);
    
    // QR Code (top right) - only if available
    if (qrUrl) {
        try {
            doc.addImage(qrUrl, 'PNG', pageWidth - 50, 10, 30, 30);
        } catch (e) {
            console.error('Error adding QR code to PDF:', e);
        }
    }
    
    // Ticket Content
    doc.setTextColor(0, 0, 0);
    
    // Booking Reference (prominent)
    doc.setFontSize(16);
    doc.setFont('helvetica', 'bold');
    doc.text('Booking Reference:', 20, 60);
    doc.setTextColor(52, 152, 219);
    doc.text(ticketData.reference, 20, 70);
    
    // Attendee Information
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text('Attendee Information', 20, 90);
    
    doc.setFontSize(11);
    doc.setFont('helvetica', 'normal');
    doc.text('Name: ' + ticketData.name, 20, 100);
    doc.text('Email: ' + ticketData.email, 20, 108);
    doc.text('Phone: ' + ticketData.phone, 20, 116);
    doc.text('Booking Date: ' + ticketData.date, 20, 124);
    
    // Events Section
    let yPosition = 140;
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text('Event Details', 20, yPosition);
    
    yPosition += 10;
    events.forEach((event, index) => {
        // Event card background
        doc.setFillColor(248, 249, 250);
        doc.rect(15, yPosition - 5, pageWidth - 30, 35, 'F');
        
        // Event details
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.setTextColor(52, 152, 219);
        doc.text(event.title, 20, yPosition + 5);
        
        doc.setFontSize(10);
        doc.setFont('helvetica', 'normal');
        doc.setTextColor(0, 0, 0);
        doc.text('Date: ' + event.date + ' at ' + event.time, 20, yPosition + 13);
        doc.text('Venue: ' + event.venue, 20, yPosition + 21);
        doc.text('Location: ' + event.location, 20, yPosition + 29);
        
        // Quantity and price (right aligned)
        doc.text('Qty: ' + event.quantity, pageWidth - 60, yPosition + 13);
        doc.text('XAF ' + event.subtotal, pageWidth - 60, yPosition + 21);
        
        yPosition += 45;
    });
    
    // Total Amount
    yPosition += 10;
    doc.setFillColor(52, 152, 219);
    doc.rect(15, yPosition - 5, pageWidth - 30, 20, 'F');
    
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(14);
    doc.setFont('helvetica', 'bold');
    doc.text('Total Amount: XAF ' + ticketData.total, 20, yPosition + 7);
    
    // Important Instructions
    yPosition += 30;
    doc.setTextColor(0, 0, 0);
    doc.setFontSize(12);
    doc.setFont('helvetica', 'bold');
    doc.text('Important Instructions:', 20, yPosition);
    
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('• Please arrive at least 30 minutes before the event', 20, yPosition + 10);
    doc.text('• Bring a valid ID and this ticket for entry', 20, yPosition + 18);
    doc.text('• Present the QR code for quick scanning at the venue', 20, yPosition + 26);
    doc.text('• Tickets are non-refundable but may be transferable', 20, yPosition + 34);
    
    // Footer
    doc.setFillColor(240, 240, 240);
    doc.rect(0, pageHeight - 30, pageWidth, 30, 'F');
    
    doc.setTextColor(100, 100, 100);
    doc.setFontSize(9);
    doc.text('EventBook - Your Premier Event Booking Platform', 20, pageHeight - 15);
    doc.text('Generated on: ' + new Date().toLocaleString(), 20, pageHeight - 8);
    doc.text('For support: support@eventbook.com', pageWidth - 80, pageHeight - 8);
    
    // Save the PDF
    doc.save(`EventBook-Tickets-${ticketData.reference}.pdf`);
    
    alert('PDF ticket downloaded successfully!');
}

function generateQR() {
    const qrData = JSON.stringify({
        booking_reference: '<?php echo $booking['booking_reference']; ?>',
        attendee: '<?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>',
        total: <?php echo $booking['total_amount']; ?>
    });
    
    // Use Google Charts API as fallback
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=256x256&data=${encodeURIComponent(qrData)}`;
    
    document.getElementById('qr-code').innerHTML = `<img src="${qrUrl}" alt="QR Code" class="img-fluid">`;
    
    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
    modal.show();
}
</script>

<?php require_once 'includes/footer.php'; ?>

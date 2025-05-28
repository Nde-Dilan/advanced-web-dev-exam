<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if (!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
    exit;
}

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit;
    }
    
    // Get booking items
    $stmt = $pdo->prepare("
        SELECT bi.*, e.title, e.event_date, e.event_time, e.venue, e.location, e.image, e.description
        FROM booking_items bi
        JOIN events e ON bi.event_id = e.id
        WHERE bi.booking_id = ?
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$booking_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate HTML
    ob_start();
    ?>
    
    <!-- Booking Information -->
    <div class="row mb-3">
        <div class="col-md-6">
            <h6>Booking Reference</h6>
            <p class="h5 text-primary"><?php echo htmlspecialchars($booking['booking_reference']); ?></p>
        </div>
        <div class="col-md-6">
            <h6>Booking Date</h6>
            <p><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></p>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <h6>Total Amount</h6>
            <p class="h5 text-success">$<?php echo number_format($booking['total_amount'], 2); ?></p>
        </div>
        <div class="col-md-6">
            <h6>Status</h6>
            <span class="badge bg-success">Confirmed</span>
        </div>
    </div>
    
    <!-- Attendee Information -->
    <hr>
    <h6>Attendee Information</h6>
    <div class="row mb-3">
        <div class="col-md-6">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
        </div>
        <div class="col-md-6">
            <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
            <p><strong>Address:</strong><br>
            <?php echo htmlspecialchars($booking['address']); ?><br>
            <?php echo htmlspecialchars($booking['city'] . ', ' . $booking['state'] . ' ' . $booking['zip']); ?></p>
        </div>
    </div>
    
    <!-- Events -->
    <hr>
    <h6>Booked Events</h6>
    <?php foreach ($items as $item): ?>
        <div class="card mb-2">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <img src="<?php echo htmlspecialchars($item['image'] ?? 'assets/images/default-event.jpg'); ?>" 
                             class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             style="height: 60px; width: 100%; object-fit: cover;">
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-1"><?php echo htmlspecialchars($item['title']); ?></h6>
                        <p class="mb-1 small">
                            <i class="fas fa-calendar me-1"></i>
                            <?php echo date('M j, Y', strtotime($item['event_date'])); ?> at 
                            <?php echo date('g:i A', strtotime($item['event_time'])); ?>
                        </p>
                        <p class="mb-0 small text-muted">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($item['venue']); ?>, <?php echo htmlspecialchars($item['location']); ?>
                        </p>
                    </div>
                    <div class="col-md-2 text-center">
                        <small>Quantity</small><br>
                        <span class="fw-bold"><?php echo $item['quantity']; ?></span>
                    </div>
                    <div class="col-md-2 text-end">
                        <small>Subtotal</small><br>
                        <span class="fw-bold">$<?php echo number_format($item['subtotal'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Payment Summary -->
    <div class="card bg-light mt-3">
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-8"></div>
                <div class="col-md-4">
                    <div class="text-end">
                        <p class="mb-1">Subtotal: $<?php echo number_format($booking['total_amount'] - $booking['service_fee'], 2); ?></p>
                        <p class="mb-1">Service Fee: $<?php echo number_format($booking['service_fee'], 2); ?></p>
                        <hr class="my-2">
                        <p class="h6 text-success mb-0"><strong>Total: $<?php echo number_format($booking['total_amount'], 2); ?></strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Actions -->
    <div class="text-center mt-4">
        <button class="btn btn-primary me-2" onclick="downloadTickets(<?php echo $booking['id']; ?>)">
            <i class="fas fa-download"></i> Download Tickets
        </button>
        <button class="btn btn-outline-primary" onclick="generateQRCode('<?php echo htmlspecialchars($booking['booking_reference']); ?>')">
            <i class="fas fa-qrcode"></i> QR Code
        </button>
    </div>
    
    <?php
    $html = ob_get_clean();
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>

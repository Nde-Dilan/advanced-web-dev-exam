<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user bookings
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               COUNT(bi.id) as total_tickets,
               GROUP_CONCAT(e.title SEPARATOR ', ') as event_titles
        FROM bookings b
        LEFT JOIN booking_items bi ON b.id = bi.booking_id
        LEFT JOIN events e ON bi.event_id = e.id
        WHERE b.user_id = ?
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $bookings = [];
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history"></i> Booking History</h2>
                <a href="events.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Book More Events
                </a>
            </div>
            
            <?php if (empty($bookings)): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h4>No Bookings Yet</h4>
                        <p class="text-muted">You haven't booked any events yet. Start exploring our amazing events!</p>
                        <a href="events.php" class="btn btn-primary">Browse Events</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Booking Statistics -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5>Total Bookings</h5>
                                        <h3><?php echo count($bookings); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-ticket-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5>Total Tickets</h5>
                                        <h3><?php echo array_sum(array_column($bookings, 'total_tickets')); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5>Total Spent</h5>
                                        <h3>XAF <?php echo number_format(array_sum(array_column($bookings, 'total_amount')), 2); ?></h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter Tabs -->
                <ul class="nav nav-tabs mb-4" id="bookingTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button">
                            All Bookings
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button">
                            Upcoming Events
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button">
                            Past Events
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="bookingTabContent">
                    <!-- All Bookings -->
                    <div class="tab-pane fade show active" id="all" role="tabpanel">
                        <?php foreach ($bookings as $booking): ?>
                            <?php echo renderBookingCard($booking, $pdo); ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Upcoming Events -->
                    <div class="tab-pane fade" id="upcoming" role="tabpanel">
                        <div id="upcoming-bookings">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    
                    <!-- Past Events -->
                    <div class="tab-pane fade" id="past" role="tabpanel">
                        <div id="past-bookings">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modal-content">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<?php
function renderBookingCard($booking, $pdo) {
    // Get booking items
    $stmt = $pdo->prepare("
        SELECT bi.*, e.title, e.event_date, e.event_time, e.venue, e.location, e.image
        FROM booking_items bi
        JOIN events e ON bi.event_id = e.id
        WHERE bi.booking_id = ?
        ORDER BY e.event_date ASC
    ");
    $stmt->execute([$booking['id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $earliest_event = $items[0] ?? null;
    $is_upcoming = $earliest_event && strtotime($earliest_event['event_date']) > time();
    
    ob_start();
    ?>
    <div class="card mb-3 booking-card" data-booking-id="<?php echo $booking['id']; ?>" data-is-upcoming="<?php echo $is_upcoming ? 1 : 0; ?>">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2">
                    <?php if ($earliest_event): ?>
                        <img src="<?php echo htmlspecialchars($earliest_event['image'] ?? 'assets/images/default-event.jpg'); ?>" 
                             class="img-fluid rounded" alt="Event" style="height: 80px; width: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                            <i class="fas fa-calendar fa-2x text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-1">
                        <?php echo htmlspecialchars($booking['booking_reference']); ?>
                        <?php if ($is_upcoming): ?>
                            <span class="badge bg-success ms-2">Upcoming</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">Past</span>
                        <?php endif; ?>
                    </h5>
                    <p class="mb-1"><?php echo htmlspecialchars($booking['event_titles']); ?></p>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Booked on <?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                    </small>
                    <?php if ($earliest_event): ?>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Event: <?php echo date('M j, Y g:i A', strtotime($earliest_event['event_date'] . ' ' . $earliest_event['event_time'])); ?>
                        </small>
                    <?php endif; ?>
                </div>
                <div class="col-md-2 text-center">
                    <h6>Total Tickets</h6>
                    <span class="h4 text-primary"><?php echo $booking['total_tickets']; ?></span>
                </div>
                <div class="col-md-2">
                    <div class="text-end">
                        <h6 class="text-success mb-2">XAF <?php echo number_format($booking['total_amount'], 2); ?></h6>
                        <button class="btn btn-outline-primary btn-sm mb-1 w-100" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                        <?php if ($is_upcoming): ?>
                            <button class="btn btn-outline-success btn-sm w-100" onclick="downloadTickets(<?php echo $booking['id']; ?>)">
                                <i class="fas fa-download"></i> Download
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tab switching
    const tabButtons = document.querySelectorAll('#bookingTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(event) {
            const target = event.target.getAttribute('data-bs-target');
            filterBookings(target);
        });
    });
});

function filterBookings(target) {
    const allBookings = document.querySelectorAll('.booking-card');
    
    if (target === '#upcoming') {
        const upcomingContainer = document.getElementById('upcoming-bookings');
        upcomingContainer.innerHTML = '';
        
        allBookings.forEach(booking => {
            if (booking.dataset.isUpcoming === '1') {
                upcomingContainer.appendChild(booking.cloneNode(true));
            }
        });
        
        if (upcomingContainer.children.length === 0) {
            upcomingContainer.innerHTML = '<div class="alert alert-info">No upcoming events found.</div>';
        }
    } else if (target === '#past') {
        const pastContainer = document.getElementById('past-bookings');
        pastContainer.innerHTML = '';
        
        allBookings.forEach(booking => {
            if (booking.dataset.isUpcoming === '0') {
                pastContainer.appendChild(booking.cloneNode(true));
            }
        });
        
        if (pastContainer.children.length === 0) {
            pastContainer.innerHTML = '<div class="alert alert-info">No past events found.</div>';
        }
    }
}

function viewBookingDetails(bookingId) {
    fetch(`api/get_booking_details.php?id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modal-content').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
                modal.show();
            } else {
                showToast(data.message || 'Error loading booking details', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error loading booking details', 'danger');
        });
}

function downloadTickets(bookingId) {
    fetch(`api/download_tickets.php?booking_id=${bookingId}`)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `tickets-booking-${bookingId}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showToast('Tickets downloaded successfully!', 'success');
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error downloading tickets', 'danger');
        });
}
</script>

<?php require_once 'includes/footer.php'; ?>

<?php
session_start();
require_once 'config/database.php';

// Get event ID from URL
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$event_id) {
    header('Location: events.php');
    exit;
}

// Fetch event details
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        header('Location: events.php');
        exit;
    }
    
    // Format date and time
    $event['formatted_date'] = date('M j, Y', strtotime($event['event_date']));
    $event['formatted_time'] = date('g:i A', strtotime($event['event_time']));
    $event['price_formatted'] = 'XAF' . number_format($event['price'], 2);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: events.php');
    exit;
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Event Details -->
        <div class="col-lg-8">
            <div class="card">
                <img src="<?php echo htmlspecialchars($event['image'] ?? 'assets/images/default-event.jpg'); ?>" 
                     class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>" 
                     style="height: 400px; object-fit: cover;">
                <div class="card-body">
                    <h1 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h1>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                    
                    <!-- Event Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-calendar text-primary"></i> Date & Time</h5>
                            <p><?php echo $event['formatted_date']; ?> at <?php echo $event['formatted_time']; ?></p>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-map-marker-alt text-primary"></i> Venue</h5>
                            <p><?php echo htmlspecialchars($event['venue']); ?></p>
                            <p class="text-muted"><?php echo htmlspecialchars($event['location']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5><i class="fas fa-user text-primary"></i> Organizer</h5>
                            <p><?php echo htmlspecialchars($event['organizer']); ?></p>
                            <?php if (!empty($event['organizer_contact'])): ?>
                                <p class="text-muted">Contact: <?php echo htmlspecialchars($event['organizer_contact']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5><i class="fas fa-ticket-alt text-primary"></i> Ticket Price</h5>
                            <h3 class="text-success"><?php echo $event['price_formatted']; ?></h3>
                            <p class="text-muted">Available Tickets: <?php echo $event['available_tickets']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Booking Sidebar -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-ticket-alt"></i> Book Tickets</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form id="booking-form">
                            <input type="hidden" id="event-id" value="<?php echo $event['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="ticket-quantity" class="form-label">Number of Tickets</label>
                                <select class="form-select" id="ticket-quantity" required>
                                    <option value="">Select quantity</option>
                                    <?php for ($i = 1; $i <= min(10, $event['available_tickets']); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> ticket<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <span>Price per ticket:</span>
                                    <span class="fw-bold"><?php echo $event['price_formatted']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total:</span>
                                    <span class="fw-bold text-success" id="total-price">XAF 0.00</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100 mb-2">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                            <button type="button" class="btn btn-primary w-100" onclick="bookNow()">
                                <i class="fas fa-bolt"></i> Book Now
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <p class="text-muted">Please login to book tickets</p>
                            <a href="auth/login.php" class="btn btn-primary w-100">Login</a>
                            <a href="auth/register.php" class="btn btn-outline-secondary w-100 mt-2">Register</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Map Section -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-map"></i> Location</h5>
                </div>
                <div class="card-body">
                    <div id="map" style="height: 200px; background: #f8f9fa; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center text-muted">
                            <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                            <p><?php echo htmlspecialchars($event['venue']); ?><br>
                            <?php echo htmlspecialchars($event['location']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const eventPrice = <?php echo $event['price']; ?>;

document.getElementById('ticket-quantity').addEventListener('change', function() {
    const quantity = parseInt(this.value) || 0;
    const total = quantity * eventPrice;
    document.getElementById('total-price').textContent = 'XAF' + total.toFixed(2);
});

document.getElementById('booking-form').addEventListener('submit', function(e) {
    e.preventDefault();
    addToCart();
});

function addToCart() {

    console.log("Alllo");
    
    const eventId = document.getElementById('event-id').value;
    const quantity = document.getElementById('ticket-quantity').value;
    
    if (!quantity) {
        showToast('Please select number of tickets', 'warning');
        return;
    }
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&event_id=${eventId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log("Helllllllllllllllllllo");
            
            showToast('Tickets added to cart!', 'success');
            updateCartCount();
        } else {
            showToast(data.message || 'Error adding to cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding to cart', 'danger');
    });
}

function bookNow() {
    const quantity = document.getElementById('ticket-quantity').value;
    
    if (!quantity) {
        showToast('Please select number of tickets', 'warning');
        return;
    }
    
    addToCart();
    setTimeout(() => {
        window.location.href = 'checkout.php';
    }, 1000);
}

function updateCartCount() {
    fetch('api/cart.php?action=count')
        .then(response => response.json())
        .then(data => {
            const cartBadge = document.querySelector('.cart-count');
            if (cartBadge) {
                cartBadge.textContent = data.count || 0;
            }
        });
}
</script>

<?php require_once 'includes/footer.php'; ?>

<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

// Get cart items
try {
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, 
               e.id, e.title, e.event_date, e.event_time, e.venue, e.price, e.available_tickets,
               (c.quantity * e.price) as subtotal
        FROM cart c 
        JOIN events e ON c.event_id = e.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        header('Location: cart.php');
        exit;
    }
    
    $total = 0;
    foreach ($cart_items as &$item) {
        $item['formatted_date'] = date('M j, Y', strtotime($item['event_date']));
        $item['formatted_time'] = date('g:i A', strtotime($item['event_time']));
        $item['price_formatted'] = 'XAF' . number_format($item['price'], 2);
        $item['subtotal_formatted'] = 'XAF' . number_format($item['subtotal'], 2);
        $total += $item['subtotal'];
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: cart.php');
    exit;
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                    <li class="breadcrumb-item active">Checkout</li>
                </ol>
            </nav>
            <h2><i class="fas fa-credit-card"></i> Checkout</h2>
            <hr>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <form id="checkout-form">
                <!-- Attendee Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-user"></i> Attendee Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-credit-card"></i> Payment Information</h5>
                        <small class="text-muted">This is a demo - no real payment will be processed</small>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Card Number *</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                   placeholder="1234 5678 9012 3456" maxlength="19" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expiry" class="form-label">Expiry Date *</label>
                                    <input type="text" class="form-control" id="expiry" name="expiry" 
                                           placeholder="MM/YY" maxlength="5" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cvv" class="form-label">CVV *</label>
                                    <input type="text" class="form-control" id="cvv" name="cvv" 
                                           placeholder="123" maxlength="3" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="card_name" class="form-label">Cardholder Name *</label>
                            <input type="text" class="form-control" id="card_name" name="card_name" required>
                        </div>
                    </div>
                </div>
                
                <!-- Billing Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-home"></i> Billing Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="address" class="form-label">Street Address *</label>
                            <input type="text" class="form-control" id="address" name="address" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="state" class="form-label">State *</label>
                                    <input type="text" class="form-control" id="state" name="state" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="zip" class="form-label">ZIP Code *</label>
                                    <input type="text" class="form-control" id="zip" name="zip" required>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" target="_blank">Terms and Conditions</a> and 
                                <a href="#" target="_blank">Privacy Policy</a> *
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($item['title']); ?></h6>
                                <small class="text-muted"><?php echo $item['quantity']; ?> × <?php echo $item['price_formatted']; ?></small>
                            </div>
                            <span><?php echo $item['subtotal_formatted']; ?></span>
                        </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span><?php echo 'XAF' . number_format($total, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Service Fee:</span>
                        <span>XAF<?php echo number_format($total * 0.05, 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total:</strong>
                        <strong class="text-success"><?php echo 'XAF' . number_format($total * 1.05, 2); ?></strong>
                    </div>
                    
                    <button type="submit" form="checkout-form" class="btn btn-success w-100 mb-2" id="place-order-btn">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                    <a href="cart.php" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay" class="d-none position-fixed w-100 h-100" style="top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border text-white" role="status">
            <span class="visually-hidden">Processing...</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Format card number input
    document.getElementById('card_number').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
        let formattedValue = value.replace(/(.{4})/g, 'XAF 1 ').trim();
        if (formattedValue.length > 19) formattedValue = formattedValue.substring(0, 19);
        e.target.value = formattedValue;
    });
    
    // Format expiry date input
    document.getElementById('expiry').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });
    
    // CVV numeric only
    document.getElementById('cvv').addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
    
    // Handle form submission
    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        e.preventDefault();
        processOrder();
    });
});

function processOrder() {
    const btn = document.getElementById('place-order-btn');
    const originalText = btn.innerHTML;
    
    // Validate form
    if (!document.getElementById('checkout-form').checkValidity()) {
        document.getElementById('checkout-form').reportValidity();
        return;
    }
    
    // Show loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    document.getElementById('loading-overlay').classList.remove('d-none');
    
    // Collect form data
    const formData = new FormData(document.getElementById('checkout-form'));
    
    // Simulate payment processing delay
    setTimeout(() => {
        fetch('api/process_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to success page
                window.location.href = `booking-confirmation.php?booking_id=${data.booking_id}`;
            } else {
                showToast(data.message || 'Order processing failed', 'danger');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error processing order', 'danger');
            btn.disabled = false;
            btn.innerHTML = originalText;
        })
        .finally(() => {
            document.getElementById('loading-overlay').classList.add('d-none');
        });
    }, 2000); // 2 second delay to simulate processing
}
</script>

<?php require_once 'includes/footer.php'; ?>

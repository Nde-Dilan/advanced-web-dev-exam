<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h2><i class="fas fa-shopping-cart"></i> Your Cart</h2>
            <hr>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div id="cart-items">
                <!-- Cart items will be loaded here -->
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-receipt"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <span>Total Items:</span>
                        <span id="total-items">0</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5">Total:</span>
                        <span class="h5 text-success" id="total-amount">XAF 0.00</span>
                    </div>
                    <hr>
                    <button id="checkout-btn" class="btn btn-success w-100 mb-2" disabled>
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </button>
                    <button id="clear-cart-btn" class="btn btn-outline-danger w-100">
                        <i class="fas fa-trash"></i> Clear Cart
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading overlay -->
<div id="loading-overlay" class="d-none position-fixed w-100 h-100" style="top: 0; left: 0; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border text-white" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<script>
let cartData = null;

document.addEventListener('DOMContentLoaded', function() {
    loadCart();
    
    document.getElementById('checkout-btn').addEventListener('click', function() {
        if (cartData && cartData.items.length > 0) {
            window.location.href = 'checkout.php';
        }
    });
    
    document.getElementById('clear-cart-btn').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear your cart?')) {
            clearCart();
        }
    });
});

function loadCart() {
    showLoading(true);
    
    fetch('api/cart.php?action=get')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cartData = data;
                displayCartItems(data.items);
                updateSummary(data.items, data.total_formatted);
            } else {
                showToast(data.message || 'Error loading cart', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error loading cart', 'danger');
        })
        .finally(() => {
            showLoading(false);
        });
}

function displayCartItems(items) {
    const container = document.getElementById('cart-items');
    
    if (!items || items.length === 0) {
        container.innerHTML = `
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Start browsing events to add tickets to your cart</p>
                    <a href="events.php" class="btn btn-primary">Browse Events</a>
                </div>
            </div>
        `;
        return;
    }
    
    let html = '';
    items.forEach(item => {
        html += `
            <div class="card mb-3" data-cart-id="${item.cart_id}">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="${item.image || 'assets/images/default-event.jpg'}" 
                                 class="img-fluid rounded" alt="${item.title}" 
                                 style="height: 80px; width: 100%; object-fit: cover;">
                        </div>
                        <div class="col-md-4">
                            <h5 class="mb-1">${item.title}</h5>
                            <p class="mb-1 text-muted">
                                <i class="fas fa-calendar me-1"></i>${item.formatted_date} at ${item.formatted_time}
                            </p>
                            <p class="mb-0 text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>${item.venue}
                            </p>
                        </div>
                        <div class="col-md-2">
                            <span class="fw-bold">${item.price_formatted}</span>
                            <small class="text-muted d-block">per ticket</small>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group">
                                <button class="btn btn-outline-secondary btn-sm" type="button" 
                                        onclick="updateQuantity(${item.cart_id}, ${item.quantity - 1})">-</button>
                                <input type="number" class="form-control form-control-sm text-center" 
                                       value="${item.quantity}" min="1" max="10" 
                                       onchange="updateQuantity(${item.cart_id}, this.value)">
                                <button class="btn btn-outline-secondary btn-sm" type="button" 
                                        onclick="updateQuantity(${item.cart_id}, ${item.quantity + 1})">+</button>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <span class="fw-bold text-success">${item.subtotal_formatted}</span>
                        </div>
                        <div class="col-md-1">
                            <button class="btn btn-outline-danger btn-sm" 
                                    onclick="removeItem(${item.cart_id})"
                                    title="Remove item">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function updateSummary(items, totalFormatted) {
    const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
    
    document.getElementById('total-items').textContent = totalItems;
    document.getElementById('total-amount').textContent = totalFormatted;
    
    const checkoutBtn = document.getElementById('checkout-btn');
    checkoutBtn.disabled = items.length === 0;
    
    updateCartCount();
}

function updateQuantity(cartId, newQuantity) {
    newQuantity = parseInt(newQuantity);
    
    if (newQuantity < 1) {
        removeItem(cartId);
        return;
    }
    
    if (newQuantity > 10) {
        showToast('Maximum 10 tickets per event', 'warning');
        return;
    }
    
    showLoading(true);
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update&cart_id=${cartId}&quantity=${newQuantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadCart(); // Reload cart to update totals
        } else {
            showToast(data.message || 'Error updating quantity', 'danger');
            loadCart(); // Reload to reset to previous state
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating quantity', 'danger');
        loadCart();
    })
    .finally(() => {
        showLoading(false);
    });
}

function removeItem(cartId) {
    if (!confirm('Remove this item from your cart?')) {
        return;
    }
    
    showLoading(true);
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove&cart_id=${cartId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Item removed from cart', 'success');
            loadCart();
        } else {
            showToast(data.message || 'Error removing item', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error removing item', 'danger');
    })
    .finally(() => {
        showLoading(false);
    });
}

function clearCart() {
    showLoading(true);
    
    fetch('api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Cart cleared', 'success');
            loadCart();
        } else {
            showToast(data.message || 'Error clearing cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error clearing cart', 'danger');
    })
    .finally(() => {
        showLoading(false);
    });
}

function showLoading(show) {
    const overlay = document.getElementById('loading-overlay');
    if (show) {
        overlay.classList.remove('d-none');
    } else {
        overlay.classList.add('d-none');
    }
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

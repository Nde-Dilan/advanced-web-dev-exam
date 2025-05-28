<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

// Get all bookings
try {
    $stmt = $pdo->query("
        SELECT b.*, 
               GROUP_CONCAT(e.title SEPARATOR ', ') as event_titles,
               SUM(bi.quantity) as total_tickets
        FROM bookings b
        LEFT JOIN booking_items bi ON b.id = bi.booking_id
        LEFT JOIN events e ON bi.event_id = e.id
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ");
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    $error = "Error loading bookings: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<!-- Custom Admin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-cogs"></i> EventBook Admin
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events.php">
                        <i class="fas fa-calendar"></i> Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="bookings.php">
                        <i class="fas fa-ticket-alt"></i> Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-line"></i> Reports
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../index.php">
                            <i class="fas fa-home"></i> View Site
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-ticket-alt"></i> Booking Management</h2>
                <div>
                    <button class="btn btn-outline-primary" onclick="exportBookings()">
                        <i class="fas fa-download"></i> Export CSV
                    </button>
                </div>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Filter Controls -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select class="form-select" id="statusFilter" onchange="filterBookings()">
                                <option value="">All Statuses</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="dateFilter" class="form-label">Date Range</label>
                            <select class="form-select" id="dateFilter" onchange="filterBookings()">
                                <option value="">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="searchFilter" class="form-label">Search</label>
                            <input type="text" class="form-control" id="searchFilter" placeholder="Search by reference, name, or email" onkeyup="filterBookings()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Bookings Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped" id="bookingsTable">
                            <thead>
                                <tr>
                                    <th>Booking Ref</th>
                                    <th>Customer</th>
                                    <th>Events</th>
                                    <th>Tickets</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr data-booking='<?php echo htmlspecialchars(json_encode($booking)); ?>'>
                                        <td>
                                            <strong class="text-primary"><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['email']); ?></small><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($booking['phone']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($booking['event_titles']); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?php echo $booking['total_tickets']; ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-success">$<?php echo number_format($booking['total_amount'], 2); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($booking['created_at'])); ?><br>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($booking['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $booking['booking_status'] === 'confirmed' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewBookingDetails(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" 
                                                        onclick="downloadBookingTickets(<?php echo $booking['id']; ?>)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
            <div class="modal-body" id="modalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function filterBookings() {
    const statusFilter = document.getElementById('statusFilter').value;
    const dateFilter = document.getElementById('dateFilter').value;
    const searchFilter = document.getElementById('searchFilter').value.toLowerCase();
    
    const table = document.getElementById('bookingsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) { // Skip header row
        const row = rows[i];
        const bookingData = JSON.parse(row.getAttribute('data-booking'));
        let show = true;
        
        // Status filter
        if (statusFilter && bookingData.booking_status !== statusFilter) {
            show = false;
        }
        
        // Date filter
        if (dateFilter) {
            const bookingDate = new Date(bookingData.created_at);
            const today = new Date();
            const daysDiff = Math.floor((today - bookingDate) / (1000 * 60 * 60 * 24));
            
            switch (dateFilter) {
                case 'today':
                    if (daysDiff !== 0) show = false;
                    break;
                case 'week':
                    if (daysDiff > 7) show = false;
                    break;
                case 'month':
                    if (daysDiff > 30) show = false;
                    break;
            }
        }
        
        // Search filter
        if (searchFilter) {
            const searchableText = (
                bookingData.booking_reference + ' ' +
                bookingData.first_name + ' ' +
                bookingData.last_name + ' ' +
                bookingData.email
            ).toLowerCase();
            
            if (!searchableText.includes(searchFilter)) {
                show = false;
            }
        }
        
        row.style.display = show ? '' : 'none';
    }
}

function clearFilters() {
    document.getElementById('statusFilter').value = '';
    document.getElementById('dateFilter').value = '';
    document.getElementById('searchFilter').value = '';
    filterBookings();
}

function viewBookingDetails(bookingId) {
    fetch(`../api/get_booking_details.php?id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalContent').innerHTML = data.html;
                const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
                modal.show();
            } else {
                alert('Error loading booking details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading booking details');
        });
}

function downloadBookingTickets(bookingId) {
    window.open(`../api/download_tickets.php?booking_id=${bookingId}`, '_blank');
}

function exportBookings() {
    // Create CSV export
    const table = document.getElementById('bookingsTable');
    const rows = table.getElementsByTagName('tr');
    let csv = 'Booking Reference,Customer Name,Email,Phone,Events,Tickets,Amount,Date,Status\n';
    
    for (let i = 1; i < rows.length; i++) {
        if (rows[i].style.display !== 'none') {
            const booking = JSON.parse(rows[i].getAttribute('data-booking'));
            csv += `"${booking.booking_reference}","${booking.first_name} ${booking.last_name}","${booking.email}","${booking.phone}","${booking.event_titles}",${booking.total_tickets},$${booking.total_amount},"${new Date(booking.created_at).toLocaleDateString()}","${booking.booking_status}"\n`;
        }
    }
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `bookings_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}
</script>

<?php require_once '../includes/footer.php'; ?>

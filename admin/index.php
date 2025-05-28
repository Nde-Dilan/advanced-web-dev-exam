<?php
session_start();
require_once '../config/database.php';

// Check if user is admin (you can modify this logic based on your user table structure)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
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
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="events.php">
                        <i class="fas fa-calendar"></i> Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="bookings.php">
                        <i class="fas fa-ticket-alt"></i> Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users"></i> Users
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
            <h2><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h2>
            <hr>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Events</h6>
                            <h3 id="total-events">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>Active events in system</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Bookings</h6>
                            <h3 id="total-bookings">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-ticket-alt fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>All time bookings</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Total Revenue</h6>
                            <h3 id="total-revenue">XAF 0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>Total earnings</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Active Users</h6>
                            <h3 id="total-users">0</h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small>Registered users</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Booking Trends (Last 7 Days)</h5>
                </div>
                <div class="card-body">
                    <canvas id="bookingChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-pie"></i> Event Categories</h5>
                </div>
                <div class="card-body">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5><i class="fas fa-history"></i> Recent Bookings</h5>
                    <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div id="recent-bookings" class="table-responsive">
                        <!-- Recent bookings will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5><i class="fas fa-calendar-plus"></i> Upcoming Events</h5>
                    <a href="events.php" class="btn btn-sm btn-outline-primary">Manage Events</a>
                </div>
                <div class="card-body">
                    <div id="upcoming-events">
                        <!-- Upcoming events will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadCharts();
    loadRecentActivity();
});

function loadDashboardData() {
    fetch('api/dashboard_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-events').textContent = data.stats.total_events;
                document.getElementById('total-bookings').textContent = data.stats.total_bookings;
                document.getElementById('total-revenue').textContent = 'XAF' + parseFloat(data.stats.total_revenue).toLocaleString();
                document.getElementById('total-users').textContent = data.stats.total_users;
            }
        })
        .catch(error => console.error('Error loading dashboard stats:', error));
}

function loadCharts() {
    // Booking trends chart
    fetch('api/booking_trends.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('bookingChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Bookings',
                            data: data.values,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    
    // Category chart (placeholder with dummy data)
    const categoryCtx = document.getElementById('categoryChart').getContext('2d');
    new Chart(categoryCtx, {
        type: 'doughnut',
        data: {
            labels: ['Concerts', 'Conferences', 'Sports', 'Theater', 'Other'],
            datasets: [{
                data: [30, 25, 20, 15, 10],
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true
        }
    });
}

function loadRecentActivity() {
    // Load recent bookings
    fetch('api/recent_bookings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRecentBookings(data.bookings);
            }
        });
    
    // Load upcoming events
    fetch('api/upcoming_events.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayUpcomingEvents(data.events);
            }
        });
}

function displayRecentBookings(bookings) {
    const container = document.getElementById('recent-bookings');
    
    if (!bookings || bookings.length === 0) {
        container.innerHTML = '<p class="text-muted">No recent bookings</p>';
        return;
    }
    
    let html = '<table class="table table-sm"><tbody>';
    bookings.forEach(booking => {
        html += `
            <tr>
                <td>
                    <strong>${booking.booking_reference}</strong><br>
                    <small class="text-muted">${booking.customer_name}</small>
                </td>
                <td class="text-end">
                    <span class="text-success">$${parseFloat(booking.total_amount).toFixed(2)}</span><br>
                    <small class="text-muted">${booking.formatted_date}</small>
                </td>
            </tr>
        `;
    });
    html += '</tbody></table>';
    
    container.innerHTML = html;
}

function displayUpcomingEvents(events) {
    const container = document.getElementById('upcoming-events');
    
    if (!events || events.length === 0) {
        container.innerHTML = '<p class="text-muted">No upcoming events</p>';
        return;
    }
    
    let html = '';
    events.forEach(event => {
        html += `
            <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="mb-1">${event.title}</h6>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>${event.formatted_date}
                        <i class="fas fa-map-marker-alt ms-2 me-1"></i>${event.venue}
                    </small>
                </div>
                <span class="badge bg-primary">${event.available_tickets} tickets</span>
            </div>
        `;
    });
    
    container.innerHTML = html;
}
</script>

<?php require_once '../includes/footer.php'; ?>

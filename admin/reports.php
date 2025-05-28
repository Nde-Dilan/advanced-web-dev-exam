<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

// Date range defaults
$start_date = $_GET['start_date'] ?? date('Y-m-01'); // First day of current month
$end_date = $_GET['end_date'] ?? date('Y-m-d'); // Today

// Report type
$report_type = $_GET['report_type'] ?? 'overview';

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
                    <a class="nav-link" href="bookings.php">
                        <i class="fas fa-ticket-alt"></i> Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="reports.php">
                        <i class="fas fa-chart-line"></i> Reports
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
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
            <h2><i class="fas fa-chart-line"></i> Reports & Analytics</h2>
            <hr>
            
            <!-- Report Filters -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-filter"></i> Report Filters</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label for="report_type" class="form-label">Report Type</label>
                                    <select class="form-select" name="report_type" id="report_type">
                                        <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                                        <option value="bookings" <?php echo $report_type === 'bookings' ? 'selected' : ''; ?>>Bookings Analysis</option>
                                        <option value="events" <?php echo $report_type === 'events' ? 'selected' : ''; ?>>Events Performance</option>
                                        <option value="revenue" <?php echo $report_type === 'revenue' ? 'selected' : ''; ?>>Revenue Analysis</option>
                                        <option value="users" <?php echo $report_type === 'users' ? 'selected' : ''; ?>>User Activity</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">&nbsp;</label>
                                    <div class="d-grid gap-2 d-md-flex">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i> Generate Report
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="exportReport()">
                                            <i class="fas fa-download"></i> Export
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Report Content -->
            <div id="reportContent">
                <!-- Content will be loaded based on report type -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    loadReportData();
});

function loadReportData() {
    const reportType = '<?php echo $report_type; ?>';
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    
    fetch(`api/reports.php?type=${reportType}&start_date=${startDate}&end_date=${endDate}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayReport(data.report, reportType);
            } else {
                document.getElementById('reportContent').innerHTML = 
                    '<div class="alert alert-danger">Error loading report: ' + (data.message || 'Unknown error') + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('reportContent').innerHTML = 
                '<div class="alert alert-danger">Error loading report data</div>';
        });
}

function displayReport(report, type) {
    const container = document.getElementById('reportContent');
    
    switch(type) {
        case 'overview':
            displayOverviewReport(report, container);
            break;
        case 'bookings':
            displayBookingsReport(report, container);
            break;
        case 'events':
            displayEventsReport(report, container);
            break;
        case 'revenue':
            displayRevenueReport(report, container);
            break;
        case 'users':
            displayUsersReport(report, container);
            break;
    }
}

function displayOverviewReport(report, container) {
    container.innerHTML = `
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Bookings</h6>
                                <h3>${report.total_bookings}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Total Revenue</h6>
                                <h3>XAF ${parseFloat(report.total_revenue).toLocaleString()}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-dollar-sign fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Tickets Sold</h6>
                                <h3>${report.total_tickets}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h6>Active Events</h6>
                                <h3>${report.active_events}</h3>
                            </div>
                            <div class="align-self-center">
                                <i class="fas fa-calendar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Daily Booking Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="dailyBookingsChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Top Events</h5>
                    </div>
                    <div class="card-body">
                        <div id="topEventsList">
                            ${report.top_events.map(event => `
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="mb-0">${event.title}</h6>
                                        <small class="text-muted">${event.bookings} bookings</small>
                                    </div>
                                    <span class="badge bg-primary">XAF ${parseFloat(event.revenue).toLocaleString()}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Create chart
    if (report.daily_bookings) {
        const ctx = document.getElementById('dailyBookingsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: report.daily_bookings.labels,
                datasets: [{
                    label: 'Bookings',
                    data: report.daily_bookings.values,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

function displayBookingsReport(report, container) {
    container.innerHTML = `
        <div class="card">
            <div class="card-header">
                <h5>Bookings Analysis</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Booking Reference</th>
                                <th>Customer</th>
                                <th>Event</th>
                                <th>Tickets</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${report.bookings.map(booking => `
                                <tr>
                                    <td>${new Date(booking.created_at).toLocaleDateString()}</td>
                                    <td>${booking.booking_reference}</td>
                                    <td>${booking.first_name} ${booking.last_name}</td>
                                    <td>${booking.event_titles}</td>
                                    <td>${booking.total_tickets}</td>
                                    <td>XAF ${parseFloat(booking.total_amount).toFixed(2)}</td>
                                    <td><span class="badge bg-${booking.booking_status === 'confirmed' ? 'success' : 'secondary'}">${booking.booking_status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

function displayEventsReport(report, container) {
    container.innerHTML = `
        <div class="card">
            <div class="card-header">
                <h5>Events Performance</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date</th>
                                <th>Total Tickets</th>
                                <th>Sold</th>
                                <th>Available</th>
                                <th>Revenue</th>
                                <th>Conversion Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${report.events.map(event => `
                                <tr>
                                    <td>${event.title}</td>
                                    <td>${new Date(event.event_date).toLocaleDateString()}</td>
                                    <td>${event.total_tickets}</td>
                                    <td>${event.tickets_sold}</td>
                                    <td>${event.available_tickets}</td>
                                    <td>XAF ${parseFloat(event.revenue || 0).toFixed(2)}</td>
                                    <td>${(((event.tickets_sold / event.total_tickets) * 100) || 0).toFixed(1)}%</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

function displayRevenueReport(report, container) {
    container.innerHTML = `
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Revenue Trends</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Revenue Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Total Revenue:</strong>XAF ${parseFloat(report.total_revenue).toLocaleString()}</p>
                        <p><strong>Service Fees:</strong>XAF ${parseFloat(report.total_service_fees).toLocaleString()}</p>
                        <p><strong>Net Revenue:</strong>XAF ${parseFloat(report.net_revenue).toLocaleString()}</p>
                        <p><strong>Average Order:</strong>XAF ${parseFloat(report.average_order).toFixed(2)}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Create revenue chart
    if (report.daily_revenue) {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: report.daily_revenue.labels,
                datasets: [{
                    label: 'Revenue',
                    data: report.daily_revenue.values,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
}

function displayUsersReport(report, container) {
    container.innerHTML = `
        <div class="card">
            <div class="card-header">
                <h5>User Activity Report</h5>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-primary">${report.total_users}</h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-success">${report.active_users}</h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-info">${report.new_users}</h3>
                            <p>New Users</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <h3 class="text-warning">${parseFloat(report.conversion_rate).toFixed(1)}%</h3>
                            <p>Conversion Rate</p>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Joined</th>
                                <th>Total Bookings</th>
                                <th>Total Spent</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${report.user_details.map(user => `
                                <tr>
                                    <td>${user.username}</td>
                                    <td>${user.email}</td>
                                    <td>${new Date(user.created_at).toLocaleDateString()}</td>
                                    <td>${user.total_bookings}</td>
                                    <td>XAF ${parseFloat(user.total_spent || 0).toFixed(2)}</td>
                                    <td>${user.last_booking ? new Date(user.last_booking).toLocaleDateString() : 'Never'}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

function exportReport() {
    const reportType = '<?php echo $report_type; ?>';
    const startDate = '<?php echo $start_date; ?>';
    const endDate = '<?php echo $end_date; ?>';
    
    const url = `api/export_report.php?type=${reportType}&start_date=${startDate}&end_date=${endDate}`;
    window.open(url, '_blank');
}
</script>

<?php require_once '../includes/footer.php'; ?>

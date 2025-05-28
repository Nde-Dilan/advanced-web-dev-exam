<?php
session_start();
require_once '../../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../../auth/login.php');
    exit;
}

$type = $_GET['type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="report_' . $type . '_' . $start_date . '_to_' . $end_date . '.csv"');

$output = fopen('php://output', 'w');

try {
    switch($type) {
        case 'overview':
            exportOverviewReport($pdo, $output, $start_date, $end_date);
            break;
        case 'bookings':
            exportBookingsReport($pdo, $output, $start_date, $end_date);
            break;
        case 'events':
            exportEventsReport($pdo, $output, $start_date, $end_date);
            break;
        case 'revenue':
            exportRevenueReport($pdo, $output, $start_date, $end_date);
            break;
        case 'users':
            exportUsersReport($pdo, $output, $start_date, $end_date);
            break;
        default:
            fputcsv($output, ['Error: Invalid report type']);
    }
} catch (Exception $e) {
    fputcsv($output, ['Error: ' . $e->getMessage()]);
}

fclose($output);

function exportOverviewReport($pdo, $output, $start_date, $end_date) {
    // Write summary
    fputcsv($output, ['EventBook Admin Report - Overview']);
    fputcsv($output, ['Report Period', $start_date . ' to ' . $end_date]);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Get summary stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'");
    $stmt->execute([$start_date, $end_date]);
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT SUM(bi.quantity) as count 
        FROM booking_items bi 
        JOIN bookings b ON bi.booking_id = b.id 
        WHERE DATE(b.created_at) BETWEEN ? AND ? AND b.booking_status = 'confirmed'
    ");
    $stmt->execute([$start_date, $end_date]);
    $total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    fputcsv($output, ['Metric', 'Value']);
    fputcsv($output, ['Total Bookings', $total_bookings]);
    fputcsv($output, ['Total Revenue', 'XAF' . number_format($total_revenue, 2)]);
    fputcsv($output, ['Total Tickets Sold', $total_tickets]);
    fputcsv($output, []);
    
    // Daily trends
    fputcsv($output, ['Daily Booking Trends']);
    fputcsv($output, ['Date', 'Bookings', 'Revenue']);
    
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as bookings, SUM(total_amount) as revenue
        FROM bookings 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['date'],
            $row['bookings'],
            'XAF' . number_format($row['revenue'] ?? 0, 2)
        ]);
    }
}

function exportBookingsReport($pdo, $output, $start_date, $end_date) {
    fputcsv($output, ['EventBook Admin Report - Bookings']);
    fputcsv($output, ['Report Period', $start_date . ' to ' . $end_date]);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, [
        'Booking Reference',
        'Customer Name',
        'Email',
        'Phone',
        'Events',
        'Total Tickets',
        'Total Amount',
        'Service Fee',
        'Payment Status',
        'Booking Status',
        'Created Date'
    ]);
    
    $stmt = $pdo->prepare("
        SELECT b.*, GROUP_CONCAT(e.title SEPARATOR ', ') as event_titles,
               SUM(bi.quantity) as total_tickets
        FROM bookings b
        LEFT JOIN booking_items bi ON b.id = bi.booking_id
        LEFT JOIN events e ON bi.event_id = e.id
        WHERE DATE(b.created_at) BETWEEN ? AND ?
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['booking_reference'],
            $row['first_name'] . ' ' . $row['last_name'],
            $row['email'],
            $row['phone'],
            $row['event_titles'],
            $row['total_tickets'],
            'XAF' . number_format($row['total_amount'], 2),
            'XAF' . number_format($row['service_fee'], 2),
            $row['payment_status'],
            $row['booking_status'],
            $row['created_at']
        ]);
    }
}

function exportEventsReport($pdo, $output, $start_date, $end_date) {
    fputcsv($output, ['EventBook Admin Report - Events Performance']);
    fputcsv($output, ['Report Period', $start_date . ' to ' . $end_date]);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, [
        'Event Title',
        'Event Date',
        'Venue',
        'Location',
        'Ticket Price',
        'Total Tickets',
        'Tickets Sold',
        'Available Tickets',
        'Revenue',
        'Conversion Rate'
    ]);
    
    $stmt = $pdo->prepare("
        SELECT e.*,
               COALESCE(SUM(bi.quantity), 0) as tickets_sold,
               COALESCE(SUM(bi.subtotal), 0) as revenue
        FROM events e
        LEFT JOIN booking_items bi ON e.id = bi.event_id
        LEFT JOIN bookings b ON bi.booking_id = b.id 
            AND DATE(b.created_at) BETWEEN ? AND ?
            AND b.booking_status = 'confirmed'
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $conversion_rate = $row['total_tickets'] > 0 ? 
            round(($row['tickets_sold'] / $row['total_tickets']) * 100, 1) : 0;
            
        fputcsv($output, [
            $row['title'],
            $row['event_date'],
            $row['venue'],
            $row['location'],
            'XAF' . number_format($row['price'], 2),
            $row['total_tickets'],
            $row['tickets_sold'],
            $row['available_tickets'],
            'XAF' . number_format($row['revenue'], 2),
            $conversion_rate . '%'
        ]);
    }
}

function exportRevenueReport($pdo, $output, $start_date, $end_date) {
    fputcsv($output, ['EventBook Admin Report - Revenue Analysis']);
    fputcsv($output, ['Report Period', $start_date . ' to ' . $end_date]);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    // Summary
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'");
    $stmt->execute([$start_date, $end_date]);
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT SUM(service_fee) as total FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'");
    $stmt->execute([$start_date, $end_date]);
    $total_service_fees = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    fputcsv($output, ['Revenue Summary']);
    fputcsv($output, ['Total Revenue', 'XAF' . number_format($total_revenue, 2)]);
    fputcsv($output, ['Service Fees', 'XAF' . number_format($total_service_fees, 2)]);
    fputcsv($output, ['Net Revenue', 'XAF' . number_format($total_revenue - $total_service_fees, 2)]);
    fputcsv($output, []);
    
    // Daily breakdown
    fputcsv($output, ['Daily Revenue Breakdown']);
    fputcsv($output, ['Date', 'Gross Revenue', 'Service Fees', 'Net Revenue', 'Orders']);
    
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, 
               SUM(total_amount) as gross_revenue,
               SUM(service_fee) as service_fees,
               COUNT(*) as orders
        FROM bookings 
        WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $net_revenue = $row['gross_revenue'] - $row['service_fees'];
        fputcsv($output, [
            $row['date'],
            'XAF' . number_format($row['gross_revenue'], 2),
            'XAF' . number_format($row['service_fees'], 2),
            'XAF' . number_format($net_revenue, 2),
            $row['orders']
        ]);
    }
}

function exportUsersReport($pdo, $output, $start_date, $end_date) {
    fputcsv($output, ['EventBook Admin Report - User Activity']);
    fputcsv($output, ['Report Period', $start_date . ' to ' . $end_date]);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s')]);
    fputcsv($output, []);
    
    fputcsv($output, [
        'Username',
        'Email',
        'Join Date',
        'Total Bookings',
        'Total Spent',
        'Last Booking',
        'Status'
    ]);
    
    $stmt = $pdo->prepare("
        SELECT u.username, u.email, u.created_at,
               COUNT(b.id) as total_bookings,
               COALESCE(SUM(b.total_amount), 0) as total_spent,
               MAX(b.created_at) as last_booking
        FROM users u
        LEFT JOIN bookings b ON u.id = b.user_id
        WHERE u.created_at BETWEEN ? AND ?
        GROUP BY u.id
        ORDER BY total_spent DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = $row['total_bookings'] > 0 ? 'Active' : 'Inactive';
        
        fputcsv($output, [
            $row['username'],
            $row['email'],
            $row['created_at'],
            $row['total_bookings'],
            'XAF' . number_format($row['total_spent'], 2),
            $row['last_booking'] ?? 'Never',
            $status
        ]);
    }
}
?>

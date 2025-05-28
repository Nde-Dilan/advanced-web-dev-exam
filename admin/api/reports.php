<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? 'overview';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

try {
    $report = [];
    
    switch($type) {
        case 'overview':
            $report = generateOverviewReport($pdo, $start_date, $end_date);
            break;
        case 'bookings':
            $report = generateBookingsReport($pdo, $start_date, $end_date);
            break;
        case 'events':
            $report = generateEventsReport($pdo, $start_date, $end_date);
            break;
        case 'revenue':
            $report = generateRevenueReport($pdo, $start_date, $end_date);
            break;
        case 'users':
            $report = generateUsersReport($pdo, $start_date, $end_date);
            break;
        default:
            throw new Exception('Invalid report type');
    }
    
    echo json_encode([
        'success' => true,
        'report' => $report
    ]);
    
} catch (Exception $e) {
    error_log("Reports API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateOverviewReport($pdo, $start_date, $end_date) {
    // Total bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total revenue
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'");
    $stmt->execute([$start_date, $end_date]);
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Total tickets sold
    $stmt = $pdo->prepare("
        SELECT SUM(bi.quantity) as count 
        FROM booking_items bi 
        JOIN bookings b ON bi.booking_id = b.id 
        WHERE DATE(b.created_at) BETWEEN ? AND ? AND b.booking_status = 'confirmed'
    ");
    $stmt->execute([$start_date, $end_date]);
    $total_tickets = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Active events
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM events WHERE event_date >= CURDATE()");
    $stmt->execute();
    $active_events = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Daily booking trends
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM bookings 
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $daily_bookings = [
        'labels' => array_map(function($row) { return date('M j', strtotime($row['date'])); }, $daily_data),
        'values' => array_map(function($row) { return (int)$row['count']; }, $daily_data)
    ];
    
    // Top events
    $stmt = $pdo->prepare("
        SELECT e.title, COUNT(b.id) as bookings, COALESCE(SUM(b.total_amount), 0) as revenue
        FROM events e
        LEFT JOIN booking_items bi ON e.id = bi.event_id
        LEFT JOIN bookings b ON bi.booking_id = b.id AND DATE(b.created_at) BETWEEN ? AND ?
        WHERE b.booking_status = 'confirmed' OR b.id IS NULL
        GROUP BY e.id
        ORDER BY revenue DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_bookings' => (int)$total_bookings,
        'total_revenue' => (float)$total_revenue,
        'total_tickets' => (int)$total_tickets,
        'active_events' => (int)$active_events,
        'daily_bookings' => $daily_bookings,
        'top_events' => $top_events
    ];
}

function generateBookingsReport($pdo, $start_date, $end_date) {
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
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['bookings' => $bookings];
}

function generateEventsReport($pdo, $start_date, $end_date) {
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
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return ['events' => $events];
}

function generateRevenueReport($pdo, $start_date, $end_date) {
    // Total revenue
    $stmt = $pdo->prepare("SELECT SUM(total_amount) as total FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'");
    $stmt->execute([$start_date, $end_date]);
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Service fees
    $stmt = $pdo->prepare("SELECT SUM(service_fee) as total FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'");
    $stmt->execute([$start_date, $end_date]);
    $total_service_fees = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Net revenue
    $net_revenue = $total_revenue - $total_service_fees;
    
    // Average order value
    $stmt = $pdo->prepare("SELECT AVG(total_amount) as avg FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'");
    $stmt->execute([$start_date, $end_date]);
    $average_order = $stmt->fetch(PDO::FETCH_ASSOC)['avg'] ?? 0;
    
    // Daily revenue
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as date, SUM(total_amount) as revenue
        FROM bookings 
        WHERE DATE(created_at) BETWEEN ? AND ? AND payment_status = 'completed'
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $daily_revenue = [
        'labels' => array_map(function($row) { return date('M j', strtotime($row['date'])); }, $daily_data),
        'values' => array_map(function($row) { return (float)$row['revenue']; }, $daily_data)
    ];
    
    return [
        'total_revenue' => (float)$total_revenue,
        'total_service_fees' => (float)$total_service_fees,
        'net_revenue' => (float)$net_revenue,
        'average_order' => (float)$average_order,
        'daily_revenue' => $daily_revenue
    ];
}

function generateUsersReport($pdo, $start_date, $end_date) {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active users (users who made bookings in the period)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT user_id) as count 
        FROM bookings 
        WHERE DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // New users in period
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$start_date, $end_date]);
    $new_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Conversion rate
    $conversion_rate = $total_users > 0 ? ($active_users / $total_users) * 100 : 0;
    
    // User details with booking stats
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
        LIMIT 50
    ");
    $stmt->execute([$start_date, $end_date]);
    $user_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'total_users' => (int)$total_users,
        'active_users' => (int)$active_users,
        'new_users' => (int)$new_users,
        'conversion_rate' => (float)$conversion_rate,
        'user_details' => $user_details
    ];
}
?>

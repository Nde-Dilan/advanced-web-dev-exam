<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get total events
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM events");
    $total_events = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total bookings
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM bookings");
    $total_bookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Get total revenue
    $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM bookings WHERE payment_status = 'completed'");
    $total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Get total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_events' => (int)$total_events,
            'total_bookings' => (int)$total_bookings,
            'total_revenue' => (float)$total_revenue,
            'total_users' => (int)$total_users
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>

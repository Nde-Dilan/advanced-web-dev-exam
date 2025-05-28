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
    // Get recent bookings (last 10)
    $stmt = $pdo->prepare("
        SELECT b.booking_reference, b.total_amount, b.created_at,
               CONCAT(b.first_name, ' ', b.last_name) as customer_name
        FROM bookings b
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($bookings as &$booking) {
        $booking['formatted_date'] = date('M j, g:i A', strtotime($booking['created_at']));
    }
    
    echo json_encode([
        'success' => true,
        'bookings' => $bookings
    ]);
    
} catch (PDOException $e) {
    error_log("Recent bookings error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>

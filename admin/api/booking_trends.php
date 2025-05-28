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
    // Get booking data for the last 7 days
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) as booking_date, COUNT(*) as count
        FROM bookings 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY booking_date ASC
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create arrays for chart data
    $labels = [];
    $values = [];
    
    // Fill in the last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M j', strtotime($date));
        
        // Find count for this date
        $count = 0;
        foreach ($results as $result) {
            if ($result['booking_date'] === $date) {
                $count = $result['count'];
                break;
            }
        }
        $values[] = (int)$count;
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'values' => $values
    ]);
    
} catch (PDOException $e) {
    error_log("Booking trends error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>

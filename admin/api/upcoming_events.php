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
    // Get upcoming events (next 5)
    $stmt = $pdo->prepare("
        SELECT id, title, event_date, event_time, venue, available_tickets
        FROM events 
        WHERE event_date >= CURDATE()
        ORDER BY event_date ASC, event_time ASC
        LIMIT 5
    ");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($events as &$event) {
        $event['formatted_date'] = date('M j, Y g:i A', strtotime($event['event_date'] . ' ' . $event['event_time']));
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (PDOException $e) {
    error_log("Upcoming events error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>

<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $featured = isset($_GET['featured']) && $_GET['featured'] === 'true';
    
    if ($featured) {
        $stmt = $db->prepare("SELECT * FROM events WHERE is_featured = 1 AND status = 'active' ORDER BY event_date ASC LIMIT 6");
        $stmt->execute();
    } else {
        $stmt = $db->prepare("SELECT * FROM events WHERE status = 'active' ORDER BY event_date ASC");
        $stmt->execute();
    }
    
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format events data
    foreach ($events as &$event) {
        $event['formatted_date'] = date('M d, Y', strtotime($event['event_date']));
        $event['formatted_time'] = date('g:i A', strtotime($event['event_time']));
        $event['price_formatted'] = '$' . number_format($event['price'], 2);
    }
    
    echo json_encode($events);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch events']);
}
?>
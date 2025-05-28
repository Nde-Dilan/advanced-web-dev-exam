<?php
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $name = $_GET['name'] ?? '';
    $location = $_GET['location'] ?? '';
    $date = $_GET['date'] ?? '';
    
    $sql = "SELECT * FROM events WHERE status = 'active'";
    $params = [];
    
    if (!empty($name)) {
        $sql .= " AND (title LIKE ? OR description LIKE ?)";
        $params[] = "%$name%";
        $params[] = "%$name%";
    }
    
    if (!empty($location)) {
        $sql .= " AND venue LIKE ?";
        $params[] = "%$location%";
    }
    
    if (!empty($date)) {
        $sql .= " AND DATE(event_date) = ?";
        $params[] = $date;
    }
    
    $sql .= " ORDER BY event_date ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
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
    echo json_encode(['error' => 'Failed to search events']);
}
?>
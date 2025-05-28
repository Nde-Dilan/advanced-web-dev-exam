<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();
    
    // Get cart items
    $stmt = $pdo->prepare("
        SELECT c.id as cart_id, c.quantity, c.event_id,
               e.title, e.price, e.available_tickets
        FROM cart c 
        JOIN events e ON c.event_id = e.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($cart_items)) {
        throw new Exception('Cart is empty');
    }
    
    // Calculate total
    $total = 0;
    foreach ($cart_items as $item) {
        // Check if tickets are still available
        if ($item['available_tickets'] < $item['quantity']) {
            throw new Exception("Not enough tickets available for {$item['title']}");
        }
        $total += $item['price'] * $item['quantity'];
    }
    
    // Add service fee (5%)
    $service_fee = $total * 0.05;
    $final_total = $total + $service_fee;
    
    // Create booking
    $booking_reference = 'BK' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $stmt = $pdo->prepare("
        INSERT INTO bookings (user_id, booking_reference, total_amount, service_fee, 
                             first_name, last_name, email, phone, 
                             address, city, state, zip, 
                             payment_status, booking_status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', 'confirmed', NOW())
    ");
    
    $stmt->execute([
        $user_id,
        $booking_reference,
        $final_total,
        $service_fee,
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['address'],
        $_POST['city'],
        $_POST['state'],
        $_POST['zip']
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // Create booking items and update event tickets
    foreach ($cart_items as $item) {
        // Insert booking item
        $stmt = $pdo->prepare("
            INSERT INTO booking_items (booking_id, event_id, quantity, unit_price, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");
        $subtotal = $item['price'] * $item['quantity'];
        $stmt->execute([$booking_id, $item['event_id'], $item['quantity'], $item['price'], $subtotal]);
        
        // Update available tickets
        $stmt = $pdo->prepare("
            UPDATE events 
            SET available_tickets = available_tickets - ? 
            WHERE id = ?
        ");
        $stmt->execute([$item['quantity'], $item['event_id']]);
    }
    
    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'booking_reference' => $booking_reference,
        'message' => 'Booking confirmed successfully'
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Order processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>

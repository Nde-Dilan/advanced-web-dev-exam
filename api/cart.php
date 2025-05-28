<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}


$user_id = $_SESSION['user_id'];

// Add this validation to check if user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$user_id]);
if (!$stmt->fetch()) {
    // User doesn't exist, clear session
    session_destroy();
    echo json_encode(['success' => false, 'message' => 'Invalid user session. Please login again.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $event_id = (int)($_POST['event_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if (!$event_id || !$quantity) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }
            
            // Check if event exists and has enough tickets
            $stmt = $pdo->prepare("SELECT available_tickets, price FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$event) {
                echo json_encode(['success' => false, 'message' => 'Event not found']);
                exit;
            }
            
            if ($event['available_tickets'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Not enough tickets available']);
                exit;
            }
            
            // Check if item already in cart
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND event_id = ?");
            $stmt->execute([$user_id, $event_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update quantity
                $new_quantity = $existing['quantity'] + $quantity;
                if ($new_quantity > $event['available_tickets']) {
                    echo json_encode(['success' => false, 'message' => 'Total quantity exceeds available tickets']);
                    exit;
                }
                
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND event_id = ?");
                $stmt->execute([$new_quantity, $user_id, $event_id]);
            } else {
                // Add new item
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, event_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $event_id, $quantity]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Added to cart successfully']);
            break;
            
        case 'update':
            $cart_id = (int)($_POST['cart_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if (!$cart_id || !$quantity) {
                echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
                exit;
            }
            
            // Check if cart item belongs to user
            $stmt = $pdo->prepare("SELECT c.event_id FROM cart c WHERE c.id = ? AND c.user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cart_item) {
                echo json_encode(['success' => false, 'message' => 'Cart item not found']);
                exit;
            }
            
            // Check available tickets
            $stmt = $pdo->prepare("SELECT available_tickets FROM events WHERE id = ?");
            $stmt->execute([$cart_item['event_id']]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($quantity > $event['available_tickets']) {
                echo json_encode(['success' => false, 'message' => 'Not enough tickets available']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
            break;
            
        case 'remove':
            $cart_id = (int)($_POST['cart_id'] ?? 0);
            
            if (!$cart_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid cart ID']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
            break;
            
        case 'get':
            $stmt = $pdo->prepare("
                SELECT c.id as cart_id, c.quantity, 
                       e.id, e.title, e.event_date, e.event_time, e.venue, e.price, e.image,
                       (c.quantity * e.price) as subtotal
                FROM cart c 
                JOIN events e ON c.event_id = e.id 
                WHERE c.user_id = ? 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $total = 0;
            foreach ($items as &$item) {
                $item['formatted_date'] = date('M j, Y', strtotime($item['event_date']));
                $item['formatted_time'] = date('g:i A', strtotime($item['event_time']));
                $item['price_formatted'] = 'XAF' . number_format($item['price'], 2);
                $item['subtotal_formatted'] = 'XAF' . number_format($item['subtotal'], 2);
                $total += $item['subtotal'];
            }
            
            echo json_encode([
                'success' => true,
                'items' => $items,
                'total' => $total,
                'total_formatted' => 'XAF' . number_format($total, 2)
            ]);
            break;
            
        case 'count':
            $stmt = $pdo->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['count' => (int)($result['count'] ?? 0)]);
            break;
            
        case 'clear':
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Cart cleared']);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    error_log("Cart API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error' . $e->getMessage()]);
}
?>

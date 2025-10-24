<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

// Enable CORS for mobile apps
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['event_id']) || !isset($input['user_id']) || !isset($input['num_tickets'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$event_id = intval($input['event_id']);
$user_id = intval($input['user_id']);
$num_tickets = intval($input['num_tickets']);

// Validate ticket count
if ($num_tickets <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid number of tickets']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check event existence and available tickets
    $stmt = $pdo->prepare("SELECT available_tickets, name FROM events WHERE id = ? FOR UPDATE");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit();
    }
    
    if ($event['available_tickets'] < $num_tickets) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Not enough tickets available',
            'available' => $event['available_tickets']
        ]);
        exit();
    }
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit();
    }
    
    // Create booking
    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, event_id, num_tickets, status) VALUES (?, ?, ?, 'confirmed')");
    $stmt->execute([$user_id, $event_id, $num_tickets]);
    $booking_id = $pdo->lastInsertId();
    
    // Update available tickets
    $stmt = $pdo->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
    $stmt->execute([$num_tickets, $event_id]);
    
    $pdo->commit();
    
    // Return success response with booking details
    echo json_encode([
        'success' => true,
        'message' => 'Tickets booked successfully',
        'booking_id' => $booking_id,
        'event_name' => $event['name'],
        'num_tickets' => $num_tickets,
        'remaining_tickets' => $event['available_tickets'] - $num_tickets
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Booking failed: ' . $e->getMessage()
    ]);
}
?>
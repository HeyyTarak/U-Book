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
if (!isset($input['booking_id']) || !isset($input['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing booking ID or user ID']);
    exit();
}

$booking_id = intval($input['booking_id']);
$user_id = intval($input['user_id']);

try {
    $pdo->beginTransaction();
    
    // Check if booking exists and belongs to user
    $stmt = $pdo->prepare("
        SELECT b.id, b.event_id, b.num_tickets, b.status, e.name as event_name 
        FROM bookings b 
        JOIN events e ON b.event_id = e.id 
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    if ($booking['status'] === 'cancelled') {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Booking already cancelled']);
        exit();
    }
    
    // Update booking status to cancelled
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$booking_id]);
    
    // Return tickets to event
    $stmt = $pdo->prepare("UPDATE events SET available_tickets = available_tickets + ? WHERE id = ?");
    $stmt->execute([$booking['num_tickets'], $booking['event_id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking cancelled successfully',
        'event_name' => $booking['event_name'],
        'tickets_returned' => $booking['num_tickets']
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Cancellation failed: ' . $e->getMessage()
    ]);
}
?>
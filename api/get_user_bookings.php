<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

$user_id = intval($_GET['user_id']);

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id as booking_id,
            b.num_tickets,
            b.booking_date,
            b.status,
            e.id as event_id,
            e.name as event_name,
            e.event_date,
            e.venue
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates
    foreach ($bookings as &$booking) {
        $booking['booking_date'] = date('c', strtotime($booking['booking_date']));
        $booking['event_date'] = date('c', strtotime($booking['event_date']));
        $booking['num_tickets'] = intval($booking['num_tickets']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $bookings,
        'count' => count($bookings)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
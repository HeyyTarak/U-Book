<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Simple token-based auth for API
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

try {
    // Total events
    $stmt = $pdo->query("SELECT COUNT(*) as total_events FROM events");
    $total_events = $stmt->fetchColumn();
    
    // Total bookings
    $stmt = $pdo->query("SELECT COUNT(*) as total_bookings FROM bookings WHERE status = 'confirmed'");
    $total_bookings = $stmt->fetchColumn();
    
    // Total available tickets
    $stmt = $pdo->query("SELECT SUM(available_tickets) as available_tickets FROM events");
    $available_tickets = $stmt->fetchColumn();
    
    // Today's bookings
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as today_bookings 
        FROM bookings 
        WHERE DATE(booking_date) = CURDATE() AND status = 'confirmed'
    ");
    $stmt->execute();
    $today_bookings = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_events' => (int)$total_events,
            'total_bookings' => (int)$total_bookings,
            'available_tickets' => (int)$available_tickets,
            'today_bookings' => (int)$today_bookings
        ]
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    ]);
}
?>
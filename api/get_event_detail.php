<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Event ID required']);
    exit();
}

$event_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("
        SELECT id, name, description, event_date, venue, total_tickets, available_tickets 
        FROM events 
        WHERE id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Event not found']);
        exit();
    }
    
    // Format response
    $event['event_date'] = date('c', strtotime($event['event_date']));
    $event['available_tickets'] = intval($event['available_tickets']);
    $event['total_tickets'] = intval($event['total_tickets']);
    
    echo json_encode([
        'success' => true,
        'data' => $event
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
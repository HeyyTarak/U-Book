<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

try {
    $stmt = $pdo->query("
        SELECT id, name, description, event_date, venue, total_tickets, available_tickets 
        FROM events 
        WHERE event_date >= NOW() 
        ORDER BY event_date ASC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format dates for better client-side handling
    foreach ($events as &$event) {
        $event['event_date'] = date('c', strtotime($event['event_date']));
        $event['available_tickets'] = intval($event['available_tickets']);
        $event['total_tickets'] = intval($event['total_tickets']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $events,
        'count' => count($events)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/manage_events.php');
    exit();
}

$bulk_action = $_POST['bulk_action'] ?? '';
$event_ids = $_POST['event_ids'] ?? [];

if (empty($event_ids) || empty($bulk_action)) {
    $_SESSION['error'] = 'No events selected or invalid action';
    header('Location: ../admin/manage_events.php');
    exit();
}

// Convert to integers and validate
$event_ids = array_map('intval', $event_ids);
$event_ids = array_filter($event_ids);

if (empty($event_ids)) {
    $_SESSION['error'] = 'Invalid event IDs';
    header('Location: ../admin/manage_events.php');
    exit();
}

try {
    $placeholders = str_repeat('?,', count($event_ids) - 1) . '?';
    
    switch ($bulk_action) {
        case 'delete':
            // Check if any events have active bookings
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as booking_count 
                FROM bookings 
                WHERE event_id IN ($placeholders) AND status = 'confirmed'
            ");
            $stmt->execute($event_ids);
            $result = $stmt->fetch();
            
            if ($result['booking_count'] > 0) {
                $_SESSION['error'] = 'Cannot delete events with active bookings';
                header('Location: ../admin/manage_events.php');
                exit();
            }
            
            // Delete events
            $stmt = $pdo->prepare("DELETE FROM events WHERE id IN ($placeholders)");
            $stmt->execute($event_ids);
            $deleted_count = $stmt->rowCount();
            
            $_SESSION['success'] = "Successfully deleted $deleted_count event(s)";
            break;
            
        case 'cancel':
            // Cancel all bookings for these events and return tickets
            $pdo->beginTransaction();
            
            // Get total tickets to return for each event
            $stmt = $pdo->prepare("
                SELECT event_id, SUM(num_tickets) as total_tickets 
                FROM bookings 
                WHERE event_id IN ($placeholders) AND status = 'confirmed' 
                GROUP BY event_id
            ");
            $stmt->execute($event_ids);
            $tickets_to_return = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Update bookings status to cancelled
            $stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'cancelled', cancelled_at = NOW() 
                WHERE event_id IN ($placeholders) AND status = 'confirmed'
            ");
            $stmt->execute($event_ids);
            $cancelled_count = $stmt->rowCount();
            
            // Return tickets to events
            foreach ($tickets_to_return as $ticket_info) {
                $stmt = $pdo->prepare("
                    UPDATE events 
                    SET available_tickets = available_tickets + ? 
                    WHERE id = ?
                ");
                $stmt->execute([$ticket_info['total_tickets'], $ticket_info['event_id']]);
            }
            
            $pdo->commit();
            $_SESSION['success'] = "Cancelled $cancelled_count booking(s) and returned tickets";
            break;
            
        default:
            $_SESSION['error'] = 'Invalid bulk action';
    }
    
    header('Location: ../admin/manage_events.php');
    exit();
    
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = 'Bulk operation failed: ' . $e->getMessage();
    header('Location: ../admin/manage_events.php');
    exit();
}
?>
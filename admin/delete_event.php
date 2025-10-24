<?php
require_once 'auth_check.php';
require_once '../includes/config.php';

if (!isset($_GET['id'])) {
    header('Location: manage_events.php');
    exit();
}

$event_id = intval($_GET['id']);

// Check if there are any bookings for this event
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE event_id = ? AND status = 'confirmed'");
    $stmt->execute([$event_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['booking_count'] > 0) {
        $_SESSION['error'] = "Cannot delete event with active bookings. Cancel bookings first.";
        header('Location: manage_events.php');
        exit();
    }
    
    // Delete the event
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    
    $_SESSION['success'] = "Event deleted successfully!";
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting event: " . $e->getMessage();
}

header('Location: manage_events.php');
exit();
?>
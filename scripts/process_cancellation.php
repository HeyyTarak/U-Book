<?php
require_once '../includes/config.php';

if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to manage bookings';
    header('Location: ../pages/login.php');
    exit();
}

$booking_id = intval($_GET['booking_id'] ?? 0);
$user_id = getCurrentUserId();

if ($booking_id <= 0) {
    $_SESSION['error'] = 'Invalid booking ID';
    header('Location: ../pages/booking_history.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Get booking details with user verification
    $stmt = $pdo->prepare("
        SELECT b.*, e.name as event_name, e.event_date, e.id as event_id 
        FROM bookings b 
        JOIN events e ON b.event_id = e.id 
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Booking not found or access denied';
        header('Location: ../pages/booking_history.php');
        exit();
    }
    
    if ($booking['status'] === 'cancelled') {
        $pdo->rollBack();
        $_SESSION['error'] = 'Booking is already cancelled';
        header('Location: ../pages/booking_history.php');
        exit();
    }
    
    // Check if event is in the past
    if (isPastEvent($booking['event_date'])) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Cannot cancel booking for past events';
        header('Location: ../pages/booking_history.php');
        exit();
    }
    
    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$booking_id]);
    
    // Return tickets to event
    $stmt = $pdo->prepare("UPDATE events SET available_tickets = available_tickets + ? WHERE id = ?");
    $stmt->execute([$booking['num_tickets'], $booking['event_id']]);
    
    $pdo->commit();
    
    $_SESSION['success'] = "Booking for '{$booking['event_name']}' cancelled successfully. {$booking['num_tickets']} ticket(s) returned.";
    header('Location: ../pages/booking_history.php');
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Cancellation failed: ' . $e->getMessage();
    header('Location: ../pages/booking_history.php');
    exit();
}
?>
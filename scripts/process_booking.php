<?php
require_once '../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/events.php');
    exit();
}

if (!isLoggedIn()) {
    $_SESSION['error'] = 'Please login to book tickets';
    header('Location: ../pages/login.php');
    exit();
}

$event_id = intval($_GET['id'] ?? $_POST['event_id'] ?? 0);
$num_tickets = intval($_POST['num_tickets'] ?? 0);
$user_id = getCurrentUserId();

// Validate input
if ($event_id <= 0 || $num_tickets <= 0) {
    $_SESSION['error'] = 'Invalid booking request';
    header("Location: ../pages/event_detail.php?id=$event_id");
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check event availability with lock
    $stmt = $pdo->prepare("SELECT available_tickets, name, event_date FROM events WHERE id = ? FOR UPDATE");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Event not found';
        header('Location: ../pages/events.php');
        exit();
    }
    
    // Check if event is in the past
    if (isPastEvent($event['event_date'])) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Cannot book tickets for past events';
        header("Location: ../pages/event_detail.php?id=$event_id");
        exit();
    }
    
    // Check ticket availability
    if ($event['available_tickets'] < $num_tickets) {
        $pdo->rollBack();
        $_SESSION['error'] = "Only {$event['available_tickets']} tickets available";
        header("Location: ../pages/event_detail.php?id=$event_id");
        exit();
    }
    
    // Check if user already has a booking for this event
    $stmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ? AND event_id = ? AND status = 'confirmed'");
    $stmt->execute([$user_id, $event_id]);
    $existing_booking = $stmt->fetch();
    
    if ($existing_booking) {
        $pdo->rollBack();
        $_SESSION['error'] = 'You already have a booking for this event';
        header("Location: ../pages/event_detail.php?id=$event_id");
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
    
    // Success
    $_SESSION['success'] = "Successfully booked $num_tickets ticket(s) for '{$event['name']}'!";
    header('Location: ../pages/booking_history.php');
    exit();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Booking failed: ' . $e->getMessage();
    header("Location: ../pages/event_detail.php?id=$event_id");
    exit();
}
?>
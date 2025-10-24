<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/manage_events.php');
    exit();
}

$action = $_POST['action'] ?? '';
$event_id = intval($_POST['event_id'] ?? 0);

try {
    switch ($action) {
        case 'update_event':
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $event_date = $_POST['event_date'];
            $venue = trim($_POST['venue']);
            $total_tickets = intval($_POST['total_tickets']);
            
            // Get current event data
            $stmt = $pdo->prepare("SELECT total_tickets, available_tickets FROM events WHERE id = ?");
            $stmt->execute([$event_id]);
            $current_event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current_event) {
                $_SESSION['error'] = 'Event not found';
                header("Location: ../admin/edit_event.php?id=$event_id");
                exit();
            }
            
            // Calculate new available tickets
            $booked_tickets = $current_event['total_tickets'] - $current_event['available_tickets'];
            $new_available_tickets = $total_tickets - $booked_tickets;
            
            if ($new_available_tickets < 0) {
                $_SESSION['error'] = "Cannot reduce tickets below already booked count ($booked_tickets tickets booked)";
                header("Location: ../admin/edit_event.php?id=$event_id");
                exit();
            }
            
            // Update event
            $stmt = $pdo->prepare("
                UPDATE events 
                SET name = ?, description = ?, event_date = ?, venue = ?, total_tickets = ?, available_tickets = ? 
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $event_date, $venue, $total_tickets, $new_available_tickets, $event_id]);
            
            $_SESSION['success'] = 'Event updated successfully';
            header('Location: ../admin/manage_events.php');
            break;
            
        case 'add_event':
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $event_date = $_POST['event_date'];
            $venue = trim($_POST['venue']);
            $total_tickets = intval($_POST['total_tickets']);
            $user_id = getCurrentUserId();
            
            $stmt = $pdo->prepare("
                INSERT INTO events (name, description, event_date, venue, total_tickets, available_tickets, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $description, $event_date, $venue, $total_tickets, $total_tickets, $user_id]);
            
            $_SESSION['success'] = 'Event added successfully';
            header('Location: ../admin/manage_events.php');
            break;
            
        default:
            $_SESSION['error'] = 'Invalid action';
            header('Location: ../admin/manage_events.php');
    }
    
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Operation failed: ' . $e->getMessage();
    
    if ($action === 'update_event') {
        header("Location: ../admin/edit_event.php?id=$event_id");
    } else {
        header('Location: ../admin/manage_events.php');
    }
    exit();
}
?>
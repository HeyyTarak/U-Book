<?php
// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);

// Start output buffering to catch any accidental output
ob_start();

// Fix file paths - use absolute paths
$config_path = __DIR__ . '/../includes/config.php';
if (!file_exists($config_path)) {
    // Make sure we output clean JSON
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System configuration error - config file not found']);
    exit();
}

try {
    require_once $config_path;
} catch (Exception $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'System configuration error - could not load config']);
    exit();
}

// Clear any previous output
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Set JSON header immediately
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get POST data
$event_id = intval($_POST['event_id'] ?? 0);
$num_tickets = intval($_POST['num_tickets'] ?? 0);
$user_id = getCurrentUserId();

// Log the request for debugging
error_log("Payment request: user_id=$user_id, event_id=$event_id, num_tickets=$num_tickets");

// Validate input
if ($event_id <= 0 || $num_tickets <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking data: event_id=' . $event_id . ', num_tickets=' . $num_tickets]);
    exit();
}

// Initialize database connection check
if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    $pdo->beginTransaction();
    
    // Check if user already has an active booking for this event
    $stmt = $pdo->prepare("SELECT id, num_tickets, total_amount FROM bookings WHERE user_id = ? AND event_id = ? AND status = 'confirmed'");
    $stmt->execute([$user_id, $event_id]);
    $existing_booking = $stmt->fetch();
    
    if ($existing_booking) {
        // User already has a booking - update it
        error_log("Updating existing booking for user $user_id");
        
        $new_total_tickets = $existing_booking['num_tickets'] + $num_tickets;
        
        // Verify event availability
        $stmt = $pdo->prepare("SELECT available_tickets, price, name FROM events WHERE id = ? FOR UPDATE");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            exit();
        }
        
        if ($event['available_tickets'] < $num_tickets) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Not enough tickets available. Only ' . $event['available_tickets'] . ' left.']);
            exit();
        }
        
        // Calculate additional amount
        $additional_amount = $event['price'] * $num_tickets;
        $new_total_amount = $existing_booking['total_amount'] + $additional_amount;
        
        // Update existing booking
        $stmt = $pdo->prepare("UPDATE bookings SET num_tickets = ?, total_amount = ? WHERE id = ?");
        $stmt->execute([$new_total_tickets, $new_total_amount, $existing_booking['id']]);
        
        $booking_id = $existing_booking['id'];
        
        // Create additional individual tickets
        for ($i = 1; $i <= $num_tickets; $i++) {
            $ticket_number = 'TKT-' . date('Ymd-His') . '-' . strtoupper(uniqid());
            
            $qr_data = json_encode([
                'booking_id' => $booking_id,
                'ticket_number' => $ticket_number,
                'event' => $event['name'],
                'user' => $_SESSION['user_name'],
                'timestamp' => time()
            ]);
            $qr_code_data = base64_encode($qr_data);
            
            $stmt = $pdo->prepare("INSERT INTO tickets (booking_id, user_id, event_id, ticket_number, qr_code_data) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$booking_id, $user_id, $event_id, $ticket_number, $qr_code_data]);
        }
        
        // Update event ticket count
        $stmt = $pdo->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
        $stmt->execute([$num_tickets, $event_id]);
        
        $pdo->commit();
        
        $response = [
            'success' => true,
            'booking_id' => $booking_id,
            'message' => 'Additional tickets added to your existing booking'
        ];
        
    } else {
        // Create new booking
        error_log("Creating new booking for user $user_id");
        
        $stmt = $pdo->prepare("SELECT available_tickets, price, name FROM events WHERE id = ? FOR UPDATE");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch();
        
        if (!$event) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Event not found']);
            exit();
        }
        
        if ($event['available_tickets'] < $num_tickets) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Not enough tickets available. Only ' . $event['available_tickets'] . ' left.']);
            exit();
        }
        
        // Calculate total amount
        $total_amount = $event['price'] * $num_tickets;
        
        // Generate unique ticket number
        $ticket_number = 'TKT-' . date('Ymd-His') . '-' . strtoupper(uniqid());
        
        // Create booking
        $stmt = $pdo->prepare("INSERT INTO bookings (user_id, event_id, num_tickets, total_amount, payment_status, ticket_number) VALUES (?, ?, ?, ?, 'completed', ?)");
        $stmt->execute([$user_id, $event_id, $num_tickets, $total_amount, $ticket_number]);
        $booking_id = $pdo->lastInsertId();
        
        // Generate QR code data
        // Generate QR code data
$qr_data = json_encode([
    'booking_id' => $booking_id,
    'ticket_number' => $ticket_number,
    'event' => $event['name'],
    'user' => $_SESSION['user_name'],
    'timestamp' => time()
]);

$qr_code_data = base64_encode($qr_data);
        
        $qr_code_data = base64_encode($qr_data);
        
        // Update booking with QR code
        $stmt = $pdo->prepare("UPDATE bookings SET qr_code_data = ?, payment_date = NOW() WHERE id = ?");
        $stmt->execute([$qr_code_data, $booking_id]);
        
        // Create individual tickets
        for ($i = 1; $i <= $num_tickets; $i++) {
            $individual_ticket_number = $ticket_number . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO tickets (booking_id, user_id, event_id, ticket_number, qr_code_data) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$booking_id, $user_id, $event_id, $individual_ticket_number, $qr_code_data]);
        }
        
        // Update event ticket count
        $stmt = $pdo->prepare("UPDATE events SET available_tickets = available_tickets - ? WHERE id = ?");
        $stmt->execute([$num_tickets, $event_id]);
        
        $pdo->commit();
        
        $response = [
            'success' => true,
            'booking_id' => $booking_id,
            'ticket_number' => $ticket_number,
            'message' => 'Payment processed successfully'
        ];
    }
    
    // Clear pending booking session
    unset($_SESSION['pending_booking']);
    
    // Final output
    echo json_encode($response);
    
} catch (PDOException $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Payment PDO error: " . $e->getMessage());
    
    if (strpos($e->getMessage(), '1062') !== false) {
        echo json_encode(['success' => false, 'message' => 'You already have an active booking for this event. Please check your bookings.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Payment general error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}

// Ensure no extra output
exit();
?>
<?php
$page_title = "Event Details";
require_once '../includes/config.php';
require_once '../includes/header.php';

if (!isset($_GET['id'])) {
    redirect('/pages/events.php', 'Event not specified', 'error');
}

$event_id = intval($_GET['id']);

// Fetch event details with category
try {
    $stmt = $pdo->prepare("
        SELECT e.*, c.name as category_name, c.color as category_color, c.icon as category_icon 
        FROM events e 
        LEFT JOIN categories c ON e.category_id = c.id 
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        redirect('/pages/events.php', 'Event not found', 'error');
    }
} catch (PDOException $e) {
    redirect('/pages/events.php', 'Error loading event', 'error');
}

// Check if user already has a confirmed booking for this event
$existing_booking = null;
$existing_tickets = 0;

// Safe check for existing bookings
if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    if ($user_id) {
        try {
            $stmt = $pdo->prepare("SELECT id, num_tickets FROM bookings WHERE user_id = ? AND event_id = ? AND status = 'confirmed'");
            $stmt->execute([$user_id, $event_id]);
            $existing_booking = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing_booking) {
                $existing_tickets = (int)$existing_booking['num_tickets'];
            }
        } catch (PDOException $e) {
            // Silently fail - don't prevent booking if check fails
            error_log("Error checking for existing booking: " . $e->getMessage());
        }
    }
}

// Handle booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_tickets'])) {
    if (!isLoggedIn()) {
        redirect('/pages/login.php', 'Please login to book tickets', 'error');
    }
    
    $user_id = getCurrentUserId();
    if (!$user_id) {
        redirect('/pages/login.php', 'Please login to book tickets', 'error');
    }
    
    $num_tickets = intval($_POST['num_tickets']);
    
    // Max tickets the user is allowed to book in total
    $USER_MAX_TICKETS = 10;
    
    // Calculate total tickets after purchase
    $total_tickets_after_purchase = $num_tickets + $existing_tickets;
    
    // Max available tickets considers the current remaining total tickets
    $max_available_to_book = $event['available_tickets'] + $existing_tickets;
    
    // Validation
    if ($num_tickets <= 0) {
        addFlashMessage('error', 'Please select at least 1 ticket.');
    } elseif ($num_tickets > $max_available_to_book) {
        addFlashMessage('error', 'Not enough tickets available. Only ' . $event['available_tickets'] . ' tickets remaining.');
    } elseif ($total_tickets_after_purchase > $USER_MAX_TICKETS) {
        $allowed_additional = $USER_MAX_TICKETS - $existing_tickets;
        addFlashMessage('error', "You are limited to $USER_MAX_TICKETS tickets total. You can only book up to $allowed_additional additional ticket(s).");
    } else {
        // Redirect to payment gateway
        $_SESSION['pending_booking'] = [
            'event_id' => $event_id,
            'num_tickets' => $num_tickets,
            'is_update' => (bool)$existing_booking,
            'existing_booking_id' => $existing_booking['id'] ?? null
        ];
        header('Location: payment_gateway.php');
        exit();
    }
}
?>

<div class="container">
    <div class="event-detail">
        <a href="events.php" class="btn btn-secondary mb-3">← Back to Events</a>
        
        <div class="event-detail-card">
            <?php if ($event['image_url']): ?>
            <div class="event-hero-image" style="background-image: url('<?php echo $event['image_url']; ?>');"></div>
            <?php endif; ?>
            
            <div class="event-header">
                <?php if ($event['category_name']): ?>
                <div class="event-category" style="border-left-color: <?php echo $event['category_color']; ?>">
                    <span><?php echo $event['category_icon']; ?></span>
                    <?php echo sanitize($event['category_name']); ?>
                </div>
                <?php endif; ?>
                
                <h1><?php echo sanitize($event['name']); ?></h1>
                <div class="event-meta-large">
                    <div class="meta-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                        </svg>
                        <span><?php echo sanitize($event['venue']); ?></span>
                    </div>
                    <div class="meta-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/>
                        </svg>
                        <span><?php echo formatDate($event['event_date'], 'l, F j, Y \a\t g:i A'); ?></span>
                    </div>
                    <div class="meta-item">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M20 12c0-1.1.9-2 2-2V6c0-1.1-.9-2-2-2H4c-1.1 0-1.99.9-1.99 2v4c1.1 0 1.99.9 1.99 2s-.89 2-2 2v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-1.1 0-2-.9-2-2zm-4.42 4.8L12 14.5l-3.58 2.3 1.08-4.12-3.29-2.69 4.24-.25L12 5.8l1.54 3.95 4.24.25-3.29 2.69 1.09 4.11z"/>
                        </svg>
                        <span><?php echo $event['available_tickets']; ?> tickets available</span>
                    </div>
                    <?php if ($event['price'] > 0): ?>
                    <div class="meta-item price-tag">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9v-5h2v5zm4 0h-2V7h2v9z"/>
                        </svg>
                        <span>$<?php echo number_format($event['price'], 2); ?> per ticket</span>
                    </div>
                    <?php else: ?>
                    <div class="meta-item price-tag free">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        <span>Free Event</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="event-content">
                <div class="event-description">
                    <h3>About this Event</h3>
                    <p><?php echo nl2br(sanitize($event['description'])); ?></p>
                </div>
                
                <?php if (!isPastEvent($event['event_date'])): ?>
                <div class="booking-section">
                    <h3>Book Your Tickets</h3>
                    
                    <?php 
                    // Calculate max tickets user can select
                    $USER_MAX_TICKETS = 10; 
                    $remaining_user_cap = $USER_MAX_TICKETS - $existing_tickets;
                    $max_select_tickets = min($remaining_user_cap, $event['available_tickets']);
                    ?>
                    
                    <?php if ($max_select_tickets > 0): ?>
                        <?php if (isLoggedIn()): ?>
                            
                            <?php if ($existing_booking): ?>
                                <div class="existing-booking-alert">
                                    <div class="alert alert-info">
                                        <strong>You already have <?php echo $existing_tickets; ?> ticket<?php echo $existing_tickets > 1 ? 's' : ''; ?> for this event.</strong>
                                        <br>You can book additional tickets below.
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="booking-form">
                                <input type="hidden" name="book_tickets" value="1">
                                
                                <div class="form-group">
                                    <label for="num_tickets">
                                        Number of Tickets 
                                        <?php if ($existing_booking): ?> (Additional) <?php endif; ?>
                                    </label>
                                    <select name="num_tickets" id="num_tickets" required>
                                        <?php for ($i = 1; $i <= $max_select_tickets; $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> ticket<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    
                                    <?php if ($existing_booking): ?>
                                        <small>Total tickets after booking: <span id="total-after-booking"><?php echo $existing_tickets + 1; ?></span> (Max <?php echo $USER_MAX_TICKETS; ?> per user)</small>
                                    <?php else: ?>
                                        <small>Maximum <?php echo $USER_MAX_TICKETS; ?> tickets per user</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="booking-summary">
                                    <div class="summary-item">
                                        <span>Ticket Price:</span>
                                        <span>$<?php echo number_format($event['price'], 2); ?> each</span>
                                    </div>
                                    <div class="summary-item">
                                        <span>Quantity:</span>
                                        <span id="quantity-display">1</span>
                                    </div>
                                    <div class="summary-item total">
                                        <span>Total Amount:</span>
                                        <span id="total-amount">$<?php echo number_format($event['price'], 2); ?></span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-large btn-full">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path d="M12 1v22M5 6h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z"/>
                                    </svg>
                                    Proceed to Payment
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="login-prompt">
                                <p>Please <a href="login.php">login</a> or <a href="register.php">create an account</a> to book tickets for this event.</p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($existing_tickets >= $USER_MAX_TICKETS): ?>
                            <div class="sold-out">
                                <p class="text-danger">You have reached the maximum booking limit of <?php echo $USER_MAX_TICKETS; ?> tickets for this event.</p>
                            </div>
                        <?php else: ?>
                            <div class="sold-out">
                                <p class="text-danger">❌ This event is completely sold out!</p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="event-ended">
                    <p class="text-danger">This event has already ended.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ticketSelect = document.getElementById('num_tickets');
    const quantityDisplay = document.getElementById('quantity-display');
    const totalAmount = document.getElementById('total-amount');
    const totalAfterBookingDisplay = document.getElementById('total-after-booking');
    const ticketPrice = <?php echo $event['price']; ?>;
    const existingTickets = <?php echo $existing_tickets; ?>;
    
    function updateBookingSummary() {
        if (!ticketSelect) return;
        
        const quantity = parseInt(ticketSelect.value) || 1;
        const total = ticketPrice * quantity;
        
        if (quantityDisplay) quantityDisplay.textContent = quantity;
        if (totalAmount) totalAmount.textContent = '$' + total.toFixed(2);
        
        if (totalAfterBookingDisplay) {
            totalAfterBookingDisplay.textContent = existingTickets + quantity;
        }
    }
    
    if (ticketSelect) {
        ticketSelect.addEventListener('change', updateBookingSummary);
        updateBookingSummary();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
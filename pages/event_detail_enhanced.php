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
                    
                    <?php if ($event['available_tickets'] > 0): ?>
                        <?php if (isLoggedIn()): ?>
                            <form method="POST" action="process_booking.php" class="booking-form">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                
                                <div class="form-group">
                                    <label for="num_tickets">Number of Tickets (Max 10)</label>
                                    <select name="num_tickets" id="num_tickets" required>
                                        <?php for ($i = 1; $i <= min(10, $event['available_tickets']); $i++): ?>
                                            <option value="<?php echo $i; ?>"><?php echo $i; ?> ticket<?php echo $i > 1 ? 's' : ''; ?></option>
                                        <?php endfor; ?>
                                    </select>
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
                        <div class="sold-out">
                            <p class="text-danger">❌ This event is completely sold out!</p>
                        </div>
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
    const ticketPrice = <?php echo $event['price']; ?>;
    
    function updateBookingSummary() {
        const quantity = parseInt(ticketSelect.value);
        const total = ticketPrice * quantity;
        
        quantityDisplay.textContent = quantity;
        totalAmount.textContent = '$' + total.toFixed(2);
    }
    
    ticketSelect.addEventListener('change', updateBookingSummary);
    updateBookingSummary(); // Initial calculation
});
</script>

<?php require_once '../includes/footer.php'; ?>
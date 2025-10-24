<?php
$page_title = "My Bookings";
require_once '../includes/config.php';
require_once '../includes/header.php';

requireLogin();

$user_id = getCurrentUserId();

// Fetch user's bookings
try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id as booking_id,
            b.num_tickets,
            b.booking_date,
            b.status,
            e.id as event_id,
            e.name as event_name,
            e.event_date,
            e.venue
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        WHERE b.user_id = ?
        ORDER BY b.booking_date DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $bookings = [];
    addFlashMessage('error', 'Error loading bookings: ' . $e->getMessage());
}
?>

<div class="container">
    <div class="page-header">
        <h1>My Bookings</h1>
        <p>View your event booking history and status</p>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="no-bookings">
            <h3>No bookings yet</h3>
            <p>You haven't booked any events yet. <a href="events.php">Browse events</a> to get started!</p>
        </div>
    <?php else: ?>
        <div class="bookings-list">
            <?php foreach ($bookings as $booking): ?>
            <div class="booking-card <?php echo $booking['status']; ?>">
                <div class="booking-header">
                    <h3><?php echo sanitize($booking['event_name']); ?></h3>
                    <span class="booking-status status-<?php echo $booking['status']; ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </div>
                
                <div class="booking-details">
                    <p><strong>Event Date:</strong> <?php echo formatDate($booking['event_date']); ?></p>
                    <p><strong>Venue:</strong> <?php echo sanitize($booking['venue']); ?></p>
                    <p><strong>Tickets Booked:</strong> <?php echo $booking['num_tickets']; ?></p>
                    <p><strong>Booking Date:</strong> <?php echo formatDate($booking['booking_date']); ?></p>
                </div>
                
                <div class="booking-actions">
                    <?php if ($booking['status'] === 'confirmed' && !isPastEvent($booking['event_date'])): ?>
                        <a href="../scripts/process_cancellation.php?booking_id=<?php echo $booking['booking_id']; ?>" 
                           class="btn btn-danger"
                           onclick="return confirm('Are you sure you want to cancel this booking?')">
                            Cancel Booking
                        </a>
                    <?php endif; ?>
                    
                    <?php if (isPastEvent($booking['event_date'])): ?>
                        <span class="text-muted">Event completed</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>
<?php
$page_title = "Ticket Confirmation";
require_once '../includes/config.php';
require_once '../includes/header.php';

requireLogin();

if (!isset($_GET['booking_id'])) {
    redirect('/pages/booking_history.php', 'Invalid ticket request', 'error');
}

$booking_id = intval($_GET['booking_id']);
$user_id = getCurrentUserId();

// Fetch booking details
try {
    $stmt = $pdo->prepare("
        SELECT b.*, e.name as event_name, e.event_date, e.venue, e.image_url,
               u.name as user_name, u.email as user_email
        FROM bookings b
        JOIN events e ON b.event_id = e.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        redirect('/pages/booking_history.php', 'Ticket not found', 'error');
    }
    
    // Fetch individual tickets
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE booking_id = ? ORDER BY ticket_number");
    $stmt->execute([$booking_id]);
    $tickets = $stmt->fetchAll();
    
} catch (PDOException $e) {
    redirect('/pages/booking_history.php', 'Error loading ticket', 'error');
}

// Generate QR code data - UNIQUE FUNCTION NAME
function generateTicketQRCode($data) {
    $text = urlencode(json_encode($data));
    // Use a free QR code API
    return "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . $text;
}

// Or use local dummy images
function generateLocalDummyQR($type = 'default') {
    $dummy_qrs = [
        'default' => 'https://via.placeholder.com/150/6366f1/ffffff?text=QR+CODE',
        'ticket' => 'https://via.placeholder.com/150/10b981/ffffff?text=TICKET+QR',
        'event' => 'https://via.placeholder.com/150/8b5cf6/ffffff?text=EVENT+QR'
    ];
    return $dummy_qrs[$type] ?? $dummy_qrs['default'];
}

$qr_data = [
    'booking_id' => $booking['id'],
    'ticket_number' => $booking['ticket_number'],
    'event' => $booking['event_name'],
    'user' => $booking['user_name'],
    'date' => $booking['event_date']
];

// Choose your preferred QR method:
$qr_code = generateTicketQRCode($qr_data); // Online API
// $qr_code = generateLocalDummyQR('ticket'); // Local dummy
?>

<div class="container">
    <div class="confirmation-container">
        <!-- Success Header -->
        <div class="confirmation-header">
            <div class="success-icon">🎉</div>
            <h1>Booking Confirmed!</h1>
            <p>Your tickets for <strong><?php echo sanitize($booking['event_name']); ?></strong> are ready</p>
        </div>

        <!-- Digital Ticket -->
        <div class="ticket-card">
            <div class="ticket-header">
                <div class="ticket-qr">
                    <img src="<?php echo $qr_code; ?>" alt="QR Code" class="qr-image" 
                         onerror="this.src='https://via.placeholder.com/150/6366f1/ffffff?text=QR+CODE'">
                </div>
                <div class="ticket-info">
                    <h2><?php echo sanitize($booking['event_name']); ?></h2>
                    <div class="event-details">
                        <div class="detail-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/>
                            </svg>
                            <span><?php echo formatDate($booking['event_date'], 'l, F j, Y \a\t g:i A'); ?></span>
                        </div>
                        <div class="detail-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/>
                            </svg>
                            <span><?php echo sanitize($booking['venue']); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ticket-body">
                <div class="ticket-details">
                    <div class="detail-grid">
                        <div class="detail-column">
                            <div class="detail-group">
                                <label>Ticket Holder</label>
                                <div class="detail-value"><?php echo sanitize($booking['user_name']); ?></div>
                            </div>
                            <div class="detail-group">
                                <label>Email</label>
                                <div class="detail-value"><?php echo sanitize($booking['user_email']); ?></div>
                            </div>
                        </div>
                        <div class="detail-column">
                            <div class="detail-group">
                                <label>Ticket Number</label>
                                <div class="detail-value ticket-number"><?php echo $booking['ticket_number']; ?></div>
                            </div>
                            <div class="detail-group">
                                <label>Quantity</label>
                                <div class="detail-value"><?php echo $booking['num_tickets']; ?> ticket<?php echo $booking['num_tickets'] > 1 ? 's' : ''; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($booking['total_amount'] > 0): ?>
                    <div class="payment-details">
                        <div class="detail-group">
                            <label>Total Paid</label>
                            <div class="detail-value price">$<?php echo number_format($booking['total_amount'], 2); ?></div>
                        </div>
                        <div class="detail-group">
                            <label>Payment Status</label>
                            <div class="detail-value status-completed">Completed</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="ticket-footer">
                <div class="ticket-actions">
                    <button onclick="window.print()" class="btn btn-secondary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <path d="M6 14h12v8H6z"/>
                        </svg>
                        Print Ticket
                    </button>
                    <button id="download-ticket" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        Download PDF
                    </button>
                </div>
            </div>
        </div>

        <!-- Individual Tickets -->
        <?php if (count($tickets) > 1): ?>
        <div class="individual-tickets">
            <h3>Individual Tickets</h3>
            <div class="tickets-grid">
                <?php foreach ($tickets as $ticket): 
                    $ticket_qr_data = [
                        'ticket' => $ticket['ticket_number'],
                        'event' => $booking['event_name'],
                        'holder' => $booking['user_name']
                    ];
                    $ticket_qr = generateTicketQRCode($ticket_qr_data);
                ?>
                <div class="individual-ticket">
                    <div class="ticket-qr-small">
                        <img src="<?php echo $ticket_qr; ?>" alt="QR Code" 
                             onerror="this.src='https://via.placeholder.com/80/10b981/ffffff?text=QR'">
                    </div>
                    <div class="ticket-info-small">
                        <div class="ticket-number"><?php echo $ticket['ticket_number']; ?></div>
                        <div class="ticket-seat">Seat: General Admission</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Next Steps -->
        <div class="next-steps">
            <h3>What's Next?</h3>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-icon">📧</div>
                    <h4>Email Confirmation</h4>
                    <p>A confirmation has been sent to your email</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">📱</div>
                    <h4>Save Your Ticket</h4>
                    <p>Keep this ticket handy for event entry</p>
                </div>
                <div class="step-card">
                    <div class="step-icon">⏰</div>
                    <h4>Arrive Early</h4>
                    <p>Please arrive 30 minutes before the event</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="confirmation-actions">
            <a href="booking_history.php" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                View All Bookings
            </a>
            <a href="events.php" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3"/>
                </svg>
                Book More Events
            </a>
        </div>
    </div>
</div>

<script>
// Add QR code fallback
document.addEventListener('DOMContentLoaded', function() {
    const qrImages = document.querySelectorAll('.qr-image, .ticket-qr-small img');
    
    qrImages.forEach(img => {
        img.addEventListener('error', function() {
            this.src = 'https://via.placeholder.com/150/6366f1/ffffff?text=QR+CODE';
        });
    });

    // Simulate PDF download
    document.getElementById('download-ticket').addEventListener('click', function() {
        this.innerHTML = 'Generating PDF...';
        setTimeout(() => {
            alert('PDF download would start here. In a real app, this would generate a proper ticket PDF.');
            this.innerHTML = `
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Download PDF
            `;
        }, 2000);
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
<?php
$page_title = "Payment";
require_once '../includes/config.php';
require_once '../includes/header.php';

requireLogin();

// Check if we have a pending booking in session
if (!isset($_SESSION['pending_booking'])) {
    redirect('/pages/events.php', 'No booking found. Please select tickets first.', 'error');
}

$event_id = $_SESSION['pending_booking']['event_id'];
$num_tickets = $_SESSION['pending_booking']['num_tickets'];

// Validate booking and get event details
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event || $event['available_tickets'] < $num_tickets) {
        unset($_SESSION['pending_booking']);
        redirect('/pages/events.php', 'Invalid booking request', 'error');
    }
    
    $total_amount = $event['price'] * $num_tickets;
    
} catch (PDOException $e) {
    unset($_SESSION['pending_booking']);
    redirect('/pages/events.php', 'Error processing booking', 'error');
}
?>

<div class="container">
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h1>Complete Your Payment</h1>
                <p>Securely process your ticket purchase</p>
            </div>
            
            <div class="payment-summary">
                <h3>Order Summary</h3>
                <div class="summary-item">
                    <span>Event:</span>
                    <span><?php echo sanitize($event['name']); ?></span>
                </div>
                <div class="summary-item">
                    <span>Tickets:</span>
                    <span><?php echo $num_tickets; ?> x $<?php echo number_format($event['price'], 2); ?></span>
                </div>
                <div class="summary-item total">
                    <span>Total:</span>
                    <span>$<?php echo number_format($total_amount, 2); ?></span>
                </div>
            </div>
            
            <div class="payment-methods">
                <h3>Payment Method</h3>
                <div class="method-options">
                    <label class="method-option active">
                        <input type="radio" name="payment_method" value="card" checked>
                        <div class="method-content">
                            <div class="method-icon">💳</div>
                            <div class="method-info">
                                <div class="method-name">Credit/Debit Card</div>
                                <div class="method-desc">Pay securely with your card</div>
                            </div>
                        </div>
                    </label>
                    
                    <label class="method-option">
                        <input type="radio" name="payment_method" value="paypal">
                        <div class="method-content">
                            <div class="method-icon">🔵</div>
                            <div class="method-info">
                                <div class="method-name">PayPal</div>
                                <div class="method-desc">Fast and secure online payments</div>
                            </div>
                        </div>
                    </label>
                </div>
                
                <div class="card-form" id="card-form">
                    <div class="form-group">
                        <label for="card_number">Card Number</label>
                        <input type="text" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expiry_date">Expiry Date</label>
                            <input type="text" id="expiry_date" placeholder="MM/YY" maxlength="5">
                        </div>
                        
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" placeholder="123" maxlength="3">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="card_holder">Card Holder Name</label>
                        <input type="text" id="card_holder" placeholder="John Doe">
                    </div>
                </div>
            </div>
            
            <div class="payment-actions">
                <button id="pay-now-btn" class="btn btn-primary btn-large btn-full">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/>
                        <path d="M4 6v12c0 1.1.9 2 2 2h14v-4"/>
                        <path d="M18 12a2 2 0 0 0-2 2c0 1.1.9 2 2 2h4v-4h-4z"/>
                    </svg>
                    Pay $<?php echo number_format($total_amount, 2); ?>
                </button>
                
                <a href="event_detail.php?id=<?php echo $event_id; ?>" class="btn btn-secondary btn-full">
                    Cancel Payment
                </a>
            </div>
            
            <div class="payment-security">
                <div class="security-badge">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                    </svg>
                    <span>Secure SSL Encryption</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="payment-processing" class="payment-processing hidden">
    <div class="processing-content">
        <div class="processing-spinner"></div>
        <h3>Processing Payment...</h3>
        <p>Please wait while we secure your tickets</p>
        <div class="countdown" id="countdown">5</div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const payBtn = document.getElementById('pay-now-btn');
    const processingModal = document.getElementById('payment-processing');
    const countdownElement = document.getElementById('countdown');
    
    payBtn.addEventListener('click', function() {
        console.log('Payment button clicked');
        
        // Show processing modal
        processingModal.classList.remove('hidden');
        
        // Start countdown
        let countdown = 5;
        const countdownInterval = setInterval(() => {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                processPayment();
            }
        }, 1000);
    });
    
    function processPayment() {
        console.log('Starting payment process...');
        
        // Create form data
        const formData = new URLSearchParams();
        formData.append('event_id', <?php echo $event_id; ?>);
        formData.append('num_tickets', <?php echo $num_tickets; ?>);
        
        console.log('Sending payment request to server...');
        
        // Submit to payment processor
        fetch('../scripts/process_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData
        })
        .then(response => {
            console.log('Received response, status:', response.status);
            
            // Check if response is OK
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            
            // Get response text first to see what we're dealing with
            return response.text().then(text => {
                console.log('Raw response:', text);
                
                // Try to parse as JSON
                try {
                    if (!text.trim()) {
                        throw new Error('Empty response from server');
                    }
                    return JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response text that failed to parse:', text);
                    throw new Error('Invalid JSON response from server: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('Parsed response data:', data);
            
            if (data.success) {
                console.log('Payment successful, redirecting...');
                // Clear pending booking session
                fetch('../scripts/clear_booking_session.php').catch(e => console.log('Session clear failed:', e));
                // Redirect to ticket confirmation
                window.location.href = 'ticket_confirmation.php?booking_id=' + data.booking_id;
            } else {
                console.error('Payment failed with message:', data.message);
                alert('Payment failed: ' + data.message);
                processingModal.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Payment processing error:', error);
            alert('Payment processing error: ' + error.message + '\n\nPlease check console for details.');
            processingModal.classList.add('hidden');
        });
    }
    
    // Payment method switching
    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.querySelectorAll('.method-option').forEach(option => {
                option.classList.remove('active');
            });
            this.closest('.method-option').classList.add('active');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
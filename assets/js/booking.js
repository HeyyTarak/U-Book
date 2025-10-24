// Booking-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.querySelector('.booking-form');
    const numTicketsSelect = document.getElementById('num_tickets');
    const availableTicketsSpan = document.querySelector('.available-tickets-count');
    
    if (bookingForm && numTicketsSelect && availableTicketsSpan) {
        const availableTickets = parseInt(availableTicketsSpan.textContent);
        
        // Update select options based on available tickets
        function updateTicketOptions() {
            const currentValue = parseInt(numTicketsSelect.value);
            numTicketsSelect.innerHTML = '';
            
            const maxTickets = Math.min(availableTickets, 10); // Max 10 tickets per booking
            
            for (let i = 1; i <= maxTickets; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i + ' ticket' + (i > 1 ? 's' : '');
                numTicketsSelect.appendChild(option);
            }
            
            // Set to previous value if still valid, otherwise set to 1
            if (currentValue <= maxTickets) {
                numTicketsSelect.value = currentValue;
            } else {
                numTicketsSelect.value = 1;
            }
            
            updateBookingSummary();
        }
        
        // Update booking summary
        function updateBookingSummary() {
            const ticketCount = parseInt(numTicketsSelect.value);
            const summaryElement = document.getElementById('booking-summary');
            
            if (summaryElement) {
                summaryElement.innerHTML = `
                    <div class="booking-summary">
                        <h4>Booking Summary</h4>
                        <p><strong>Tickets:</strong> ${ticketCount}</p>
                        <p><strong>Remaining:</strong> ${availableTickets - ticketCount} tickets available</p>
                    </div>
                `;
            }
        }
        
        // Real-time availability check
        function checkAvailability() {
            fetch(`../api/get_event_detail.php?id=${getEventId()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const newAvailable = data.data.available_tickets;
                        availableTicketsSpan.textContent = newAvailable;
                        
                        if (newAvailable === 0) {
                            disableBookingForm('Event is sold out!');
                        } else if (newAvailable < parseInt(numTicketsSelect.value)) {
                            alert(`Only ${newAvailable} tickets remaining! Adjusting your selection.`);
                            updateTicketOptions();
                        }
                    }
                })
                .catch(error => console.error('Availability check failed:', error));
        }
        
        function getEventId() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('id');
        }
        
        function disableBookingForm(message) {
            numTicketsSelect.disabled = true;
            const submitBtn = bookingForm.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = message;
            
            const soldOutDiv = document.createElement('div');
            soldOutDiv.className = 'sold-out-message';
            soldOutDiv.innerHTML = `<p class="text-danger">${message}</p>`;
            bookingForm.appendChild(soldOutDiv);
        }
        
        // Initialize
        updateTicketOptions();
        
        // Check availability every 30 seconds
        setInterval(checkAvailability, 30000);
        
        // Update summary when tickets change
        numTicketsSelect.addEventListener('change', updateBookingSummary);
    }
    
    // Countdown to event
    const eventDateElement = document.querySelector('.event-date');
    if (eventDateElement) {
        const eventDate = new Date(eventDateElement.dataset.datetime);
        updateCountdown(eventDate);
        setInterval(() => updateCountdown(eventDate), 60000);
    }
    
    function updateCountdown(eventDate) {
        const now = new Date();
        const diff = eventDate - now;
        
        if (diff <= 0) {
            document.getElementById('event-countdown').innerHTML = 'Event in progress!';
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        
        let countdownText = '';
        if (days > 0) countdownText += `${days}d `;
        if (hours > 0) countdownText += `${hours}h `;
        countdownText += `${minutes}m`;
        
        document.getElementById('event-countdown').textContent = `Starts in: ${countdownText}`;
    }
});
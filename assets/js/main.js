// Main JavaScript file for U-Book
document.addEventListener('DOMContentLoaded', function () {
    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s ease';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    });

    // Form validation enhancements
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Processing...';
            }
        });
    });

    // Dynamic ticket availability check
    const ticketSelect = document.getElementById('num_tickets');
    if (ticketSelect) {
        ticketSelect.addEventListener('change', function () {
            const selectedTickets = parseInt(this.value);
            const availableSpan = document.querySelector('.event-tickets');
            if (availableSpan) {
                const availableText = availableSpan.textContent;
                const availableMatch = availableText.match(/(\d+)\s+tickets/);
                if (availableMatch) {
                    const availableTickets = parseInt(availableMatch[1]);
                    if (selectedTickets > availableTickets) {
                        alert(`Only ${availableTickets} tickets available!`);
                        this.value = availableTickets;
                    }
                }
            }
        });
    }

    // Search form enhancement
    const searchForm = document.querySelector('.filter-form');
    if (searchForm) {
        const searchInput = searchForm.querySelector('input[name="search"]');
        searchInput.addEventListener('input', function () {
            if (this.value.length > 2) {
                // Optional: Add auto-search functionality here
            }
        });
    }

    // Booking confirmation
    const bookingForms = document.querySelectorAll('.booking-form');
    bookingForms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const ticketCount = parseInt(this.querySelector('#num_tickets').value);
            if (ticketCount > 10) {
                e.preventDefault();
                alert('Maximum 10 tickets per booking allowed');
                return false;
            }

            if (!confirm(`Confirm booking for ${ticketCount} ticket(s)?`)) {
                e.preventDefault();
                return false;
            }
        });
    });
});

// Utility functions
const UBook = {
    // Format date for display
    formatDate: function (dateString) {
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('en-US', options);
    },

    // Check if event is in the past
    isPastEvent: function (eventDate) {
        return new Date(eventDate) < new Date();
    },

    // AJAX helper
    ajax: function (url, options = {}) {
        return fetch(url, {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            body: options.body ? JSON.stringify(options.body) : null
        }).then(response => response.json());
    }
};
// Enhanced JavaScript for modern interactions
class UBookEnhanced {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupAnimations();
        this.setupRealTimeUpdates();
    }

    setupEventListeners() {
        // Smooth scrolling for anchor links
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href^="#"]')) {
                e.preventDefault();
                const target = document.querySelector(e.target.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });

        // Enhanced form interactions
        this.enhanceForms();

        // Lazy loading for images
        this.setupLazyLoading();
    }

    enhanceForms() {
        // Real-time form validation
        document.querySelectorAll('input, textarea, select').forEach(input => {
            input.addEventListener('blur', this.validateField.bind(this));
            input.addEventListener('input', this.clearFieldError.bind(this));
        });

        // Auto-save forms
        this.setupAutoSave();
    }

    validateField(e) {
        const field = e.target;
        const value = field.value.trim();

        // Clear previous errors
        this.clearFieldError(e);

        // Required field validation
        if (field.hasAttribute('required') && !value) {
            this.showFieldError(field, 'This field is required');
            return false;
        }

        // Email validation
        if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                return false;
            }
        }

        // Number validation
        if (field.type === 'number' && field.hasAttribute('min')) {
            const min = parseInt(field.getAttribute('min'));
            if (parseInt(value) < min) {
                this.showFieldError(field, `Value must be at least ${min}`);
                return false;
            }
        }

        return true;
    }

    showFieldError(field, message) {
        field.style.borderColor = '#ef4444';

        let errorElement = field.parentNode.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            field.parentNode.appendChild(errorElement);
        }

        errorElement.textContent = message;
        errorElement.style.color = '#ef4444';
        errorElement.style.fontSize = '0.875rem';
        errorElement.style.marginTop = '0.25rem';
    }

    clearFieldError(e) {
        const field = e.target;
        field.style.borderColor = '';

        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img.lazy').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    setupAnimations() {
        // Add scroll animations
        this.setupScrollAnimations();

        // Add hover effects
        this.setupHoverEffects();
    }

    setupScrollAnimations() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.event-card, .page-header').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    }

    setupHoverEffects() {
        // Enhanced card hover effects
        document.addEventListener('mouseover', (e) => {
            if (e.target.closest('.event-card')) {
                const card = e.target.closest('.event-card');
                card.style.transform = 'translateY(-8px) scale(1.02)';
            }
        });

        document.addEventListener('mouseout', (e) => {
            if (e.target.closest('.event-card')) {
                const card = e.target.closest('.event-card');
                card.style.transform = 'translateY(0) scale(1)';
            }
        });
    }

    setupRealTimeUpdates() {
        // Update event counts in real-time
        setInterval(() => {
            this.updateEventCounts();
        }, 30000); // Every 30 seconds
    }

    async updateEventCounts() {
        try {
            const response = await fetch('../api/get_event_counts.php');
            const data = await response.json();

            if (data.success) {
                // Update any event count displays
                document.querySelectorAll('.event-tickets').forEach(element => {
                    const eventId = element.closest('.event-card')?.dataset.eventId;
                    if (eventId && data.events[eventId]) {
                        const available = data.events[eventId].available_tickets;
                        element.textContent = `${available} tickets available`;
                    }
                });
            }
        } catch (error) {
            console.log('Failed to update event counts:', error);
        }
    }

    // Utility method for showing notifications
    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span>${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#10B981' : '#EF4444'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);

        // Close on click
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new UBookEnhanced();
});

// CSS for notifications
const notificationStyles = `
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.notification-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.2rem;
    cursor: pointer;
    margin-left: 1rem;
}
`;

// Inject styles
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);
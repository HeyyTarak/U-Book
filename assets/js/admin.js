// Admin-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Event form validation
    const eventForms = document.querySelectorAll('.event-form');
    eventForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const eventDate = new Date(this.querySelector('#event_date').value);
            const now = new Date();
            
            if (eventDate <= now) {
                e.preventDefault();
                alert('Event date must be in the future!');
                return false;
            }
            
            const totalTickets = parseInt(this.querySelector('#total_tickets').value);
            if (totalTickets <= 0) {
                e.preventDefault();
                alert('Total tickets must be greater than 0!');
                return false;
            }
        });
    });
    
    // Bulk actions for events management
    const bulkActionForm = document.querySelector('.bulk-actions');
    if (bulkActionForm) {
        const checkboxes = document.querySelectorAll('.event-checkbox');
        const bulkActionSelect = document.getElementById('bulk-action');
        const applyBulkActionBtn = document.getElementById('apply-bulk-action');
        
        // Toggle select all
        const selectAllCheckbox = document.getElementById('select-all');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkActionButton();
            });
        }
        
        // Update bulk action button state
        function updateBulkActionButton() {
            const checkedCount = document.querySelectorAll('.event-checkbox:checked').length;
            applyBulkActionBtn.disabled = checkedCount === 0;
            applyBulkActionBtn.textContent = `Apply to ${checkedCount} event(s)`;
        }
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateBulkActionButton);
        });
        
        // Apply bulk action
        applyBulkActionBtn.addEventListener('click', function() {
            const selectedEvents = Array.from(document.querySelectorAll('.event-checkbox:checked'))
                .map(checkbox => checkbox.value);
                
            const action = bulkActionSelect.value;
            
            if (selectedEvents.length === 0) {
                alert('Please select at least one event');
                return;
            }
            
            if (action === 'delete' && !confirm(`Delete ${selectedEvents.length} event(s)? This action cannot be undone!`)) {
                return;
            }
            
            // Submit bulk action form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../scripts/process_bulk_actions.php';
            
            selectedEvents.forEach(eventId => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'event_ids[]';
                input.value = eventId;
                form.appendChild(input);
            });
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'bulk_action';
            actionInput.value = action;
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        });
    }
    
    // Real-time statistics
    if (document.getElementById('admin-dashboard')) {
        updateAdminStats();
        setInterval(updateAdminStats, 60000); // Update every minute
    }
    
    function updateAdminStats() {
        fetch('../api/get_admin_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('total-events').textContent = data.data.total_events;
                    document.getElementById('total-bookings').textContent = data.data.total_bookings;
                    document.getElementById('available-tickets').textContent = data.data.available_tickets;
                    document.getElementById('today-bookings').textContent = data.data.today_bookings;
                }
            })
            .catch(error => console.error('Stats update failed:', error));
    }
});
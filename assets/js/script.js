document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle form submissions with confirmation
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });
    
    // Auto-close alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var fade = new bootstrap.Collapse(alert, {
                toggle: false
            });
            fade.hide();
        });
    }, 5000);
    
    // Enable date pickers
    if (typeof flatpickr !== 'undefined') {
        flatpickr('[data-datepicker]', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });
    }
    
    // Handle notification clicks
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            if (notificationId) {
                fetch(`/mark_notification_read.php?id=${notificationId}`);
            }
        });
    });


    function markNotificationRead(notificationId) {
        fetch(`${BASE_URL}/mark_notification_read.php?id=${notificationId}`);
    }
    
    // Time-ago helper (used in notifications)
    function time_elapsed_string(datetime) {
        // Implementation from earlier
    }
});
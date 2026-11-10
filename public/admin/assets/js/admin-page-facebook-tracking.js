/* Facebook tracking settings: status probe + configuration test.
   Moved from backEnd/facebook-tracking/index.blade.php. Route URLs now ride
   in on data attributes: [data-status-urls] {status:test...} and
   [data-test-url]. */
// Check tracking status on page load
document.addEventListener('DOMContentLoaded', function() {
    checkTrackingStatus();
});

// Function to check tracking status
function checkTrackingStatus() {
    var row = document.querySelector('[data-status-urls]');
    var urls = row ? JSON.parse(row.getAttribute('data-status-urls')) : {};
    fetch(urls.status || '')
        .then(response => response.json())
        .then(data => {
            updateStatusIndicator('tracking-status', data.enabled, 'Tracking Enabled', 'Tracking Disabled');
            updateStatusIndicator('pixel-status', data.pixel_id, 'Pixel ID Set', 'Pixel ID Missing');
            updateStatusIndicator('api-status', data.api_version, 'API Configured', 'API Not Configured');
            updateStatusIndicator('server-status', data.server_side_tracking, 'Server-Side Enabled', 'Server-Side Disabled');
        })
        .catch(error => {
            console.error('Error checking status:', error);
        });
}

// Function to update status indicators
function updateStatusIndicator(elementId, condition, successText, failureText) {
    const element = document.getElementById(elementId);
    const textElement = document.getElementById(elementId.replace('-status', '-text'));
    
    if (condition) {
        element.innerHTML = '<i class="fas fa-circle text-success"></i>';
        textElement.textContent = successText;
    } else {
        element.innerHTML = '<i class="fas fa-circle text-danger"></i>';
        textElement.textContent = failureText;
    }
}

// Function to test tracking configuration
function testTracking() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
    button.disabled = true;
    
    fetch(document.querySelector('[data-test-url]').getAttribute('data-test-url'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        // Show result in modal
        const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
        const modalBody = document.getElementById('testResultBody');
        
        if (data.success) {
            modalBody.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Success!</strong> ${data.message}
                </div>
            `;
        } else {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error!</strong> ${data.message}
                </div>
            `;
        }
        
        modal.show();
    })
    .catch(error => {
        console.error('Error testing tracking:', error);
        
        // Show error in modal
        const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
        const modalBody = document.getElementById('testResultBody');
        
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Error!</strong> Failed to test tracking configuration. Please try again.
            </div>
        `;
        
        modal.show();
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

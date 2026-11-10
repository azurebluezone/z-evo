/* TikTok test console: connection check + test event sends.
   Moved from backEnd/tiktok-test/index.blade.php. Endpoints are read from
   [data-connection-url] / [data-send-event-url]; CSRF from the layout meta. */
function testConnection() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="icon-base ri ri-loader-line spinner-icon"></i> Testing...';

    fetch(document.querySelector('[data-connection-url]').getAttribute('data-connection-url'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('connection-result');
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>✓ Connection Successful!</strong><br>
                    ${data.message || ''}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>✗ Connection Failed</strong><br>
                    ${data.message || 'Unknown error'}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <strong>✗ Error</strong><br>
                ${error.message}
            </div>
        `;
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="icon-base ri ri-wifi-line"></i> Test Connection';
    });
}

function sendTestEvent(eventName) {
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="icon-base ri ri-loader-line spinner-icon"></i> Sending...';

    fetch(document.querySelector('[data-send-event-url]').getAttribute('data-send-event-url'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ event_name: eventName })
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('event-result');
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success">
                    <strong>✓ Event Sent Successfully!</strong><br>
                    Event: ${data.event}<br>
                    ${data.message || ''}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-danger">
                    <strong>✗ Failed to Send Event</strong><br>
                    ${data.message || 'Unknown error'}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div class="alert alert-danger">
                <strong>✗ Error</strong><br>
                ${error.message}
            </div>
        `;
    })
    .finally(() => {
        btn.disabled = false;
        
        // Reset button text based on event type
        const eventNames = {
            'PageView': 'PageView',
            'ViewContent': 'ViewContent',
            'AddToCart': 'AddToCart',
            'InitiateCheckout': 'InitiateCheckout',
            'Purchase': 'Purchase'
        };
        btn.innerHTML = `<i class="icon-base ri ri-check-line"></i><br>${eventNames[eventName] || eventName}`;
        
        setTimeout(() => {
            location.reload();
        }, 2000);
    });
}

/*
 * fm-temp-password.js
 * ---------------------------------------------------------------------------
 * Live countdown on the temporary-password notice shown after a guest's first
 * order (resources/views/frontend/fm/partials/account/temp-password.blade.php).
 *
 * WHAT THIS IS AND IS NOT
 * This is a COURTESY. The 30 minute limit is enforced on the server:
 * GuestCheckoutAccountService::temporaryPasswordFor() re-reads the issue
 * timestamp and returns null once it has passed, so the value is never sent to
 * the browser after the window closes. Nothing here can extend it.
 *
 * When the countdown reaches zero this hides the notice, because leaving a
 * password on screen with a frozen "00:00" invites the reader to assume it is
 * still valid. The next page load simply will not contain it.
 */
(function () {
    'use strict';

    var notice = document.querySelector('[data-fm-temp-password]');
    if (!notice) return;

    var counter = notice.querySelector('[data-fm-temp-countdown]');
    if (!counter) return;

    var remaining = parseInt(counter.getAttribute('data-seconds') || '0', 10);
    if (!isFinite(remaining) || remaining <= 0) {
        notice.remove();
        return;
    }

    function render(seconds) {
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        counter.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }

    render(remaining);

    var timer = setInterval(function () {
        remaining -= 1;

        if (remaining <= 0) {
            clearInterval(timer);
            notice.remove();
            return;
        }

        render(remaining);
    }, 1000);
})();

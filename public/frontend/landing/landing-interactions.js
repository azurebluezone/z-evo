/* Shared landing interactions.
 *
 * The design-specific demo order scripts are intentionally not loaded: every
 * design now posts through the single /landing-order form owned by the shared
 * layout. This file only adds behaviour that is safe on every design.
 */
(function () {
    'use strict';

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function startCountdowns() {
        var nodes = document.querySelectorAll('[data-countdown-end]');

        Array.prototype.forEach.call(nodes, function (node) {
            var raw = node.getAttribute('data-countdown-end');
            var output = node.hasAttribute('data-countdown-output')
                ? node
                : node.querySelector('[data-countdown-output]');

            if (!raw || !output) {
                return;
            }

            var target = new Date(String(raw).replace(' ', 'T')).getTime();

            if (isNaN(target)) {
                return;
            }

            function tick() {
                var remaining = Math.max(0, Math.floor((target - Date.now()) / 1000));
                var hours = Math.floor(remaining / 3600);
                var minutes = Math.floor((remaining % 3600) / 60);
                var seconds = remaining % 60;

                output.textContent = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);

                if (remaining <= 0) {
                    window.clearInterval(timer);
                }
            }

            tick();
            var timer = window.setInterval(tick, 1000);
        });
    }

    function bindSmoothOrderLinks() {
        document.addEventListener('click', function (event) {
            var link = event.target.closest ? event.target.closest('a[href="#landing-order"]') : null;

            if (!link) {
                return;
            }

            var target = document.getElementById('landing-order');

            if (!target) {
                return;
            }

            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            startCountdowns();
            bindSmoothOrderLinks();
        });
    } else {
        startCountdowns();
        bindSmoothOrderLinks();
    }
})();

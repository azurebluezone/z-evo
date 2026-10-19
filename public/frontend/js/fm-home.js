/*
 * fm-home.js
 * ---------------------------------------------------------------------------
 * Extracted verbatim from resources/views/frontend/fm/pages/home.blade.php,
 * which used to carry it as an inline @push('script') block. Moving it here
 * keeps the page template readable and lets the browser cache it instead of
 * re-downloading it on every page view.
 *
 * Loaded at the end of <body>, in order, by the page's own @push('script').
 */

(function () {
    'use strict';

    /* ------------------------------------------------------------------ *
     * Hero slider
     * ------------------------------------------------------------------ */
    var hero = document.querySelector('[data-fm-hero]');

    if (hero) {
        var slides = Array.prototype.slice.call(hero.querySelectorAll('[data-fm-hero-slide]'));
        var dots = Array.prototype.slice.call(document.querySelectorAll('[data-fm-hero-dot]'));
        var prev = document.querySelector('[data-fm-hero-prev]');
        var next = document.querySelector('[data-fm-hero-next]');

        // A single slide has nothing to switch between.
        if (slides.length > 1) {
            var current = 0;
            var timer = null;
            var INTERVAL = 5500;

            function show(index) {
                // Keep the index inside the rail, so "previous" on the
                // first slide wraps to the last one instead of freezing.
                current = ((index % slides.length) + slides.length) % slides.length;

                slides.forEach(function (slide, i) {
                    var active = i === current;
                    slide.classList.toggle('is-active', active);
                    // aria-hidden keeps a screen reader on the visible
                    // slide only; the links inside the hidden ones are
                    // already tabindex="-1", so they stay unreachable.
                    slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                });

                dots.forEach(function (dot, i) {
                    var active = i === current;
                    dot.classList.toggle('fm-active', active);
                    dot.setAttribute('aria-selected', active ? 'true' : 'false');
                });
            }

            function start() {
                stop();
                timer = window.setInterval(function () {
                    show(current + 1);
                }, INTERVAL);
            }

            function stop() {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            }

            dots.forEach(function (dot, i) {
                dot.addEventListener('click', function () {
                    show(i);
                    start();
                });
            });

            if (prev) {
                prev.addEventListener('click', function () {
                    show(current - 1);
                    start();
                });
            }

            if (next) {
                next.addEventListener('click', function () {
                    show(current + 1);
                    start();
                });
            }

            // Autoplay stops while the banner is off screen and while the
            // visitor is reading it - a tab in the background should not
            // be running a timer.
            document.addEventListener('visibilitychange', function () {
                if (document.hidden) {
                    stop();
                } else {
                    start();
                }
            });

            hero.addEventListener('mouseenter', stop);
            hero.addEventListener('mouseleave', start);

            // Keyboard support on the dot strip (it is a tablist).
            dots.forEach(function (dot, i) {
                dot.addEventListener('keydown', function (event) {
                    var target = null;
                    if (event.key === 'ArrowRight') target = i + 1;
                    if (event.key === 'ArrowLeft') target = i - 1;
                    if (target === null) return;
                    event.preventDefault();
                    var wrapped = ((target % dots.length) + dots.length) % dots.length;
                    dots[wrapped].focus();
                    show(wrapped);
                });
            });

            start();
        }
    }

    /* ------------------------------------------------------------------ *
     * Flash sale countdown
     * ------------------------------------------------------------------ */
    document.querySelectorAll('[data-fm-countdown]').forEach(function (clock) {
        var target = Date.parse(clock.getAttribute('data-target') || '');

        if (isNaN(target)) return;

        var hOut = clock.querySelector('[data-fm-cd="h"]');
        var mOut = clock.querySelector('[data-fm-cd="m"]');
        var sOut = clock.querySelector('[data-fm-cd="s"]');
        var labelLive = clock.getAttribute('data-label-live') || 'Ends in';
        var labelDone = clock.getAttribute('data-label-done') || 'Sale ended';

        // The three unit captions. Hours / Mins / Secs matches the
        // reference and stays correct for a sale 30 hours out - the first
        // unit simply reads 30. labelLive is the prefix a screen reader
        // gets from the group's own accessible name instead.
        var units = clock.querySelectorAll('.fm-countdown-lbl');

        // "Ends in" / "Starts in" is the word a screen reader needs and
        // the design has nowhere to print it.
        clock.setAttribute('aria-label', labelLive);

        function pad(value) {
            return value < 10 ? '0' + value : String(value);
        }

        function render() {
            var left = Math.max(0, Math.round((target - Date.now()) / 1000));

            if (hOut) hOut.textContent = pad(Math.floor(left / 3600));
            if (mOut) mOut.textContent = pad(Math.floor((left % 3600) / 60));
            if (sOut) sOut.textContent = pad(left % 60);

            if (left > 0) return;

            // The target passed. Say so once in the first unit, blank the
            // other two, then stop - a clock frozen on 00:00:00 forever
            // is noise, and "Ends in 00:00:00" would be a lie.
            clock.setAttribute('data-ended', '1');
            if (units[0]) units[0].textContent = labelDone;
            if (units[1]) units[1].textContent = '';
            if (units[2]) units[2].textContent = '';

            clock.setAttribute('aria-live', 'polite');
            window.clearInterval(handle);
        }

        var handle = window.setInterval(render, 1000);
        render();
    });
})();

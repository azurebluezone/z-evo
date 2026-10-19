/**
 * fm-order-stepper.js - wire the order form's three-step flow.
 *
 * Three facts about the bs-stepper build that ships with this template, each
 * found by reading the bundle rather than the docs:
 *
 *  1. It does NOT initialise itself. The bundle exports the Stepper class and
 *     listens for show.bs-stepper on anything already carrying .bs-stepper.
 *     Without `new Stepper(...)` the markup is perfect and nothing happens.
 *
 *  2. It binds a click handler to each step's header link and nothing else.
 *     The data-bs-stepper-next / -prev buttons are NOT wired for you; they only
 *     look like they are. They have to be pointed at the instance's next() and
 *     previous().
 *
 *  3. Panes are hidden by the rule `.bs-stepper .content:not(.fade)`, so a
 *     pane must carry the class "content". One called .step-pane is never
 *     hidden and every step shows at once.
 *
 * The clicks are delegated from the document rather than bound to the buttons,
 * because this file is pushed into the layout's script stack and can be parsed
 * before the section that holds the form. A listener on the document does not
 * care which came first; one bound at load time would silently bind nothing.
 */
(function () {
    'use strict';

    var INSTANCES = 'data-fm-stepper';

    function instanceFor(node) {
        var root = node && node.closest ? node.closest('.bs-stepper') : null;
        return root ? window.Stepper && window.Stepper.getInstance
            ? window.Stepper.getInstance(root)
            : (root.__fmStepper || null)
            : null;
    }

    function boot() {
        Array.prototype.forEach.call(document.querySelectorAll('.bs-stepper'), function (el) {
            if (el.__fmStepper || typeof window.Stepper === 'undefined') return;
            el.__fmStepper = new window.Stepper(el, { linear: true });
            el.setAttribute(INSTANCES, '1');
        });
    }

    document.addEventListener('click', function (e) {
        var next = e.target.closest ? e.target.closest('[data-bs-stepper-next]') : null;
        var prev = e.target.closest ? e.target.closest('[data-bs-stepper-prev]') : null;
        if (!next && !prev) return;

        // Delegated, so the button may not have existed when boot() ran.
        boot();

        var stepper = instanceFor(next || prev);
        if (!stepper) return;

        e.preventDefault();
        if (next) {
            stepper.next();
        } else {
            stepper.previous();
        }
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
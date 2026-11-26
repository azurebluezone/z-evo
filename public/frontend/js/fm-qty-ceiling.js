/*
 * fm-qty-ceiling.js
 * ---------------------------------------------------------------------------
 * One rule, one place: how many units the buy-box stepper may offer.
 *
 * The server publishes available-to-promise for the product and then again for
 * the selected variant. Without a single owner, the page-load ceiling, the
 * clamp inside fm-product-detail.js and the disabled state of the plus button
 * drift apart - a 4-unit product kept offering 4 after a 1-unit variant was
 * picked, and the shopper only learned the truth when the cart refused.
 *
 * No dependencies and one global (window.FmQtyCeiling), so the rule can be
 * driven from a Node harness instead of only by hand in a browser.
 */
(function (global) {
    'use strict';

    /**
     * Units that can actually ship.
     *
     * Anything that is not a positive integer means "nothing available".
     * Falling back to 1 here would silently invent stock.
     *
     * @param {*} raw
     * @return {number} 0 when nothing is available
     */
    function normalizeQty(raw) {
        var value = parseInt(raw, 10);
        return (isFinite(value) && value > 0) ? value : 0;
    }

    /**
     * Create the controller for one stepper.
     *
     * @param {{
     *   input: {value: string, setAttribute: Function}|null,
     *   plus: {disabled: boolean}|null,
     *   minus: {disabled: boolean}|null,
     *   sync: Function
     * }} options
     * @return {{set: Function, refresh: Function, value: Function, max: Function}}
     */
    function create(options) {
        var opts = options || {};
        var input = opts.input || null;
        var plus = opts.plus || null;
        var minus = opts.minus || null;
        var sync = typeof opts.sync === 'function' ? opts.sync : function () {};

        // A ceiling below one cannot be written into a min="1" field, but the
        // real available count (0) is still published on data-max-stock so the
        // markup never claims stock that does not exist.
        var maxQty = 1;

        function currentValue() {
            var value = input ? parseInt(input.value, 10) : 1;
            return (isFinite(value) && value > 0) ? value : 1;
        }

        function refresh() {
            var value = currentValue();
            if (plus) plus.disabled = value >= maxQty;
            if (minus) minus.disabled = value <= 1;
        }

        function set(rawQty) {
            var available = normalizeQty(rawQty);
            maxQty = available > 0 ? available : 1;

            if (input) {
                input.setAttribute('max', String(maxQty));
                input.setAttribute('data-max-stock', String(available));

                if (currentValue() > maxQty) {
                    input.value = String(maxQty);
                    sync();
                }
            }

            refresh();
        }

        return {
            set: set,
            refresh: refresh,
            value: currentValue,
            max: function () { return maxQty; }
        };
    }

    global.FmQtyCeiling = {
        normalizeQty: normalizeQty,
        create: create
    };
})(typeof window !== 'undefined' ? window : globalThis);

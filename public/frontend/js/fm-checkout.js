/*
 * fm-checkout.js
 * ---------------------------------------------------------------------------
 * Behaviour for /customer/checkout
 * (resources/views/frontend/fm/pages/customer/checkout.blade.php).
 *
 * Ported from the inline @push('scripts') block the classic checkout page
 * carried, so the page template is readable and the browser can cache this
 * file instead of re-downloading it on every visit.
 *
 * WHAT IS LOADED FROM WHERE
 *   window.FM_CONFIG   csrf token + shared cart endpoints (fm.layouts)
 *   window.FM_CO       page specific endpoints + the Pixel payload (this page)
 *   data-* attributes  the current value of every number on screen
 *
 * DESIGN RULES THIS FILE KEEPS
 *   1. Quantity lives on the row as data-qty. The repaint and the POST both
 *      read it, so the number the customer sees and the number the server gets
 *      cannot disagree.
 *   2. Money is read from data-value, never parsed back out of formatted text.
 *      The classic script stripped "৳" and "," with a regex; that returns 0 the
 *      moment the currency prefix changes to "Tk " or the number is grouped
 *      differently, and it fails silently.
 *   3. Every mutation is optimistic-then-confirmed, and every failure path
 *      restores the previous value and tells the customer why.
 *   4. A row is locked (is-pending + disabled buttons) while its POST is in
 *      flight so a double click cannot race the server.
 *
 * Loaded at the end of <body> by the page's own @push('script').
 */

(function () {
    'use strict';

    var CFG = window.FM_CONFIG || {};
    var CO = window.FM_CO || {};

    var root = document.querySelector('[data-section="checkout"]');
    if (!root) return;

    /* ----------------------------------------------------------------------
     * CSRF token
     *
     * FM_CONFIG first: the modern chrome has no <meta name="csrf-token"> by
     * design, it ships the token through window.FM_CONFIG. The meta lookup is
     * kept as a fallback so this script keeps working on a layout that does
     * emit it.
     * -------------------------------------------------------------------- */
    function csrfToken() {
        if (CFG.csrfToken) return CFG.csrfToken;

        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? (meta.getAttribute('content') || '') : '';
    }

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    /* ----------------------------------------------------------------------
     * Money
     * -------------------------------------------------------------------- */

    function money(value) {
        var n = Number(value);
        if (!isFinite(n)) n = 0;
        return '৳ ' + Math.round(n).toLocaleString('en-US');
    }

    function nodeValue(selector) {
        var el = root.querySelector(selector);
        if (!el) return 0;
        var n = Number(el.getAttribute('data-value'));
        return isFinite(n) ? n : 0;
    }

    function setNodeValue(selector, value) {
        var el = root.querySelector(selector);
        if (!el) return;
        el.setAttribute('data-value', String(value));
    }

    function paint(selector, value) {
        var el = root.querySelector(selector);
        if (el) el.textContent = money(value);
    }

    /* ----------------------------------------------------------------------
     * Totals
     *
     * Subtotal is summed from the rows on screen (so a quantity edit shows up
     * immediately); shipping and discount come from their own data-value.
     * -------------------------------------------------------------------- */

    function rows() {
        return Array.prototype.slice.call(root.querySelectorAll('[data-fm-line]'));
    }

    function rowQty(row) {
        var v = parseInt(row.getAttribute('data-qty'), 10);
        return isFinite(v) && v > 0 ? v : 1;
    }

    function rowUnit(row) {
        var n = Number(row.getAttribute('data-unit-price'));
        return isFinite(n) ? n : 0;
    }

    function renderTotals() {
        var subtotal = 0;
        rows().forEach(function (row) {
            subtotal += rowUnit(row) * rowQty(row);
        });

        var shipping = nodeValue('[data-fm-shipping]');
        var discount = nodeValue('[data-fm-discount]');
        var grand = subtotal + shipping - discount;

        setNodeValue('[data-fm-subtotal]', subtotal);
        paint('[data-fm-subtotal]', subtotal);
        paint('[data-fm-grand]', grand);

        var mini = root.querySelector('[data-fm-grand-mini]');
        if (mini) mini.textContent = money(grand);
    }

    /* ----------------------------------------------------------------------
     * Row repaint
     * -------------------------------------------------------------------- */

    function paintRow(row, qty) {
        var safe = Math.max(1, parseInt(qty, 10) || 1);

        row.setAttribute('data-qty', String(safe));

        var qtyEl = row.querySelector('[data-fm-line-qty]');
        if (qtyEl) qtyEl.textContent = String(safe);

        var priceEl = row.querySelector('[data-fm-line-price]');
        if (priceEl) priceEl.textContent = money(rowUnit(row) * safe);
    }

    function lockRow(row, locked) {
        row.classList.toggle('is-pending', !!locked);
        Array.prototype.forEach.call(row.querySelectorAll('button'), function (btn) {
            btn.disabled = !!locked;
        });
    }

    function findRow(rowId) {
        return root.querySelector('[data-fm-line][data-rowid="' + rowId + '"]');
    }

    /* ----------------------------------------------------------------------
     * Toast
     *
     * fm-app.js already owns one site-wide toast and exposes it as
     * window.FM.toast(message, kind) with kind = '' | 'error' | 'success'.
     * Reuse it so a quantity edit and an add-to-cart notice look identical.
     * The local node below is only a fallback for the case where this script
     * is ever loaded on a page that did not include fm-app.js.
     * -------------------------------------------------------------------- */

    function toast(message, isError) {
        if (window.FM && typeof window.FM.toast === 'function') {
            window.FM.toast(message, isError ? 'error' : 'success');
            return;
        }

        var el = document.getElementById('fmCoToast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'fmCoToast';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            el.style.cssText = [
                'position:fixed', 'left:50%', 'bottom:24px', 'transform:translateX(-50%)',
                'z-index:1200', 'padding:12px 24px', 'border-radius:9999px',
                'font-size:.875rem', 'font-weight:600', 'color:#fff',
                'opacity:0', 'pointer-events:none', 'transition:opacity .2s ease',
                'box-shadow:0 8px 24px rgba(0,0,0,.18)'
            ].join(';');
            document.body.appendChild(el);
        }

        el.textContent = message;
        el.style.background = isError ? '#b91c1c' : '#047857';
        el.style.opacity = '1';

        clearTimeout(el._fmTimer);
        el._fmTimer = setTimeout(function () { el.style.opacity = '0'; }, 2800);
    }

    /* ----------------------------------------------------------------------
     * Payment method selection
     *
     * A plain <label> wrapper already toggles the native radio, so this only
     * has to move the .is-selected ring. It still re-asserts on change so a
     * keyboard arrow-key change repaints too (the browser fires change, not
     * click, when focus is inside the group).
     * -------------------------------------------------------------------- */

    var payList = root.querySelector('[data-fm-pay-list]');

    function paintPay() {
        if (!payList) return;
        Array.prototype.forEach.call(payList.querySelectorAll('[data-fm-pay]'), function (label) {
            var input = label.querySelector('input[type="radio"]');
            label.classList.toggle('is-selected', !!(input && input.checked));
        });
    }

    if (payList) {
        payList.addEventListener('change', paintPay);
    }
    paintPay();

    /* ----------------------------------------------------------------------
     * Delivery area -> shipping charge
     * -------------------------------------------------------------------- */

    var areaSelect = document.getElementById('coArea');

    if (areaSelect && CO.shippingChargeUrl) {
        areaSelect.addEventListener('change', function () {
            var id = this.value;
            if (!id) return;

            var previous = nodeValue('[data-fm-shipping]');

            fetch(CO.shippingChargeUrl + '?id=' + encodeURIComponent(id), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) { return res.json().then(function (d) { return { ok: res.ok, data: d }; }); })
                .then(function (r) {
                    if (!r.ok || !r.data || r.data.status !== true) {
                        // The endpoint rejects an unknown id; put the old number
                        // back so the summary never shows a charge that is not
                        // what the server would charge.
                        setNodeValue('[data-fm-shipping]', previous);
                        paint('[data-fm-shipping]', previous);
                        renderTotals();
                        toast((r.data && r.data.message) || 'Could not update shipping', true);
                        return;
                    }

                    var amount = Number(r.data.amount) || 0;
                    setNodeValue('[data-fm-shipping]', amount);
                    paint('[data-fm-shipping]', amount);
                    renderTotals();
                    toast('Shipping updated: ' + money(amount), false);
                })
                .catch(function () {
                    setNodeValue('[data-fm-shipping]', previous);
                    paint('[data-fm-shipping]', previous);
                    renderTotals();
                });
        });
    }

    /* ----------------------------------------------------------------------
     * Summary collapse (phones / tablets)
     *
     * The body starts open in the markup (so it is readable with JS off); this
     * closes it once and takes over. Clicking a quantity or remove button
     * while collapsed re-opens it, because otherwise the button would appear to
     * do nothing.
     * -------------------------------------------------------------------- */

    var summary = root.querySelector('[data-fm-summary]');
    var summaryBody = root.querySelector('[data-fm-summary-body]');
    var summaryToggle = root.querySelector('[data-fm-summary-toggle]');
    var summaryToggleText = root.querySelector('[data-fm-summary-toggle-text]');

    function setSummary(open) {
        if (!summary || !summaryBody) return;
        summary.classList.toggle('is-open', open);
        if (summaryToggle) {
            summaryToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        if (summaryToggleText) {
            summaryToggleText.textContent = open ? 'Hide summary' : 'Show summary';
        }
    }

    if (summaryToggle && summary && summaryBody) {
        // Collapse only when the CSS actually collapses it (< 992px).
        var collapsible = window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches;
        setSummary(!collapsible);

        summaryToggle.addEventListener('click', function () {
            setSummary(!summary.classList.contains('is-open'));
        });
    }

    /* ----------------------------------------------------------------------
     * Header badge / mini cart sync
     *
     * The two compat endpoints this page posts to were written separately and
     * they disagree on the field name:
     *     POST /cart-update/{rowId}      -> { cart_count:     N }
     *     POST /remove-from-cart/{rowId} -> { cartItemCount:  N }
     * Both were verified live, so the reader has to accept either rather than
     * assume one. updateCartCountBadge() is called as (0, count) to match the
     * signature the modern header registered.
     * -------------------------------------------------------------------- */

    function syncHeader(payload) {
        if (!payload) return;

        var count = (payload.cartItemCount !== undefined && payload.cartItemCount !== null)
            ? payload.cartItemCount
            : payload.cart_count;

        if (count === undefined || count === null) {
            if (typeof window.setCompareWishlistAndCartData === 'function') {
                window.setCompareWishlistAndCartData();
            }
            return;
        }

        if (typeof window.updateCartCountBadge === 'function') {
            window.updateCartCountBadge(0, count);
        } else if (typeof window.setCompareWishlistAndCartData === 'function') {
            window.setCompareWishlistAndCartData();
        }
    }

    /* ----------------------------------------------------------------------
     * Quantity + / -
     * -------------------------------------------------------------------- */

    var busy = false;

    function changeQty(rowId, delta) {
        if (busy) return;

        var row = findRow(rowId);
        if (!row) return;

        var previousQty = rowQty(row);
        var nextQty = previousQty + delta;

        // Floor at 1. The classic cart page removes the row when the customer
        // decrements past 1; checkout does not, because the customer has
        // already filled in an address and losing the line under them is worse
        // than an ignored click. Remove has its own button.
        if (nextQty < 1) nextQty = 1;
        if (nextQty === previousQty) return;

        busy = true;
        lockRow(row, true);
        paintRow(row, nextQty);
        renderTotals();

        fetch(CO.cartUpdateUrl + '/' + encodeURIComponent(rowId), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ quantity: nextQty })
        })
            .then(function (res) { return res.json().catch(function () { return {}; }).then(function (d) { return { ok: res.ok, data: d }; }); })
            .then(function (r) {
                if (!r.ok || !r.data || r.data.status !== 'success') {
                    // The route enforces stock: data.quantity carries the qty it
                    // would actually accept. Repaint with that, not the guess.
                    var fallback = (r.data && r.data.quantity !== undefined && r.data.quantity !== null)
                        ? parseInt(r.data.quantity, 10)
                        : previousQty;

                    paintRow(row, isFinite(fallback) ? fallback : previousQty);
                    renderTotals();
                    toast((r.data && r.data.message) || 'Failed to update quantity', true);
                    return;
                }

                if (r.data.quantity !== undefined && r.data.quantity !== null) {
                    paintRow(row, parseInt(r.data.quantity, 10));
                }
                renderTotals();
                syncHeader(r.data.cartItemCount);
            })
            .catch(function () {
                paintRow(row, previousQty);
                renderTotals();
                toast('Failed to update quantity', true);
            })
            .then(function () {
                busy = false;
                if (row.isConnected) lockRow(row, false);
            });
    }

    /* ----------------------------------------------------------------------
     * Remove a line
     * -------------------------------------------------------------------- */

    function removeLine(rowId) {
        if (busy) return;

        var row = findRow(rowId);
        if (!row) return;

        if (!window.confirm('Remove this item from cart?')) return;

        busy = true;
        lockRow(row, true);

        fetch(CO.cartRemoveUrl + '/' + encodeURIComponent(rowId), {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({})
        })
            .then(function (res) { return res.json().catch(function () { return {}; }).then(function (d) { return { ok: res.ok, data: d }; }); })
            .then(function (r) {
                if (!r.ok || !r.data || r.data.status !== 'success') {
                    toast((r.data && r.data.message) || 'Failed to remove item', true);
                    return;
                }

                row.remove();
                renderTotals();
                syncHeader(r.data.cartItemCount);
                toast('Removed from cart', false);

                // Last line gone: the summary would show an empty list with no
                // explanation, so point at the shop.
                if (rows().length === 0) {
                    window.setTimeout(function () { window.location.reload(); }, 700);
                }
            })
            .catch(function () {
                toast('Failed to remove item', true);
            })
            .then(function () {
                busy = false;
            });
    }

    /* ----------------------------------------------------------------------
     * Coupon
     * -------------------------------------------------------------------- */

    var couponBtn = root.querySelector('[data-fm-coupon-apply]');
    var couponInput = document.getElementById('coCouponInput');
    var couponStatus = root.querySelector('[data-fm-coupon-status]');

    function paintCoupon(message, isError) {
        if (!couponStatus) return;
        couponStatus.textContent = message;
        couponStatus.classList.toggle('is-error', !!isError);
        couponStatus.hidden = false;
    }

    if (couponBtn && couponInput) {
        couponBtn.addEventListener('click', function () {
            var code = couponInput.value.trim();

            if (!code) {
                paintCoupon('Please enter a coupon code', true);
                return;
            }

            couponBtn.disabled = true;

            // Subtotal is recomputed the same way the summary shows it, so a
            // percentage coupon is calculated against what the customer is
            // actually being charged right now.
            var subtotal = 0;
            rows().forEach(function (row) { subtotal += rowUnit(row) * rowQty(row); });

            fetch(CO.couponApplyUrl, {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ coupon_code: code, subtotal: subtotal })
            })
                .then(function (res) { return res.json().catch(function () { return {}; }).then(function (d) { return { ok: res.ok, data: d }; }); })
                .then(function (r) {
                    var d = r.data || {};

                    if (!r.ok || d.status !== 'success') {
                        paintCoupon(d.message || 'Invalid coupon code', true);
                        toast(d.message || 'Invalid coupon code', true);
                        return;
                    }

                    var amount = Number(d.amount) || 0;
                    setNodeValue('[data-fm-discount]', amount);
                    paint('[data-fm-discount]', amount);
                    renderTotals();
                    paintCoupon(d.message || 'Coupon applied successfully', false);
                    toast('Coupon applied successfully!', false);
                })
                .catch(function () {
                    paintCoupon('Failed to apply coupon', true);
                })
                .then(function () {
                    couponBtn.disabled = false;
                });
        });
    }

    /* ----------------------------------------------------------------------
     * Delegated row controls
     *
     * One listener on the summary instead of one per row: rows are removed
     * from the DOM by removeLine(), and per-row listeners would have to be
     * torn down with them.
     * -------------------------------------------------------------------- */

    if (summary) {
        summary.addEventListener('click', function (e) {
            var inc = e.target.closest('[data-fm-inc]');
            if (inc) {
                e.preventDefault();
                var id = inc.closest('[data-fm-line]').getAttribute('data-rowid');
                setSummary(true);
                changeQty(id, 1);
                return;
            }

            var dec = e.target.closest('[data-fm-dec]');
            if (dec) {
                e.preventDefault();
                var dId = dec.closest('[data-fm-line]').getAttribute('data-rowid');
                setSummary(true);
                changeQty(dId, -1);
                return;
            }

            var rm = e.target.closest('[data-fm-remove]');
            if (rm) {
                e.preventDefault();
                var rId = rm.closest('[data-fm-line]').getAttribute('data-rowid');
                removeLine(rId);
            }
        });
    }

    /* ----------------------------------------------------------------------
     * Double-submit guard on Place Order
     *
     * The button navigates away on success, but if order_save() comes back
     * with a validation error the page reloads and an impatient second tap in
     * the meantime would place the order twice.
     * -------------------------------------------------------------------- */

    var submitBtn = root.querySelector('[data-fm-submit]');
    var orderForm = root.querySelector('form[action]');

    if (submitBtn && orderForm && orderForm.querySelector('input[name="name"]')) {
        orderForm.addEventListener('submit', function () {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Placing order&hellip;';
        });
    }

    /* ----------------------------------------------------------------------
     * InitiateCheckout (Pixel + Conversions API)
     *
     * CustomerController@checkout() already sent the server side event from
     * PHP. This is the browser side half, so the two halves can be matched.
     * Payloads match the classic inline block exactly.
     * -------------------------------------------------------------------- */

    (function fireInitiateCheckout() {
        var payload = (CO.tracking && CO.tracking.total !== undefined)
            ? CO.tracking
            : { total: 0, currency: 'BDT', num_items: 0, product_ids: [] };

        function send() {
            var tracker = window.facebookTracker;

            if (tracker && typeof tracker.trackInitiateCheckout === 'function') {
                tracker.trackInitiateCheckout(payload);
            } else if (typeof window.fbq === 'function') {
                window.fbq('track', 'InitiateCheckout', {
                    value: payload.total,
                    currency: payload.currency,
                    content_ids: payload.product_ids,
                    content_type: 'product',
                    num_items: payload.num_items
                });
            }
        }

        if (document.readyState === 'complete') {
            send();
        } else {
            window.addEventListener('load', send);
        }
    })();

    /* ----------------------------------------------------------------------
     * First paint
     * -------------------------------------------------------------------- */

    renderTotals();
})();

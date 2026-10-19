/*
 * fm-cart.js
 * ---------------------------------------------------------------------------
 * Extracted verbatim from resources/views/frontend/fm/pages/cart.blade.php,
 * which used to carry it as an inline @push('script') block. Moving it here
 * keeps the page template readable and lets the browser cache it instead of
 * re-downloading it on every page view.
 *
 * Loaded at the end of <body>, in order, by the page's own @push('script').
 */

(function () {
    'use strict';

    var CFG = window.FM_CONFIG || {};
    var root = document.querySelector('[data-section="cart"]');
    if (!root) return;

    var summaryCard = root.querySelector('[data-fm-summary-card]');
    var shipping = Number(summaryCard ? summaryCard.getAttribute('data-fm-shipping') : 0) || 0;
    var discount = Number(summaryCard ? summaryCard.getAttribute('data-fm-discount') : 0) || 0;

    function rows() {
        return Array.prototype.slice.call(root.querySelectorAll('[data-fm-line]'));
    }

    /**
     * Quantity lives on the row as an attribute - it is the single value the
     * repaint and the POST both read, so the number on screen and the number
     * sent to the server can never disagree.
     */
    function rowQty(row) {
        var value = parseInt(row.getAttribute('data-fm-line-qty'), 10);
        return isFinite(value) && value > 0 ? value : 1;
    }

    function money(value) {
        var n = Number(value);
        if (!isFinite(n)) n = 0;
        return (CFG.currency || 'Tk ') + Math.round(n).toLocaleString('en-US');
    }

    function toast(message, kind) {
        if (window.FM && typeof window.FM.toast === 'function') window.FM.toast(message, kind);
    }

    /* ---------------------------------------------------------------- *
     * Repaint from the DOM.
     *
     * Price and qty come from server rendered markup, so this mirrors
     * exactly what a reload would show: subtotal = sum(price * qty),
     * total = subtotal + shipping - discount. Prices in this catalogue
     * are whole taka, so there is no rounding drift between here and the
     * server.
     * ---------------------------------------------------------------- */
    function repaint() {
        var subtotal = 0;
        var qty = 0;

        rows().forEach(function (row) {
            var price = Number(row.getAttribute('data-fm-line-price')) || 0;
            var lineQty = rowQty(row);
            var lineTotal = price * lineQty;
            subtotal += lineTotal;
            qty += lineQty;

            var totalEl = row.querySelector('[data-fm-line-total]');
            if (totalEl) totalEl.textContent = money(lineTotal);

            var qtyEl = row.querySelector('[data-fm-line-qty-text]');
            if (qtyEl) qtyEl.textContent = String(lineQty);
        });

        var total = (subtotal + shipping) - discount;

        set('[data-fm-sum-items]', String(qty));
        set('[data-fm-sum-subtotal]', money(subtotal));
        set('[data-fm-sum-total]', money(total));
        set('[data-fm-sum-discount]', '- ' + money(discount));

        var discountRow = document.querySelector('[data-fm-sum-discount-row]');
        if (discountRow) {
            if (discount > 0) discountRow.removeAttribute('hidden');
            else discountRow.setAttribute('hidden', '');
        }

        // Header badge and header/drawer total carry the same subtotal, so
        // they are repainted from the same number instead of a second fetch.
        document.querySelectorAll('[data-fm-cart-count]').forEach(function (el) {
            el.textContent = String(qty);
        });
        document.querySelectorAll('[data-fm-cart-total]').forEach(function (el) {
            el.textContent = money(subtotal);
        });

        // Savings depends on qty too.
        var savingEl = document.querySelector('[data-fm-sum-saving]');
        if (savingEl) {
            var saving = 0;
            rows().forEach(function (row) {
                var price = Number(row.getAttribute('data-fm-line-price')) || 0;
                var old = Number(row.getAttribute('data-fm-line-old')) || 0;
                var lineQty = rowQty(row);
                if (old > price) saving += (old - price) * lineQty;
            });
            if (saving > 0) {
                savingEl.removeAttribute('hidden');
                var valueEl = savingEl.querySelector('[data-fm-sum-saving-value]');
                if (valueEl) valueEl.textContent = money(saving);
            } else {
                savingEl.setAttribute('hidden', '');
            }
        }
    }

    function set(selector, text) {
        var el = document.querySelector(selector);
        if (el) el.textContent = text;
    }

    function findRow(rowId) {
        return root.querySelector('[data-fm-line="' + rowId + '"]');
    }

    function jsonRequest(url, options) {
        return fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        }, options || {})).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        });
    }

    /* ---------------------------------------------------------------- *
     * Quantity changes.
     *
     * POST /cart-update/{rowId} is the classic endpoint: it validates
     * stock and answers with the authoritative quantity, so a 422 rolls
     * the row straight back to what the server actually holds.
     * ---------------------------------------------------------------- */
    function postQuantity(rowId, quantity) {
        return jsonRequest(CFG.cartUpdateUrl + '/' + encodeURIComponent(rowId), {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: '_token=' + encodeURIComponent(CFG.csrfToken || '') +
                  '&quantity=' + encodeURIComponent(quantity),
        });
    }

    function removeRow(row) {
        return jsonRequest(CFG.cartRemoveUrl + '?id=' + encodeURIComponent(row.getAttribute('data-fm-line')));
    }

    /**
     * Classic drops an applied coupon the moment the basket changes and
     * tells the shopper so. The classic page only cleared it in the DOM and
     * then reloaded, which brought it straight back; here the session is
     * actually cleared so what is on screen stays true after a reload.
     */
    function dropCouponIfNeeded() {
        if (discount <= 0) return Promise.resolve();

        return jsonRequest(CFG.couponRemoveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: '_token=' + encodeURIComponent(CFG.csrfToken || ''),
        }).catch(function () { return { ok: false, data: {} }; }).then(function () {
            discount = 0;
            var code = document.querySelector('[data-fm-sum-code]');
            if (code) code.textContent = '';
            swapCouponBlock(false);
            toast('Applied coupon has been removed', 'info');
        });
    }

    function handleLineAction(button) {
        var action = button.getAttribute('data-fm-line-action');
        var rowId = button.getAttribute('data-fm-line-target');
        var row = rowId ? findRow(rowId) : null;
        if (!row || button.disabled) return;

        var current = rowQty(row);

        if (action === 'remove' || (action === 'dec' && current <= 1)) {
            row.style.opacity = '.5';
            removeRow(row).then(function (res) {
                if (!res.ok || !res.data || res.data.success !== true) {
                    throw new Error('remove rejected');
                }
                row.remove();

                var emptied = rows().length === 0;
                // The coupon has to be dropped even when the basket empties,
                // otherwise the session keeps a discount against a zero
                // subtotal and the reload would print a negative total.
                return dropCouponIfNeeded().then(function () {
                    if (emptied) {
                        // The empty state is server rendered, so a reload is
                        // the only honest way to swap to it.
                        window.location.reload();
                        return;
                    }
                    repaint();
                });
            }).catch(function () {
                row.style.opacity = '';
                toast((CFG.messages && CFG.messages.error) || 'Something went wrong', 'error');
            });
            return;
        }

        var next = action === 'inc' ? current + 1 : Math.max(1, current - 1);
        if (next === current) return;

        var pending = row.querySelectorAll('[data-fm-line-action="inc"],[data-fm-line-action="dec"]');
        pending.forEach(function (btn) { btn.disabled = true; });

        // Optimistic: the number moves first so the click feels instant.
        row.setAttribute('data-fm-line-qty', String(next));
        repaint();

        postQuantity(rowId, next).then(function (res) {
            pending.forEach(function (btn) { btn.disabled = false; });

            if (!res.ok || !res.data || res.data.status !== 'success') {
                // Roll back to whatever the server says the row really holds.
                var serverQty = parseInt(res.data && res.data.quantity, 10);
                row.setAttribute('data-fm-line-qty', isFinite(serverQty) && serverQty > 0 ? String(serverQty) : String(current));
                repaint();
                var message = res.data && res.data.message;
                toast(message || ((CFG.messages && CFG.messages.stock) || 'Not enough stock'), 'error');
                return;
            }

            dropCouponIfNeeded().then(repaint);
        }).catch(function () {
            pending.forEach(function (btn) { btn.disabled = false; });
            row.setAttribute('data-fm-line-qty', String(current));
            repaint();
            toast((CFG.messages && CFG.messages.error) || 'Something went wrong', 'error');
        });
    }

    /* ---------------------------------------------------------------- *
     * Coupon
     *
     * POST /apply-coupon expects coupon_code + subtotal and answers with
     * the amount it computed; /remove-coupon forgets the session keys.
     * ---------------------------------------------------------------- */
    function swapCouponBlock(applied) {
        var form = document.querySelector('[data-fm-coupon-form]');
        var chip = document.querySelector('[data-fm-coupon-applied]');
        if (form) {
            if (applied) form.setAttribute('hidden', '');
            else form.removeAttribute('hidden');
        }
        if (chip) {
            if (applied) chip.removeAttribute('hidden');
            else chip.setAttribute('hidden', '');
        }
    }

    function currentSubtotal() {
        var subtotal = 0;
        rows().forEach(function (row) {
            var price = Number(row.getAttribute('data-fm-line-price')) || 0;
            var lineQty = rowQty(row);
            subtotal += price * lineQty;
        });
        return subtotal;
    }

    function applyCoupon() {
        var input = document.querySelector('[data-fm-coupon-input]');
        if (!input) return;
        var code = (input.value || '').trim();
        if (!code) {
            toast('Coupon field is required', 'error');
            input.focus();
            return;
        }

        var button = document.querySelector('[data-fm-coupon-apply]');
        if (button) button.disabled = true;

        var subtotal = currentSubtotal();

        jsonRequest(CFG.couponApplyUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: '_token=' + encodeURIComponent(CFG.csrfToken || '') +
                  '&coupon_code=' + encodeURIComponent(code) +
                  '&subtotal=' + encodeURIComponent(subtotal),
        }).then(function (res) {
            if (button) button.disabled = false;

            if (!res.ok || !res.data || res.data.status !== 'success') {
                toast((res.data && res.data.message) || 'Invalid or expired coupon', 'error');
                return;
            }

            var amount = Number(res.data.amount) || 0;
            // A coupon can never discount more than the basket is worth.
            if (amount > subtotal) amount = subtotal;
            discount = amount;

            var codeEl = document.querySelector('[data-fm-sum-code]');
            if (codeEl) codeEl.textContent = res.data.coupon_code || code.toUpperCase();
            var appliedEl = document.querySelector('[data-fm-applied-code]');
            if (appliedEl) appliedEl.textContent = res.data.coupon_code || code.toUpperCase();

            input.value = '';
            swapCouponBlock(true);
            repaint();
            toast(res.data.message || 'Coupon applied successfully');
        }).catch(function () {
            if (button) button.disabled = false;
            toast((CFG.messages && CFG.messages.error) || 'Something went wrong', 'error');
        });
    }

    function removeCoupon() {
        var button = document.querySelector('[data-fm-coupon-remove]');
        if (button) button.disabled = true;

        jsonRequest(CFG.couponRemoveUrl, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            },
            body: '_token=' + encodeURIComponent(CFG.csrfToken || ''),
        }).then(function (res) {
            if (button) button.disabled = false;
            if (!res.ok || !res.data || res.data.status !== 'success') {
                toast((res.data && res.data.message) || 'Something went wrong', 'error');
                return;
            }
            discount = 0;
            var codeEl = document.querySelector('[data-fm-sum-code]');
            if (codeEl) codeEl.textContent = '';
            swapCouponBlock(false);
            repaint();
            toast(res.data.message || res.data.success || 'Coupon removed');
        }).catch(function () {
            if (button) button.disabled = false;
            toast((CFG.messages && CFG.messages.error) || 'Something went wrong', 'error');
        });
    }

    root.addEventListener('click', function (event) {
        var lineAction = event.target.closest('[data-fm-line-action]');
        if (lineAction) {
            event.preventDefault();
            handleLineAction(lineAction);
            return;
        }

        if (event.target.closest('[data-fm-coupon-apply]')) {
            event.preventDefault();
            applyCoupon();
            return;
        }

        if (event.target.closest('[data-fm-coupon-remove]')) {
            event.preventDefault();
            removeCoupon();
        }
    });

    root.addEventListener('keydown', function (event) {
        if (event.key !== 'Enter') return;
        if (event.target.matches('[data-fm-coupon-input]')) {
            event.preventDefault();
            applyCoupon();
        }
    });

    /* ---------------------------------------------------------------- *
     * Drawer sync
     *
     * The off canvas can change the basket while this page is open, and
     * its own handlers only repaint the drawer. If the header count
     * changed while it was open, the rows here are stale, so the page is
     * reloaded to a server rendered truth rather than left lying.
     * ---------------------------------------------------------------- */
    var drawer = document.getElementById('cartDrawer');
    var countBefore = null;

    if (drawer) {
        drawer.addEventListener('show.bs.offcanvas', function () {
            var el = document.querySelector('[data-fm-cart-count]');
            countBefore = el ? el.textContent : null;
        });

        drawer.addEventListener('hidden.bs.offcanvas', function () {
            var snapshot = countBefore;
            countBefore = null;
            if (snapshot === null) return;

            var el = document.querySelector('[data-fm-cart-count]');
            if (el && el.textContent !== snapshot) window.location.reload();
        });
    }
})();

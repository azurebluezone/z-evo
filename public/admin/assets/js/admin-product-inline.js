/* ==========================================================================
   admin-product-inline.js - edit price and stock from the product grid.

   The grid rows are server-rendered HTML that DataTables re-injects on every
   draw, so handlers bound to a row die with the row. Everything here is
   therefore delegated from the document, which survives a redraw.

   The row partial (product/_row.blade.php) prints, per editable cell, a
   <span> for reading and an <input> for writing. Clicking the span swaps one
   for the other; Enter or blur saves; Escape cancels. The save is a POST to
   ProductController::quick_update(), which re-renders nothing - it returns the
   stored numbers and this file paints them, so what the screen shows is what
   the database answered.
   ========================================================================== */
(function () {
    'use strict';

    if (window.__fmProductInline) return;
    window.__fmProductInline = true;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function notify(message, kind) {
        if (typeof window.toastSuccess === 'function' && kind === 'success') {
            window.toastSuccess(message);
        } else if (typeof window.toastError === 'function') {
            window.toastError(message);
        }
    }

    /* Stock under 6 is what the row's red badge means; keep that rule here so
       the colour after a save is the same colour as before it. */
    function paintStock(span, stock) {
        span.textContent = String(stock);
        span.classList.toggle('bg-label-danger', Number(stock) <= 5);
        span.classList.toggle('bg-label-success', Number(stock) > 5);
    }

    function swap(textEl, inputEl, editing) {
        textEl.classList.toggle('d-none', editing);
        inputEl.classList.toggle('d-none', !editing);
    }

    function beginEdit(event) {
        var text = event.target.closest('[data-inline-price], [data-inline-stock]');
        if (!text) return;

        var isPrice = text.hasAttribute('data-inline-price');
        var input = isPrice
            ? document.querySelector('[data-inline-price-input][data-product-id="' + text.dataset.productId + '"]')
            : document.querySelector('[data-inline-stock-input][data-product-id="' + text.dataset.productId + '"]');

        if (!input) return;

        // The input is a sibling of the span in the same cell, not of the whole
        // row, so scope the lookup to the cell rather than the document.
        var cell = text.closest('td');
        input = cell ? cell.querySelector('.js-inline-input') : input;

        event.preventDefault();
        event.stopPropagation();

        swap(text, input, true);
        input.focus();
        input.select();
    }

    function cancel(input) {
        var cell = input.closest('td');
        var text = cell ? cell.querySelector('.js-inline-text') : null;
        if (text) swap(text, input, false);
    }

    function save(input) {
        var cell = input.closest('td');
        var text = cell ? cell.querySelector('.js-inline-text') : null;
        if (!text) return;

        var isPrice = input.hasAttribute('data-inline-price-input');
        var id = input.getAttribute('data-product-id');
        var raw = input.value;

        if (raw === '' || isNaN(Number(raw)) || Number(raw) < 0) {
            notify('Enter a number of 0 or more', 'error');
            swap(text, input, false);
            return;
        }

        var payload = new URLSearchParams();
        payload.append('id', id);
        payload.append(isPrice ? 'price' : 'stock', raw);

        input.disabled = true;

        fetch(input.dataset.inlineUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: payload
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                input.disabled = false;
                if (!res.ok || !res.body || res.body.status !== 'success') {
                    notify((res.body && res.body.message) || 'Could not save', 'error');
                    swap(text, input, false);
                    return;
                }
                if (isPrice) {
                    text.textContent = '৳' + res.body.price;
                } else {
                    paintStock(text, res.body.stock);
                }
                swap(text, input, false);
                notify('Saved', 'success');
            })
            .catch(function () {
                input.disabled = false;
                notify('Could not save', 'error');
                swap(text, input, false);
            });
    }

    document.addEventListener('click', beginEdit);

    document.addEventListener('keydown', function (e) {
        var input = e.target.closest ? e.target.closest('.js-inline-input') : null;
        if (!input) return;
        if (e.key === 'Enter') { e.preventDefault(); save(input); }
        if (e.key === 'Escape') cancel(input);
    });

    document.addEventListener('focusout', function (e) {
        var input = e.target.closest ? e.target.closest('.js-inline-input') : null;
        if (!input || input.disabled) return;
        save(input);
    });
})();
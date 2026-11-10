/* Order board (jKanban drag & drop with server-confirmed moves).
   Moved from backEnd/order/board.blade.php. The server values (move URL,
   process URL template, boards payload) ride in on [data-order-board].
   NOTE: line 67's processUrlTemplate trick: the 'x' placeholder inside the
   route must be exact; blade generates it with invoice_id = x. */
/*
 * The order board: one column per status, one card per order, drag to move.
 *
 * The bundled jKanban builds its own DOM from a `boards` array - it reads
 * data-id on each board, data-eid on each card and .kanban-drag on each
 * column, then attaches dragula - so the page ships the data as JSON and lets
 * the library render the markup. (Two of its contracts are easy to get wrong:
 * `element` must be a SELECTOR string, and cards must carry `id`, not a
 * property named `eid`, or the library drops them.)
 *
 * A drag moves the card immediately, which is optimistic and can be wrong: the
 * server may refuse the move. So every drop posts to order/board/move. If the
 * server names a column other than where the card now sits, the card is put
 * back - a card is never left somewhere the database disagrees with.
 */
(function () {
    'use strict';

    var cfgEl = document.querySelector('[data-order-board]');
    var CFG = cfgEl ? (function () {
        try { return JSON.parse(cfgEl.getAttribute('data-order-board')); } catch (e) { return null; }
    })() : null;
    if (!CFG) return;

    var root = document.getElementById('kanbanBoard');
    if (!root || typeof jKanban === 'undefined') return;

    var moveUrl = CFG.moveUrl;
    var tokenEl = document.querySelector('meta[name="csrf-token"]');
    var csrf = tokenEl ? tokenEl.getAttribute('content') : '';

    function flash(kind, message) {
        if (kind === 'success' && typeof window.toastSuccess === 'function') window.toastSuccess(message);
        else if (typeof window.toastError === 'function') window.toastError(message);
    }

    function columnEl(statusId) {
        return root.querySelector('[data-id="' + statusId + '"] .kanban-drag');
    }

    function cardEl(orderId) {
        return root.querySelector('.kanban-item[data-eid="' + orderId + '"]');
    }

    var kanban = new jKanban({
        element: '#kanbanBoard',
        boards: CFG.boards,
        gutter: '15px',
        dragItems: true,
        dragBoards: false,
        // Where a card landed: (draggedItem, board, item, sibling).
        dropEl: function (item, board) {
            if (!board) return;

            var target = board.classList && board.classList.contains('kanban-board') ? board : null;
            if (!target) {
                // the second argument is the board wrapper in some paths
                target = (board.closest && board.closest('.kanban-board')) || board;
            }
            var toStatus = target.getAttribute('data-id');
            var fromStatus = item.getAttribute('data-column-from');

            item.setAttribute('data-column-from', toStatus);
            if (String(fromStatus) === String(toStatus)) return;

            persist(item, toStatus);
        },
        click: function (item) {
            // admin.order.process is keyed by invoice_id, which the card carries.
            var invoice = item.getAttribute('data-invoice');
            if (invoice) {
                window.location = CFG.processUrl.replace(/x$/, encodeURIComponent(invoice));
            }
        }
    });

    /* jKanban renders from data-* only; re-attach what the save needs. */
    Array.prototype.forEach.call(root.querySelectorAll('.kanban-item'), function (item) {
        var meta = item.getAttribute('data-meta');
        if (!meta) return;
        var parsed = JSON.parse(meta);
        item.setAttribute('data-column-from', parsed.status);
        item.setAttribute('data-invoice', parsed.invoice);
    });

    /*
     * The columns are wider than the card, so the scroller jKanban created
     * inside #kanbanBoard owns the overflow. A plain div is not reachable by
     * keyboard and a hidden overlay scrollbar makes the off-screen columns
     * undiscoverable, so give the scroller a tab stop, a name and a role.
     */
    var scroller = root.querySelector('.kanban-container');
    if (scroller) {
        scroller.setAttribute('tabindex', '0');
        scroller.setAttribute('role', 'region');
        scroller.setAttribute('aria-label',
            'Order board, ' + scroller.querySelectorAll('.kanban-board').length +
            ' status columns, scrolls horizontally');
    }

    /* Remember the column a card is dragged FROM, so a drop back is not a change. */
    root.addEventListener('mousedown', function (e) {
        var card = e.target.closest ? e.target.closest('.kanban-item') : null;
        if (!card) return;
        var board = card.closest('[data-id]');
        if (board) card.setAttribute('data-column-from', board.getAttribute('data-id'));
    });

    function persist(item, toStatus) {
        var orderId = item.getAttribute('data-eid');
        var body = new URLSearchParams();
        body.append('order_id', orderId);
        body.append('status_id', toStatus);

        item.style.opacity = '.5';

        fetch(moveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: body
        })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, body: j }; }); })
            .then(function (res) {
                item.style.opacity = '';
                if (res.ok && res.body && res.body.status === 'success') {
                    flash('success', res.body.message || 'Moved');
                } else {
                    var target = res.body && res.body.column ? columnEl(res.body.column) : null;
                    if (target) target.appendChild(item);          // the server's column wins
                    flash('error', 'The move did not save; the card is back where it started.');
                }
            })
            .catch(function () {
                item.style.opacity = '';
                flash('error', 'The move could not be saved.');
            });
    }
})();

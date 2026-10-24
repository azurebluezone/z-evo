@extends('backEnd.layouts.app')
@section('title', 'Order Board')

@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
                <h4 class="page-title mb-0">Order Board</h4>
                <div class="page-title-right d-flex gap-2">
                    <a href="{{ route('admin.orders', ['slug' => 'all']) }}" class="btn btn-outline-secondary btn-square waves-effect">
                        <i class="ri ri-list-check me-1"></i> List view
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    {{--
        The library builds the boards itself from the JSON below, so the markup
        here is an empty host. A card's column is its order_status in the
        database: after a drag the save endpoint answers with the column the
        order is really in, and the browser moves the card back if that is not
        where it was dropped. The DOM is never treated as the truth.
    --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="kanbanBoard" class="kanban-scroll"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/*
 * Scoped to the board so nothing here can reach another admin screen.
 *
 * jKanban builds its own `.kanban-container` INSIDE #kanbanBoard and gives it
 * an inline width of (boards x 250px + gutters). The host itself is
 * `.kanban-scroll`, so the old `#kanbanBoard.kanban-container` selector never
 * matched anything: the columns stacked vertically and the 1680px inner
 * container escaped the 1044px card, pushing the whole page sideways
 * (scrollWidth 2008 at 1440). The scroll owner must be the element that is
 * actually too wide - the child container - not the page.
 */
#kanbanBoard { max-width: 100%; }
#kanbanBoard > .kanban-container {
  display: flex;
  gap: .75rem;
  align-items: flex-start;
  max-width: 100%;
  overflow-x: auto;
  padding-bottom: .5rem;
}
/* Keep the horizontal scrollbar visible: without it the columns past the
   fourth are undiscoverable until the user happens to scroll. */
#kanbanBoard > .kanban-container::-webkit-scrollbar { height: 8px; }
#kanbanBoard > .kanban-container::-webkit-scrollbar-track { background: transparent; }
#kanbanBoard > .kanban-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
#kanbanBoard > .kanban-container:focus-visible { outline: 2px solid var(--bs-primary, #666cff); outline-offset: 2px; }
/* The library writes margin-left/right:15px inline on every board; the gap
   above already owns the spacing, so drop the inline margins (inline styles
   need !important to lose against a stylesheet). */
#kanbanBoard > .kanban-container > .kanban-board { margin-left: 0 !important; margin-right: 0 !important; }
#kanbanBoard .kanban-board { flex: 0 0 auto; background: #f8f9fa; border: 1px solid var(--bs-border-color, #e9ecef); border-radius: .5rem; min-width: 15rem; }
#kanbanBoard .kanban-board-header { background: #fff; border-bottom: 1px solid var(--bs-border-color, #e9ecef); border-radius: .5rem .5rem 0 0; padding: .6rem .8rem; }
#kanbanBoard .kanban-title-board { font-weight: 600; }
#kanbanBoard .kanban-drag { min-height: 10rem; padding: .5rem; display: flex; flex-direction: column; gap: .5rem; }
#kanbanBoard .kanban-item { background: #fff; border: 1px solid var(--bs-border-color, #e9ecef); border-radius: .375rem; padding: .6rem; box-shadow: 0 1px 1px rgba(0,0,0,.04); cursor: grab; }
#kanbanBoard .kanban-item:active { cursor: grabbing; }
.board-invoice { font-size: .85rem; }
.board-customer { font-size: .8rem; color: #6c757d; }
</style>
@endsection

@push('script')
<script src="{{ asset('admin/assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/jkanban/jkanban.js') }}"></script>
<script>
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

    var root = document.getElementById('kanbanBoard');
    if (!root || typeof jKanban === 'undefined') return;

    var moveUrl = @json(route('admin.order.board.move'));
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
        boards: @json($boardData),
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
                window.location = @json(route('admin.order.process', ['invoice_id' => 'x'])).replace(/x$/, encodeURIComponent(invoice));
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
</script>
@endpush
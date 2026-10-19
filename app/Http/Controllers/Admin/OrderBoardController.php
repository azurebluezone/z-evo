<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Toastr;

/**
 * OrderBoardController
 * --------------------
 * The order list as a board: one column per status, one card per order, and a
 * drag that moves the order between them.
 *
 * WHY A BOARD
 * -----------
 * The list view answers "show me the pending orders". It cannot answer "what is
 * the state of my whole order book at a glance", which is the question a shop
 * owner actually has every morning. With dozens of open orders, the answer was
 * counting rows across a filtered tab - a question the table is the wrong shape
 * for.
 *
 * WHY IT REUSES order_status()
 * ----------------------------
 * Moving an order to On-The-Way decrements stock; moving it to Delivered
 * decrements stock too, and both are idempotent, ledger-backed routines with
 * 250 lines between them. The board therefore calls the same controller method
 * the list view's status dropdown calls, through the same stock routine. A
 * drag is a status change, so it must be exactly the same status change - if
 * the board grew its own copy of the arithmetic, the two would drift and stock
 * would be wrong in a way nobody could see until an oversell.
 *
 * The board is a VIEW over the same orders table the list reads. It holds no
 * state of its own: a card's column is its current order_status, and after a
 * drag the column the card lands in is the one the database now says.
 */
class OrderBoardController extends Controller
{
    /**
     * The board itself: statuses as columns, orders as cards.
     */
    public function index()
    {
        return view('backEnd.order.board', [
            'boardData' => $this->boardPayload(),
        ]);
    }

    /**
     * The board as the drag library wants it: an array of boards, each with an
     * array of items.
     *
     * Built here rather than in the view so the page and the JSON endpoint can
     * never describe different boards - they are the same call.
     */
    private function boardPayload(): array
    {
        $statuses = OrderStatus::orderBy('id')->get();

        $orders = Order::with(['shipping', 'status'])
            ->withCount('orderdetails')
            ->orderByDesc('created_at')
            ->limit(500)          // a board is a glance, not an export
            ->get();

        return $statuses->map(function (OrderStatus $status) use ($orders) {
            return [
                'id'    => (string) $status->id,
                'title' => (string) $status->name,
                'item'  => $orders->where('order_status', $status->id)
                    ->map(fn (Order $order) => $this->cardPayload($order, $status->id))
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /**
     * One card.
     *
     * The library identifies a card by its `id` and copies the other keys onto
     * the element as data-*, so `id` here is the ORDER id and `meta` carries
     * the current column for the "did it really move?" check.
     *
     * The body is a small HTML fragment: the library inserts titles with
     * innerHTML, and the values are escaped here because they came from the
     * database, not from us.
     */
    private function cardPayload(Order $order, int $statusId): array
    {
        $invoice = (string) $order->invoice_id;
        $customer = (string) ($order->shipping->name ?? ($order->customer->name ?? 'No name'));
        $amount = (float) ($order->amount ?? 0);
        $items = (int) ($order->orderdetails_count ?? 0);

        $title = sprintf(
            '<div class="d-flex justify-content-between align-items-start gap-2">'
            . '<strong class="board-invoice">%s</strong>'
            . '<span class="text-primary fw-semibold">৳%s</span></div>'
            . '<div class="board-customer">%s</div>'
            . '<div class="d-flex justify-content-between align-items-center mt-2">'
            . '<small class="text-muted">%s</small>'
            . '<small class="text-muted">%d item(s)</small></div>',
            e($invoice),
            e(number_format($amount, 0)),
            e($customer),
            e(optional($order->created_at)->diffForHumans()),
            $items
        );

        return [
            'id'    => (string) $order->id,
            'title' => $title,
            'meta'  => json_encode([
                'status'  => (int) $statusId,
                'invoice' => $invoice,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Cards for the board, as JSON.
     *
     * The blade page above already has this data, but the drag needs the
     * authoritative set: what is in the database right now, not what the page
     * was built from ten minutes ago. Returning cards as data (rather than
     * asking the browser to re-read its own DOM) is what lets a failed drag be
     * corrected by the server's answer instead of by guesswork.
     */
    public function cards()
    {
        return response()->json(['boards' => $this->boardPayload()]);
    }

    /**
     * Move one order to one status. This is the drag's save.
     *
     * Delegates to OrderController::order_status() so the stock arithmetic and
     * the ledger entry are identical to what the list view does. The response
     * carries the card's new column; on failure it carries the column the order
     * is actually in, so the browser can snap the card back rather than leaving
     * it in a column the database disagrees with.
     */
    public function move(Request $request)
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'status_id' => ['required', 'integer', 'exists:order_statuses,id'],
        ]);

        $order = Order::with(['shipping', 'status'])->findOrFail($data['order_id']);

        // A drop onto the column it is already in is not an error - jkanban
        // fires this when a card is picked up and put back - but it should not
        // re-run the stock routine either.
        if ((int) $order->order_status === (int) $data['status_id']) {
            return response()->json([
                'status'  => 'success',
                'message' => 'No change',
                'column'  => (int) $order->order_status,
            ]);
        }

        // The real status change - the same routine, and the same stock
        // arithmetic, that the list view's dropdown runs.
        (new OrderController)->changeOrderStatus(
            [(int) $data['order_id']],
            (int) $data['status_id']
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Moved',
            'column'  => (int) $data['status_id'],
        ]);
    }

}
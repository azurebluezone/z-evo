{{--
    FM:SECTION page-customer-orders -------------------------------------------
    Classic source : resources/views/frontend/layouts/customer/orders.blade.php
    Controller     : Frontend\CustomerController@orders()
    Variables      : $orders = Order::where('customer_id', …)->with('status')
                                  ->latest()->get()
    Sections       : fm-acct-body, fm-acct-actions
    Consumes       : $orders, Auth::guard('customer') (indirectly, via the rail)

    NOT PAGINATED
    The controller uses ->get(), not ->paginate(), so this page renders every
    order the customer has ever placed in one go. That is a real scalability
    question but it is a controller decision, not a template one: adding
    ->paginate() here without changing the controller is impossible, and
    changing it silently would alter what the page is allowed to show. Left
    as-is and flagged in the handover notes.

    TWO CLASSIC BUGS FIXED HERE
    1. Unclosed table cell. The classic row opened <td> for the note column and
       closed it INSIDE an @if, so when admin_note was empty the cell was never
       closed and the browser re-parented the rest of the table. Rendering
       cards instead of a table removes the failure mode entirely.
    2. Wrong page title. The classic file said @section('title','Customer Account')
       on the orders page. The heading below is the correct one.
-------------------------------------------------------------------------- --}}

@extends('frontend.fm.layouts.account', [
    'fmAcctTitle'   => 'My Orders',
    'fmAcctSub'     => 'Every order you have placed, newest first.',
    'fmAcctEyebrow' => 'Orders',
])

@section('fm-acct-body')
    @php
        // Order status -> pill modifier. The classic table printed the status as
        // plain text; a pill makes a list of twenty orders scannable. The mapping
        // is by keyword because OrderStatus rows are admin-authored free text
        // ("Pending", "Processing", "Shipped", "Delivered", "Cancelled", …) and
        // there is no stable enum to switch on.
        $ordersPill = static function (?string $name): array {
            $key = strtolower(trim((string) $name));

            return match (true) {
                str_contains($key, 'deliver') => ['green',  'Delivered'],
                str_contains($key, 'ship')    => ['blue',   $name],
                str_contains($key, 'process') => ['amber',  $name],
                str_contains($key, 'cancel')  => ['red',    $name],
                str_contains($key, 'pend')    => ['amber',  $name],
                default                      => ['grey',   $name !== '' ? $name : 'Unknown'],
            };
        };
    @endphp

    @forelse($orders as $order)
        @php
            [$pillTone, $pillLabel] = $ordersPill($order->status?->name);
            $orderDiscount = (float) ($order->discount ?? 0);
            $orderShipping = (float) ($order->shipping_charge ?? 0);
        @endphp

        <article class="fm-acct__card mb-3">
            <div class="fm-acct__card-head">
                <div>
                    <h2 class="fm-acct__card-title">
                        {{ $order->invoice_id ?? ('Order #' . $order->id) }}
                    </h2>
                    <p class="fm-acct__card-meta">
                        Placed {{ optional($order->created_at)->format('d M Y, h:i A') }}
                    </p>
                </div>

                <span class="fm-acct__pill fm-acct__pill--{{ $pillTone }}">{{ $pillLabel }}</span>
            </div>

            <dl class="fm-acct__lines">
                <div class="fm-acct__line">
                    <dt class="fm-acct__line-label">Items</dt>
                    <dd class="fm-acct__line-value">৳ {{ number_format((float) $order->amount, 0) }}</dd>
                </div>

                {{-- Discount and shipping are only worth a row when they are
                     non-zero; an unconditional "Discount: 0" row on every order
                     is noise, and the classic page printed both unconditionally. --}}
                @if($orderDiscount > 0)
                    <div class="fm-acct__line">
                        <dt class="fm-acct__line-label">Discount</dt>
                        <dd class="fm-acct__line-value">− ৳ {{ number_format($orderDiscount, 0) }}</dd>
                    </div>
                @endif

                @if($orderShipping > 0)
                    <div class="fm-acct__line">
                        <dt class="fm-acct__line-label">Shipping</dt>
                        <dd class="fm-acct__line-value">৳ {{ number_format($orderShipping, 0) }}</dd>
                    </div>
                @endif
            </dl>

            <div class="fm-acct__card-actions">
                <a href="{{ route('customer.invoice', ['id' => $order->id]) }}"
                   class="btn btn-brand-outline btn-sm">
                    <i class="bi bi-receipt" aria-hidden="true"></i>
                    View invoice
                </a>

                {{-- The admin note link only exists when there is a note. The
                     classic guarded the </td> rather than the link, which is
                     how the unclosed-cell bug happened. --}}
                @if($order->admin_note)
                    <a href="{{ route('customer.order_note', ['id' => $order->id]) }}"
                       class="btn btn-brand-outline btn-sm">
                        <i class="bi bi-chat-left-text" aria-hidden="true"></i>
                        Note from us
                    </a>
                @endif
            </div>
        </article>
    @empty
        <div class="fm-acct__empty">
            <i class="bi bi-bag" aria-hidden="true"></i>
            <p>You have not placed any orders yet.</p>
            <a href="{{ route('products.page') }}" class="btn btn-brand-primary btn-sm px-3">
                Start shopping
            </a>
        </div>
    @endforelse
@endsection

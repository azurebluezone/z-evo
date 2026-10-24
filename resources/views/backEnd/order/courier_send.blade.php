{{--
    backEnd/order/courier_send - send one order to a courier.

    ---------------------------------------------------------------------------
    TWO STEPS, ON PURPOSE
    ---------------------------------------------------------------------------
    Creating a consignment is irreversible and billable: the courier charges for
    it and a parcel exists upstream whether or not we ever hear back. The
    previous flow fired the API call from inside a status-change POST, so the
    first time anyone saw the recipient, the address and the cash amount was
    also the moment the parcel was created.

    So this screen only ever SHOWS what will be sent. CourierOrderSender does the
    actual work and refuses anything incomplete; the admin confirms at the end.

    ---------------------------------------------------------------------------
    HOUSE STYLE
    ---------------------------------------------------------------------------
    Matches the rest of this admin panel (see backEnd/childcategory/edit.blade.php):
      card rounded-0, header bg-white border-bottom py-3, h5 text-uppercase fw-bold,
      btn-square waves-effect, x-admin.button for primary actions, `app-ecommerce`
      wrapper for the grid. No bespoke CSS - Materialize's own responsive
      utilities do the layout, so the screen cannot drift from the rest.
--}}
@extends('backEnd.layouts.app')

@section('title', 'Send to courier — ' . $order->invoice_id)

@section('content')
<div class="app-ecommerce">
  <div class="row">

    {{-- ==================== LEFT: the three steps ==================== --}}
    <div class="col-12 col-xl-8">

      {{-- Result of the last attempt. Placed at the very top: after pressing
           Send, the admin's first question is "did it go?", not "what does this
           page do". --}}
      @if(session('courier_success'))
        <div class="alert alert-success d-flex align-items-start gap-2 mb-4" role="alert">
          <i class="ri ri-checkbox-circle-fill"></i>
          <div>
            <h6 class="alert-heading mb-1">Sent to the courier.</h6>
            <p class="mb-0">{{ session('courier_success') }}</p>
          </div>
        </div>
      @endif

      @if(session('courier_error'))
        <div class="alert alert-danger d-flex align-items-start gap-2 mb-4" role="alert">
          <i class="ri ri-error-warning-fill"></i>
          <div>
            <h6 class="alert-heading mb-1">Not sent.</h6>
            <p class="mb-0">{{ session('courier_error') }}</p>
          </div>
        </div>
      @endif

      @if($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      {{-- ================= 1. CHOOSE THE COURIER ================= --}}
      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">1 · Choose the courier</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            @foreach($couriers as $courier)
              <div class="col-12 col-md-6">
                <a href="{{ route('admin.order.courier.show', ['provider' => $courier->type, 'order' => $order->id]) }}"
                   class="card rounded-0 h-100 text-body text-decoration-none
                          {{ $courier->type === $provider ? 'border-primary' : '' }}"
                   style="{{ $courier->type === $provider ? 'background-color: rgba(105,108,255,.06);' : '' }}">
                  <div class="card-body d-flex align-items-center gap-3 py-3">
                    <i class="ri ri-truck-line"></i>
                    <div class="flex-grow-1 min-w-0">
                      <h6 class="mb-1">{{ $courier->label() }}</h6>
                      @if($courier->isReady())
                        <span class="badge bg-label-success">Ready</span>
                      @elseif($courier->status)
                        <span class="badge bg-label-warning">On, credentials missing</span>
                      @else
                        <span class="badge bg-label-secondary">Switched off</span>
                      @endif
                    </div>
                    @if($courier->type === $provider)
                      <i class="ri ri-check-line"></i>
                    @endif
                  </div>
                </a>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- ================= 2. WHAT WILL BE SENT ================= --}}
      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">2 · Check what will be sent</h5>
          <small class="text-muted">Nothing is sent until step 3.</small>
        </div>
        <div class="card-body">

          @if($preview['ok'])
            <div class="table-responsive">
              <table class="table table-sm mb-0 align-middle">
                <tbody>
                  <tr>
                    <th scope="row" class="w-25 fw-medium">Invoice</th>
                    <td class="text-break">{{ $preview['payload']['invoice'] }}</td>
                  </tr>
                  <tr>
                    <th scope="row" class="fw-medium">Recipient</th>
                    <td>{{ $preview['payload']['recipient_name'] }}</td>
                  </tr>
                  <tr>
                    <th scope="row" class="fw-medium">Phone</th>
                    <td>{{ $preview['payload']['recipient_phone'] }}</td>
                  </tr>
                  <tr>
                    <th scope="row" class="fw-medium">Address</th>
                    <td class="text-break">{{ $preview['payload']['recipient_address'] }}</td>
                  </tr>
                  <tr>
                    <th scope="row" class="fw-medium">Cash to collect</th>
                    <td class="fw-semibold">৳ {{ number_format((float) $preview['payload']['cod_amount'], 0) }}</td>
                  </tr>
                  @isset($preview['payload']['item_description'])
                    <tr>
                      <th scope="row" class="fw-medium">Items</th>
                      <td>
                        {{ $preview['payload']['item_description'] }}
                        <span class="text-muted">
                          ({{ $preview['payload']['total_lot'] ?? 1 }} item{{ ($preview['payload']['total_lot'] ?? 1) === 1 ? '' : 's' }})
                        </span>
                      </td>
                    </tr>
                  @endisset
                  <tr>
                    <th scope="row" class="fw-medium">Endpoint</th>
                    <td class="font-monospace small text-break">{{ $preview['endpoint'] }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          @else
            {{-- The refusal reason IS this panel's content. It names what is
                 wrong and where to fix it, which is the entire reason for
                 previewing before sending. --}}
            <div class="alert alert-warning d-flex align-items-start gap-2 mb-0" role="alert">
              <i class="ri ri-information-fill"></i>
              <div>
                <p class="mb-2">{{ $preview['message'] }}</p>
                <a href="{{ route('admin.order.edit', ['invoice_id' => $order->invoice_id]) }}"
                   class="btn btn-sm btn-outline-primary btn-square waves-effect">
                  <i class="ri ri-edit-line me-1"></i> Open the order to fix it
                </a>
              </div>
            </div>
          @endif
        </div>
      </div>

      {{-- ================= 3. CONFIRM ================= --}}
      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">3 · Send it</h5>
        </div>
        <div class="card-body">

          @if($shipment && $shipment->consignment_id)
            {{-- Already booked. Sending again would create a second parcel and
                 a second charge, so the form is replaced by the existing
                 consignment rather than merely disabled. --}}
            <div class="alert alert-info mb-0">
              <h6 class="alert-heading mb-2">
                <i class="ri ri-information-fill me-1"></i>This order already has a consignment.
              </h6>
              <div class="table-responsive">
                <table class="table table-sm mb-0">
                  <tbody>
                    <tr><th scope="row" class="fw-medium">Consignment</th><td>{{ $shipment->consignment_id }}</td></tr>
                    <tr><th scope="row" class="fw-medium">Tracking code</th><td>{{ $shipment->tracking_code ?: '—' }}</td></tr>
                    <tr><th scope="row" class="fw-medium">Status</th><td>{{ $shipment->current_status ?: '—' }}</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

          @elseif($preview['ok'])
            <form method="POST"
                  action="{{ route('admin.order.courier.store', ['provider' => $provider, 'order' => $order->id]) }}"
                  id="fmCourierForm">
              @csrf

              <div class="mb-3">
                <label class="form-label" for="courierNote">Note for the rider (optional)</label>
                <input type="text"
                       id="courierNote"
                       name="note"
                       class="form-control"
                       maxlength="255"
                       value="{{ old('note') }}"
                       placeholder="e.g. Call before delivery">
                <div class="form-text">Sent to the courier as their <code>note</code> field.</div>
              </div>

              {{-- First confirmation. A consignment is billable and cannot be
                   undone, so the admin states they have read the details. --}}
              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" id="courierConfirm" required>
                <label class="form-check-label" for="courierConfirm">
                  I have checked the name, phone, address and cash amount above.
                </label>
                <div class="invalid-feedback">Please confirm before sending.</div>
              </div>

              <div class="d-flex flex-column flex-sm-row gap-2">
                <x-admin.button variant="primary" shape="square" ripple="true"
                                type="submit" class="flex-grow-1" data-confirm-send>
                  <i class="ri ri-truck-line me-1"></i>
                  Send to {{ $couriers->firstWhere('type', $provider)->label() ?? 'courier' }}
                </x-admin.button>

                <a href="{{ route('admin.order.edit', ['invoice_id' => $order->invoice_id]) }}"
                   class="btn btn-outline-secondary btn-square waves-effect">
                  Cancel
                </a>
              </div>
            </form>

          @else
            {{-- Nothing can be sent; panel 2 above already explains why. --}}
            <a href="{{ route('admin.order.edit', ['invoice_id' => $order->invoice_id]) }}"
               class="btn btn-outline-secondary btn-square waves-effect">
              <i class="ri ri-arrow-left-line me-1"></i> Back to the order
            </a>
          @endif
        </div>
      </div>
    </div>

    {{-- ==================== RIGHT: order context ==================== --}}
    <div class="col-12 col-xl-4">
      @php $cfg = $couriers->firstWhere('type', $provider); @endphp

      <div class="card rounded-0 mb-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
          <h5 class="mb-0 text-uppercase fw-bold">Order</h5>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-sm mb-0">
              <tbody>
                <tr><th scope="row" class="fw-medium">Invoice</th><td class="text-break">{{ $order->invoice_id }}</td></tr>
                <tr><th scope="row" class="fw-medium">Placed</th><td>{{ optional($order->created_at)->format('d M Y, h:i A') }}</td></tr>
                <tr><th scope="row" class="fw-medium">Amount</th><td class="fw-semibold">৳ {{ number_format((float) $order->amount, 0) }}</td></tr>
                <tr><th scope="row" class="fw-medium">Status</th><td>{{ $order->status?->name ?? 'Unknown' }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      {{--
          Webhook token.

          An admin has to paste this into the courier's dashboard to register our
          callback URL, and there is nowhere else to get it from - the column is
          $hidden on the model precisely so it does not leak into debug output.

          Shown only because this is the courier settings screen; it is never
          sent to any JSON response.
      --}}
      @if($cfg && $cfg->webhook_token)
        <div class="card rounded-0 mb-4">
          <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom py-3">
            <h5 class="mb-0 text-uppercase fw-bold">Webhook token</h5>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-2">
              Paste this into {{ $cfg->label() }}'s dashboard as the callback secret.
            </p>

            <div class="input-group mb-3">
              <input type="text"
                     id="fmWebhookToken"
                     class="form-control font-monospace small"
                     readonly
                     value="{{ $cfg->webhook_token }}">
              <button class="btn btn-outline-secondary btn-square waves-effect"
                      type="button"
                      data-copy-token="#fmWebhookToken"
                      aria-label="Copy webhook token">
                <i class="ri ri-file-copy-line"></i>
              </button>
            </div>

            <p class="text-muted small mb-0">
              Our callback URL:
              <code class="text-break">{{ route('api.steadfast.webhook') }}</code>
            </p>
          </div>
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        'use strict';

        /*
         * Copy the webhook token.

         * navigator.clipboard needs a secure context, so a plain-http staging
         * host falls back to select + execCommand. Selection alone leaves the
         * admin able to copy by hand if even that fails.
         */
        document.querySelectorAll('[data-copy-token]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.querySelector(button.getAttribute('data-copy-token'));
                if (!input) return;

                input.focus();
                input.select();
                input.setSelectionRange(0, 99999);

                function done() {
                    var original = button.innerHTML;
                    button.innerHTML = '<i class="ri ri-check-line"></i>';
                    setTimeout(function () { button.innerHTML = original; }, 1500);
                }

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(input.value).then(done).catch(fallback);
                } else {
                    fallback();
                }

                function fallback() {
                    try {
                        document.execCommand('copy');
                        done();
                    } catch (e) {
                        /* the text is selected; the admin can copy manually */
                    }
                }
            });
        });

        /*
         * Second confirmation before an irreversible, billable action.
         *
         * The checkbox is the deliberate "I have read it" gate; this is the
         * "are you sure" on top, so a mis-click that happens to land on Send
         * still cannot create the consignment.
         */
        var form = document.getElementById('fmCourierForm');
        if (!form) return;

        var box = document.getElementById('courierConfirm');
        var sendBtn = form.querySelector('[data-confirm-send]');

        if (box && sendBtn) {
            // Disabled until the box is ticked. An HTML-disabled button is
            // actually un-clickable, so the click handler below is a second
            // layer rather than the only one.
            sendBtn.disabled = true;

            box.addEventListener('change', function () {
                sendBtn.disabled = !box.checked;
                box.classList.remove('is-invalid');
            });
        }
    })();
</script>
@endpush
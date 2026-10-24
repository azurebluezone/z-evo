@extends('backEnd.layouts.app')
@section('title', 'Shipment details')
@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-0">Shipment #{{ $shipment->id }}</h4>
                <p class="text-muted mb-0 small">
                    {{ $shipment->provider }} · {{ $shipment->current_status ?: 'awaiting update' }}
                </p>
            </div>
            <a href="{{ route('admin.courier.shipments') }}" class="btn btn-secondary btn-sm">
                <i class="ri ri-arrow-left-line me-1"></i> All shipments
            </a>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri ri-file-list-3-line me-2"></i>Courier reference</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Courier</label>
                        <div><span class="badge bg-label-info">{{ $shipment->provider }}</span></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Current status</label>
                        <div>
                            @if($shipment->current_status)
                                <span class="badge bg-label-primary">{{ $shipment->current_status }}</span>
                            @else
                                <span class="badge bg-label-secondary">awaiting update</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Consignment ID</label>
                        <div><code>{{ $shipment->consignment_id ?: '—' }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Tracking code</label>
                        <div><code>{{ $shipment->tracking_code ?: '—' }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Courier's own order reference</label>
                        <div><code>{{ $shipment->merchant_order_id ?: '—' }}</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-0">Last update from the webhook</label>
                        <div>
                            {{ $shipment->last_synced_at ? $shipment->last_synced_at->format('d M Y, H:i') : 'never' }}
                            @if($shipment->last_synced_at)
                                <span class="text-muted small">({{ $shipment->last_synced_at->diffForHumans() }})</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="ri ri-code-s-slash-line me-2"></i>Raw courier payload</h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="copy-payload">Copy JSON</button>
            </div>
            <div class="card-body">
                {{-- The raw body is kept so a disagreement with the courier can be
                     settled from evidence rather than a phone call. --}}
                <pre class="mb-0 small" style="max-height: 28rem; overflow:auto;" id="payload">{{ json_encode($shipment->raw_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'No payload stored.' }}</pre>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri ri-shopping-bag-3-line me-2"></i>Order</h5>
            </div>
            <div class="card-body">
                @if($shipment->order)
                    <dl class="mb-0">
                        <dt class="text-muted small">Invoice</dt>
                        <dd>{{ $shipment->order->invoice_id }}</dd>

                        <dt class="text-muted small">Amount</dt>
                        <dd>{{ number_format((float) $shipment->order->amount, 2) }}</dd>

                        <dt class="text-muted small">Order status</dt>
                        <dd>{{ $shipment->order->order_status }}</dd>
                    </dl>

                    {{-- This route is the preview-then-confirm send screen, which
                         exists for this order and provider. --}}
                    <a href="{{ route('admin.order.courier.show', [$shipment->order_id, $shipment->provider]) }}"
                       class="btn btn-outline-primary btn-sm w-100">
                        <i class="ri ri-send-plane-line me-1"></i> Open the send screen
                    </a>
                @else
                    <p class="text-danger small mb-0">
                        The order this shipment points at (id {{ $shipment->order_id }}) no longer exists.
                        The shipment row is kept so the courier reference is not lost.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Discarded by Blade after @endsection - kept inside @section('script'). --}}
@section('script')
<script>
    (function () {
        'use strict';

        var button = document.getElementById('copy-payload');
        var payload = document.getElementById('payload');
        if (!button || !payload) { return; }

        button.addEventListener('click', function () {
            var text = payload.textContent || '';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
            }
            var label = button.innerHTML;
            button.innerHTML = 'Copied';
            setTimeout(function () { button.innerHTML = label; }, 1500);
        });
    })();
</script>
@endsection

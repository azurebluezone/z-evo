{{--
    backEnd/order/courier_choose - pick the courier for ONE order.

    The order row's truck icon lands here. Both couriers are always listed so
    the admin can see what exists, whether it is ready, and whether this order
    already has a consignment with it. Selecting a courier opens the preview
    screen; nothing is sent from this page.
--}}
@extends('backEnd.layouts.app')

@section('title', 'Send to courier — ' . $order->invoice_id)

@section('content')
<div class="app-ecommerce">
    <div class="row g-3">

        {{-- ==================== order context ==================== --}}
        <div class="col-12">
            <div class="card rounded-0">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center bg-white border-bottom py-3 gap-2">
                    <div>
                        <h5 class="mb-0 text-uppercase fw-bold">
                            <i class="ri ri-truck-line me-2"></i>Send order #{{ $order->invoice_id }}
                        </h5>
                        <small class="text-muted">Choose one courier. The next screen shows exactly what will be sent before you confirm.</small>
                    </div>
                    <x-admin.button variant="outline-secondary" shape="square" ripple="true"
                                    href="{{ route('admin.order.edit', ['invoice_id' => $order->invoice_id]) }}">
                        <i class="ri ri-arrow-left-line me-1"></i>Back to order
                    </x-admin.button>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="text-muted small text-uppercase">Customer</div>
                            <div class="fw-medium text-break">{{ $order->shipping->name ?? '—' }}</div>
                            <div class="small text-muted">{{ $order->shipping->phone ?? '' }}</div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="text-muted small text-uppercase">Address</div>
                            <div class="small text-break">{{ $order->shipping->address ?? '—' }}</div>
                        </div>
                        <div class="col-6 col-sm-6 col-lg-2">
                            <div class="text-muted small text-uppercase">Amount</div>
                            <div class="fw-semibold">৳ {{ number_format((float) $order->amount, 0) }}</div>
                        </div>
                        <div class="col-6 col-sm-6 col-lg-2">
                            <div class="text-muted small text-uppercase">Status</div>
                            <div>{{ $order->status?->name ?? 'Unknown' }}</div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <div class="text-muted small text-uppercase">Placed</div>
                            <div class="small">{{ optional($order->created_at)->format('d M Y, h:i A') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ==================== courier choices ==================== --}}
        @foreach($couriers as $courier)
            @php
                $booked  = $shipments->get($courier->type);
                $support = $support[$courier->type] ?? ['supported' => true, 'reason' => null];
                $canSend = ($support['supported'] ?? false) && $courier->isReady() && ! $booked;
            @endphp

            <div class="col-12 col-md-6">
                <div class="card h-100 rounded-0">
                    <div class="card-body d-flex flex-column gap-3">

                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar avatar-sm">
                                    <span class="avatar-initial rounded bg-label-primary">
                                        <i class="icon-base ri ri-truck-line"></i>
                                    </span>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ $courier->label() }}</h6>
                                    <small class="text-muted">One parcel, one consignment</small>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                @if($courier->isReady())
                                    <span class="badge bg-label-success">Ready</span>
                                @elseif($courier->status)
                                    <span class="badge bg-label-warning">Needs setup</span>
                                @else
                                    <span class="badge bg-label-secondary">Disabled</span>
                                @endif
                                @if(! $courier->isConfigured())
                                    <span class="badge bg-label-danger">Credentials missing</span>
                                @endif
                            </div>
                        </div>

                        <p class="small text-muted mb-0">
                            @if($courier->type === 'pathao')
                                Books from your saved Pathao pickup store. The address and cash amount are checked on the next screen.
                            @else
                                Sends the recipient, address and cash amount to SteadFast in one parcel.
                            @endif
                        </p>

                        @if($booked)
                            <div class="alert alert-info py-2 small mb-0" role="alert">
                                <div class="fw-medium">Already booked with {{ $courier->label() }}.</div>
                                <div>Consignment <span class="font-monospace">{{ $booked->consignment_id ?: '—' }}</span>
                                    · {{ \App\Services\CourierDashboardService::statusMeta($booked->current_status)['label'] }}</div>
                            </div>
                            <a href="{{ route('admin.order.courier.show', ['provider' => $courier->type, 'order' => $order->id]) }}"
                               class="btn btn-outline-primary btn-square waves-effect w-100 mt-auto">
                                <i class="ri ri-eye-line me-1"></i>View consignment
                            </a>
                        @elseif(! ($support['supported'] ?? false))
                            <div class="alert alert-warning py-2 small mb-0" role="alert">
                                {{ $support['reason'] }}
                            </div>
                        @elseif(! $courier->isReady())
                            <div class="alert alert-warning py-2 small mb-0" role="alert">
                                {{ $courier->status ? 'This courier is switched on, but its credentials are incomplete.' : 'This courier is switched off.' }}
                                Complete it in Courier Settings before sending.
                            </div>
                            <a href="{{ route('admin.courier.settings') }}#courier-{{ $courier->type }}"
                               class="btn btn-outline-secondary btn-square waves-effect w-100 mt-auto">
                                <i class="ri ri-settings-3-line me-1"></i>Open Courier Settings
                            </a>
                        @else
                            <a href="{{ route('admin.order.courier.show', ['provider' => $courier->type, 'order' => $order->id]) }}"
                               class="btn btn-primary btn-square waves-effect w-100 mt-auto {{ $canSend ? '' : 'disabled' }}"
                               data-courier-preview>
                                <i class="ri ri-arrow-right-line me-1"></i>Select and preview
                            </a>
                        @endif

                    </div>
                </div>
            </div>
        @endforeach

        @if($couriers->isEmpty())
            <div class="col-12">
                <div class="card rounded-0">
                    <div class="card-body text-center text-muted py-5">
                        No courier account is set up yet.
                        <div class="mt-2">
                            <x-admin.button variant="primary" size="sm" shape="square"
                                            href="{{ route('admin.courier.settings') }}">
                                Open Courier Settings
                            </x-admin.button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="col-12">
            <p class="small text-muted mb-2">
                Nothing is sent until you confirm on the next screen. If the order already has a consignment with a
                courier, that courier is shown as booked so a second billable parcel is not created by accident.
            </p>
        </div>

    </div>
</div>

{{-- Pathao's preview can take up to a minute. Without this the admin clicks
     "Select and preview" and the browser just sits there. --}}
<div id="courierPreviewOverlay" class="courier-loading-overlay d-none" role="status" aria-live="polite">
    <div class="courier-loading-card text-center">
        <span class="spinner-border text-primary" aria-hidden="true"></span>
        <div class="fw-semibold mt-3">Opening the courier preview…</div>
        <div class="small text-muted mt-1">Pathao may take up to a minute. Please keep this tab open.</div>
    </div>
</div>
@endsection

@push('css')
<style>
    .courier-loading-overlay {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.5rem;
        background: rgba(15, 23, 42, .55);
        backdrop-filter: blur(2px);
    }
    .courier-loading-card {
        width: 100%;
        max-width: 22rem;
        padding: 2rem 1.5rem;
        background: #fff;
        border-radius: .75rem;
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .25);
    }
</style>
@endpush

@push('script')
<script>
    (function () {
        'use strict';

        var overlay = document.getElementById('courierPreviewOverlay');

        document.querySelectorAll('a[data-courier-preview]').forEach(function (link) {
            link.addEventListener('click', function () {
                if (link.classList.contains('disabled') || !overlay) return;

                overlay.classList.remove('d-none');
                link.setAttribute('aria-busy', 'true');
                // A second click must not queue a second slow preview.
                link.style.pointerEvents = 'none';
            });
        });
    })();
</script>
@endpush

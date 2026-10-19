@extends('backEnd.layouts.app')
@section('title','Shipping Verification')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Shipping Verification / Weight Checker</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('admin.shipping.verification') }}" class="row g-2 align-items-end">
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Order Number (Invoice ID)</label>
                            <input type="text" name="invoice_id" class="form-control" value="{{ request()->get('invoice_id') }}" placeholder="e.g. 12345">
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ request()->get('phone') }}" placeholder="01xxxxxxxxx">
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <x-admin.button variant="primary" class="w-100" type="submit">Search</x-admin.button>
                        </div>
                    </form>
                </div>
            </div>

            @if($order)
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="mb-3">Order Summary</h5>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="text-muted">Invoice</div>
                                        <div class="fw-semibold">{{ $order->invoice_id }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Amount</div>
                                        <div class="fw-semibold">{{ $order->amount }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Shipping Charge</div>
                                        <div class="fw-semibold">{{ $order->shipping_charge }}</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted">Discount</div>
                                        <div class="fw-semibold">{{ $order->discount }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="mb-3">Shipping Address</h5>
                                <div class="mb-2"><span class="text-muted">Name:</span> <span class="fw-semibold">{{ $order->shipping?->name ?? '' }}</span></div>
                                <div class="mb-2"><span class="text-muted">Phone:</span> <span class="fw-semibold">{{ $order->shipping?->phone ?? '' }}</span></div>
                                <div class="mb-2"><span class="text-muted">Area:</span> <span class="fw-semibold">{{ $order->shipping?->area ?? '' }}</span></div>
                                <div class="mb-0"><span class="text-muted">Address:</span> <span class="fw-semibold">{{ $order->shipping?->address ?? '' }}</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <h5 class="mb-0">Items & Weight Estimate</h5>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="text-muted">Total Qty:</div>
                                        <div class="fw-semibold" id="totalQty">0</div>
                                        <div class="text-muted ms-3">Total Weight (kg):</div>
                                        <div class="fw-semibold" id="totalWeight">0</div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-striped align-middle w-100">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th style="width:110px;">Qty</th>
                                                <th style="width:150px;">Weight/Item (kg)</th>
                                                <th style="width:150px;">Line Weight (kg)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($items as $it)
                                                @php
                                                    $product = $productsById->get($it->product_id);
                                                    $name = $product?->name ?? ($it->product_name ?? '');
                                                    $defaultWeight = 0.5;
                                                @endphp
                                                <tr>
                                                    <td class="text-nowrap">{{ $name }}</td>
                                                    <td>
                                                        <span class="qty" data-qty="{{ (int) $it->qty }}">{{ (int) $it->qty }}</span>
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm weight-per" value="{{ $defaultWeight }}">
                                                    </td>
                                                    <td class="fw-semibold line-weight">0</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row g-2">
                                    <div class="col-12 col-lg-6">
                                        <form method="post" action="{{ route('admin.shipping.verification.save') }}">
                                            @csrf
                                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                                            <input type="hidden" name="total_qty" id="totalQtyInput" value="0">
                                            <input type="hidden" name="total_weight_kg" id="totalWeightInput" value="0">

                                            <label class="form-label">Manual Notes</label>
                                            <textarea class="form-control" name="manual_note" rows="4" placeholder="Write manual verification notes..."></textarea>

                                            <x-admin.button variant="success" class="mt-2" type="submit">Save Verification Note</x-admin.button>
                                        </form>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <label class="form-label">Courier Suggestion</label>
                                        @php
                                            $area = strtolower((string) ($order->shipping?->area ?? ''));
                                            $suggestion = str_contains($area, 'dhaka') ? 'Pathao (Dhaka)' : 'Steadfast (Outside Dhaka)';
                                        @endphp
                                        <div class="p-3 bg-light rounded">
                                            <div class="fw-semibold mb-2">Suggested: {{ $suggestion }}</div>
                                            <div class="text-muted">Enabled couriers:</div>
                                            <div class="mt-1">
                                                @forelse($enabledCouriers as $c)
                                                    <span class="badge bg-soft-primary text-primary me-1 mb-1">{{ $c->type }}</span>
                                                @empty
                                                    <span class="text-muted">No courier API enabled</span>
                                                @endforelse
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            @elseif(request()->get('invoice_id') || request()->get('phone'))
                <div class="card">
                    <div class="card-body">
                        <div class="text-center">No order found</div>
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
        function recalc() {
            let totalQty = 0;
            let totalWeight = 0;

            document.querySelectorAll('table .qty').forEach(function (qtyEl) {
                const tr = qtyEl.closest('tr');
                const qty = parseFloat(qtyEl.getAttribute('data-qty') || '0') || 0;
                const weightInput = tr ? tr.querySelector('.weight-per') : null;
                const w = weightInput ? (parseFloat(weightInput.value) || 0) : 0;
                const lineW = qty * w;

                const lineEl = tr ? tr.querySelector('.line-weight') : null;
                if (lineEl) lineEl.textContent = lineW.toFixed(2);

                totalQty += qty;
                totalWeight += lineW;
            });

            const totalQtyEl = document.getElementById('totalQty');
            const totalWeightEl = document.getElementById('totalWeight');
            if (totalQtyEl) totalQtyEl.textContent = totalQty;
            if (totalWeightEl) totalWeightEl.textContent = totalWeight.toFixed(2);

            const totalQtyInput = document.getElementById('totalQtyInput');
            const totalWeightInput = document.getElementById('totalWeightInput');
            if (totalQtyInput) totalQtyInput.value = String(totalQty);
            if (totalWeightInput) totalWeightInput.value = totalWeight.toFixed(2);
        }

        document.querySelectorAll('.weight-per').forEach(function (el) {
            el.addEventListener('input', recalc);
        });

        recalc();
    })();
</script>
@endpush

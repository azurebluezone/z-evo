@extends('backEnd.layouts.app')
@section('title','Fraud Checker')
@section('content')

@php
    /*
     * Same form markup as backEnd/website-setup/appearance.blade.php:
     * row / col-* / label.form-label / .form-control.
     *
     * One phone field drives three actions:
     *   Search        -> GET the saved checks for that number (no API call)
     *   Save risk     -> POST a manual risk label / success rate
     *   Check courier -> POST the live courier lookup
     * The override inputs are pre-filled from the newest saved row, so adding
     * or correcting a success rate starts from the current value instead of an
     * empty form.
     */
    $riskLabels = [
        'unknown'   => 'Unknown',
        'normal'    => 'Normal',
        'risky'     => 'Risky',
        'high_risk' => 'High Risk',
    ];

    $latestRisk = strtolower(trim((string) ($latest->risk_label ?? 'unknown')));
    if (! array_key_exists($latestRisk, $riskLabels)) {
        $latestRisk = 'unknown';
    }
@endphp

<div class="row">
    <div class="col-12">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Fraud Checker</h4>
        </div>

        @if($phone !== '')
            {{-- The searched number's newest saved result, shown before the form
                 so the admin sees it without scrolling or opening the table. --}}
            <div class="alert alert-light border d-flex flex-wrap align-items-center gap-3 py-2 mb-3">
                <span class="fw-semibold"><i class="ri ri-phone-line me-1"></i>{{ $phone }}</span>

                @if($latest)
                    <span>Risk:
                        @if($latestRisk === 'high_risk')
                            <span class="badge bg-danger">High Risk</span>
                        @elseif($latestRisk === 'risky')
                            <span class="badge bg-warning text-dark">Risky</span>
                        @elseif($latestRisk === 'normal')
                            <span class="badge bg-success">Normal</span>
                        @else
                            <span class="badge bg-secondary">Unknown</span>
                        @endif
                    </span>
                    <span>Success rate:
                        <strong>{{ $latest->success_rate_percent !== null ? $latest->success_rate_percent . '%' : '—' }}</strong>
                    </span>
                    <span class="text-muted small">
                        {{ $latest->provider ?: 'unknown' }}
                        @if($latest->checked_at)
                            · {{ $latest->checked_at->format('d-m-Y h:i a') }}
                        @endif
                    </span>
                @else
                    <span class="text-muted">No saved check for this number yet. Add a success rate below.</span>
                @endif
            </div>
        @endif

        @if($phone !== '' && ! empty($providerStatus))
            {{-- Whether the couriers can actually check this number, in their
                 own words. A red "No" is the honest answer when Pathao rejects
                 the merchant login or SteadFast has an inactive account; the
                 saved local row below is then labelled as local history, never
                 dressed up as a courier score. --}}
            <div class="card mb-3 rounded-0" id="fraud-provider-status">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="ri ri-live-line me-2"></i>Can the couriers check this number?</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Provider</th>
                                <th scope="col">Available</th>
                                <th scope="col">Answer from the provider</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($providerStatus as $provider)
                                <tr>
                                    <td class="fw-medium">{{ $provider['label'] }}</td>
                                    <td>
                                        @if(($provider['available'] ?? null) === true)
                                            <span class="badge bg-success">Yes</span>
                                        @elseif(($provider['available'] ?? null) === false)
                                            <span class="badge bg-danger">No</span>
                                        @else
                                            <span class="badge bg-secondary">Not tested</span>
                                        @endif
                                    </td>
                                    <td class="small text-break">
                                        {{ $provider['reason'] }}
                                        <span class="text-muted d-block">Checked {{ $provider['checked_at'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted py-2">
                    A "No" means no courier score was invented. The saved result falls back to this shop's own
                    delivery history, which only knows orders sent from here.
                </div>
            </div>
        @endif

        <div class="card mb-3 rounded-0">
            <div class="card-header py-3">
                <h5 class="mb-0"><i class="ri ri-shield-check-line me-2"></i>Check a number</h5>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.courier.fraud-checker.override') }}" id="fraudForm">
                    @csrf

                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-4 mb-3">
                            <label class="form-label" for="fraudPhone">Phone</label>
                            <input type="text" class="form-control" id="fraudPhone" name="phone"
                                   value="{{ old('phone', $phone) }}" placeholder="01XXXXXXXXX"
                                   inputmode="numeric" required>
                        </div>

                        <div class="col-12 col-md-6 col-lg-4 mb-3">
                            <label class="form-label" for="fraudRiskLabel">Risk label</label>
                            <select class="form-select" id="fraudRiskLabel" name="risk_label">
                                @foreach($riskLabels as $value => $label)
                                    <option value="{{ $value }}" @selected(old('risk_label', $latestRisk) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-6 col-lg-4 mb-3">
                            <label class="form-label" for="fraudSuccessRate">Success rate (%)</label>
                            <input type="number" class="form-control" id="fraudSuccessRate"
                                   name="success_rate_percent" min="0" max="100"
                                   value="{{ old('success_rate_percent', $latest->success_rate_percent ?? '') }}"
                                   placeholder="Optional">
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        {{-- Search reads saved rows only, so it works even when the
                             courier's live endpoint is unavailable. --}}
                        <x-admin.button variant="info" shape="square" type="submit"
                                        formmethod="get"
                                        formaction="{{ route('admin.courier.fraud-checker') }}">
                            <i class="ri ri-search-line me-1"></i>Search
                        </x-admin.button>
                        <x-admin.button variant="warning" shape="square" type="submit">
                            <i class="ri ri-save-line me-1"></i>Save risk
                        </x-admin.button>
                        <x-admin.button variant="outline-secondary" shape="square" type="submit"
                                        formaction="{{ route('admin.courier.fraud-checker.check') }}">
                            <i class="ri ri-radar-line me-1"></i>Check courier
                        </x-admin.button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mb-3 rounded-0">
            <div class="card-header py-3">
                <h5 class="mb-0"><i class="ri ri-refresh-line me-2"></i>Sync order risk</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 mb-3">
                        <label class="form-label" for="syncLimit">Orders to check</label>
                        <input type="number" id="syncLimit" class="form-control" value="50" min="1" max="100">
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 mb-3 d-flex align-items-end">
                        <x-admin.button variant="outline-primary" shape="square" type="button"
                                        id="syncOrdersBtn"
                                        data-sync-url="{{ route('admin.courier.sync.orders') }}">
                            <i class="ri ri-refresh-line me-1"></i>Sync orders
                        </x-admin.button>
                    </div>
                </div>
                <div id="syncResults"></div>
            </div>
        </div>

        <div class="card rounded-0">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                <h5 class="mb-0">Recent checks</h5>

                @if($phone !== '')
                    <x-admin.button variant="text-secondary" size="sm"
                                    href="{{ route('admin.courier.fraud-checker') }}">
                        Clear filter
                    </x-admin.button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Checked at</th>
                            <th>Phone</th>
                            <th>Provider</th>
                            <th>Success rate</th>
                            <th>Risk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checks as $row)
                            @php
                                $riskLabel = strtolower(trim((string) ($row->risk_label ?? '')));
                                $successRate = $row->success_rate_percent;
                                if (($riskLabel === '' || $riskLabel === 'unknown') && $successRate !== null && is_numeric($successRate)) {
                                    $sr = (int) $successRate;
                                    if ($sr < 70) {
                                        $riskLabel = 'high_risk';
                                    } elseif ($sr < 80) {
                                        $riskLabel = 'risky';
                                    } else {
                                        $riskLabel = 'normal';
                                    }
                                }
                            @endphp
                            <tr>
                                <td>{{ $row->checked_at ? $row->checked_at->format('d-m-Y h:i:s a') : '' }}</td>
                                <td>{{ $row->phone }}</td>
                                <td>
                                    @php
                                        $providerLabel = (string) ($row->provider ?? '');
                                        if (in_array($providerLabel, ['internal', 'internal_history'], true)) {
                                            $providerLabel = 'Local history';
                                        }
                                        $apiData = is_array($row->raw_payload ?? null)
                                            ? ($row->raw_payload['api_data'] ?? null)
                                            : null;
                                        if (is_array($apiData) && $apiData !== []) {
                                            $providerLabel .= ' (' . implode(', ', array_map(
                                                static fn (string $key): string => $key === 'steadfast' ? 'SteadFast' : ucfirst($key),
                                                array_keys($apiData)
                                            )) . ')';
                                        }
                                    @endphp
                                    {{ $providerLabel !== '' ? $providerLabel : 'Unknown' }}
                                </td>
                                <td>{{ $row->success_rate_percent !== null ? ($row->success_rate_percent . '%') : '' }}</td>
                                <td>
                                    @if($riskLabel === 'high_risk')
                                        <span class="badge bg-danger">High Risk</span>
                                    @elseif($riskLabel === 'risky')
                                        <span class="badge bg-warning text-dark">Risky</span>
                                    @elseif($riskLabel === 'normal')
                                        <span class="badge bg-success">Normal</span>
                                    @else
                                        @php
                                            $rawMessage = '';
                                            if (is_array($row->raw_payload ?? null)) {
                                                $rawMessage = (string) ($row->raw_payload['message'] ?? '');
                                            }
                                            $providerMissing = trim((string) ($row->provider ?? '')) === '';
                                        @endphp
                                        @if($row->success_rate_percent === null && ($providerMissing || ($rawMessage !== '' && str_contains(strtolower($rawMessage), 'not configured'))))
                                            <span class="badge bg-info">Not Configured</span>
                                        @else
                                            <span class="badge bg-secondary">Unknown</span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No checks yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($checks->total() > 0)
                <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span class="text-muted small">
                        Showing {{ $checks->firstItem() }}–{{ $checks->lastItem() }} of {{ $checks->total() }}
                    </span>
                    {{ $checks->links('pagination::bootstrap-5') }}
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
         * "Check courier" runs two live provider probes, so the click must show
         * work in progress. The submitter's formaction/formmethod are copied
         * onto the form first: a disabled button is not submitted, and
         * disabling it without copying would silently send "Save risk" instead.
         */
        var form = document.getElementById('fraudForm');
        if (!form) return;

        form.addEventListener('submit', function () {
            var submitter = document.activeElement;

            if (!submitter || submitter.tagName !== 'BUTTON' || submitter.form !== form) {
                return;
            }

            if (submitter.hasAttribute('formaction')) {
                form.setAttribute('action', submitter.getAttribute('formaction'));
            }

            if (submitter.hasAttribute('formmethod')) {
                form.setAttribute('method', submitter.getAttribute('formmethod'));
            }

            submitter.disabled = true;
            submitter.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Working…';
        });
    })();
</script>
@endpush

@extends('backEnd.layouts.app')
@section('title','Fraud Checker')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <h4 class="mb-0">Fraud Checker</h4>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="get" action="{{ route('admin.courier.fraud-checker') }}" class="row g-2 align-items-end">
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ $phone }}" placeholder="01XXXXXXXXX">
                        </div>
                        <div class="col-sm-6 col-md-8">
                            <x-admin.button variant="info" class="w-100" type="submit">Search</x-admin.button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('admin.courier.fraud-checker.check') }}" class="row g-2 align-items-end mt-2">
                        @csrf
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Phone (Manual Check)</label>
                            <input type="text" name="phone" class="form-control" value="{{ $phone }}" placeholder="01XXXXXXXXX">
                        </div>
                        <div class="col-sm-6 col-md-8">
                            <x-admin.button variant="primary" class="w-100" type="submit">Check Now</x-admin.button>
                        </div>
                    </form>

                    <form method="post" action="{{ route('admin.courier.fraud-checker.override') }}" class="row g-2 align-items-end mt-2">
                        @csrf
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Phone (Override)</label>
                            <input type="text" name="phone" class="form-control" value="{{ $phone }}" placeholder="01XXXXXXXXX">
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">Risk Label</label>
                            <select name="risk_label" class="form-select">
                                <option value="unknown">Unknown</option>
                                <option value="normal">Normal</option>
                                <option value="risky">Risky</option>
                                <option value="high_risk">High Risk</option>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label class="form-label">Success Rate (%)</label>
                            <input type="number" name="success_rate_percent" class="form-control" min="0" max="100" placeholder="Optional">
                        </div>
                        <div class="col-sm-6 col-md-2">
                            <x-admin.button variant="warning" class="w-100" type="submit">Override</x-admin.button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped w-100 align-middle">
                            <thead>
                                <tr>
                                    <th>Checked At</th>
                                    <th>Phone</th>
                                    <th>Provider</th>
                                    <th>Success Rate</th>
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
                                        <td>{{ $row->provider }}</td>
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
                                                @endphp
                                                @php
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
                                        <td colspan="5" class="text-center">No data</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

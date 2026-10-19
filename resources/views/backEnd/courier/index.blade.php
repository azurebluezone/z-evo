@extends('backEnd.layouts.app')
@section('title', 'Couriers')
@section('content')
<div class="row">
    <div class="col-12">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-0">Couriers</h4>
                <p class="text-muted mb-0 small">
                    Every courier account, whether it is switched on, and a live connection test.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.courier.shipments') }}" class="btn btn-outline-primary btn-sm">
                    <i class="ri ri-list-check-2 me-1"></i> Shipments
                </a>
                <a href="{{ route('admin.courier.settings') }}" class="btn btn-secondary btn-sm">
                    <i class="ri ri-settings-3-line me-1"></i> Settings
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        {{-- A connection test run as part of saving shows its steps here, so the
             admin does not have to press Test again to find out what happened. --}}
        @if(session('connection_test'))
            @php($ct = session('connection_test'))
            <div class="alert {{ ($ct['ok'] ?? false) ? 'alert-success' : 'alert-warning' }}">
                <strong>{{ $ct['message'] ?? '' }}</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach(($ct['steps'] ?? []) as $step)
                        <li>
                            {{ $step['ok'] ? '✓' : '✗' }} {{ $step['label'] }}
                            @if(!empty($step['detail']))
                                <span class="text-muted">— {{ $step['detail'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri ri-truck-line me-2"></i>Courier accounts</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Courier</th>
                            <th>Base URL</th>
                            <th>Credentials</th>
                            <th>Status</th>
                            <th>Shipments</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($couriers as $courier)
                        @php($stat = $shipmentStats->get($courier->type))
                        <tr>
                            <td>
                                <strong>{{ $courier->label() }}</strong>
                                <div class="text-muted small">{{ $courier->type }}</div>
                            </td>
                            <td><code class="small">{{ $courier->url ?: '—' }}</code></td>
                            <td>
                                @if($courier->isConfigured())
                                    <span class="badge bg-label-success">Complete</span>
                                @else
                                    <span class="badge bg-label-danger">Incomplete</span>
                                @endif
                            </td>
                            <td>
                                @if($courier->status)
                                    <span class="badge bg-label-primary">Enabled</span>
                                @else
                                    <span class="badge bg-label-secondary">Disabled</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.courier.shipments', ['provider' => $courier->type]) }}">
                                    {{ (int) ($stat->total ?? 0) }}
                                </a>
                                @if($stat && $stat->last_synced_at)
                                    <div class="text-muted small">last {{ \Illuminate\Support\Carbon::parse($stat->last_synced_at)->diffForHumans() }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="{{ route('admin.courier.edit', $courier->type) }}"
                                       class="btn btn-sm btn-outline-primary">Edit</a>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary js-courier-test"
                                            data-url="{{ route('admin.courier.test', $courier->type) }}"
                                            data-target="test-result-{{ $courier->type }}">
                                        Test
                                    </button>

                                    <form method="POST" action="{{ route('admin.courier.toggle', $courier->type) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $courier->status ? 'btn-outline-danger' : 'btn-outline-success' }}">
                                            {{ $courier->status ? 'Disable' : 'Enable' }}
                                        </button>
                                    </form>
                                </div>
                                <div class="mt-2 text-start" id="test-result-{{ $courier->type }}"></div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No courier accounts exist yet. Run the courier migration, or add one below.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Webhooks. The token is the ONLY thing protecting this endpoint:
             without a matching bearer token every delivery update is refused, so
             the admin has to be able to see it and copy it. --}}
        @foreach($couriers as $courier)
            <div class="card mt-4">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h5 class="mb-0">
                        <i class="ri ri-webhook-line me-2"></i>{{ $courier->label() }} webhook
                    </h5>
                    <form method="POST" action="{{ route('admin.courier.rotate-token', $courier->type) }}"
                          onsubmit="return confirm('Rotate the token? The old token stops working immediately and the courier portal must be updated.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">Rotate token</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Endpoint to paste into {{ $courier->label() }}'s portal</label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly
                                       value="{{ url('/api/' . $courier->type . '-webhook') }}"
                                       id="hook-url-{{ $courier->type }}">
                                <button class="btn btn-outline-secondary" type="button"
                                        data-copy="hook-url-{{ $courier->type }}">Copy</button>
                            </div>
                            <div class="form-text">Must be publicly reachable, and {{ strtoupper($courier->type) }} must be Enabled above.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Bearer token (sent as <code>Authorization</code>)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" readonly
                                       value="{{ $courier->webhook_token ?: '— none generated yet —' }}"
                                       id="hook-token-{{ $courier->type }}">
                                <button class="btn btn-outline-secondary" type="button"
                                        data-copy="hook-token-{{ $courier->type }}">Copy</button>
                            </div>
                            <div class="form-text">Requests without this token are refused with 401.</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

    </div>
</div>
@endsection

<script>
    // Vanilla JS on purpose: this block lives inside the content section, and the
    // admin layout loads jQuery at the bottom, so a jQuery handler here would
    // depend on load order. A fetch() needs no library at all.
    (function () {
        'use strict';

        var csrf = @json(csrf_token());

        document.querySelectorAll('.js-courier-test').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.dataset.target);
                var original = button.innerHTML;

                button.disabled = true;
                button.innerHTML = 'Testing…';
                if (target) {
                    target.innerHTML = '<span class="text-muted small">Contacting the courier…</span>';
                }

                fetch(button.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (result) {
                        if (!target) { return; }

                        var cls = result.ok ? 'text-success' : 'text-danger';
                        var html = '<div class="' + cls + ' small"><strong>' + (result.message || '') + '</strong></div><ul class="small mb-0 ps-3">';

                        (result.steps || []).forEach(function (step) {
                            html += '<li>' + (step.ok ? '✓' : '✗') + ' ' + step.label;
                            if (step.detail) {
                                html += ' <span class="text-muted">— ' + step.detail + '</span>';
                            }
                            html += '</li>';
                        });

                        target.innerHTML = html + '</ul>';
                    })
                    .catch(function (error) {
                        if (target) {
                            target.innerHTML = '<span class="text-danger small">Test failed: ' + error + '</span>';
                        }
                    })
                    .finally(function () {
                        button.disabled = false;
                        button.innerHTML = original;
                    });
            });
        });

        // Copy buttons for the webhook URL and token.
        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.copy);
                if (!input) { return; }

                input.select();
                input.setSelectionRange(0, 99999);

                var done = function () {
                    var label = button.innerHTML;
                    button.innerHTML = 'Copied';
                    setTimeout(function () { button.innerHTML = label; }, 1500);
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value).then(done);
                } else {
                    document.execCommand('copy');
                    done();
                }
            });
        });
    })();
</script>

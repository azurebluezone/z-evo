@extends('backEnd.layouts.app')
@section('title', ucfirst($type) . ' Credentials')
@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h4 class="mb-0">{{ ucfirst($type) }} credentials</h4>
                <p class="text-muted mb-0 small">
                    Used by every courier call: sending a parcel, the connection test, and the fraud lookup.
                </p>
            </div>
            <a href="{{ route('admin.courier.index') }}" class="btn btn-secondary btn-sm">
                <i class="ri ri-arrow-left-line me-1"></i> Back
            </a>
        </div>

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri ri-key-2-line me-2"></i>API credentials</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.courier.update', $type) }}" autocomplete="off">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="title">Display name</label>
                        <input type="text" class="form-control" id="title" name="title"
                               value="{{ old('title', $courier->title) }}"
                               placeholder="{{ ucfirst($type) }}">
                        <div class="form-text">Shown in the courier list and on the send screen.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="url">Base URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control @error('url') is-invalid @enderror"
                               id="url" name="url" required
                               value="{{ old('url', $courier->url ?: ($type === 'steadfast' ? 'https://portal.packzy.com/api/v1' : 'https://api-hermes.pathao.com')) }}">
                        <div class="form-text">
                            @if($type === 'steadfast')
                                SteadFast: <code>https://portal.packzy.com/api/v1</code>
                            @else
                                Pathao production: <code>https://api-hermes.pathao.com</code> ·
                                sandbox: <code>https://courier-api-sandbox.pathao.com</code>.
                                The <code>/aladdin</code> prefix is added automatically for production hosts
                                and skipped for the sandbox, so enter the host only.
                            @endif
                        </div>
                    </div>

                    @if($type === 'steadfast')
                        <div class="mb-3">
                            <label class="form-label" for="api_key">API key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('api_key') is-invalid @enderror"
                                   id="api_key" name="api_key" required
                                   value="{{ old('api_key', $courier->api_key) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="secret_key">Secret key <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('secret_key') is-invalid @enderror"
                                   id="secret_key" name="secret_key" required
                                   value="{{ old('secret_key', $courier->secret_key) }}">
                        </div>
                    @else
                        {{-- Pathao authenticates with a username/password pair that is
                             exchanged for a bearer token, so all four values are needed. --}}
                        <div class="mb-3">
                            <label class="form-label" for="client_id">Client ID <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('client_id') is-invalid @enderror"
                                   id="client_id" name="client_id" required
                                   value="{{ old('client_id', $courier->client_id) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="client_secret">Client secret <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('client_secret') is-invalid @enderror"
                                   id="client_secret" name="client_secret" required
                                   value="{{ old('client_secret', $courier->client_secret) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="username">Merchant email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('username') is-invalid @enderror"
                                   id="username" name="username" required
                                   value="{{ old('username', $courier->username) }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">Merchant password <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required
                                   value="{{ old('password', $courier->password) }}">
                        </div>
                    @endif

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="status" name="status" value="1"
                               {{ old('status', $courier->status) ? 'checked' : '' }}>
                        <label class="form-check-label" for="status">Enable this courier</label>
                        <div class="form-text">
                            Saving with an incomplete set of credentials leaves it disabled - a courier that
                            is switched on but unusable makes every send fail with an upstream error.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri ri-save-line me-1"></i> Save and test
                        </button>
                        <a href="{{ route('admin.courier.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri ri-webhook-line me-2"></i>Webhook</h5>
            </div>
            <div class="card-body">
                <label class="form-label">Endpoint</label>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" readonly
                           value="{{ url('/api/' . $type . '-webhook') }}" id="hook-url">
                    <button class="btn btn-outline-secondary" type="button" data-copy="hook-url">Copy</button>
                </div>

                <label class="form-label">Bearer token</label>
                <div class="input-group">
                    <input type="text" class="form-control" readonly
                           value="{{ $courier->webhook_token ?: '— generated on first save —' }}" id="hook-token">
                    <button class="btn btn-outline-secondary" type="button" data-copy="hook-token">Copy</button>
                </div>
                <div class="form-text">
                    Generate the token by saving this form once, then paste both values into
                    {{ ucfirst($type) }}'s portal.
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri ri-pulse-line me-2"></i>Connection test</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Tests the SAVED values, not what is typed above. Checks that the service is reachable
                    first (no key needed), then whether the credentials are accepted.
                </p>
                <button type="button" class="btn btn-outline-primary btn-sm js-courier-test"
                        data-url="{{ route('admin.courier.test', $type) }}"
                        data-target="test-result">
                    Test saved credentials
                </button>
                <div class="mt-3" id="test-result"></div>
            </div>
        </div>
    </div>
</div>
@endsection

{{-- See courier/index.blade.php: a block after @endsection is discarded by
     Blade, so these handlers must live inside a @section('script'). --}}
@section('script')
<script>
    (function () {
        'use strict';

        var csrf = @json(csrf_token());

        document.querySelectorAll('.js-courier-test').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.dataset.target);
                button.disabled = true;

                fetch(button.dataset.url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (result) {
                        if (!target) { return; }
                        var html = '<div class="' + (result.ok ? 'text-success' : 'text-danger') + ' small"><strong>'
                            + (result.message || '') + '</strong></div><ul class="small mb-0 ps-3">';
                        (result.steps || []).forEach(function (step) {
                            html += '<li>' + (step.ok ? '✓' : '✗') + ' ' + step.label
                                + (step.detail ? ' <span class="text-muted">— ' + step.detail + '</span>' : '')
                                + '</li>';
                        });
                        target.innerHTML = html + '</ul>';
                    })
                    .catch(function (error) {
                        if (target) { target.innerHTML = '<span class="text-danger small">' + error + '</span>'; }
                    })
                    .finally(function () { button.disabled = false; });
            });
        });

        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.copy);
                if (!input) { return; }
                input.select();
                input.setSelectionRange(0, 99999);
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value);
                } else {
                    document.execCommand('copy');
                }
                var label = button.innerHTML;
                button.innerHTML = 'Copied';
                setTimeout(function () { button.innerHTML = label; }, 1500);
            });
        });
    })();
</script>
@endsection

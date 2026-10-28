@extends('backEnd.layouts.app')
@section('title', 'Courier API Settings')
@section('content')

@php
    /*
     * ONE canonical courier screen.
     *
     * Credentials used to be editable in three different pages (courier/settings,
     * courierapi/manage and courier/{type}/edit), each with its own form and its
     * own test button. They drifted. This page is now the only place a courier
     * key is written; every other entry point redirects here.
     */
    $providerMeta = [
        'steadfast' => [
            'icon'  => 'ri-truck-line',
            'hint'  => 'Paste the API key and secret key from the SteadFast merchant panel.',
        ],
        'pathao' => [
            'icon'  => 'ri-road-map-line',
            'hint'  => 'Paste the client credentials and merchant login from the Pathao merchant panel.',
        ],
    ];
@endphp

<div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-0">Courier API Settings</h4>
        <p class="text-muted mb-0 small">
            The single place to store courier credentials, run a live connection test and copy the delivery webhook.
        </p>
    </div>
    <x-admin.button variant="outline-secondary" size="sm" href="{{ route('admin.courier.index') }}">
        <i class="ri ri-arrow-left-line me-1"></i>Couriers overview
    </x-admin.button>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <strong>The settings were not saved.</strong>
        <ul class="mb-0 mt-1 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- A test run as part of saving shows its steps here, so the admin does not
     have to press Test again to find out what happened. --}}
@if(session('connection_test'))
    @php
        $ct = session('connection_test');
    @endphp
    <div class="alert {{ ($ct['ok'] ?? false) ? 'alert-success' : 'alert-warning' }}">
        <strong>{{ $ct['message'] ?? '' }}</strong>
        <ul class="mb-0 mt-2 ps-3">
            @foreach(($ct['steps'] ?? []) as $step)
                <li>
                    {{ !empty($step['ok']) ? '✓' : '✗' }} {{ $step['label'] }}
                    @if(!empty($step['detail']))
                        <span class="text-muted">— {{ $step['detail'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif

@forelse($couriers as $courier)
    @php
        $meta = $providerMeta[$courier->type] ?? ['icon' => 'ri-truck-line', 'hint' => ''];
        $elementId = 'courier-' . $courier->type;
        $testTarget = 'test-result-' . $courier->type;
    @endphp

    <div class="card mb-4" id="{{ $elementId }}">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-0">
                    <i class="ri {{ $meta['icon'] }} me-2"></i>{{ $courier->label() }}
                </h5>
                <p class="text-muted small mb-0">{{ $meta['hint'] }}</p>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                @if($courier->isConfigured())
                    <span class="badge bg-label-info">Credentials complete</span>
                @else
                    <span class="badge bg-label-warning">Credentials incomplete</span>
                @endif
                <span class="badge {{ $courier->status ? 'bg-label-success' : 'bg-label-secondary' }}">
                    {{ $courier->status ? 'Enabled' : 'Disabled' }}
                </span>
            </div>
        </div>

        <div class="card-body">
            <form method="POST" action="{{ route('admin.courier.update', $courier->type) }}">
                @csrf

                <div class="row g-3">
                    @if($courier->type === 'steadfast')
                        <div class="col-12 col-lg-6">
                            <x-admin.input name="api_key" label="API key" :value="$courier->api_key"
                                           placeholder="Your SteadFast API key" required />
                        </div>
                        <div class="col-12 col-lg-6">
                            <x-admin.input name="secret_key" label="Secret key" type="password"
                                           :value="$courier->secret_key" placeholder="Your SteadFast secret key"
                                           autocomplete="new-password" required />
                        </div>
                    @else
                        <div class="col-12 col-lg-6">
                            <x-admin.input name="client_id" label="Client ID" :value="$courier->client_id"
                                           placeholder="Client ID from Pathao" required />
                        </div>
                        <div class="col-12 col-lg-6">
                            <x-admin.input name="client_secret" label="Client secret" type="password"
                                           :value="$courier->client_secret" placeholder="Client secret from Pathao"
                                           autocomplete="new-password" required />
                        </div>
                        <div class="col-12 col-lg-6">
                            <x-admin.input name="username" label="Merchant username" type="email"
                                           :value="$courier->username" placeholder="merchant@example.com"
                                           autocomplete="username" required />
                        </div>
                        <div class="col-12 col-lg-6">
                            <x-admin.input name="password" label="Merchant password" type="password"
                                           :value="$courier->password" placeholder="Pathao merchant password"
                                           autocomplete="new-password" required />
                        </div>
                    @endif

                    <div class="col-12 col-lg-8">
                        <x-admin.input name="url" label="Base URL" type="url" :value="$courier->url"
                                       placeholder="https://"
                                       :help="$courier->type === 'steadfast'
                                            ? 'The api/v1 root, e.g. https://portal.packzy.com/api/v1. A create_order endpoint is also accepted.'
                                            : 'Production: https://api-hermes.pathao.com · Sandbox: https://courier-api-sandbox.pathao.com'"
                                       required />
                    </div>

                    <div class="col-12 col-lg-4">
                        <label class="form-label d-block">Availability</label>
                        <x-admin.switch name="status" label="Enabled for checkout" :checked="(bool) $courier->status"
                                        help="Only a configured courier can be enabled." />
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mt-3">
                    <x-admin.button variant="primary" type="submit">
                        <i class="ri ri-save-line me-1"></i>Save settings
                    </x-admin.button>
                    <x-admin.button variant="outline-secondary" type="button"
                                    class="js-courier-test"
                                    data-url="{{ route('admin.courier.test', $courier->type) }}"
                                    data-target="{{ $testTarget }}">
                        <i class="ri ri-test-tube-line me-1"></i>Test connection
                    </x-admin.button>
                </div>
            </form>

            <div class="mt-3" id="{{ $testTarget }}" aria-live="polite"></div>

            <div class="border-top pt-3 mt-4">
                <h6 class="mb-1">
                    <i class="ri ri-webhook-line me-1"></i>Delivery webhook
                </h6>
                <p class="text-muted small mb-3">
                    Paste the endpoint and bearer token into {{ $courier->label() }}'s portal.
                    Requests without the token are refused with HTTP 401.
                </p>

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label class="form-label" for="hook-url-{{ $courier->type }}">Webhook endpoint</label>
                        <div class="input-group">
                            <input type="text" class="form-control" readonly
                                   value="{{ url('/api/' . $courier->type . '-webhook') }}"
                                   id="hook-url-{{ $courier->type }}">
                            <button class="btn btn-outline-secondary" type="button"
                                    data-copy="hook-url-{{ $courier->type }}">Copy</button>
                        </div>
                        <div class="form-text">Must be publicly reachable while this courier is enabled.</div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <label class="form-label" for="hook-token-{{ $courier->type }}">Bearer token</label>
                        <div class="input-group">
                            <input type="text" class="form-control" readonly
                                   value="{{ $courier->webhook_token ?: '— none generated yet —' }}"
                                   id="hook-token-{{ $courier->type }}">
                            <button class="btn btn-outline-secondary" type="button"
                                    data-copy="hook-token-{{ $courier->type }}">Copy</button>
                        </div>
                        <div class="form-text">
                            Rotating a token invalidates the old one immediately; update the courier portal in the same step.
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.courier.rotate-token', $courier->type) }}"
                      class="mt-3"
                      onsubmit="return confirm('Rotate the token? The old token stops working immediately and the courier portal must be updated.');">
                    @csrf
                    <x-admin.button variant="outline-warning" size="sm" type="submit">
                        <i class="ri ri-refresh-line me-1"></i>{{ $courier->webhook_token ? 'Rotate token' : 'Generate token' }}
                    </x-admin.button>
                </form>
            </div>
        </div>
    </div>
@empty
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            No courier accounts exist yet. Run the courier migration, then reload this page.
        </div>
    </div>
@endforelse

@endsection

@section('script')
<script>
    /*
     * Vanilla JS on purpose: this block is rendered before the layout's jQuery
     * bundle, and none of this needs a library.
     */
    (function () {
        'use strict';

        var csrf = @json(csrf_token());

        function textRow(container, text, className) {
            var line = document.createElement('div');
            line.className = className || '';
            line.textContent = text;
            container.appendChild(line);
        }

        document.querySelectorAll('.js-courier-test').forEach(function (button) {
            button.addEventListener('click', function () {
                var target = document.getElementById(button.dataset.target);
                var original = button.innerHTML;

                button.disabled = true;
                button.innerHTML = 'Testing…';
                if (target) {
                    target.replaceChildren();
                    textRow(target, 'Contacting the courier…', 'text-muted small');
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

                        target.replaceChildren();
                        textRow(target, result.message || '', result.ok ? 'text-success small fw-medium' : 'text-danger small fw-medium');

                        var list = document.createElement('ul');
                        list.className = 'small mb-0 ps-3';

                        (result.steps || []).forEach(function (step) {
                            var item = document.createElement('li');
                            item.textContent = (step.ok ? '✓ ' : '✗ ') + step.label;
                            if (step.detail) {
                                var detail = document.createElement('span');
                                detail.className = 'text-muted';
                                detail.textContent = ' — ' + step.detail;
                                item.appendChild(detail);
                            }
                            list.appendChild(item);
                        });

                        target.appendChild(list);
                    })
                    .catch(function (error) {
                        if (target) {
                            target.replaceChildren();
                            textRow(target, 'Test failed: ' + error, 'text-danger small');
                        }
                    })
                    .finally(function () {
                        button.disabled = false;
                        button.innerHTML = original;
                    });
            });
        });

        document.querySelectorAll('[data-copy]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.copy);
                if (!input) { return; }

                input.select();
                input.setSelectionRange(0, 99999);

                var done = function () {
                    var label = button.textContent;
                    button.textContent = 'Copied';
                    setTimeout(function () { button.textContent = label; }, 1500);
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
@endsection

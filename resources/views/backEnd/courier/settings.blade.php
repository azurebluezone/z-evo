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
     *
     * FORM STYLE: the same markup as backEnd/website-setup/appearance.blade.php
     * (row + col-lg-6 mb-3 + label.form-label + input.form-control). No hint
     * paragraphs under the fields - the label says what the field is, and the
     * placeholder shows the expected shape.
     */
    $providerIcons = [
        'steadfast' => 'ri-truck-line',
        'pathao'    => 'ri-road-map-line',
    ];
@endphp

<div class="row">
    <div class="col-12">

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Courier API Settings</h4>
            <x-admin.button variant="outline-secondary" size="sm" href="{{ route('admin.courier.index') }}">
                <i class="ri ri-arrow-left-line me-1"></i>Couriers
            </x-admin.button>
        </div>

        {{-- One result line. Saving already runs the live test, so the message
             here IS the save confirmation too - printing "Settings saved" next
             to a refused test would say two different things at once. --}}
        @if(session('connection_test'))
            @php
                $ct = session('connection_test');
            @endphp
            <div class="alert {{ ($ct['ok'] ?? false) ? 'alert-success' : 'alert-danger' }} d-flex align-items-center gap-2 py-2">
                <i class="ri {{ ($ct['ok'] ?? false) ? 'ri-checkbox-circle-fill' : 'ri-error-warning-fill' }}"></i>
                <span>{{ $ct['message'] ?? '' }}</span>
            </div>
        @elseif(session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger py-2">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @forelse($couriers as $courier)
            @php
                $icon      = $providerIcons[$courier->type] ?? 'ri-truck-line';
                $elementId = 'courier-' . $courier->type;
                $testTarget = 'test-result-' . $courier->type;
                $p = $courier->type;

                /*
                 * A stored secret is never written back into the HTML. The
                 * field shows a fixed mask instead, and an empty submit means
                 * "keep the saved value" (CourierController::update() restores
                 * it). Masking only the type=password dot rendering is not
                 * enough: the value used to sit in the value attribute, where
                 * View Source, DevTools and any HTML dump could read it.
                 */
                $mask = '••••••••••••';
            @endphp

            <div class="card mb-4" id="{{ $elementId }}">
                <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-3">
                    <h5 class="mb-0">
                        <i class="ri {{ $icon }} me-2"></i>{{ $courier->label() }}
                    </h5>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="badge {{ $courier->isConfigured() ? 'bg-label-success' : 'bg-label-warning' }}">
                            {{ $courier->isConfigured() ? 'Configured' : 'Incomplete' }}
                        </span>
                        <span class="badge {{ $courier->status ? 'bg-label-success' : 'bg-label-secondary' }}">
                            {{ $courier->status ? 'Enabled' : 'Disabled' }}
                        </span>
                    </div>
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('admin.courier.update', $courier->type) }}">
                        @csrf

                        <div class="row">
                            @if($courier->type === 'steadfast')
                                <div class="col-12 col-lg-6 mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label class="form-label" for="{{ $p }}-api-key">API key</label>
                                        <span class="badge {{ filled($courier->api_key) ? 'bg-label-success' : 'bg-label-secondary' }}">
                                            {{ filled($courier->api_key) ? 'Saved' : 'Not set' }}
                                        </span>
                                    </div>
                                    <input type="password" class="form-control" id="{{ $p }}-api-key" name="api_key"
                                           value="{{ old('api_key') }}"
                                           placeholder="{{ filled($courier->api_key) ? $mask : '' }}"
                                           autocomplete="new-password" @if(! filled($courier->api_key)) required @endif>
                                    @error('api_key')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-lg-6 mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label class="form-label" for="{{ $p }}-secret-key">Secret key</label>
                                        <span class="badge {{ filled($courier->secret_key) ? 'bg-label-success' : 'bg-label-secondary' }}">
                                            {{ filled($courier->secret_key) ? 'Saved' : 'Not set' }}
                                        </span>
                                    </div>
                                    <input type="password" class="form-control" id="{{ $p }}-secret-key" name="secret_key"
                                           value="{{ old('secret_key') }}"
                                           placeholder="{{ filled($courier->secret_key) ? $mask : '' }}"
                                           autocomplete="new-password" @if(! filled($courier->secret_key)) required @endif>
                                    @error('secret_key')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            @else
                                <div class="col-12 col-lg-6 mb-3">
                                    <label class="form-label" for="{{ $p }}-client-id">Client ID</label>
                                    <input type="text" class="form-control" id="{{ $p }}-client-id" name="client_id"
                                           value="{{ old('client_id', $courier->client_id) }}" required>
                                    @error('client_id')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-lg-6 mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label class="form-label" for="{{ $p }}-client-secret">Client secret</label>
                                        <span class="badge {{ filled($courier->client_secret) ? 'bg-label-success' : 'bg-label-secondary' }}">
                                            {{ filled($courier->client_secret) ? 'Saved' : 'Not set' }}
                                        </span>
                                    </div>
                                    <input type="password" class="form-control" id="{{ $p }}-client-secret" name="client_secret"
                                           value="{{ old('client_secret') }}"
                                           placeholder="{{ filled($courier->client_secret) ? $mask : '' }}"
                                           autocomplete="new-password" @if(! filled($courier->client_secret)) required @endif>
                                    @error('client_secret')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-lg-6 mb-3">
                                    <label class="form-label" for="{{ $p }}-username">Merchant username</label>
                                    <input type="email" class="form-control" id="{{ $p }}-username" name="username"
                                           value="{{ old('username', $courier->username) }}"
                                           autocomplete="username" required>
                                    @error('username')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-12 col-lg-6 mb-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <label class="form-label" for="{{ $p }}-password">Merchant password</label>
                                        <span class="badge {{ filled($courier->password) ? 'bg-label-success' : 'bg-label-secondary' }}">
                                            {{ filled($courier->password) ? 'Saved' : 'Not set' }}
                                        </span>
                                    </div>
                                    <input type="password" class="form-control" id="{{ $p }}-password" name="password"
                                           value="{{ old('password') }}"
                                           placeholder="{{ filled($courier->password) ? $mask : '' }}"
                                           autocomplete="new-password" @if(! filled($courier->password)) required @endif>
                                    @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                </div>
                            @endif

                            <div class="col-12 mb-3">
                                <label class="form-label" for="{{ $p }}-url">Base URL</label>
                                <input type="url" class="form-control" id="{{ $p }}-url" name="url"
                                       value="{{ old('url', $courier->url) }}"
                                       placeholder="{{ $courier->type === 'pathao' ? 'https://courier-api-sandbox.pathao.com' : 'https://portal.packzy.com/api/v1' }}"
                                       required>
                                @error('url')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 col-lg-6 mb-3">
                                <label class="form-label d-block" for="{{ $p }}-status">Status</label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                           id="{{ $p }}-status" name="status" value="1"
                                           @checked(old('status', $courier->status))>
                                    <label class="form-check-label" for="{{ $p }}-status">Enabled</label>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <x-admin.button variant="primary" shape="square" ripple="true" type="submit">
                                <i class="ri ri-save-line me-1"></i>Save
                            </x-admin.button>
                            <x-admin.button variant="outline-secondary" shape="square" ripple="true" type="button"
                                            class="js-courier-test"
                                            data-url="{{ route('admin.courier.test', $courier->type) }}"
                                            data-target="{{ $testTarget }}">
                                <i class="ri ri-test-tube-line me-1"></i>Test
                            </x-admin.button>
                        </div>
                    </form>

                    <div class="mt-2 small" id="{{ $testTarget }}" aria-live="polite"></div>

                    <div class="border-top pt-3 mt-4">
                        <h6 class="mb-3">
                            <i class="ri ri-webhook-line me-1"></i>Delivery webhook
                        </h6>

                        <div class="row g-3">
                            <div class="col-12 col-lg-6 mb-3">
                                <label class="form-label" for="hook-url-{{ $courier->type }}">Webhook endpoint</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" readonly
                                           value="{{ url('/api/' . $courier->type . '-webhook') }}"
                                           id="hook-url-{{ $courier->type }}">
                                    <button class="btn btn-outline-secondary" type="button"
                                            data-copy="hook-url-{{ $courier->type }}">Copy</button>
                                </div>
                            </div>

                            <div class="col-12 col-lg-6 mb-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <label class="form-label" for="hook-token-{{ $courier->type }}">Webhook token</label>
                                    <span class="badge {{ filled($courier->webhook_token) ? 'bg-label-success' : 'bg-label-secondary' }}">
                                        {{ filled($courier->webhook_token) ? 'Saved' : 'Not set' }}
                                    </span>
                                </div>
                                <div class="input-group">
                                    <input type="text" class="form-control" readonly
                                           value="{{ filled($courier->webhook_token) ? $mask : '' }}"
                                           placeholder="{{ filled($courier->webhook_token) ? '' : '— none generated yet —' }}"
                                           id="hook-token-{{ $courier->type }}">
                                    <button class="btn btn-outline-secondary" type="button"
                                            data-copy-url="{{ route('admin.courier.webhook-token', $courier->type) }}">Copy token</button>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('admin.courier.rotate-token', $courier->type) }}"
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
                    No courier accounts exist yet.
                </div>
            </div>
        @endforelse

    </div>
</div>
@endsection

@section('script')
<script src="{{ asset('admin/assets/js/admin-page-courier-settings.js') }}"></script>
@endsection

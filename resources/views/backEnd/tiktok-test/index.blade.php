@extends('backEnd.layouts.app')
@section('title', 'TikTok Server-Side Tracking Test')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">🎯 TikTok Server-Side Tracking Test</h5>
                    <a href="{{ route('admin.social-events.index') }}" class="btn btn-sm btn-label-secondary">
                        <i class="icon-base ri ri-settings-line"></i> Settings
                    </a>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong>ℹ️ Before you test:</strong>
                        <ul style="margin-top: 8px; margin-left: 20px;">
                            <li>Open <code>/admin/social-events</code> and set the TikTok Pixel ID and Access Token.</li>
                            <li>Enable TikTok Server-Side Tracking.</li>
                            <li>With Test Mode on, events are sent as tests only (no real events are recorded).</li>
                        </ul>
                    </div>

                    <!-- Connection Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">Connection Status</h6>
                                    <div id="connection-status" class="mt-3">
                                        <x-admin.button variant="primary" type="button" onclick="testConnection()">
                                            <i class="icon-base ri ri-wifi-line"></i> Test Connection
                                        </x-admin.button>
                                        <div id="connection-result" class="mt-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title">Configuration</h6>
                                    <ul class="list-unstyled mt-3">
                                        @php
                                            $settings = \App\Models\GeneralSetting::first();
                                        @endphp
                                        <li><strong>Pixel ID:</strong> {{ $settings->tiktok_pixel_id ?? '❌ Not Set' }}</li>
                                        <li><strong>Access Token:</strong> {{ $settings->tiktok_access_token ? '✓ Set' : '❌ Not Set' }}</li>
                                        <li><strong>Server Tracking:</strong> 
                                            <span class="badge {{ $settings->tiktok_server_side_tracking ? 'bg-success' : 'bg-label-secondary' }}">
                                                {{ $settings->tiktok_server_side_tracking ? 'Enabled' : 'Disabled' }}
                                            </span>
                                        </li>
                                        <li><strong>Test Mode:</strong> 
                                            <span class="badge {{ $settings->tiktok_test_mode ? 'bg-warning' : 'bg-label-secondary' }}">
                                                {{ $settings->tiktok_test_mode ? 'ON' : 'OFF' }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Send Test Events -->
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Send Test Events</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6 col-lg-3">
                                    <x-admin.button variant="outline-primary" class="w-100" type="button" onclick="sendTestEvent('PageView')">
                                        <i class="icon-base ri ri-eye-line"></i><br>
                                        PageView
                                    </x-admin.button>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <x-admin.button variant="outline-info" class="w-100" type="button" onclick="sendTestEvent('ViewContent')">
                                        <i class="icon-base ri ri-file-list-line"></i><br>
                                        ViewContent
                                    </x-admin.button>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <x-admin.button variant="outline-warning" class="w-100" type="button" onclick="sendTestEvent('AddToCart')">
                                        <i class="icon-base ri ri-shopping-cart-line"></i><br>
                                        AddToCart
                                    </x-admin.button>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <x-admin.button variant="outline-success" class="w-100" type="button" onclick="sendTestEvent('InitiateCheckout')">
                                        <i class="icon-base ri ri-bank-card-line"></i><br>
                                        InitiateCheckout
                                    </x-admin.button>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <x-admin.button variant="outline-danger" class="w-100" type="button" onclick="sendTestEvent('Purchase')">
                                        <i class="icon-base ri ri-check-double-line"></i><br>
                                        Purchase
                                    </x-admin.button>
                                </div>
                            </div>

                            <div id="event-result" class="mt-4"></div>
                        </div>
                    </div>

                    <!-- Logs -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h6 class="mb-0">Recent Event Logs</h6>
                        </div>
                        <div class="card-body">
                            <div class="card-datatable table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Event Name</th>
                                            <th>URL</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $recentLogs = \App\Models\EventLog::where('event_name', 'LIKE', '%TikTok%')
                                                ->orderByDesc('id')
                                                ->limit(10)
                                                ->get();
                                        @endphp
                                        @forelse($recentLogs as $log)
                                        <tr>
                                            <td>{{ $log->id }}</td>
                                            <td>{{ $log->event_name }}</td>
                                            <td>{{ Str::limit($log->url, 50) }}</td>
                                            <td>{{ $log->created_at->diffForHumans() }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-muted text-center">No TikTok events logged yet</td>
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
    </div>
</div>
@endsection

@section('script')
<div class="d-none" data-connection-url="{{ route('admin.tiktok-test.connection') }}" data-send-event-url="{{ route('admin.tiktok-test.send-event') }}"></div>
<script src="{{ asset('admin/assets/js/admin-page-tiktok-test.js') }}"></script>
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-tiktok-test-index.css') }}">
@endsection

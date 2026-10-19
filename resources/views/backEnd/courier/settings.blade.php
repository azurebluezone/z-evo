@extends('backEnd.layouts.app')
@section('title', 'Courier API Settings')
@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h4 class="mb-0">Courier API Settings</h4>
            {{-- Route name is `dashboard` (admin/dashboard). This was written
                 as admin.dashboard, which does not exist, so the page threw
                 RouteNotFoundException before rendering anything at all. --}}
            <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">
                <i class="ri-arrow-left-line me-1"></i> Back
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <!-- SteadFast Settings -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ri-truck-line me-2"></i>SteadFast Courier</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="steadfastToggle" {{ ($steadfast->status ?? 0) ? 'checked' : '' }}>
                    <label class="form-check-label" for="steadfastToggle">{{ ($steadfast->status ?? 0) ? 'Enabled' : 'Disabled' }}</label>
                </div>
            </div>
            <div class="card-body">
                <form id="steadfastForm" method="POST" action="{{ route('admin.courier.steadfast.update') }}">
                    @csrf
                    <input type="hidden" name="status" id="steadfastStatus" value="{{ $steadfast->status ?? 0 }}">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">API Key</label>
                            <input type="text" name="api_key" class="form-control" value="{{ $steadfast->api_key ?? '' }}" placeholder="Enter SteadFast API Key">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Secret Key</label>
                            <input type="password" name="secret_key" class="form-control" value="{{ $steadfast->secret_key ?? '' }}" placeholder="Enter SteadFast Secret Key">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">API URL</label>
                        <input type="url" name="url" class="form-control" value="{{ $steadfast->url ?? 'https://portal.steadfast.com.bd/api/v1/create_order' }}" placeholder="https://portal.steadfast.com.bd/api/v1/create_order">
                        <small class="text-muted">Base URL for SteadFast API endpoints</small>
                    </div>
                    
                    <x-admin.button variant="primary" type="submit">
                        <i class="ri-save-line me-1"></i>Save SteadFast Settings
                    </x-admin.button>
                    <x-admin.button variant="outline-info" class="ms-2" type="button" id="testSteadfastBtn">
                        <i class="ri-test-tube-line me-1"></i>Test Connection
                    </x-admin.button>
                </form>
                
                <div id="steadfastTestResult" class="mt-3 d-none">
                    <div class="alert" role="alert"></div>
                </div>
            </div>
        </div>

        <!-- Pathao Settings -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="ri-road-map-line me-2"></i>Pathao Courier</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="pathaoToggle" {{ ($pathao->status ?? 0) ? 'checked' : '' }}>
                    <label class="form-check-label" for="pathaoToggle">{{ ($pathao->status ?? 0) ? 'Enabled' : 'Disabled' }}</label>
                </div>
            </div>
            <div class="card-body">
                <form id="pathaoForm" method="POST" action="{{ route('admin.courier.pathao.update') }}">
                    @csrf
                    <input type="hidden" name="status" id="pathaoStatus" value="{{ $pathao->status ?? 0 }}">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client ID</label>
                            <input type="text" name="client_id" class="form-control" value="{{ $pathao->client_id ?? '' }}" placeholder="Enter Pathao Client ID">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client Secret</label>
                            <input type="password" name="client_secret" class="form-control" value="{{ $pathao->client_secret ?? '' }}" placeholder="Enter Pathao Client Secret">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username (Email)</label>
                            <input type="email" name="username" class="form-control" value="{{ $pathao->username ?? '' }}" placeholder="your@email.com">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" value="{{ $pathao->password ?? '' }}" placeholder="Enter Pathao Password">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">API URL</label>
                        <input type="url" name="url" class="form-control" value="{{ $pathao->url ?? 'https://api-hermes.pathao.com' }}" placeholder="https://api-hermes.pathao.com">
                        <small class="text-muted">Base URL for Pathao API (Production: https://api-hermes.pathao.com, Sandbox: https://courier-api-sandbox.pathao.com)</small>
                    </div>
                    
                    <x-admin.button variant="primary" type="submit">
                        <i class="ri-save-line me-1"></i>Save Pathao Settings
                    </x-admin.button>
                    <x-admin.button variant="outline-info" class="ms-2" type="button" id="testPathaoBtn">
                        <i class="ri-test-tube-line me-1"></i>Test Connection
                    </x-admin.button>
                </form>
                
                <div id="pathaoTestResult" class="mt-3 d-none">
                    <div class="alert" role="alert"></div>
                </div>
            </div>
        </div>

        <!-- Sync Orders Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="ri-refresh-line me-2"></i>Sync Orders with Courier Fraud Check</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    This will check existing orders against courier delivery history and update fraud scores.
                    Orders with matching phone numbers in courier shipments will be updated with success rates.
                </p>
                
                <div class="row align-items-end">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Number of Orders to Sync</label>
                        <input type="number" id="syncLimit" class="form-control" value="50" min="1" max="100">
                    </div>
                    <div class="col-md-4 mb-3">
                        <x-admin.button variant="success" type="button" id="syncOrdersBtn">
                            <i class="ri-refresh-line me-1"></i>Sync Orders
                        </x-admin.button>
                    </div>
                </div>
                
                <div id="syncResult" class="mt-3 d-none">
                    <div class="alert" role="alert"></div>
                    <div id="syncDetails" class="table-responsive mt-2 d-none">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Invoice</th>
                                    <th>Phone</th>
                                    <th>Risk Label</th>
                                    <th>Success Rate</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
<script>
(function() {
    if (!window.jQuery) return;
    var $ = window.jQuery;

    $(document).ready(function() {
        // SteadFast Toggle
        $('#steadfastToggle').on('change', function() {
            var isEnabled = $(this).is(':checked');
            $('#steadfastStatus').val(isEnabled ? 1 : 0);
            $(this).next('label').text(isEnabled ? 'Enabled' : 'Disabled');
        });

        // Pathao Toggle
        $('#pathaoToggle').on('change', function() {
            var isEnabled = $(this).is(':checked');
            $('#pathaoStatus').val(isEnabled ? 1 : 0);
            $(this).next('label').text(isEnabled ? 'Enabled' : 'Disabled');
        });

        // SteadFast Form Submit
        $('#steadfastForm').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).html('<i class="ri-loader-4-line spin me-1"></i>Saving...');
            
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                success: function(res) {
                    toastr.success(res.message);
                    if (res.test_result) {
                        showTestResult('steadfast', res.test_result);
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || 'Failed to save settings';
                    toastr.error(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i>Save SteadFast Settings');
                }
            });
        });

        // Pathao Form Submit
        $('#pathaoForm').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            
            $btn.prop('disabled', true).html('<i class="ri-loader-4-line spin me-1"></i>Saving...');
            
            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                success: function(res) {
                    toastr.success(res.message);
                    if (res.test_result) {
                        showTestResult('pathao', res.test_result);
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || 'Failed to save settings';
                    toastr.error(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="ri-save-line me-1"></i>Save Pathao Settings');
                }
            });
        });

        // Test SteadFast Connection
        $('#testSteadfastBtn').on('click', function() {
            var $btn = $(this);
            var apiKey = $('#steadfastForm input[name="api_key"]').val();
            var secretKey = $('#steadfastForm input[name="secret_key"]').val();
            var url = $('#steadfastForm input[name="url"]').val();
            
            if (!apiKey || !secretKey) {
                toastr.error('Please enter API Key and Secret Key');
                return;
            }
            
            $btn.prop('disabled', true).html('<i class="ri-loader-4-line spin me-1"></i>Testing...');
            
            $.ajax({
                url: '{{ route("admin.courier.steadfast.update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    api_key: apiKey,
                    secret_key: secretKey,
                    url: url,
                    status: $('#steadfastStatus').val(),
                },
                success: function(res) {
                    showTestResult('steadfast', res.test_result);
                    toastr.success('Connection test completed');
                },
                error: function(xhr) {
                    toastr.error('Connection test failed');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="ri-test-tube-line me-1"></i>Test Connection');
                }
            });
        });

        // Test Pathao Connection
        $('#testPathaoBtn').on('click', function() {
            var $btn = $(this);
            var clientId = $('#pathaoForm input[name="client_id"]').val();
            var clientSecret = $('#pathaoForm input[name="client_secret"]').val();
            var username = $('#pathaoForm input[name="username"]').val();
            var password = $('#pathaoForm input[name="password"]').val();
            var url = $('#pathaoForm input[name="url"]').val();
            
            if (!clientId || !clientSecret || !username || !password) {
                toastr.error('Please enter all fields');
                return;
            }
            
            $btn.prop('disabled', true).html('<i class="ri-loader-4-line spin me-1"></i>Testing...');
            
            $.ajax({
                url: '{{ route("admin.courier.pathao.update") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    client_id: clientId,
                    client_secret: clientSecret,
                    username: username,
                    password: password,
                    url: url,
                    status: $('#pathaoStatus').val(),
                },
                success: function(res) {
                    showTestResult('pathao', res.test_result);
                    toastr.success('Connection test completed');
                },
                error: function(xhr) {
                    toastr.error('Connection test failed');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="ri-test-tube-line me-1"></i>Test Connection');
                }
            });
        });

        // Sync Orders
        $('#syncOrdersBtn').on('click', function() {
            var $btn = $(this);
            var limit = $('#syncLimit').val();
            
            $btn.prop('disabled', true).html('<i class="ri-loader-4-line spin me-1"></i>Syncing...');
            
            $.ajax({
                url: '{{ route("admin.courier.sync.orders") }}',
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    limit: limit,
                },
                success: function(res) {
                    var $result = $('#syncResult');
                    var $alert = $result.find('.alert');
                    
                    $result.removeClass('d-none');
                    
                    if (res.status === 'completed') {
                        $alert.removeClass('alert-danger').addClass('alert-success')
                              .html('<i class="ri-check-line me-1"></i>Synced ' + res.processed + ' orders successfully');
                        
                        // Show details table
                        if (res.results && res.results.length > 0) {
                            var $tbody = $('#syncDetails').removeClass('d-none').find('tbody');
                            $tbody.empty();
                            
                            res.results.forEach(function(item) {
                                var badgeClass = item.risk_label === 'high_risk' ? 'bg-danger' : 
                                                item.risk_label === 'risky' ? 'bg-warning' : 'bg-success';
                                
                                $tbody.append('<tr>' +
                                    '<td>' + item.order_id + '</td>' +
                                    '<td>' + item.invoice_id + '</td>' +
                                    '<td>' + item.phone + '</td>' +
                                    '<td><span class="badge ' + badgeClass + '">' + item.risk_label + '</span></td>' +
                                    '<td>' + (item.success_rate || 'N/A') + '%</td>' +
                                '</tr>');
                            });
                        }
                        
                        toastr.success('Orders synced successfully');
                    } else {
                        $alert.removeClass('alert-success').addClass('alert-danger')
                              .text('Sync failed');
                    }
                },
                error: function(xhr) {
                    var msg = xhr.responseJSON?.message || 'Sync failed';
                    toastr.error(msg);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="ri-refresh-line me-1"></i>Sync Orders');
                }
            });
        });

        function showTestResult(type, result) {
            var $div = $('#' + type + 'TestResult');
            var $alert = $div.find('.alert');
            
            $div.removeClass('d-none');
            
            if (result.success) {
                $alert.removeClass('alert-danger').addClass('alert-success')
                      .html('<i class="ri-check-line me-1"></i>' + result.message);
                
                if (result.balance !== undefined) {
                    $alert.append('<br><strong>Balance:</strong> ৳' + result.balance);
                }
                if (result.token_received) {
                    $alert.append('<br><strong>Token:</strong> Received successfully');
                }
            } else {
                $alert.removeClass('alert-success').addClass('alert-danger')
                      .html('<i class="ri-error-warning-line me-1"></i>' + result.message);
            }
        }
    });
})();
</script>
@endpush
@endsection

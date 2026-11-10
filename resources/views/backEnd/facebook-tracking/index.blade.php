@extends('backEnd.layouts.app')
@section('title')
    Facebook Server-Side Tracking Configuration
@endsection

@section('content')
<div class="container-fluid">
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0">Facebook Server-Side Tracking Configuration</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Facebook Tracking</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                    </div>
                    <div class="card-body">
                        <form action="{{ route('facebook-tracking.update') }}" method="POST">
                            @csrf
                            <div class="row">
                                <!-- Facebook Pixel ID -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="facebook_pixel_id" class="form-label">Facebook Pixel ID</label>
                                        <input type="text" class="form-control @error('facebook_pixel_id') is-invalid @enderror" 
                                               name="facebook_pixel_id" 
                                               value="{{ $settings->facebook_pixel_id ?? '' }}" 
                                               placeholder="e.g., 123456789012345">
                                        <small class="form-text text-muted">Your Facebook Pixel ID from Facebook Business Manager</small>
                                        @error('facebook_pixel_id')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                
                                <!-- Conversion API Access Token -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="facebook_conversion_api_token" class="form-label">Conversion API Access Token</label>
                                        <input type="password" autocomplete="new-password" class="form-control @error('facebook_conversion_api_token') is-invalid @enderror" 
                                               name="facebook_conversion_api_token" 
                                               value="" 
                                               placeholder="{{ filled($settings->facebook_conversion_api_token ?? null) ? 'Stored — leave blank to keep' : 'Paste the Conversions API token' }}">
                                        <small class="form-text text-muted">Server-side access token for the Conversions API. It is never printed back into the page; leave blank to keep the stored value.</small>
                                        @error('facebook_conversion_api_token')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                
                                <!-- API Version -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="facebook_conversion_api_version" class="form-label">API Version</label>
                                        <input type="text" class="form-control @error('facebook_conversion_api_version') is-invalid @enderror"
                                               id="facebook_conversion_api_version"
                                               name="facebook_conversion_api_version"
                                               value="{{ $settings->facebook_conversion_api_version ?? 'v17.0' }}"
                                               placeholder="v23.0">
                                        <small class="form-text text-muted">Graph API version, e.g. <code>v23.0</code>. Take the current value from Meta's changelog — Meta retires old versions on a schedule.</small>
                                        @error('facebook_conversion_api_version')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                
                                <!-- Custom Events -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="facebook_custom_events" class="form-label">Custom Events (JSON)</label>
                                        <textarea class="form-control @error('facebook_custom_events') is-invalid @enderror" 
                                                  name="facebook_custom_events" 
                                                  rows="3" 
                                                  placeholder='{"event_name": "custom_event", "parameters": {"custom_parameter": "value"}}'>{{ $settings->facebook_custom_events ?? '' }}</textarea>
                                        <small class="form-text text-muted">Custom events configuration in JSON format</small>
                                        @error('facebook_custom_events')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                
                                <!-- Server-Side Tracking Toggle -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="facebook_server_side_tracking" class="d-block">Enable Server-Side Tracking</label>
                                        <label class="switch">
                                            <input type="checkbox" value="1" name="facebook_server_side_tracking" 
                                                   @if($settings->facebook_server_side_tracking == 1) checked @endif>
                                            <span class="slider round"></span>
                                        </label>
                                        <small class="form-text text-muted">Enable Facebook Conversion API tracking</small>
                                    </div>
                                </div>
                                
                                <!-- Enhanced E-commerce Toggle -->
                                <div class="col-sm-6 mb-3">
                                    <div class="form-group">
                                        <label for="facebook_enhanced_ecommerce" class="d-block">Enable Enhanced E-commerce</label>
                                        <label class="switch">
                                            <input type="checkbox" value="1" name="facebook_enhanced_ecommerce" 
                                                   @if($settings->facebook_enhanced_ecommerce == 1) checked @endif>
                                            <span class="slider round"></span>
                                        </label>
                                        <small class="form-text text-muted">Enable enhanced e-commerce event tracking</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <x-admin.button variant="info" type="button" onclick="testTracking()">
                                            <i class="fas fa-play me-2"></i>Test Configuration
                                        </x-admin.button>
                                        <div>
                                            <x-admin.button variant="secondary" class="me-2" type="button" onclick="window.history.back()">
                                                <i class="fas fa-arrow-left me-2"></i>Back
                                            </x-admin.button>
                                            <x-admin.button variant="primary" type="submit">
                                                <i class="fas fa-save me-2"></i>Save Configuration
                                            </x-admin.button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Card -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Tracking Status</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="status-indicator" id="tracking-status">
                                        <i class="fas fa-circle text-muted"></i>
                                    </div>
                                    <h6 class="mt-2">Tracking Status</h6>
                                    <small class="text-muted" id="status-text">Checking...</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="status-indicator" id="pixel-status">
                                        <i class="fas fa-circle text-muted"></i>
                                    </div>
                                    <h6 class="mt-2">Pixel ID</h6>
                                    <small class="text-muted" id="pixel-text">Checking...</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="status-indicator" id="api-status">
                                        <i class="fas fa-circle text-muted"></i>
                                    </div>
                                    <h6 class="mt-2">API Token</h6>
                                    <small class="text-muted" id="api-text">Checking...</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <div class="status-indicator" id="server-status">
                                        <i class="fas fa-circle text-muted"></i>
                                    </div>
                                    <h6 class="mt-2">Server-Side</h6>
                                    <small class="text-muted" id="server-text">Checking...</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Test Result Modal -->
<div class="modal fade" id="testResultModal" tabindex="-1" aria-labelledby="testResultModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="testResultModalLabel">Test Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="testResultBody">
                <!-- Test result content will be inserted here -->
            </div>
            <div class="modal-footer">
                <x-admin.button variant="secondary" type="button" data-bs-dismiss="modal">Close</x-admin.button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
{{-- Facebook tracking status/test URLs live on the data attributes; the JS
     that reads them is public/admin/assets/js/admin-page-facebook-tracking.js. --}}
<div class="d-none" data-status-urls="{{ json_encode(['status' => route('facebook-tracking.status')]) }}"></div>
<script src="{{ asset('admin/assets/js/admin-page-facebook-tracking.js') }}"></script>
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-page-facebook-tracking-index.css') }}">
@endsection

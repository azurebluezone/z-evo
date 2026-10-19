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
                                        <input type="text" class="form-control @error('facebook_conversion_api_token') is-invalid @enderror" 
                                               name="facebook_conversion_api_token" 
                                               value="{{ $settings->facebook_conversion_api_token ?? '' }}" 
                                               placeholder="Enter your Conversion API token">
                                        <small class="form-text text-muted">Server-side access token for Conversion API</small>
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
                                        <select class="form-control @error('facebook_conversion_api_version') is-invalid @enderror" 
                                                name="facebook_conversion_api_version">
                                            <option value="v17.0" @if(($settings->facebook_conversion_api_version ?? 'v17.0') == 'v17.0') selected @endif>v17.0</option>
                                            <option value="v16.0" @if(($settings->facebook_conversion_api_version ?? 'v17.0') == 'v16.0') selected @endif>v16.0</option>
                                            <option value="v15.0" @if(($settings->facebook_conversion_api_version ?? 'v17.0') == 'v15.0') selected @endif>v15.0</option>
                                        </select>
                                        <small class="form-text text-muted">Facebook API version to use</small>
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
<script>
// Check tracking status on page load
document.addEventListener('DOMContentLoaded', function() {
    checkTrackingStatus();
});

// Function to check tracking status
function checkTrackingStatus() {
    fetch('{{ route("facebook-tracking.status") }}')
        .then(response => response.json())
        .then(data => {
            updateStatusIndicator('tracking-status', data.enabled, 'Tracking Enabled', 'Tracking Disabled');
            updateStatusIndicator('pixel-status', data.pixel_id, 'Pixel ID Set', 'Pixel ID Missing');
            updateStatusIndicator('api-status', data.api_version, 'API Configured', 'API Not Configured');
            updateStatusIndicator('server-status', data.server_side_tracking, 'Server-Side Enabled', 'Server-Side Disabled');
        })
        .catch(error => {
            console.error('Error checking status:', error);
        });
}

// Function to update status indicators
function updateStatusIndicator(elementId, condition, successText, failureText) {
    const element = document.getElementById(elementId);
    const textElement = document.getElementById(elementId.replace('-status', '-text'));
    
    if (condition) {
        element.innerHTML = '<i class="fas fa-circle text-success"></i>';
        textElement.textContent = successText;
    } else {
        element.innerHTML = '<i class="fas fa-circle text-danger"></i>';
        textElement.textContent = failureText;
    }
}

// Function to test tracking configuration
function testTracking() {
    const button = event.target;
    const originalText = button.innerHTML;
    
    // Show loading state
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Testing...';
    button.disabled = true;
    
    fetch('{{ route("facebook-tracking.test") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        // Show result in modal
        const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
        const modalBody = document.getElementById('testResultBody');
        
        if (data.success) {
            modalBody.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Success!</strong> ${data.message}
                </div>
            `;
        } else {
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>Error!</strong> ${data.message}
                </div>
            `;
        }
        
        modal.show();
    })
    .catch(error => {
        console.error('Error testing tracking:', error);
        
        // Show error in modal
        const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
        const modalBody = document.getElementById('testResultBody');
        
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Error!</strong> Failed to test tracking configuration. Please try again.
            </div>
        `;
        
        modal.show();
    })
    .finally(() => {
        // Restore button state
        button.innerHTML = originalText;
        button.disabled = false;
    });
}
</script>

<style>
.status-indicator {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.switch {
    position: relative;
    display: inline-block;
    width: 60px;
    height: 34px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
}

input:checked + .slider {
    background-color: #2196F3;
}

input:focus + .slider {
    box-shadow: 0 0 1px #2196F3;
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}
</style>
@endsection

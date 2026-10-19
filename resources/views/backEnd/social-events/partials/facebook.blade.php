<div class="row">
    <div class="col-lg-6 mb-3">
        <label class="form-label">Facebook Pixel ID</label>
        <input type="text" class="form-control" id="facebook_pixel_id" name="facebook_pixel_id" value="{{ $settings->facebook_pixel_id ?? '' }}" placeholder="e.g. 123456789012345">
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Conversion API Token (Server)</label>
        <div class="input-group">
            <input type="password" class="form-control" id="facebook_conversion_api_token" name="facebook_conversion_api_token" value="{{ $settings->facebook_conversion_api_token ?? '' }}" placeholder="token">
            <x-admin.button variant="outline-secondary" class="toggle-visibility" data-target="facebook_conversion_api_token" type="button"><i class="icon-base ri ri-eye-line"></i></x-admin.button>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Enable Server-Side Tracking</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" value="1" id="facebook_server_side_tracking" name="facebook_server_side_tracking" @if(($settings->facebook_server_side_tracking ?? 0) == 1) checked @endif>
            <label class="form-check-label" for="facebook_server_side_tracking"></label>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Enable Enhanced E-commerce</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" value="1" id="facebook_enhanced_ecommerce" name="facebook_enhanced_ecommerce" @if(($settings->facebook_enhanced_ecommerce ?? 0) == 1) checked @endif>
            <label class="form-check-label" for="facebook_enhanced_ecommerce"></label>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">API Version</label>
        <select class="form-control" name="facebook_conversion_api_version">
            <option value="v17.0" @if(($settings->facebook_conversion_api_version ?? 'v17.0') == 'v17.0') selected @endif>v17.0</option>
            <option value="v16.0" @if(($settings->facebook_conversion_api_version ?? 'v17.0') == 'v16.0') selected @endif>v16.0</option>
            <option value="v15.0" @if(($settings->facebook_conversion_api_version ?? 'v17.0') == 'v15.0') selected @endif>v15.0</option>
        </select>
    </div>

    <div class="col-lg-12 mb-3">
        <label class="form-label">Custom Events (JSON)</label>
        <textarea class="form-control" name="facebook_custom_events" rows="3">{{ $settings->facebook_custom_events ?? '' }}</textarea>
    </div>
</div>

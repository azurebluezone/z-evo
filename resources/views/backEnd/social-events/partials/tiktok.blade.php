<div class="row">
    <div class="col-lg-6 mb-3">
        <label class="form-label">TikTok Pixel ID (Browser)</label>
        <input type="text" class="form-control" name="tiktok_pixel_id" value="{{ $settings->tiktok_pixel_id ?? '' }}" placeholder="C123ABC">
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">TikTok Access Token (Server)</label>
        <div class="input-group">
            <input type="password" class="form-control" id="tiktok_access_token" name="tiktok_access_token" value="{{ $settings->tiktok_access_token ?? '' }}" placeholder="token">
            <x-admin.button variant="outline-secondary" class="toggle-visibility" data-target="tiktok_access_token" type="button"><i class="icon-base ri ri-eye-line"></i></x-admin.button>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Enable Browser Tracking</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" value="1" id="tiktok_browser_tracking" name="tiktok_browser_tracking" @if(($settings->tiktok_browser_tracking ?? 0) == 1) checked @endif>
            <label class="form-check-label" for="tiktok_browser_tracking"></label>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Enable Server-Side Tracking</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" value="1" id="tiktok_server_side_tracking" name="tiktok_server_side_tracking" @if(($settings->tiktok_server_side_tracking ?? 0) == 1) checked @endif>
            <label class="form-check-label" for="tiktok_server_side_tracking"></label>
        </div>
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Test Mode (Development)</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" value="1" id="tiktok_test_mode" name="tiktok_test_mode" @if(($settings->tiktok_test_mode ?? 0) == 1) checked @endif>
            <label class="form-check-label" for="tiktok_test_mode">Enable to test without sending real events</label>
        </div>
    </div>
</div>

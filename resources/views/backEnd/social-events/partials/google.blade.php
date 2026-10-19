<div class="row">
    <div class="col-lg-6 mb-3">
        <label class="form-label">Server Container ID</label>
        <input type="text" class="form-control" name="gtm_server_container_id" value="{{ $settings->gtm_server_container_id ?? '' }}" placeholder="GTM-XXXX">
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Server URL</label>
        <input type="text" class="form-control" name="gtm_server_url" value="{{ $settings->gtm_server_url ?? '' }}" placeholder="https://ss.yourdomain.com">
    </div>

    <div class="col-lg-6 mb-3">
        <label class="form-label">Enable Server-Side Tracking</label>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" value="1" id="gtm_server_side_tracking" name="gtm_server_side_tracking" @if(($settings->gtm_server_side_tracking ?? 0) == 1) checked @endif>
            <label class="form-check-label" for="gtm_server_side_tracking"></label>
        </div>
    </div>
</div>

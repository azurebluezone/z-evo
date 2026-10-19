<div class="row">
    <div class="col-lg-4 mb-3">
        <label class="form-label">WhatsApp Number</label>
        <input type="text" class="form-control" name="whatsapp_number" value="{{ $settings->whatsapp_number ?? '' }}" placeholder="8801XXXXXXXXX">
    </div>

    <div class="col-lg-4 mb-3">
        <label class="form-label">Messenger Link</label>
        <input type="text" class="form-control" name="messenger_link" value="{{ $settings->messenger_link ?? '' }}" placeholder="https://m.me/yourpage">
    </div>

    <div class="col-lg-4 mb-3">
        <label class="form-label">Support Phone</label>
        <input type="text" class="form-control" name="support_phone" value="{{ $settings->support_phone ?? '' }}" placeholder="+8801XXXXXXXXX">
    </div>
</div>

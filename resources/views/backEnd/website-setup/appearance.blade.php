@extends('backEnd.layouts.app')
@section('title','Website Appearance')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="mb-0">Website Appearance</h4>
                </div>

                <form action="{{ route('admin.website-setup.appearance.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Primary Color</label>
                            <input type="text" name="primary_color" class="form-control" value="{{ old('primary_color', $settings->primary_color ?? '') }}" placeholder="#f3532b">
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Secondary Color</label>
                            <input type="text" name="secondary_color" class="form-control" value="{{ old('secondary_color', $settings->secondary_color ?? '') }}" placeholder="#111827">
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label class="form-label">Call Button Number</label>
                            <input type="text" name="support_phone" class="form-control" value="{{ old('support_phone', $settings->support_phone ?? '') }}" placeholder="01XXXXXXXXX">
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label class="form-label">Messenger Link</label>
                            <input type="text" name="messenger_link" class="form-control" value="{{ old('messenger_link', $settings->messenger_link ?? '') }}" placeholder="https://m.me/yourpage">
                        </div>

                        <div class="col-lg-4 mb-3">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" class="form-control" value="{{ old('whatsapp_number', $settings->whatsapp_number ?? '') }}" placeholder="8801XXXXXXXXX">
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Frontend Empty/Fallback Image</label>
                            <input type="file" name="frontend_empty_image" class="form-control" accept="image/*">
                            @php
                                $fallbackImage = $settings->frontend_empty_image ?? 'assets/images/empty.png';
                                $fallbackImage = preg_replace('~^public/~', '', (string) $fallbackImage);
                                $fallbackImage = ltrim($fallbackImage, '/');
                                if (empty($fallbackImage) || !\Illuminate\Support\Facades\File::exists(public_path($fallbackImage))) {
                                    $fallbackImage = 'assets/images/empty.png';
                                }
                            @endphp
                            <div class="mt-2">
                                <img src="{{ asset($fallbackImage) }}" alt="Fallback" style="max-width: 220px; height: auto; border: 1px solid #eee; border-radius: 6px;">
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Order WhatsApp</label>
                            <div class="input-group">
                                <x-admin.button variant="outline-primary" type="button" id="orderWhatsAppToggleBtn">{{ old('order_whatsapp_enabled', $settings->order_whatsapp_enabled ?? false) ? 'Enabled' : 'Disabled' }}</x-admin.button>
                                <x-admin.button variant="outline-primary" class="dropdown-toggle dropdown-toggle-split" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="visually-hidden">Toggle Dropdown</span>
                                </x-admin.button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-order-whatsapp-enabled="1">Enabled</a></li>
                                    <li><a class="dropdown-item" href="javascript:void(0);" data-order-whatsapp-enabled="0">Disabled</a></li>
                                </ul>
                                <input type="text" class="form-control" name="order_whatsapp_number" value="{{ old('order_whatsapp_number', $settings->order_whatsapp_number ?? '') }}" placeholder="8801XXXXXXXXX" aria-label="Order WhatsApp number">
                                <input type="hidden" name="order_whatsapp_enabled" id="orderWhatsAppEnabled" value="{{ old('order_whatsapp_enabled', ($settings->order_whatsapp_enabled ?? false) ? 1 : 0) }}">
                            </div>
                        </div>
                    </div>

                    <div class="mt-2">
                        <x-admin.button variant="primary" type="submit">Save</x-admin.button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    (function () {
        try {
            var input = document.getElementById('orderWhatsAppEnabled');
            var btn = document.getElementById('orderWhatsAppToggleBtn');
            if (!input || !btn) return;

            function applyState(val) {
                input.value = String(val === 1 || val === '1' ? 1 : 0);
                btn.textContent = input.value === '1' ? 'Enabled' : 'Disabled';
            }

            document.addEventListener('click', function (e) {
                var el = e.target;
                if (!el) return;
                var item = el.closest ? el.closest('[data-order-whatsapp-enabled]') : null;
                if (!item) return;
                var v = item.getAttribute('data-order-whatsapp-enabled');
                applyState(v);
            }, true);

            applyState(input.value);
        } catch (e) {
        }
    })();
</script>
@endpush

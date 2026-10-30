{{--
    Accepted payment methods strip, shared by both template sets.

    One uploaded image wins when the admin has set
    general_settings.footer_payment_image and the file still exists.
    Otherwise the bundled brand PNGs are shown. Those three files live in
    public/admin/assets/img/icons/payments/ and were saved from the admin
    template's icon set (the copies under the template's own icons/ folder
    are HTML 404 pages, not images - verified 2026-10-31).
--}}
@php
    $pmImage = preg_replace('~^public/~', '', (string) ($generalsetting->footer_payment_image ?? ''));
    $pmImage = ltrim($pmImage, '/');
    $pmHasUpload = $pmImage !== '' && \Illuminate\Support\Facades\File::exists(public_path($pmImage));
@endphp

@if($pmHasUpload)
    <img src="{{ asset($pmImage) }}" alt="Accepted payment methods"
         style="max-height:32px;width:auto" loading="lazy">
@else
    <span class="d-inline-flex align-items-center gap-2" role="img" aria-label="Accepted payment methods">
        <img src="{{ asset('admin/assets/img/icons/payments/visa-light.png') }}" alt="Visa"
             style="height:24px;width:auto" loading="lazy">
        <img src="{{ asset('admin/assets/img/icons/payments/master-light.png') }}" alt="Mastercard"
             style="height:24px;width:auto" loading="lazy">
        <img src="{{ asset('admin/assets/img/icons/payments/ae-light.png') }}" alt="American Express"
             style="height:24px;width:auto" loading="lazy">
    </span>
@endif

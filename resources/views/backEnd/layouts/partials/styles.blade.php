<link rel="stylesheet" href="{{ asset('admin/assets/vendor/fonts/iconify-icons.css') }}" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" />
{{-- SweetAlert2: the confirm dialog admin-confirm.js shows. --}}
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/sweetalert2/sweetalert2.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/quill/typography.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/quill/katex.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/quill/editor.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/select2/select2.css') }}" />
<style>
    /* Toastr Text Color Fix */
    .toast-success { background-color: #28a745 !important; color: #fff !important; }
    .toast-success .toast-message, .toast-success .toast-title { color: #fff !important; }
    .toast-error { background-color: #dc3545 !important; color: #fff !important; }
    .toast-error .toast-message, .toast-error .toast-title { color: #fff !important; }
    .toast-warning { background-color: #ffc107 !important; color: #000 !important; }
    .toast-warning .toast-message, .toast-warning .toast-title { color: #000 !important; }
    .toast-info { background-color: #17a2b8 !important; color: #fff !important; }
    .toast-info .toast-message, .toast-info .toast-title { color: #fff !important; }
    #toast-container > .toast { opacity: 1 !important; }
</style>
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/node-waves/node-waves.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/pickr/pickr-themes.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/css/core.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/css/demo.css') }}" />
<link rel="stylesheet" href="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />

<!-- Admin responsive layer: keeps card toolbars, filters and tables usable on
     small screens. Must load after core.css / demo.css. -->
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-responsive.css') }}" />

<!-- Admin icon layer: restores the mask box for the legacy `ri-*` icons that
     never got the `.ri` base class, and pins the password-visibility control.
     Must load after iconify-icons.css. -->
<link rel="stylesheet" href="{{ asset('admin/assets/css/admin-icons.css') }}" />

@stack('css')
@stack('styles')

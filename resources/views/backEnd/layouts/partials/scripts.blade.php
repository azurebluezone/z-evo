<!-- Core JS -->
<script src="{{ asset('admin/assets/vendor/libs/jquery/jquery.js') }}"></script>

<script src="{{ asset('admin/assets/vendor/libs/popper/popper.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/js/bootstrap.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/quill/quill.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/quill/katex.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/node-waves/node-waves.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/@algolia/autocomplete-js.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/pickr/pickr.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/hammer/hammer.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/i18n/i18n.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/libs/select2/select2.js') }}"></script>
<script src="{{ asset('admin/assets/vendor/js/menu.js') }}"></script>
<!-- endbuild -->

<!-- Main JS -->
<script src="{{ asset('admin/assets/js/main.js') }}">
</script>











<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<!-- Shared admin behaviour: CSRF/Quill/Select2/repeater/courier/toastr/check-all (external, no inline blocks) -->
<script src="{{ asset('admin/assets/js/admin-app-shared.js') }}"></script>




{{--
    Destructive-action confirmation.

    SweetAlert2 + admin-confirm.js load ONCE, here, rather than per view, for
    two reasons: the admin markup already tags 59 destructive controls with
    .delete-confirm / .change-confirm, and no view should be able to grow a new
    one that forgets to include the guard; and 51 KB on a page that already
    spends ~400 KB on jQuery, Quill, Select2 and ApexCharts goes unnoticed.

    admin-confirm.js falls back to a native confirm() if SweetAlert2 is
    missing, so the guard never disappears silently.
--}}
<script src="{{ asset('admin/assets/vendor/libs/sweetalert2/sweetalert2.js') }}"></script>
<script src="{{ asset('admin/assets/js/admin-confirm.js') }}"></script>



{!! Toastr::message() !!}

@stack('js')
@stack('script')
@stack('scripts')

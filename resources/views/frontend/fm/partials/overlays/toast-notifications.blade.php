{{--
    FM:PARTIAL overlays/toast-notifications
    =========================================================================
    The single Bootstrap toast every front-end action reports into: fm-app.js
    writes the message into #toastMessage and calls .show() on #actionToast.

    One toast for the whole site is deliberate - a second one would need a second
    set of ids and a second lookup in JS, and there is only ever one message on
    screen at a time.

    Props
        (none)

    Usage
        @include('frontend.fm.partials.overlays.toast-notifications')
--}}

<!-- BEGIN: ToastNotifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
    <div aria-atomic="true" aria-live="assertive" class="toast align-items-center text-bg-success border-0 shadow-lg" id="actionToast" role="alert">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 text-xs font-semibold" id="toastMessage">
                <i class="bi bi-check-circle-fill text-base"></i> Added to cart!
            </div>
            <button aria-label="Close" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" type="button"></button>
        </div>
    </div>
</div>
<!-- END: ToastNotifications -->
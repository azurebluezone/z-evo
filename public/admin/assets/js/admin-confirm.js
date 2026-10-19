/* ==========================================================================
   admin-confirm.js - "are you sure?" for every destructive admin action.
   --------------------------------------------------------------------------
   WHY THIS FILE EXISTS
   The admin markup has carried `delete-confirm` and `change-confirm` class
   hooks for years: 33 delete buttons and 26 state toggles were tagged with
   them. Nothing ever listened. A shopkeeper clicking the red bin once by
   accident destroyed the record on the spot - there was no dialog between the
   click and the POST.

   Adding the class was always half the job. This file is the other half.

   HOW IT WORKS
   One delegated listener on document, rather than a handler per button:
   list rows are rendered by blade, by modals and (from Phase 2) by DataTables
   ajax, so a handler bound at page load would miss anything drawn later.

   A button opts in with a class and may describe itself with data-*:

       <button class="delete-confirm"
               data-confirm-title="Delete this product?"
               data-confirm-text="It cannot be undone."
               data-confirm-ok="Yes, delete"
               data-confirm-cancel="Keep it">…</button>

   Without the data-* attributes the defaults below are used, so every
   existing tagged button starts working without being touched.

   The form is submitted with requestSubmit(), not .submit(), so any
   validation or submit listener the form already has still runs - the
   dialog confirms the action, it does not replace it.
   ========================================================================== */
(function () {
    'use strict';

    var DEFAULTS = {
        delete: {
            title: 'Delete this record?',
            text: 'This cannot be undone.',
            ok: 'Yes, delete',
            cancel: 'Keep it',
            icon: 'warning'
        },
        change: {
            title: 'Are you sure?',
            text: '',
            ok: 'Yes, continue',
            cancel: 'No, go back',
            icon: 'question'
        }
    };

    function messageFor(el) {
        var base = el.classList.contains('delete-confirm') ? DEFAULTS.delete : DEFAULTS.change;
        var ds = el.dataset || {};

        return {
            title: ds.confirmTitle || base.title,
            text: typeof ds.confirmText !== 'undefined' ? ds.confirmText : base.text,
            ok: ds.confirmOk || base.ok,
            cancel: ds.confirmCancel || base.cancel,
            icon: base.icon
        };
    }

    /** The form this control belongs to, if any. */
    function formOf(el) {
        var form = el.closest ? el.closest('form') : null;
        if (form) return form;

        // Several toggles submit a form by id from an inline onclick, e.g.
        // document.getElementById('hotDealToggleForm').submit(). The class
        // still has to guard them, so look the form up by that reference.
        var inline = el.getAttribute('onclick') || '';
        var id = inline.match(/getElementById\(['"]([^'"]+)['"]\)/);
        return id ? document.getElementById(id[1]) : null;
    }

    document.addEventListener('click', function (event) {
        var el = event.target && event.target.closest
            ? event.target.closest('.delete-confirm, .change-confirm')
            : null;
        if (!el) return;

        var form = formOf(el);

        // Nothing to guard (a link, say, with no form behind it): let it be.
        if (!form) return;

        event.preventDefault();
        event.stopPropagation();

        if (typeof window.Swal === 'undefined') {
            // SweetAlert2 failed to load. A plain confirm still stops the
            // accident - degrading is right here; silently submitting is not.
            if (window.confirm(messageFor(el).title)) form.requestSubmit();
            return;
        }

        var msg = messageFor(el);
        window.Swal.fire({
            title: msg.title,
            text: msg.text,
            icon: msg.icon,
            showCancelButton: true,
            confirmButtonText: msg.ok,
            cancelButtonText: msg.cancel,
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) {
            if (result.isConfirmed) form.requestSubmit();
        });
    }, true);
})();
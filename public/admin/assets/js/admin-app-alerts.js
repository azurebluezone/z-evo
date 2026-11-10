/* Global admin notifications + modals.
   Moved out of backEnd/layouts/app.blade.php so the view carries no inline JS.
   Server-side flash values (session + validation errors) ride in on the
   <body> element as JSON data attributes rendered by the blade:
     data-flash-success / data-flash-error / data-flash-warning /
     data-flash-info / data-flash-validation  (all JSON or "null")
   "null" or empty string means "nothing to tell".

   Toastr::message() (the blade package in partials/scripts) may still emit
   its own toast for session flashes - that double-draw predates this module
   and is pending controller-side cleanup.
*/
(function () {
    'use strict';

    // Bazar Logic: Holistic Notification Trigger - Using Toastr
    function showLiveToast(type, title, message) {
        if (typeof toastr === 'undefined') return;

        switch (type) {
            case 'success': toastr.success(message, title); break;
            case 'error':   toastr.error(message, title); break;
            case 'warning': toastr.warning(message, title); break;
            default:        toastr.info(message, title);
        }
    }
    window.showLiveToast = showLiveToast;

    // ===== Global Modal Helper Functions =====

    function showSuccessModal(message) {
        var modalEl = document.getElementById('successModal');
        if (modalEl) {
            if (message) document.getElementById('successModalMessage').textContent = message;
            new bootstrap.Modal(modalEl).show();
        }
    }
    window.showSuccessModal = showSuccessModal;

    function showErrorModal(message) {
        var modalEl = document.getElementById('errorModal');
        if (modalEl) {
            if (message) document.getElementById('errorModalMessage').textContent = message;
            new bootstrap.Modal(modalEl).show();
        }
    }
    window.showErrorModal = showErrorModal;

    function showBasicModal(title, bodyContent, saveCallback) {
        var modalEl = document.getElementById('basicModal');
        if (modalEl) {
            if (title) document.getElementById('basicModalTitle').textContent = title;
            if (bodyContent) document.getElementById('basicModalBody').innerHTML = bodyContent;

            var saveBtn = document.getElementById('basicModalSave');
            if (saveCallback) {
                saveBtn.onclick = function () {
                    saveCallback();
                    bootstrap.Modal.getInstance(modalEl).hide();
                };
            }
            new bootstrap.Modal(modalEl).show();
        }
    }
    window.showBasicModal = showBasicModal;

    function showLargeModal(title, bodyContent) {
        var modalEl = document.getElementById('largeModal');
        if (modalEl) {
            if (title) document.getElementById('largeModalTitle').textContent = title;
            if (bodyContent) document.getElementById('largeModalBody').innerHTML = bodyContent;
            new bootstrap.Modal(modalEl).show();
        }
    }
    window.showLargeModal = showLargeModal;

    function showScrollableModal(title, bodyContent) {
        var modalEl = document.getElementById('scrollModal');
        if (modalEl) {
            if (title) document.getElementById('scrollModalTitle').textContent = title;
            if (bodyContent) document.getElementById('scrollModalBody').innerHTML = bodyContent;
            new bootstrap.Modal(modalEl).show();
        }
    }
    window.showScrollableModal = showScrollableModal;

    // ===== Global Delete Modal Handler =====
    var activeDeleteFormId = null;
    function triggerDeleteModal(formId) {
        activeDeleteFormId = formId;
        var modalEl = document.getElementById('deleteModal');
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        } else {
            console.error('Delete modal element not found in DOM.');
        }
    }
    window.triggerDeleteModal = triggerDeleteModal;

    document.addEventListener('DOMContentLoaded', function () {
        var globalBtn = document.getElementById('globalConfirmDeleteBtn');
        if (globalBtn) {
            globalBtn.addEventListener('click', function () {
                if (activeDeleteFormId) {
                    var form = document.getElementById(activeDeleteFormId);
                    if (form) form.submit();
                }
            });
        }

        // Server-side flashes. json_encode(null) plus e() gives the literal
        // "null" when a flash is absent, and a decodable JSON string when set.
        function flash(name) {
            var raw = document.body.getAttribute(name);
            if (!raw || raw === 'null') return null;
            try {
                var value = JSON.parse(raw);
                return (typeof value === 'string' && value.length) ? value : value;
            } catch (e) {
                return null;
            }
        }

        var success = flash('data-flash-success');
        if (success) showLiveToast('success', 'Success', success);
        var error = flash('data-flash-error');
        if (error) showLiveToast('error', 'Error', error);
        var warning = flash('data-flash-warning');
        if (warning) showLiveToast('warning', 'Warning', warning);
        var info = flash('data-flash-info');
        if (info) showLiveToast('info', 'Info', info);
        var validation = flash('data-flash-validation');
        if (validation) showLiveToast('error', 'Validation Error', validation);
    });
})();

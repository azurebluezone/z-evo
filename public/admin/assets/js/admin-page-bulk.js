/* Shared bulk selection behaviour for the user-submission index pages
   (contacts, newsletter, service-requests).

   The page wires ONE data attribute on its select-all checkbox:
     data-bulk-group="contacts"
   From the group prefix the code derives the legacy DOM contracts that
   already exist in the markup:
     <prefix>-check-all, <prefix>-check rows,
     <prefix>-bulk-delete-btn, <prefix>-bulk-delete-form,
     and a bulk-notify form submitted from the header.
   Moved from the three identical inline scripts. */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var checkAll = document.querySelector('[data-bulk-group]');
        if (!checkAll) return;

        var prefix = checkAll.getAttribute('data-bulk-group');
        var bulkDeleteBtn = document.getElementById(prefix + '-bulk-delete-btn');
        var bulkDeleteForm = document.getElementById(prefix + '-bulk-delete-form');
        var rowSelector = '.' + prefix + '-check';
        if (!bulkDeleteBtn || !bulkDeleteForm) return;

        var items = function () {
            return Array.prototype.slice.call(document.querySelectorAll(rowSelector));
        };

        var fillIds = function (form, selected) {
            form.querySelectorAll('input[name="ids[]"]').forEach(function (node) { node.remove(); });
            selected.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
        };

        var selectedIds = function () {
            return items().filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
        };

        checkAll.addEventListener('change', function () {
            items().forEach(function (cb) { cb.checked = checkAll.checked; });
        });

        bulkDeleteBtn.addEventListener('click', function () {
            var selected = selectedIds();
            if (!selected.length) return;
            fillIds(bulkDeleteForm, selected);
            bulkDeleteForm.submit();
        });

        /*
            The Send button lives in a separate form (the header), so the selected
            ids have to be copied into it before its own submit proceeds.
        */
        var notifyForm = document.querySelector('[data-bulk-notify-form]');
        if (notifyForm) {
            notifyForm.addEventListener('submit', function (e) {
                var selected = selectedIds();
                if (!selected.length) {
                    e.preventDefault();
                    return;
                }
                fillIds(notifyForm, selected);
            });
        }
    });
})();

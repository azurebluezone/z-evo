/* Visitors blocked page: bulk unblock + info modal + select-all.
   Moved from backEnd/visitors/blocked.blade.php; the unblock route URL is
   read from #bulkUnblockBtn's data-action attribute. */
    (function () {
        function updateBulkButtons() {
            const checked = document.querySelectorAll('.row-check:checked').length;
            const unblockBtn = document.getElementById('bulkUnblockBtn');
            if (unblockBtn) unblockBtn.disabled = checked < 1;
        }

        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkButtons();
            });
        }

        document.querySelectorAll('.row-check').forEach(cb => {
            cb.addEventListener('change', function () {
                const all = document.querySelectorAll('.row-check').length;
                const checked = document.querySelectorAll('.row-check:checked').length;
                const selectAll = document.getElementById('selectAll');
                if (selectAll) {
                    selectAll.checked = all > 0 && checked === all;
                    selectAll.indeterminate = checked > 0 && checked < all;
                }
                updateBulkButtons();
            });
        });

        const bulkForm = document.getElementById('bulkActionForm');
        const bulkUnblockBtn = document.getElementById('bulkUnblockBtn');
        if (bulkForm && bulkUnblockBtn) {
            bulkUnblockBtn.addEventListener('click', function () {
                bulkForm.action = document.getElementById('bulkUnblockBtn').getAttribute('data-action');
                bulkForm.submit();
            });
        }

        const modalEl = document.getElementById('visitorInfoModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const title = button?.getAttribute('data-title') || 'Info';
                const value = button?.getAttribute('data-value') || '';
                const titleEl = document.getElementById('visitorInfoModalTitle');
                const valueEl = document.getElementById('visitorInfoModalValue');
                if (titleEl) titleEl.textContent = title;
                if (valueEl) valueEl.textContent = value;
            });
        }

        updateBulkButtons();
    })();

/* Visitors index: bulk block/unblock + info modal + select-all.
   Moved from backEnd/visitors/visitors.blade.php; the two route URLs are
   read from the buttons' data-action attributes. */
    (function () {
        'use strict';

        // Store visitor data for selected rows
        const visitorData = {};
        document.querySelectorAll('.row-check').forEach(cb => {
            const row = cb.closest('tr');
            if (row) {
                const cells = row.querySelectorAll('td');
                visitorData[cb.value] = {
                    id: cb.value,
                    visitorId: cells[2]?.textContent?.trim() || 'N/A',
                    ip: cells[3]?.textContent?.trim() || 'N/A',
                    location: cells[4]?.textContent?.trim() || 'N/A',
                    isp: cells[5]?.textContent?.trim() || 'N/A',
                    device: cells[6]?.textContent?.trim() || 'N/A',
                    channel: cells[7]?.textContent?.trim() || 'N/A',
                    status: cells[8]?.textContent?.trim() || 'N/A',
                    date: cells[9]?.textContent?.trim() || 'N/A'
                };
            }
        });

        // Update bulk action buttons state
        function updateBulkButtons() {
            const checked = document.querySelectorAll('.row-check:checked');
            const checkedCount = checked.length;
            const blockBtn = document.getElementById('bulkBlockBtn');
            const unblockBtn = document.getElementById('bulkUnblockBtn');
            const infoBtn = document.getElementById('visitorInfoBtn');

            if (blockBtn) blockBtn.disabled = checkedCount < 1;
            if (unblockBtn) unblockBtn.disabled = checkedCount < 1;
            if (infoBtn) infoBtn.disabled = checkedCount !== 1; // Only enable when exactly 1 selected

            // Update button text with count
            if (checkedCount > 0) {
                if (blockBtn) blockBtn.innerHTML = '<i class="icon-base ri ri-shield-cross-line me-2"></i>Block (' + checkedCount + ')';
                if (unblockBtn) unblockBtn.innerHTML = '<i class="icon-base ri ri-shield-check-line me-2"></i>Unblock (' + checkedCount + ')';
                if (infoBtn && checkedCount === 1) {
                    const id = checked[0].value;
                    const data = visitorData[id];
                    if (data) {
                        infoBtn.setAttribute('data-title', 'Visitor Info');
                        infoBtn.setAttribute('data-value',
                            'ID: ' + data.visitorId + '\n' +
                            'IP: ' + data.ip + '\n' +
                            'Location: ' + data.location + '\n' +
                            'ISP: ' + data.isp + '\n' +
                            'Device: ' + data.device + '\n' +
                            'Channel: ' + data.channel + '\n' +
                            'Status: ' + data.status + '\n' +
                            'Date: ' + data.date
                        );
                    }
                }
            } else {
                if (blockBtn) blockBtn.innerHTML = '<i class="icon-base ri ri-shield-cross-line me-2"></i>Block';
                if (unblockBtn) unblockBtn.innerHTML = '<i class="icon-base ri ri-shield-check-line me-2"></i>Unblock';
                if (infoBtn) {
                    infoBtn.innerHTML = '<i class="icon-base ri ri-eye-line me-2"></i>Visitor Info';
                    infoBtn.removeAttribute('data-value');
                }
            }
        }

        // Select All Checkbox
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.row-check').forEach(cb => {
                    cb.checked = selectAll.checked;
                });
                updateBulkButtons();
            });
        }

        // Individual Row Checkboxes
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

        // Bulk Block Button
        const bulkForm = document.getElementById('bulkActionForm');
        const bulkBlockBtn = document.getElementById('bulkBlockBtn');
        const bulkUnblockBtn = document.getElementById('bulkUnblockBtn');

        if (bulkForm && bulkBlockBtn) {
            bulkBlockBtn.addEventListener('click', function () {
                if (confirm('Are you sure you want to block selected visitors?')) {
                    bulkForm.action = bulkBlockBtn.getAttribute('data-action');
                    bulkForm.submit();
                }
            });
        }

        // Bulk Unblock Button
        if (bulkForm && bulkUnblockBtn) {
            bulkUnblockBtn.addEventListener('click', function () {
                if (confirm('Are you sure you want to unblock selected visitors?')) {
                    bulkForm.action = bulkUnblockBtn.getAttribute('data-action');
                    bulkForm.submit();
                }
            });
        }

        // Modal Data Handling
        const modalEl = document.getElementById('visitorInfoModal');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const title = button?.getAttribute('data-title') || 'Info';
                const value = button?.getAttribute('data-value') || '';
                const titleEl = document.getElementById('visitorInfoModalTitle');
                const valueEl = document.getElementById('visitorInfoModalValue');

                if (titleEl) titleEl.innerHTML = '<i class="icon-base ri ri-user-line me-2"></i>' + title;
                if (valueEl) valueEl.textContent = value;
            });
        }

        // Initialize
        updateBulkButtons();
    })();

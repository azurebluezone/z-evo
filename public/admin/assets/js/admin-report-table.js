/* Print helper for the admin report tables (stock, order).
   The page wires the table through a data attribute on the script tag:
     data-report-table="stock-report-table"  - DOM id of the report table
   Print: window.print(); the scoping lives in admin-report.css.
   Export is a server-side download link now (real XLSX/CSV over the whole
   filter, see ReportExportController), so this file no longer serialises the
   visible table to CSV. */
(function () {
    'use strict';

    var me = document.currentScript;
    if (!me || !me.getAttribute('data-report-table')) return;

    var printBtn = document.getElementById('print-report');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }
})();

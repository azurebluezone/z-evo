/* Print + CSV export for the admin report tables (stock, order).
   The page wires the script through data attributes on the script tag:
     data-report-table="stock-report-table"  - DOM id of the report table
     data-report-export="stock-report"       - download filename prefix
   Print: window.print(); the scoping lives in admin-report.css.
   Export: the table is serialised to CSV on the client and downloaded
   as a Blob - no plugin, no network call. */
(function () {
    'use strict';

    var me = document.currentScript;
    if (!me || !me.getAttribute('data-report-table')) return;

    var tableId = me.getAttribute('data-report-table');
    var exportName = me.getAttribute('data-report-export') || 'report';

    function csvRows(table) {
        var rows = [];
        var trs = table.querySelectorAll('tr');

        for (var i = 0; i < trs.length; i++) {
            var cells = trs[i].querySelectorAll('th, td');
            if (!cells.length) continue;

            var row = [];
            for (var j = 0; j < cells.length; j++) {
                var text = (cells[j].innerText || '').replace(/\s+/g, ' ').trim();
                row.push('"' + text.replace(/"/g, '""') + '"');
            }
            rows.push(row.join(','));
        }
        return rows;
    }

    var printBtn = document.getElementById('print-report');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    var exportBtn = document.getElementById('export-report');
    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            var table = document.getElementById(tableId);
            if (!table) return;

            var rows = csvRows(table);
            if (!rows.length) return;

            // \uFEFF BOM so Excel opens the UTF-8 file with the right encoding.
            var blob = new Blob(['\uFEFF' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.href = url;
            link.download = exportName + '-' + new Date().toISOString().slice(0, 10) + '.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
        });
    }
})();

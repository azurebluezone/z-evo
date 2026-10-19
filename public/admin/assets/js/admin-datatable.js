/* ==========================================================================
   admin-datatable.js - every admin list screen, one grid.

   The page marks its table with data-fm-dt and describes itself through
   data-* attributes (see backEnd/partials/datatable.blade.php):

       <table data-fm-dt
              data-fm-dt-url="..."          JSON endpoint
              data-fm-dt-params='{...}'     extra filters forwarded on every draw
              data-fm-dt-columns='[ {...} ]' per column: orderable/searchable/class
              data-fm-dt-order='[[1,"desc"]]'
              data-fm-dt-empty="No products yet.">

   Keeping this in a file rather than inline per page matters: the grid's
   behaviour is the same on thirty screens, so it should be downloaded once and
   cached once.

   ROW DATA IS HTML
   ----------------
   The endpoint returns one array entry per row and one element per column,
   already rendered. That is deliberate - an order row carries an invoice
   link, an avatar, a status select and a delete form, and re-authoring that
   in javascript would mean two implementations of one row that drift apart.
   DataTables inserts string values as HTML, so a cell's markup arrives as
   written by the controller.
   ========================================================================== */
(function () {
    'use strict';

    function readJson(el, name, fallback) {
        try {
            return el.dataset[name] ? JSON.parse(el.dataset[name]) : fallback;
        } catch (e) {
            console.warn('admin-datatable: could not read data-fm-dt-' + name, e);
            return fallback;
        }
    }

    function build(table) {
        if (!window.DataTable || table.__fmDataTable) return;

        /*
         * data-fm-dt-columns becomes dataset.fmDtColumns - the dashes are
         * camelCased and the "fm-dt-" prefix is kept. Reading dataset.columns
         * returns undefined, the column config silently falls back to an empty
         * array, and DataTables then initialises with fewer columns than the
         * table has headers: it throws "Cannot read properties of undefined
         * (reading 'sClass')" and the grid comes up empty.
         */
        var specs = readJson(table, 'fmDtColumns', []);
        var params = readJson(table, 'fmDtParams', {});
        var order = readJson(table, 'fmDtOrder', []);
        var pageLength = parseInt(table.dataset.fmDtPageLength || '20', 10);

        var columns = specs.map(function (spec, index) {
            return {
                // data: the array index. It must NOT be null - DataTables then
                // treats the whole row array as one value and comma-joins it
                // into a single cell.
                data: index,
                orderable: spec.orderable !== false,
                searchable: spec.searchable !== false,
                className: spec.className || ''
            };
        });

        table.__fmDataTable = new DataTable(table, {
            serverSide: true,
            processing: true,
            pageLength: pageLength,
            lengthMenu: [[10, 20, 50, 100], [10, 20, 50, 100]],
            order: order,
            searchDelay: 350,
            ajax: {
                url: table.dataset.fmDtUrl,
                type: 'GET',
                data: function (d) {
                    Object.keys(params).forEach(function (key) {
                        d[key] = params[key];
                    });
                }
            },
            columns: columns,
            /*
             * Layout. f = search box, l = page length, t = table,
             * r = the processing indicator, i = "showing x of y", p = pager.
             * Without this DataTables prints its default pager with no card
             * around it and the row of controls lands in an odd place.
             */
            dom: '<"row"<"col-12 col-md-6"f><"col-12 col-md-6"l>>' +
                 'rt<"row mt-3"<"col-12 col-md-6"i><"col-12 col-md-6"p>>',
            language: {
                emptyTable: table.dataset.fmDtEmpty || 'Nothing here yet.',
                zeroRecords: 'Nothing matches those filters.',
                info: 'Showing _START_ to _END_ of _TOTAL_'
            },
            drawCallback: function () {
                /*
                 * Rows are new markup after every draw, so any handler bound
                 * directly to a row is gone. Two things have to be re-applied:
                 * the bootstrap tooltips (this theme does not delegate them)
                 * and the "check all" boxes - admin-confirm.js already
                 * delegates, and the order page delegates its own handlers,
                 * but the header checkbox is re-marked here so a screen that
                 * did not bother still works.
                 */
                if (window.bootstrap && window.bootstrap.Tooltip) {
                    table.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
                        if (!window.bootstrap.Tooltip.getInstance(el)) {
                            new bootstrap.Tooltip(el);
                        }
                    });
                }

                var head = table.querySelector('.checkall');
                if (head) {
                    head.checked = false;
                    head.indeterminate = false;
                }
            }
        });
    }

    function init() {
        document.querySelectorAll('table[data-fm-dt]').forEach(build);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
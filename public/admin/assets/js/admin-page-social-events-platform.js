(function() {
    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.DataTable === 'function') {
        window.jQuery(function() {
            window.jQuery('#platform-logs-table').DataTable({
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });
    }
})();

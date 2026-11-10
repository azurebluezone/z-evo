/* Social events dashboard: events-overview + events-timeline charts.
   Moved from the two social-events blades - server data arrives as JSON data
   attributes rendered by the blade (data-social-overview / data-social-timeline).
   The .view-url click handler (URL reveal modal) lives here too. */
(function () {
    'use strict';

    function read(cfgAttr, attr) {
        var el = document.querySelector('[' + attr + ']');
        if (!el) return null;
        try { return JSON.parse(el.getAttribute(attr)); } catch (e) { return null; }
    }

    var overview = read('data-social-overview', 'data-social-overview');

    if (typeof window.ApexCharts === 'function') {
        if (overview) {
            // blade owns the ??: array_values($allowedEvents) / $eventCounts
            var labels = overview.labels || [];
            var dataMap = overview.counts || {};
            var series = labels.map(function (k) {
                return Number(dataMap && dataMap[k] ? dataMap[k] : 0);
            });

            var el = document.querySelector('#events-overview');
            if (el) {
                var options = {
                    chart: { type: 'bar', height: 260, toolbar: { show: false } },
                    series: [{ name: 'Events', data: series }],
                    xaxis: { categories: labels },
                    dataLabels: { enabled: false },
                    plotOptions: { bar: { borderRadius: 6, columnWidth: '45%' } }
                };
                try { (new ApexCharts(el, options)).render(); } catch (e) { }
            }
        }

        var timeline = read('data-social-timeline', 'data-social-timeline');
        if (timeline) {
            var el = document.querySelector('#events-timeline');
            if (el) {
                var options = {
                    chart: { type: 'area', height: 260, toolbar: { show: false } },
                    series: [{ name: 'Events', data: timeline.series || [] }],
                    xaxis: { categories: timeline.labels || [] },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient' }
                };
                try { (new ApexCharts(el, options)).render(); } catch (e) { }
            }
        }
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.view-url');
        if (!btn) return;
        e.preventDefault();
        var url = btn.getAttribute('data-url') || '';
        if (!url) return;
        if (window.Swal && window.Swal.fire) {
            window.Swal.fire({ title: 'URL', text: url, icon: 'info', confirmButtonText: 'OK' });
        } else {
            alert(url);
        }
    });
})();

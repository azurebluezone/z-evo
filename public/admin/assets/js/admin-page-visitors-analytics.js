/* Visitor analytics: real ApexCharts line for traffic, real Leaflet map for geo.
 *
 * Both payloads are rendered into <script type="application/json"> by Blade, so
 * the page needs no extra AJAX endpoint and the chart can never disagree with
 * the cards: they were built from the same query in VisitorManageController.
 *
 * Nothing here invents data. When the series is empty the chart says so, and
 * when no visitor has coordinates the map is replaced by an honest empty state
 * instead of a decorative gradient.
 */
(function () {
    'use strict';

    function readJson(id) {
        var node = document.getElementById(id);

        if (!node) {
            return null;
        }

        try {
            return JSON.parse(node.textContent || '{}');
        } catch (error) {
            return null;
        }
    }

    var ranges = readJson('visitorAnalyticsChartData');
    var chartEl = document.getElementById('trafficChart');

    // ------------------------------------------------------------- ApexCharts
    var chart = null;
    var activeRange = 'daily';

    function seriesFor(range) {
        var bucket = ranges && ranges[range];

        if (!bucket || !Array.isArray(bucket.data)) {
            return { labels: [], data: [], total: 0 };
        }

        return bucket;
    }

    function summaryText(range, total) {
        var label = range === 'daily' ? 'day' : (range === 'weekly' ? 'week' : 'month');
        return total.toLocaleString('en-US') + ' visitors, ' + label + ' by ' + label;
    }

    function drawChart() {
        if (!chartEl) {
            return;
        }

        var bucket = seriesFor(activeRange);

        if (typeof window.ApexCharts !== 'function') {
            chartEl.textContent = 'ApexCharts did not load, so the chart cannot be drawn.';
            return;
        }

        if (chart) {
            chart.updateOptions({
                series: [{ name: 'Visitors', data: bucket.data }],
                xaxis: { categories: bucket.labels }
            });
            return;
        }

        chart = new ApexCharts(chartEl, {
            chart: {
                type: 'area',
                height: 380,
                parentHeightOffset: 0,
                toolbar: { show: false },
                zoom: { enabled: false },
                fontFamily: 'inherit'
            },
            series: [{ name: 'Visitors', data: bucket.data }],
            colors: ['#696cff'],
            stroke: { curve: 'smooth', width: 2, lineCap: 'round' },
            fill: {
                type: 'gradient',
                gradient: { shadeIntensity: 0.4, opacityFrom: 0.45, opacityTo: 0.04, stops: [0, 90, 100] }
            },
            markers: { size: 0, hover: { size: 5 } },
            dataLabels: { enabled: false },
            grid: { strokeDashArray: 6, borderColor: 'rgba(0,0,0,.08)', padding: { left: 8, right: 8 } },
            xaxis: {
                categories: bucket.labels,
                axisBorder: { show: false },
                axisTicks: { show: false },
                labels: { rotate: -45, rotateAlways: bucket.labels.length > 14, hideOverlappingLabels: true, trim: true }
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    formatter: function (value) {
                        return Math.round(value).toLocaleString('en-US');
                    }
                }
            },
            tooltip: {
                shared: true,
                y: {
                    formatter: function (value) {
                        return Number(value).toLocaleString('en-US') + ' visitors';
                    }
                }
            },
            noData: { text: 'No visitors in this range' }
        });

        chart.render();
    }

    function setRange(range, button) {
        if (!ranges || !ranges[range]) {
            return;
        }

        activeRange = range;
        drawChart();

        var buttons = document.querySelectorAll('[data-chart-range]');
        buttons.forEach(function (item) {
            var isActive = item === button;
            item.classList.toggle('active', isActive);
            item.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });

        var summary = document.getElementById('trafficChartSummary');
        if (summary) {
            summary.textContent = summaryText(range, seriesFor(range).total || 0);
        }
    }

    document.querySelectorAll('[data-chart-range]').forEach(function (button) {
        button.addEventListener('click', function () {
            setRange(button.getAttribute('data-chart-range'), button);
        });
    });

    if (ranges && chartEl) {
        var summary = document.getElementById('trafficChartSummary');
        if (summary) {
            summary.textContent = summaryText('daily', seriesFor('daily').total || 0);
        }
        drawChart();
    }

    // ----------------------------------------------------------------- Leaflet
    var mapData = readJson('visitorAnalyticsMapData');
    var mapEl = document.getElementById('visitorMap');
    var emptyEl = document.getElementById('visitorMapEmpty');

    function drawMap() {
        if (!mapEl) {
            return;
        }

        var points = (mapData && Array.isArray(mapData.points)) ? mapData.points : [];

        if (typeof window.L === 'undefined') {
            if (emptyEl) {
                emptyEl.classList.remove('d-none');
                emptyEl.querySelector('span').textContent =
                    'Leaflet did not load, so the map cannot be drawn.';
            }
            return;
        }

        // The map is real even when there is nothing to plot: it starts on
        // Bangladesh so the admin can see the area the site serves, and the
        // note below says why no marker is present. Replacing it with a
        // gradient is what this page used to do, and it hid the missing data.
        var map = L.map(mapEl, {
            scrollWheelZoom: false,
            worldCopyJump: true
        }).setView([23.7, 90.35], 7);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
        }).addTo(map);

        if (emptyEl) {
            emptyEl.classList.toggle('d-none', points.length > 0);
        }

        var max = points.reduce(function (carry, point) {
            return Math.max(carry, Number(point.count) || 0);
        }, 1);

        var bounds = [];

        points.forEach(function (point) {
            var lat = Number(point.lat);
            var lng = Number(point.lng);

            if (!isFinite(lat) || !isFinite(lng)) {
                return;
            }

            var count = Number(point.count) || 0;
            var radius = 7 + 16 * Math.sqrt(count / max);

            L.circleMarker([lat, lng], {
                radius: radius,
                color: '#696cff',
                weight: 2,
                fillColor: '#696cff',
                fillOpacity: 0.2 + 0.45 * (count / max)
            })
                .bindTooltip((point.label || 'Unknown') + ' — ' + count.toLocaleString('en-US') + ' visitors')
                .addTo(map);

            bounds.push([lat, lng]);
        });

        if (bounds.length > 1) {
            map.fitBounds(bounds, { padding: [30, 30], maxZoom: 9 });
        } else if (bounds.length === 1) {
            map.setView(bounds[0], 9);
        }

        // The card may have been measured while the tab was hidden.
        window.setTimeout(function () { map.invalidateSize(); }, 0);
    }

    drawMap();
})();

<script>
    /**
     * Analytics dashboard behaviour.
     *
     * Charts use the ApexCharts build already loaded globally by the admin
     * layout (public/admin/assets/vendor/libs/apex-charts/apexcharts.js), so no
     * extra dependency is pulled in here. The journey flow is rendered in plain
     * CSS by the Blade view — no charting library can draw a Sankey properly.
     */
    (function () {
        'use strict';

        var CFG = window.__ANALYTICS__ || {};
        var chartInstances = [];

        function renderCharts() {
            if (typeof ApexCharts === 'undefined') {
                return;
            }

            // --- Trend -------------------------------------------------------------
            var trendEl = document.getElementById('analytics-trend');
            var series = (CFG.trend && CFG.trend.series) || [];

            if (trendEl) {
                var trendChart = new ApexCharts(trendEl, {
                    chart: { type: 'area', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    colors: ['#3b82f6', '#10b981', '#0ea5e9'],
                    fill: {
                        type: 'gradient',
                        gradient: { opacityFrom: 0.35, opacityTo: 0.05 }
                    },
                    xaxis: {
                        categories: series.map(function (p) { return p.label; }),
                        labels: { style: { fontSize: '10px' } },
                        tickAmount: Math.min(10, series.length)
                    },
                    yaxis: { labels: { formatter: function (v) { return Math.round(v); } } },
                    legend: { position: 'top', horizontalAlign: 'right' },
                    tooltip: { shared: true, intersect: false }
                });
                trendChart.render();
                chartInstances.push(trendChart);
            }

            // --- Channel donut ------------------------------------------------------
            var donutEl = document.getElementById('analytics-channel-donut');
            var channels = CFG.channels || [];

            if (donutEl) {
                if (!channels.length) {
                    donutEl.innerHTML = '<div class="text-center text-body-secondary py-5">'
                        + 'No channel-attributed events in this range.</div>';
                } else {
                    var donut = new ApexCharts(donutEl, {
                        chart: { type: 'donut', height: 280 },
                        labels: channels.map(function (c) { return c.name; }),
                        series: channels.map(function (c) { return c.total; }),
                        colors: channels.map(function (c) { return (CFG.palette || {})[c.key] || '#8993a4'; }),
                        stroke: { width: 2 },
                        dataLabels: {
                            enabled: true,
                            formatter: function (val, opts) {
                                var i = opts.dataPointIndex;
                                var item = channels[i];
                                return item ? item.pct + '%' : val + '%';
                            }
                        },
                        legend: { position: 'bottom', fontSize: '12px' },
                        plotOptions: {
                            pie: { donut: { size: '62%' } }
                        },
                        tooltip: { y: { formatter: function (v) { return v + ' events'; } } }
                    });
                    donut.render();
                    chartInstances.push(donut);
                }
            }

            // --- Browser vs server ---------------------------------------------------
            var methodEl = document.getElementById('analytics-method-split');
            var method = CFG.method || [];

            if (methodEl) {
                var total = method.reduce(function (s, m) { return s + m.value; }, 0);

                if (!total) {
                    methodEl.innerHTML = '<div class="text-center text-body-secondary py-4">'
                        + 'No browser or server events recorded yet.</div>';
                } else {
                    var split = new ApexCharts(methodEl, {
                        chart: { type: 'bar', height: 150, stacked: true },
                        plotOptions: {
                            bar: { horizontal: true, barHeight: '55%', borderRadius: 4 }
                        },
                        dataLabels: {
                            enabled: true,
                            formatter: function (val) { return val; }
                        },
                        colors: ['#3b82f6', '#10b981'],
                        series: [
                            { name: 'Browser Pixel', data: [method[0] ? method[0].value : 0] },
                            { name: 'Server CAPI', data: [method[1] ? method[1].value : 0] }
                        ],
                        xaxis: { categories: ['Events'], labels: { formatter: function (v) { return Math.round(v); } } },
                        legend: { position: 'top', horizontalAlign: 'right' },
                        grid: { xaxis: { lines: { show: false } }, padding: { left: 0, right: 8 } }
                    });
                    split.render();
                    chartInstances.push(split);
                }
            }
        }

        // ---------------------------------------------------- live stream polling

        var timer = null;

        function escapeHtml(value) {
            if (value === null || value === undefined) return '';
            return String(value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        /**
         * Client-side row renderer.
         *
         * It intentionally mirrors backEnd/analytics/partials/live-rows.blade.php
         * field for field, so a refreshed table never shifts columns.
         */
        function renderRows(rows, manageUrl) {
            if (!rows || !rows.length) {
                return '<tr><td colspan="11" class="text-center py-4 text-body-secondary">'
                    + 'No live events in this range. Open the storefront to generate traffic.</td></tr>';
            }

            return rows.map(function (r) {
                var palette = CFG.palette || {};
                var channelColor = palette[r.channel] || '#8993a4';
                var device = r.device || '—';
                var location = r.location
                    ? escapeHtml(r.location)
                    : '<span class="text-body-secondary">Unresolved</span>';

                var methodBadge = r.tracking_method === 'browser'
                    ? '<span class="badge bg-label-info">Browser</span>'
                    : (r.tracking_method === 'server'
                        ? '<span class="badge bg-label-success">Server</span>'
                        : '<span class="badge bg-label-secondary">—</span>');

                var statusBadge = r.status === 0
                    ? '<span class="badge bg-danger">Blocked</span>'
                    : (r.active ? '<span class="badge bg-success">Active</span>'
                                : '<span class="badge bg-label-secondary">Idle</span>');

                var dedupBadge = r.dedup_status === 'primary'
                    ? ' <span class="badge bg-success" title="Matched on both browser and server paths">dedup</span>'
                    : '';

                return '<tr data-visitor-id="' + escapeHtml(r.visitor_id) + '" style="cursor:pointer"'
                    + ' class="' + (r.active ? '' : 'opacity-75 ') + (r.status === 0 ? 'table-danger' : '') + '">'
                    + '<td><div class="fw-medium font-monospace small">' + escapeHtml(r.short_uuid) + '</div>'
                    + '<div class="text-body-secondary" style="font-size:.65rem">' + r.event_count + ' events</div></td>'
                    + '<td><div class="small font-monospace">' + escapeHtml(r.ip || '—') + '</div>'
                    + '<div class="text-body-secondary" style="font-size:.65rem">' + escapeHtml(r.isp || 'Unknown ISP') + '</div></td>'
                    + '<td class="small">' + location + '</td>'
                    + '<td class="small">' + escapeHtml(device) + '</td>'
                    + '<td><span class="badge" style="background:' + escapeHtml(channelColor) + '">'
                    + escapeHtml(r.channel_label || 'Unknown') + '</span></td>'
                    + '<td><span class="badge bg-label-primary">' + escapeHtml(r.event) + '</span>' + dedupBadge + '</td>'
                    + '<td class="small text-body-secondary text-truncate" style="max-width:180px">'
                    + escapeHtml(r.page_path || '—') + '</td>'
                    + '<td>' + methodBadge + '</td>'
                    + '<td class="small text-body-secondary text-nowrap">' + escapeHtml(r.time_ago)
                    + '<div style="font-size:.65rem">' + escapeHtml(r.event_time) + '</div></td>'
                    + '<td>' + statusBadge + '</td>'
                    + '<td><a href="' + escapeHtml(manageUrl) + '" class="btn btn-sm btn-outline-secondary"'
                    + ' title="Open in visitor manager" onclick="event.stopPropagation()">'
                    + '<i class="ri ri-external-link-line"></i></a></td>'
                    + '</tr>';
            }).join('');
        }

        function refreshLive() {
            var body = document.getElementById('live-table-body');
            var dot = document.getElementById('live-dot');
            if (!body || !CFG.liveUrl) return;

            fetch(CFG.liveUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
                .then(function (data) {
                    var manageBase = (CFG.visitorBase || '').replace(/[^/]*$/, '');
                    body.innerHTML = renderRows(data.rows, manageBase);

                    var counter = document.getElementById('live-count');
                    if (counter) counter.textContent = data.live_visitors;

                    if (dot) dot.classList.remove('bg-secondary');
                })
                .catch(function () {
                    if (dot) {
                        dot.classList.add('bg-secondary');
                        dot.innerHTML = '<i class="ri ri-circle-fill"></i> offline';
                    }
                });
        }

        function scheduleLive() {
            if (timer) clearInterval(timer);
            var toggle = document.getElementById('live-auto-refresh');
            if (toggle && toggle.checked) {
                timer = setInterval(refreshLive, CFG.interval || 20000);
            }
        }

        // ------------------------------------------------ visitor drill-down drawer

        function loadVisitor(visitorId) {
            var body = document.getElementById('visitorDrawerBody');
            if (!body) return;

            body.innerHTML = '<div class="text-center py-5"><div class="spinner-border spinner-border-sm"></div></div>';

            fetch((CFG.visitorBase || '') + '/' + encodeURIComponent(visitorId), {
                headers: { 'Accept': 'application/json' }, credentials: 'same-origin'
            })
                .then(function (res) { return res.ok ? res.json() : Promise.reject(res.status); })
                .then(function (data) {
                    var v = data.visitor;
                    var rows = (data.events || []).map(function (e) {
                        return '<tr>'
                            + '<td class="small">' + escapeHtml(e.event) + '</td>'
                            + '<td class="small text-body-secondary text-truncate" style="max-width:160px">'
                            + escapeHtml(e.page_path || '—') + '</td>'
                            + '<td class="small text-end">' + escapeHtml(e.event_time || '') + '</td>'
                            + '</tr>';
                    }).join('');

                    body.innerHTML =
                        '<dl class="row small mb-3">'
                        + row('UUID', v.visitor_id)
                        + row('IP', v.ip)
                        + row('ISP', v.isp)
                        + row('Location', [v.division, v.city, v.country].filter(Boolean).join(', '))
                        + row('Device', [v.device_type, v.browser, v.os].filter(Boolean).join(' / '))
                        + row('Channel', v.channel)
                        + row('UTM source', v.utm_source)
                        + row('UTM campaign', v.utm_campaign)
                        + row('Landing page', v.landing_page)
                        + row('Referrer', v.referrer_host)
                        + row('Events', v.event_count + ' (browser ' + v.browser_event_count + ' / server ' + v.server_event_count + ')')
                        + row('First seen', v.first_seen)
                        + row('Last seen', v.last_seen)
                        + '</dl>'
                        + '<h6 class="text-body-secondary text-uppercase small fw-semibold">Journey</h6>'
                        + '<div class="table-responsive"><table class="table table-sm table-borderless">'
                        + '<thead><tr class="small text-body-secondary"><th>Event</th><th>Page</th><th class="text-end">Time</th></tr></thead>'
                        + '<tbody>' + (rows || '<tr><td class="small text-body-secondary">No events.</td></tr>') + '</tbody></table></div>';
                })
                .catch(function () {
                    body.innerHTML = '<div class="alert alert-danger">Could not load this visitor.</div>';
                });
        }

        function row(label, value) {
            return '<dt class="col-4 text-body-secondary">' + escapeHtml(label) + '</dt>'
                + '<dd class="col-8 text-break">' + (value ? escapeHtml(value) : '—') + '</dd>';
        }

        // ----------------------------------------------------------------- wiring

        document.addEventListener('DOMContentLoaded', function () {
            renderCharts();
            scheduleLive();

            var presetSelect = document.getElementById('preset-select');
            if (presetSelect) {
                presetSelect.addEventListener('change', function () {
                    var isCustom = this.value === 'custom';
                    document.getElementById('custom-from-wrap').classList.toggle('d-none', !isCustom);
                    document.getElementById('custom-to-wrap').classList.toggle('d-none', !isCustom);
                });
            }

            var refreshBtn = document.getElementById('live-refresh-btn');
            if (refreshBtn) refreshBtn.addEventListener('click', refreshLive);

            var toggle = document.getElementById('live-auto-refresh');
            if (toggle) toggle.addEventListener('change', scheduleLive);

            document.body.addEventListener('click', function (e) {
                var row = e.target.closest ? e.target.closest('#live-table-body tr[data-visitor-id]') : null;
                if (!row) return;
                if (e.target.closest('a')) return;

                var drawerEl = document.getElementById('visitorDrawer');
                if (!drawerEl) return;

                loadVisitor(row.getAttribute('data-visitor-id'));

                if (window.bootstrap && bootstrap.Offcanvas) {
                    bootstrap.Offcanvas.getOrCreateInstance(drawerEl).show();
                }
            });
        });
    })();
</script>
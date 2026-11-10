/* Live-now page behaviour.
   Moved from backEnd/analytics/live.blade.php. Server values ride in on
   [data-live-config]: url (live feed), channelBadge, visitorBase,
   interval, windowMinutes. */
    /**
     * Live-now page behaviour.
     *
     * One endpoint, one payload (the same JSON the dashboard polls), so the
     * cards, the table and the map can never show different numbers. Rows are
     * built with createElement + textContent — scraped or user-supplied page
     * paths must never be able to inject markup into the admin.
     */
    (function () {
        'use strict';

        var cfgEl = document.querySelector('[data-live-config]');
        var CFG = cfgEl ? (function () {
            try { return JSON.parse(cfgEl.getAttribute('data-live-config')); }
            catch (e) { return {}; }
        })() : {};
        CFG.interval = CFG.interval || 10000;

        var tbody = document.getElementById('live-rows');
        var autoBox = document.getElementById('live-auto');
        var pausedBadge = document.getElementById('live-paused-badge');
        var errorBox = document.getElementById('live-error');
        var updatedEl = document.getElementById('live-updated');
        var divisionBadge = document.getElementById('live-division-badge');
        var divisionLabel = document.getElementById('live-division-label');

        var timer = null;
        var paused = false;
        var hovering = false;
        var divisionFilter = null;
        var lastOk = null;
        var map = null;
        var layer = null;

        function el(tag, className, text) {
            var node = document.createElement(tag);
            if (className) { node.className = className; }
            if (text !== undefined && text !== null) { node.textContent = String(text); }
            return node;
        }

        function fmt(value) {
            var n = Number(value) || 0;
            return n.toLocaleString('en-US');
        }

        function setBadge(node, value) {
            if (!node) { return; }
            node.textContent = (value > 0 ? '+' : '') + value + '%';
            node.className = 'badge ' + (value > 0 ? 'bg-label-success'
                : value < 0 ? 'bg-label-danger' : 'bg-label-secondary');
        }

        function updateStats(stats) {
            if (!stats) { return; }

            document.getElementById('stat-visitors').textContent = fmt(stats.visitors_now);
            document.getElementById('stat-visitors-prev').textContent = fmt(stats.visitors_prev);
            setBadge(document.getElementById('stat-visitors-delta-badge'), stats.visitors_delta);

            document.getElementById('stat-events-per-min').textContent = Number(stats.events_per_min).toFixed(1);
            document.getElementById('stat-events').textContent = fmt(stats.events_now);
            setBadge(document.getElementById('stat-events-delta-badge'), stats.events_delta);

            var page = stats.top_page;
            document.getElementById('stat-page').textContent = page ? page.path : '—';
            document.getElementById('stat-page').title = page ? page.path : '';
            document.getElementById('stat-page-events').textContent = page ? fmt(page.events) : '0';
            document.getElementById('stat-page-share').textContent = page ? page.share + '%' : '—';

            var channel = stats.top_channel;
            document.getElementById('stat-channel').textContent = channel ? channel.label : '—';
            document.getElementById('stat-channel-visitors').textContent = channel ? fmt(channel.visitors) : '0';
            document.getElementById('stat-channel-share').textContent = channel ? channel.share + '%' : '—';
        }

        function buildRow(row) {
            var tr = document.createElement('tr');
            tr.setAttribute('data-division', row.division || '');

            var tdTime = el('td', 'text-nowrap');
            var time = el('span', null, row.time_hms || '');
            time.title = (row.time_ago ? row.time_ago + ' · ' : '') + (row.event_time || '');
            tdTime.appendChild(time);

            var tdVisitor = el('td');
            var link = el('a', 'live-visitor', row.short_uuid);
            link.href = CFG.visitorBase + '/' + encodeURIComponent(row.visitor_id);
            link.title = row.visitor_id || '';
            tdVisitor.appendChild(link);

            var tdPage = el('td');
            var page = el('div', 'live-page', row.page_path || '—');
            page.title = row.page_path || '';
            tdPage.appendChild(page);

            var tdChannel = el('td');
            if (row.channel) {
                var tone = CFG.channelBadge[row.channel] || 'secondary';
                tdChannel.appendChild(el('span', 'badge bg-label-' + tone, row.channel_label));
            } else {
                tdChannel.appendChild(el('span', 'text-body-secondary', '—'));
            }

            var tdDevice = el('td', 'text-body-secondary');
            var device = el('div', 'live-device', row.device || '—');
            device.title = row.device || '';
            tdDevice.appendChild(device);

            var tdIsp = el('td', 'text-body-secondary');
            var isp = el('div', 'live-isp', row.isp || '—');
            isp.title = row.isp || '';
            tdIsp.appendChild(isp);

            var tdEvent = el('td', 'text-nowrap');
            tdEvent.appendChild(el('span', 'badge bg-label-dark', row.event));
            if (row.tracking_method) {
                tdEvent.appendChild(el('span', 'live-method text-body-secondary', row.tracking_method));
            }

            tr.appendChild(tdTime);
            tr.appendChild(tdVisitor);
            tr.appendChild(tdPage);
            tr.appendChild(tdChannel);
            tr.appendChild(tdDevice);
            tr.appendChild(tdIsp);
            tr.appendChild(tdEvent);

            return tr;
        }

        function emptyRow(message) {
            var tr = document.createElement('tr');
            tr.id = 'live-empty-row';
            var td = document.createElement('td');
            td.colSpan = 7;
            td.className = 'text-center py-5';
            td.appendChild(el('div', 'fw-medium', message));
            td.appendChild(el('div', 'text-body-secondary small',
                'Widen the range above, or wait — this table refreshes automatically.'));
            tr.appendChild(td);
            return tr;
        }

        function renderRows(rows) {
            var fragment = document.createDocumentFragment();

            if (!rows || !rows.length) {
                fragment.appendChild(emptyRow('No visitors in the last ' + CFG.windowMinutes + ' minutes.'));
            } else {
                rows.forEach(function (row) { fragment.appendChild(buildRow(row)); });
            }

            tbody.textContent = '';
            tbody.appendChild(fragment);
            document.getElementById('live-row-count').textContent = (rows ? rows.length : 0) + ' rows';

            applyDivisionFilter();
        }

        function applyDivisionFilter() {
            var shown = 0;

            Array.prototype.forEach.call(tbody.querySelectorAll('tr[data-division]'), function (tr) {
                var match = !divisionFilter || tr.getAttribute('data-division') === divisionFilter;
                tr.classList.toggle('d-none', !match);
                if (match) { shown++; }
            });

            if (divisionFilter) {
                divisionBadge.classList.remove('d-none');
                divisionLabel.textContent = divisionFilter;
                document.getElementById('live-row-count').textContent = shown + ' rows';
            } else {
                divisionBadge.classList.add('d-none');
            }

            Array.prototype.forEach.call(document.querySelectorAll('.division-chip'), function (chip) {
                chip.setAttribute('aria-pressed', chip.getAttribute('data-division') === divisionFilter ? 'true' : 'false');
            });
        }

        function setDivision(value) {
            divisionFilter = (divisionFilter === value) ? null : value;
            applyDivisionFilter();
        }

        // ---------------------------------------------------------------- map
        function initMap() {
            var node = document.getElementById('live-map');

            if (!node || typeof window.L === 'undefined' || map) {
                return;
            }

            map = L.map(node, { scrollWheelZoom: false, attributionControl: true })
                .setView([23.7, 90.35], 7);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 12,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
            }).addTo(map);

            layer = L.layerGroup().addTo(map);
        }

        function drawMap(payload) {
            var data = payload.map;

            if (!data) { return; }

            document.getElementById('map-total').textContent = fmt(data.total);
            document.getElementById('map-scope').textContent = data.scope === 'live'
                ? 'Last ' + CFG.windowMinutes + ' minutes'
                : 'No live visitors — showing today';

            var hasPoints = data.points.length > 0;

            document.getElementById('live-map').classList.toggle('d-none', !hasPoints);
            document.getElementById('live-map-empty').classList.toggle('d-none', hasPoints);

            // Chips: rebuilt every poll so the counts stay truthful.
            var chipHost = document.getElementById('division-chips');
            var chips = document.createDocumentFragment();

            data.points.forEach(function (point) {
                var chip = el('button', 'btn btn-sm btn-outline-secondary division-chip');
                chip.type = 'button';
                chip.setAttribute('data-division', point.division);
                chip.setAttribute('aria-pressed', divisionFilter === point.division ? 'true' : 'false');
                chip.appendChild(document.createTextNode(point.division + ' '));
                chip.appendChild(el('span', 'badge bg-label-primary ms-1', fmt(point.visitors)));
                chip.addEventListener('click', function () { setDivision(point.division); });
                chips.appendChild(chip);
            });

            chipHost.textContent = '';
            chipHost.appendChild(chips);

            if (!hasPoints) { return; }

            initMap();

            if (!map || !layer) { return; }

            // The container was display:none until now, so Leaflet measured a
            // zero-size box on creation.
            map.invalidateSize();
            layer.clearLayers();

            var max = data.max || 1;

            data.points.forEach(function (point) {
                // Pixel radius, not metres: the map answers "which division is
                // busy", so the dot must stay readable at every zoom level.
                L.circleMarker([point.lat, point.lng], {
                    radius: 8 + 18 * Math.sqrt(point.visitors / max),
                    color: '#0d6efd',
                    weight: 2,
                    fillColor: '#0d6efd',
                    fillOpacity: 0.22 + 0.4 * (point.visitors / max)
                })
                    .bindTooltip(point.division + ' — ' + fmt(point.visitors) + ' visitors')
                    .on('click', function () { setDivision(point.division); })
                    .addTo(layer);
            });
        }

        // ------------------------------------------------------------- polling
        function setPausedUI() {
            var isPaused = paused || hovering;
            pausedBadge.classList.toggle('d-none', !isPaused);
        }

        function refresh() {
            if (paused || hovering || document.hidden) {
                setPausedUI();
                return;
            }

            fetch(CFG.url, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    return response.json();
                })
                .then(function (payload) {
                    errorBox.classList.add('d-none');

                    if (payload.stats) {
                        CFG.windowMinutes = payload.stats.window_minutes || 5;
                    }

                    updateStats(payload.stats);
                    renderRows(payload.rows);
                    drawMap(payload);

                    lastOk = new Date();
                    updatedEl.textContent = 'Updated ' + lastOk.toLocaleTimeString();
                })
                .catch(function (error) {
                    errorBox.classList.remove('d-none');
                    document.getElementById('live-error-time').textContent = lastOk
                        ? lastOk.toLocaleTimeString()
                        : 'the last successful load';
                    // Keep the last good data on screen; never blank the table.
                    console.error('live refresh failed:', error.message);
                });
        }

        function start() {
            if (timer) { clearInterval(timer); }
            timer = setInterval(refresh, CFG.interval);
        }

        // ------------------------------------------------------------- wiring
        document.getElementById('live-refresh').addEventListener('click', refresh);
        document.getElementById('live-error-retry').addEventListener('click', refresh);

        autoBox.addEventListener('change', function () {
            paused = !autoBox.checked;
            setPausedUI();
            if (!paused) { refresh(); }
        });

        tbody.addEventListener('mouseenter', function () {
            hovering = true;
            setPausedUI();
        });

        tbody.addEventListener('mouseleave', function () {
            hovering = false;
            setPausedUI();
        });

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) { refresh(); }
        });

        document.getElementById('live-division-clear').addEventListener('click', function () {
            divisionFilter = null;
            applyDivisionFilter();
        });

        Array.prototype.forEach.call(document.querySelectorAll('.division-chip'), function (chip) {
            chip.addEventListener('click', function () { setDivision(chip.getAttribute('data-division')); });
        });

        CFG.windowMinutes = CFG.windowMinutes || 15;
        if (document.querySelectorAll('.division-chip').length) {
            initMap();
            if (map) { map.invalidateSize(); }
        }
        applyDivisionFilter();
        start();
    })();

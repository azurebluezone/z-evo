/* Analytics index bootstrap.
   Moved from backEnd/analytics/index.blade.php - the blade now renders the
   server values as one JSON data attribute (data-analytics-config) and this
   file turns them into the window.__ANALYTICS__ contract that
   admin-analytics-behaviour.js consumes. */
(function () {
    'use strict';

    var el = document.querySelector('[data-analytics-config]');
    var cfg = el ? JSON.parse(el.getAttribute('data-analytics-config')) : {};

    window.__ANALYTICS__ = {
        trend: cfg.trend || { labels: [], series: [] },
        channels: cfg.channels || [],
        method: cfg.method || [],
        palette: cfg.palette || [],
        liveUrl: cfg.liveUrl || '',
        visitorBase: cfg.visitorBase || '',
        interval: cfg.interval || 20000
    };
})();

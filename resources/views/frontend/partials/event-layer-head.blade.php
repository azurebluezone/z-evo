@php
    $gtmContainerId = null;
    if (isset($gtm_code) && count($gtm_code) > 0) {
        foreach ($gtm_code as $gtmRow) {
            if (!empty($gtmRow->code) && preg_match('/GTM-[A-Z0-9]+/i', $gtmRow->code, $m)) {
                $gtmContainerId = strtoupper($m[0]);
                break;
            }
        }
    }
@endphp

<script>
    window.dataLayer = window.dataLayer || [];
    window.__EVENT_LAYER__ = window.__EVENT_LAYER__ || {
        push: function (eventName, payload) {
            try {
                window.dataLayer.push(Object.assign({ event: eventName }, payload || {}));
            } catch (e) {
            }
        }
    };

    window.__EVENT_LOGGER__ = window.__EVENT_LOGGER__ || (function () {
        var STORAGE_KEY = '__event_queue__';
        var MAX_BATCH = 5;
        var IDLE_TIMEOUT = 30000; // 30 seconds idle threshold (Section 2)
        var idleTimer = null;
        var lastActivity = Date.now();

        function getCookie(name) {
            try {
                var parts = ('; ' + document.cookie).split('; ' + name + '=');
                if (parts.length === 2) return parts.pop().split(';').shift();
            } catch (e) {}
            return null;
        }

        function readQueue() {
            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                var parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function writeQueue(queue) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(queue || []));
            } catch (e) {
            }
        }

        /**
         * Check if batch threshold is met based on event types (Section 2):
         * - 4-5 PageView events OR
         * - 2 ProductView (ViewContent) events
         */
        function shouldFlushBatch(q) {
            var pageViewCount = 0;
            var productViewCount = 0;
            for (var i = 0; i < q.length; i++) {
                var n = q[i].name;
                if (n === 'PageView') pageViewCount++;
                if (n === 'ViewContent' || n === 'ProductView') productViewCount++;
            }
            return pageViewCount >= 4 || productViewCount >= 2;
        }

        /**
         * Reset idle timer on user activity (Section 2):
         * Flush events after 30 seconds of inactivity
         */
        function resetIdleTimer() {
            lastActivity = Date.now();
            if (idleTimer) {
                clearTimeout(idleTimer);
                idleTimer = null;
            }
            idleTimer = setTimeout(function () {
                // Flush if enough time has passed since last activity
                if (Date.now() - lastActivity >= IDLE_TIMEOUT) {
                    flush();
                }
                idleTimer = null;
            }, IDLE_TIMEOUT);
        }

        /**
         * Monitor user activity events for idle detection
         */
        function startActivityMonitoring() {
            var activityEvents = ['mousemove', 'mousedown', 'click', 'keypress', 'scroll', 'touchstart', 'touchmove'];
            for (var i = 0; i < activityEvents.length; i++) {
                try {
                    document.addEventListener(activityEvents[i], resetIdleTimer, { passive: true });
                } catch (e) {}
            }
            // Start the initial idle timer
            resetIdleTimer();
        }

        /**
         * Cross-platform event id.
         *
         * This id is shared with the server-side CAPI call for the same logical
         * event, which is exactly how Meta/Google deduplicate the pair. Without
         * it the admin dashboard would count one conversion twice.
         */
        function newEventId() {
            try {
                if (window.crypto && window.crypto.randomUUID) {
                    return window.crypto.randomUUID().replace(/-/g, '');
                }
            } catch (e) {}
            // Fallback for browsers without randomUUID: random hex of the same shape.
            var bytes = new Uint8Array(16);
            if (window.crypto && window.crypto.getRandomValues) {
                window.crypto.getRandomValues(bytes);
            } else {
                for (var i = 0; i < 16; i++) bytes[i] = Math.floor(Math.random() * 256);
            }
            return Array.prototype.map.call(bytes, function (b) {
                return ('0' + b.toString(16)).slice(-2);
            }).join('');
        }

        function readClickIds() {
            try {
                var qs = (location.search || '').replace(/^\?/, '');
                var out = {};
                if (!qs) return out;
                qs.split('&').forEach(function (pair) {
                    var idx = pair.indexOf('=');
                    if (idx === -1) return;
                    var k = decodeURIComponent(pair.slice(0, idx));
                    var v = decodeURIComponent(pair.slice(idx + 1));
                    if (k === 'fbclid' || k === 'ttclid' || k === 'gclid') out[k] = v;
                });
                return out;
            } catch (e) {
                return {};
            }
        }

        var CLICK_IDS = readClickIds();

        function queueEvent(name, meta, eventId) {
            meta = meta || {};
            var q = readQueue();
            var row = {
                name: name,
                // A stable id per event is what makes server/browser merging work.
                // Callers that already hold the server-minted id pass it in; only
                // genuinely browser-owned events mint a fresh one.
                event_id: eventId || newEventId(),
                url: meta.url ? meta.url : (location.href || null),
                ts: (new Date()).toISOString()
            };

            if (meta.click_target) row.click_target = meta.click_target;
            if (typeof meta.scroll_depth === 'number') row.scroll_depth = meta.scroll_depth;
            if (meta.outbound_host) row.outbound_host = meta.outbound_host;
            if (meta.channel) row.channel = meta.channel;

            Object.keys(CLICK_IDS).forEach(function (key) { row[key] = CLICK_IDS[key]; });

            q.push(row);
            writeQueue(q);
            // Check batch threshold: 4-5 PageViews OR 2 ProductViews (Section 2)
            if (shouldFlushBatch(q)) {
                flush();
            }
        }

        /**
         * Scroll-depth milestones. The journey panel shows 25/50/75/100% steps,
         * so we only fire when a new milestone is actually crossed - firing on
         * every scroll event would drown the queue in noise.
         */
        var scrollMilestones = [25, 50, 75, 100];
        var scrollFired = {};

        function bindScrollDepth() {
            try {
                document.addEventListener('scroll', function () {
                    var doc = document.documentElement;
                    var scrollable = (doc.scrollHeight - window.innerHeight);
                    if (scrollable <= 0) return;

                    var pct = Math.round((window.pageYOffset / scrollable) * 100);
                    if (pct > 100) pct = 100;
                    if (pct < 0) pct = 0;

                    for (var i = 0; i < scrollMilestones.length; i++) {
                        var m = scrollMilestones[i];
                        if (pct >= m && !scrollFired[m]) {
                            scrollFired[m] = true;
                            try {
                                window.__EVENT_LAYER__.push('scroll_depth', { percent: m });
                                window.__EVENT_LOGGER__.queue('Scroll_' + m, {
                                    url: location.href,
                                    scroll_depth: m
                                });
                            } catch (e) {}
                        }
                    }
                }, { passive: true });
            } catch (e) {}
        }

        /**
         * Outbound link clicks (Facebook / YouTube / WhatsApp …). These are the
         * drop-off legs of the Sankey diagram: the visitor clicked away from us.
         */
        function bindOutboundClicks() {
            try {
                var siteHost = '{{ request()->getHost() }}';

                document.addEventListener('click', function (e) {
                    var el = e.target;
                    if (!el) return;
                    var a = el.closest ? el.closest('a') : null;
                    if (!a) return;

                    var href = a.getAttribute('href') || '';
                    if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;

                    var host = null;
                    try {
                        host = new URL(href, location.href).hostname;
                    } catch (err) {
                        return;
                    }
                    if (!host || host === siteHost) return;

                    var label = (a.getAttribute('data-track-label') || a.textContent || host)
                        .trim().replace(/\s+/g, ' ').slice(0, 60);

                    try {
                        window.__EVENT_LAYER__.push('outbound_click', { label: label, host: host });
                        window.__EVENT_LOGGER__.queue('Outbound_Click', {
                            url: href,
                            click_target: label,
                            outbound_host: host
                        });
                    } catch (err) {}
                }, true);
            } catch (e) {}
        }

        function flush() {
            // Clear idle timer on manual flush
            if (idleTimer) {
                clearTimeout(idleTimer);
                idleTimer = null;
            }

            var q = readQueue();
            if (!q.length) return;

            var payload = {
                visitor_id: getCookie('visitor_id'),
                events: q
            };

            try {
                writeQueue([]);
                if (navigator.sendBeacon) {
                    var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                    navigator.sendBeacon('{{ url('/events/collect') }}', blob);
                    return;
                }
            } catch (e) {
            }

            try {
                fetch('{{ url('/events/collect') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                    keepalive: true
                }).catch(function () {});
            } catch (e) {
            }
        }

        function bindCheckoutFlush() {
            try {
                document.addEventListener('click', function (e) {
                    var el = e.target;
                    if (!el) return;
                    var a = el.closest ? el.closest('a') : null;
                    if (a && a.getAttribute('href') && a.getAttribute('href').indexOf('/checkout') !== -1) {
                        try {
                            if (window.__EVENT_LAYER__ && typeof window.__EVENT_LAYER__.push === 'function') {
                                window.__EVENT_LAYER__.push('begin_checkout', { checkout_url: a.getAttribute('href') });
                            }
                            if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.queue === 'function') {
                                window.__EVENT_LOGGER__.queue('InitiateCheckout', { url: a.getAttribute('href') });
                            }
                        } catch (e) {
                        }
                        flush();
                    }
                }, true);
            } catch (e) {
            }
        }

        function bindAddToCartFlush() {
            try {
                document.addEventListener('click', function (e) {
                    var el = e.target;
                    if (!el) return;

                    // Check for add to cart buttons
                    var btn = el.closest ? el.closest('[data-add-to-cart], .add-to-cart, .btn-add-cart') : null;
                    if (!btn) {
                        // Check by text content
                        var text = el.textContent || '';
                        if (text.toLowerCase().indexOf('add to cart') !== -1 ||
                            text.toLowerCase().indexOf('buy now') !== -1 ||
                            text.toLowerCase().indexOf('order now') !== -1) {
                            btn = el;
                        }
                    }

                    if (btn) {
                        try {
                            var productId = btn.getAttribute('data-product-id') || btn.getAttribute('data-id') || '';
                            var productName = btn.getAttribute('data-product-name') || btn.getAttribute('data-name') || '';
                            var productPrice = btn.getAttribute('data-product-price') || btn.getAttribute('data-price') || '';

                            if (window.__EVENT_LAYER__ && typeof window.__EVENT_LAYER__.push === 'function') {
                                window.__EVENT_LAYER__.push('add_to_cart', {
                                    product_id: productId,
                                    product_name: productName,
                                    price: productPrice
                                });
                            }
                            if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.queue === 'function') {
                                window.__EVENT_LOGGER__.queue('AddToCart', {
                                    url: location.href,
                                    click_target: (productId ? 'add_to_cart#' + productId : 'add_to_cart'),
                                    event_data: {
                                        product_id: productId,
                                        product_name: productName,
                                        product_price: productPrice
                                    }
                                });
                            }
                        } catch (e) {
                        }
                        flush();
                    }
                }, true);
            } catch (e) {
            }
        }

        try {
            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'hidden') {
                    flush();
                }
            });
            window.addEventListener('beforeunload', function () {
                flush();
            });
        } catch (e) {
        }

        bindCheckoutFlush();
        bindAddToCartFlush();
        bindScrollDepth();      // journey: scroll milestones
        bindOutboundClicks();   // journey: drop-off legs to external hosts
        startActivityMonitoring(); // 30-second idle flush trigger (Section 2)

        return {
            queue: queueEvent,
            flush: flush,
            // Exposed so the pixel snippets can reuse the exact same event id and
            // let Meta deduplicate the browser hit against our server-side CAPI.
            newEventId: newEventId
        };
    })();

    window.__EVENT_LAYER__.push('page_view', {
        page_url: '{{ url()->current() }}',
        page_path: '{{ request()->path() }}',
        page_title: document.title,
        page_referrer: document.referrer || null
    });

    try {
        window.__EVENT_LOGGER__.queue('PageView', { url: '{{ url()->current() }}' });
    } catch (e) {
    }
</script>

@if($gtmContainerId)
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmContainerId }}');
    </script>
@endif

@if(!empty($generalsetting?->tiktok_pixel_id) && ($generalsetting?->tiktok_browser_tracking ?? false))
    <script>
        (function (w, d, t) {
            w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e};ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{};ttq._i[e]=[];ttq._i[e]._u=i;ttq._t=ttq._t||{};ttq._t[e]=+new Date;ttq._o=ttq._o||{};ttq._o[e]=n||{};var o=d.createElement("script");o.type="text/javascript";o.async=!0;o.src=i+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
            ttq.load('{{ $generalsetting->tiktok_pixel_id }}');
            ttq.page();
        })(window, document, 'ttq');
    </script>
    
    @if(($generalsetting?->tiktok_server_side_tracking ?? false))
    <script>
        window.tiktokTrackingConfig = {
            enabled: true,
            pixelId: '{{ $generalsetting->tiktok_pixel_id }}',
            serverSideEnabled: true,
            testMode: {{ ($generalsetting?->tiktok_test_mode ?? false) ? 'true' : 'false' }}
        };
    </script>
    <script src="{{ asset('assets/js/tiktok-tracking.js') }}"></script>
    @endif
@endif

@if(!empty($generalsetting?->facebook_pixel_id))
    {{-- Facebook Pixel --}}
    <script>
        !function(f,b,e,v,n,t,s)
        {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};
        if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
        n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];
        s.parentNode.insertBefore(t,s)}(window,document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $generalsetting->facebook_pixel_id }}');
        fbq('track', 'PageView');
    </script>
    <noscript>
        <img height="1" width="1" style="display:none"
            src="https://www.facebook.com/tr?id={{ $generalsetting->facebook_pixel_id }}&ev=PageView&noscript=1"/>
    </noscript>

    {{-- Facebook Tracking Config --}}
    <script>
        window.facebookTrackingConfig = {
            enabled: true,
            pixelId: '{{ $generalsetting->facebook_pixel_id }}',
            // The Conversions API token is deliberately NOT printed here. The
            // browser posts to /api/facebook/track and the server attaches the
            // token; putting it in the page put a live ad-account secret in the
            // HTML source of every storefront page.
            apiVersion: '{{ $generalsetting->facebook_conversion_api_version ?? 'v17.0' }}',
            serverSide: {{ ($generalsetting?->facebook_server_side_tracking ?? false) ? 'true' : 'false' }},
            enhancedEcommerce: {{ ($generalsetting?->facebook_enhanced_ecommerce ?? false) ? 'true' : 'false' }}
            @if(auth()->check())
            ,user: {
                email: '{{ auth()->user()->email }}',
                first_name: '{{ auth()->user()->first_name ?? '' }}',
                last_name: '{{ auth()->user()->last_name ?? '' }}'
            }
            @endif
        };
    </script>
    <script src="{{ asset('assets/js/facebook-tracking.js') }}"></script>
@endif

@if(isset($gtm_code) && count($gtm_code) > 0)
    @foreach($gtm_code as $gtmRow)
        @php
            $gtmRaw = (string) ($gtmRow->code ?? '');
            $gtmRawTrim = trim($gtmRaw);
            $looksLikeSnippet = $gtmRawTrim !== '' && (
                str_contains($gtmRawTrim, '<script') ||
                str_contains($gtmRawTrim, '<noscript') ||
                str_contains($gtmRawTrim, '<iframe') ||
                str_contains($gtmRawTrim, 'googletagmanager.com')
            );
        @endphp
        @if($looksLikeSnippet)
            {!! $gtmRawTrim !!}
        @endif
    @endforeach
@endif

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

    // Env fallback for deployments that never touch the admin "GTM code" field.
    // The admin value wins when both exist, so a shop can move containers
    // without a redeploy.
    if (!$gtmContainerId) {
        $gtmConfigId = config('tracking.gtm.container_id');
        if (is_string($gtmConfigId) && preg_match('/GTM-[A-Z0-9]+/i', $gtmConfigId, $m)) {
            $gtmContainerId = strtoupper($m[0]);
        }
    }

    // Tracking identity for this render. Both values are set by VisitorIdentify;
    // the cookie fallback keeps the partial safe when it is included from a view
    // that was rendered outside the middleware (templates/* previews).
    $trackVisitorId = request()->attributes->get('visitor_id') ?: request()->cookie('visitor_id');
    $trackSessionId = request()->attributes->get('session_id') ?: request()->cookie('session_id');
    $trackToken = null;

    if (is_string($trackVisitorId) && $trackVisitorId !== ''
        && is_string($trackSessionId) && $trackSessionId !== '') {
        $trackToken = \App\Support\TrackToken::issue($trackVisitorId, $trackSessionId);
    }

    // Consent decides which vendor scripts may be printed at all. Defaults are
    // denied, so a first visit loads no pixel and no container; the banner asks,
    // and a grant reloads the page once so the server can print what it allowed.
    $consentState = \App\Support\Consent::state();
    $consentAnalytics = $consentState['analytics'] === \App\Support\Consent::GRANTED;
    $consentMarketing = $consentState['marketing'] === \App\Support\Consent::GRANTED;
@endphp

{{-- Must come before the event logger below: the logger queues PageView while it
     is still initialising, and the GA4 bridge has to exist by then or the first
     page view of every visit is silently dropped. --}}
@include('frontend.partials.analytics-ga4')

<script>
    window.dataLayer = window.dataLayer || [];

    // The only thing the page is given about this visit. `visitor_id` and
    // `session_id` stay HttpOnly; this token proves to /events/collect that the
    // visitor_id in the body was issued by us and belongs to this session.
    window.__TRACK__ = {
        token: @json($trackToken['token'] ?? null),
        exp: @json($trackToken['exp'] ?? null),
        page_path: @json('/' . ltrim(request()->path(), '/')),
        server_url: @json(request()->fullUrl())
    };

    // Server-rendered consent state for this request. `server*` is what the page
    // was built with; `analytics`/`marketing` is what the cookie says now.
    window.__CONSENT__ = {
        version: {{ \App\Support\Consent::VERSION }},
        ttlDays: {{ \App\Support\Consent::TTL_DAYS }},
        analytics: @json($consentState['analytics']),
        marketing: @json($consentState['marketing']),
        decided: @json($consentState['decided']),
        serverAnalytics: @json($consentState['analytics']),
        serverMarketing: @json($consentState['marketing']),
        page: @json('/' . ltrim(request()->path(), '/'))
    };

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
        // Guards against two overlapping flushes removing each other's rows.
        var inFlight = false;

        function getCookie(name) {
            try {
                var parts = ('; ' + document.cookie).split('; ' + name + '=');
                if (parts.length === 2) return parts.pop().split(';').shift();
            } catch (e) {}
            return null;
        }

        // localStorage can throw on the very first access (Safari private mode,
        // "block all cookies", storage quota). When that happens the queue moves
        // to memory for the life of the page instead of silently dropping every
        // event: the visitor still gets counted during this visit, it just cannot
        // survive a navigation.
        var memoryQueue = null;

        function readQueue() {
            if (memoryQueue !== null) {
                return memoryQueue.slice();
            }

            try {
                var raw = localStorage.getItem(STORAGE_KEY);
                var parsed = raw ? JSON.parse(raw) : [];
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                memoryQueue = [];
                return [];
            }
        }

        function writeQueue(queue) {
            if (memoryQueue !== null) {
                memoryQueue = queue || [];
                return;
            }

            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(queue || []));
            } catch (e) {
                memoryQueue = queue || [];
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
                // Always set, even when the caller supplied no url: the admin
                // journey view groups by this column, and NULL rows are invisible.
                page_path: meta.page_path || (window.__TRACK__ && window.__TRACK__.page_path) || location.pathname || '/',
                ts: (new Date()).toISOString()
            };

            if (meta.click_target) row.click_target = meta.click_target;
            if (typeof meta.scroll_depth === 'number') row.scroll_depth = meta.scroll_depth;
            if (meta.outbound_host) row.outbound_host = meta.outbound_host;
            if (meta.channel) row.channel = meta.channel;
            if (meta.title) row.page_title = meta.title;
            if (meta.referrer) row.referrer = meta.referrer;

            Object.keys(CLICK_IDS).forEach(function (key) { row[key] = CLICK_IDS[key]; });

            // GA4 bridge. One call site, so an event cannot be counted locally
            // and silently forgotten by Google. The bridge is a no-op unless
            // analytics consent is granted and a measurement id is configured.
            try {
                if (window.__GA4__ && typeof window.__GA4__.send === 'function') {
                    window.__GA4__.send(name, meta, row);
                }
            } catch (e) {}

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

            if (inFlight) return;

            // Cookies blocked: every request would be rejected with 422 (the
            // collector has no visitor_id to work with). Skip the network call
            // and say it once instead of filling the console with failures.
            try {
                if (navigator.cookieEnabled === false) {
                    noteRejected();
                    return;
                }
            } catch (e) {}

            var q = readQueue();
            if (!q.length) return;

            // One request at a time, bounded batch. A visitor who queued events
            // while offline should not push a 200KB body through keepalive.
            var batch = q.slice(0, MAX_BATCH);
            var payload = {
                visitor_id: getCookie('visitor_id'),
                events: batch
            };

            if (window.__TRACK__ && window.__TRACK__.token) {
                payload.track_token = window.__TRACK__.token;
            }

            var body;
            try {
                body = JSON.stringify(payload);
            } catch (e) {
                return;
            }

            var sentIds = batch.map(function (row) { return row.event_id; });
            var settled = false;

            function done(delivered) {
                if (settled) return;
                settled = true;
                inFlight = false;

                // The queue is only trimmed AFTER the server acknowledged it.
                // Clearing it first (the old behaviour) lost every event that
                // happened to be in flight when the tab closed or the network
                // blinked; replaying is safe because event_id is unique.
                if (delivered) {
                    removeSent(sentIds);
                    if (readQueue().length) {
                        setTimeout(flush, 0);
                    }
                }
            }

            inFlight = true;

            // 1) fetch with keepalive: works during unload and reports success.
            try {
                if (window.fetch) {
                    fetch('/events/collect', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: body,
                        keepalive: body.length < 60000,
                        credentials: 'same-origin'
                    }).then(function (res) {
                        if (res && res.status === 422) {
                            noteRejected();
                        }
                        done(!!(res && res.ok));
                    }).catch(function () {
                        done(false);
                    });

                    return;
                }
            } catch (e) {
            }

            // 2) sendBeacon: no response, but `true` means the browser accepted
            //    the request, which is the strongest ack available at unload.
            try {
                if (navigator.sendBeacon) {
                    var blob = new Blob([body], { type: 'application/json' });
                    done(navigator.sendBeacon('/events/collect', blob) === true);
                    return;
                }
            } catch (e) {
            }

            done(false);
        }

        /**
         * A 422 from the collector almost always means the visitor_id cookie is
         * unavailable (blocked cookies) or the signed token expired. Say it once,
         * at info level: the page must keep working and the console must not fill
         * with red errors for a privacy choice.
         */
        var rejectionNoted = false;

        function noteRejected() {
            if (rejectionNoted) return;
            rejectionNoted = true;

            try {
                console.info('Tracking paused: the collector rejected this request (422). The page continues normally.');
            } catch (e) {}
        }

        /**
         * Drop only the rows the server confirmed, keeping anything that was
         * queued while the request was in flight.
         */
        function removeSent(sentIds) {
            if (!sentIds || !sentIds.length) return;

            var remaining = readQueue().filter(function (row) {
                return sentIds.indexOf(row.event_id) === -1;
            });

            writeQueue(remaining);
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
        window.__EVENT_LOGGER__.queue('PageView', {
            url: '{{ url()->current() }}',
            page_path: @json('/' . ltrim(request()->path(), '/')),
            title: document.title,
            referrer: document.referrer || null
        });
    } catch (e) {
    }
</script>

@include('frontend.partials.consent-banner')

@if($gtmContainerId && $consentAnalytics)
    <script>
        (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
        new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
        j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
        'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
        })(window,document,'script','dataLayer','{{ $gtmContainerId }}');
    </script>
@endif

@if(!empty($generalsetting?->tiktok_pixel_id) && ($generalsetting?->tiktok_browser_tracking ?? false) && $consentMarketing)
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

@if(!empty($generalsetting?->facebook_pixel_id) && $consentMarketing)
    {{-- Facebook Pixel: printed only after the visitor allowed marketing. --}}
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
            enabled: {{ $consentMarketing ? 'true' : 'false' }},
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

{{-- Raw snippets pasted into the admin GTM field. Same consent gate as the
     container script above: an admin-pasted snippet is still a Google request,
     and printing it before the visitor decides would undo the whole gate. --}}
@if($consentAnalytics && isset($gtm_code) && count($gtm_code) > 0)
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

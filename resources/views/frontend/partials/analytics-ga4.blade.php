{{--
    GA4 browser tag + bridge (T3).

    Two paths, one of which is active at a time:

      1. A GTM *web* container is configured (admin field or TRACKING_GTM_ID).
         The container is printed by event-layer-head and owns the GA4 tag; the
         server container URL is set inside Tag Manager (Admin -> Container
         Settings), not here.
      2. No web container, but TRACKING_GA4_ID is set. This partial prints
         gtag.js directly and points it at the server container with
         `transport_url` when TRACKING_SERVER_CONTAINER_URL (or a host derived
         from APP_URL) exists.

    Either way the tag is printed only when analytics consent is granted -
    nothing is loaded before the visitor decides, so there is no "denied
    default then update" dance to get wrong.

    `window.__GA4__` is the single bridge between the first-party event logger
    and GA4. Keeping it here (not inside event-layer-head) means the event
    vocabulary and the GA4 names stay next to each other.
--}}
@php
    $ga4Id = \App\Support\Ga4::measurementId();
    $ga4ServerUrl = config('tracking.server_container.url');
    $ga4Debug = \App\Support\Ga4::debug();
    $ga4AnalyticsOn = \App\Support\Consent::analytics();
@endphp

@if($ga4Id && $ga4AnalyticsOn && empty($gtmContainerId))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ urlencode($ga4Id) }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };

        gtag('js', new Date());

        // Only analytics was granted; this shop does not run Google Ads, so the
        // ad signals stay denied instead of being quietly implied.
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'granted'
        });

        gtag('config', @json($ga4Id), {
            // The event layer sends page_view itself, once per page, with the
            // same path the first-party row uses. Letting gtag send a second
            // one would double every page view.
            send_page_view: false,
            @if($ga4ServerUrl)
            transport_url: @json($ga4ServerUrl),
            @endif
            debug_mode: {{ $ga4Debug ? 'true' : 'false' }}
        });

        window.__GA4__ = (function () {
            'use strict';

            // Meta event names -> GA4 recommended names. Unmapped events are
            // dropped on purpose: inventing custom names for internal steps
            // only creates noise in the GA4 UI.
            var MAP = {
                PageView: 'page_view',
                ViewContent: 'view_item',
                AddToCart: 'add_to_cart',
                RemoveFromCart: 'remove_from_cart',
                ViewCart: 'view_cart',
                BeginCheckout: 'begin_checkout',
                InitiateCheckout: 'begin_checkout',
                AddPaymentInfo: 'add_payment_info',
                AddShippingInfo: 'add_shipping_info',
                AddToWishlist: 'add_to_wishlist',
                Search: 'search',
                SelectItem: 'select_item',
                Lead: 'generate_lead',
                CompleteRegistration: 'sign_up'
            };

            function money(value) {
                var n = parseFloat(value);
                return isFinite(n) ? n : undefined;
            }

            function send(name, meta, row) {
                var ga4Name = MAP[name];
                if (!ga4Name) return;

                meta = meta || {};
                row = row || {};

                var params = {};
                if (row.url) params.page_location = row.url;
                if (row.page_title) params.page_title = row.page_title;

                var data = meta.event_data || {};
                var value = money(data.value != null ? data.value : data.total);
                if (value !== undefined) params.value = value;
                if (data.currency) params.currency = data.currency;

                if (data.product_id || data.product_name) {
                    params.items = [{
                        item_id: String(data.product_id || ''),
                        item_name: String(data.product_name || ''),
                        price: money(data.product_price)
                    }];
                }

                if (name === 'Search' && meta.click_target) {
                    params.search_term = String(meta.click_target);
                }

                gtag('event', ga4Name, params);
            }

            // Purchase is fired by the confirmation page, not by the queue: the
            // order row is written server-side. transaction_id is the invoice,
            // and GA4 deduplicates purchases that share one, so this tag and the
            // Measurement Protocol copy (when enabled) count once.
            function purchase(data) {
                data = data || {};

                gtag('event', 'purchase', {
                    transaction_id: String(data.transaction_id || data.order_id || ''),
                    value: money(data.value != null ? data.value : data.total),
                    currency: data.currency || 'BDT',
                    shipping: money(data.shipping),
                    items: data.items || []
                });
            }

            return { send: send, purchase: purchase };
        })();
    </script>
@endif

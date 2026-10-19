/**
 * TikTok Tracking for Adora
 * Handles client-side Pixel events + server-side Events API
 * Also handles localStorage event logging (parity with facebook-tracking.js)
 * 
 * Dependencies: window.ttq (TikTok Pixel), window.tiktokTrackingConfig
 * 
 * @author Code Guru Monu - Mission Barisal
 * @version 1.0.0
 */

(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define([], factory);
    } else if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        root.TikTokTracker = factory();
    }
})(typeof window !== 'undefined' ? window : this, function () {
    'use strict';

    // ──────────────────────────────────────────────
    // 1. Event ID Generator
    // ──────────────────────────────────────────────
    function generateEventId() {
        var array = new Uint32Array(4);
        if (typeof crypto !== 'undefined' && crypto.getRandomValues) {
            crypto.getRandomValues(array);
        } else {
            for (var i = 0; i < 4; i++) {
                array[i] = Math.floor(Math.random() * 4294967296);
            }
        }
        return 'tt_' + Array.from(array, function (v) { return v.toString(16).padStart(8, '0'); }).join('') + '_' + Date.now();
    }

    // ──────────────────────────────────────────────
    // 2. TikTokTracker Constructor
    // ──────────────────────────────────────────────
    function TikTokTracker(config) {
        this.config = config || {};
        this.pixelId = config.pixelId || null;
        this.enabled = config.enabled || false;
        this.serverSideEnabled = config.serverSideEnabled || false;
        this.testMode = config.testMode || false;
        this.eventQueue = [];
        this.initialized = false;

        if (this.enabled && this.pixelId) {
            this.init();
        }
    }

    TikTokTracker.prototype.init = function () {
        if (this.initialized) return;
        this.initialized = true;

        // If ttq exists already, great; otherwise ttq will be loaded by event-layer-head
        if (typeof ttq !== 'undefined') {
            this.ttq = ttq;
        }

        // Bind DOM events for enhanced e-commerce
        if (typeof this.bindDOMEvents === 'function') {
            this.bindDOMEvents();
        }
    };

    // ──────────────────────────────────────────────
    // 3. Core Tracking Methods
    // ──────────────────────────────────────────────

    /**
     * Send a client-side TikTok Pixel event
     */
    TikTokTracker.prototype.sendClientEvent = function (eventName, eventData, eventId) {
        if (!this.enabled || !this.pixelId) return;

        try {
            if (typeof ttq === 'function') {
                ttq.track(eventName, eventData, { eventID: eventId });
            } else if (typeof window.ttq !== 'undefined' && typeof window.ttq.track === 'function') {
                window.ttq.track(eventName, eventData, { eventID: eventId });
            }
        } catch (e) {
            console.warn('[TT] Client event error:', e);
        }
    };

    /**
     * Send a server-side TikTok Events API event
     */
    TikTokTracker.prototype.sendServerEvent = function (eventName, eventData, eventId) {
        if (!this.serverSideEnabled) return;

        try {
            var payload = JSON.stringify({
                event_name: eventName,
                event_data: eventData,
                event_id: eventId,
                url: window.location.href
            });

            if (navigator.sendBeacon) {
                navigator.sendBeacon('/api/tiktok/track', payload);
            } else {
                fetch('/api/tiktok/track', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: payload,
                    keepalive: true
                }).catch(function (e) { /* silent */ });
            }
        } catch (e) {
            console.warn('[TT] Server event error:', e);
        }
    };

    /**
     * Log event to localStorage (for admin dashboard reporting)
     */
    TikTokTracker.prototype.logLocalEvent = function (eventName, eventData) {
        try {
            if (typeof window.__EVENT_LOGGER__ !== 'undefined' && typeof window.__EVENT_LOGGER__.queue === 'function') {
                window.__EVENT_LOGGER__.queue(eventName, eventData);
            }
        } catch (e) { /* silent */ }
    };

    // ──────────────────────────────────────────────
    // 4. Standard Event Methods
    // ──────────────────────────────────────────────

    TikTokTracker.prototype.trackPageView = function (pageData) {
        var eventData = pageData || {};
        var eventId = generateEventId();
        this.sendClientEvent('PageView', eventData, eventId);
        this.logLocalEvent('PageView', eventData);
    };

    TikTokTracker.prototype.trackViewContent = function (productData) {
        var eventId = generateEventId();
        var eventData = {
            content_id: productData.product_id || productData.content_id || '',
            content_type: 'product',
            content_name: productData.name || '',
            price: productData.price || 0,
            currency: productData.currency || 'BDT',
            content_category: productData.category || ''
        };
        this.sendClientEvent('ViewContent', eventData, eventId);
        this.sendServerEvent('ViewContent', eventData, eventId);
        this.logLocalEvent('ViewContent', eventData);
    };

    TikTokTracker.prototype.trackSearch = function (searchData) {
        var eventId = generateEventId();
        var eventData = {
            query: searchData.query || '',
            results_count: searchData.results_count || 0
        };
        this.sendClientEvent('Search', eventData, eventId);
        this.sendServerEvent('Search', eventData, eventId);
        this.logLocalEvent('Search', eventData);
    };

    TikTokTracker.prototype.trackAddToCart = function (cartData) {
        var eventId = generateEventId();
        var eventData = {
            content_id: cartData.product_id || cartData.content_id || '',
            content_type: 'product',
            content_name: cartData.name || '',
            price: cartData.price || 0,
            quantity: cartData.quantity || 1,
            currency: cartData.currency || 'BDT',
            value: (cartData.price || 0) * (cartData.quantity || 1)
        };
        this.sendClientEvent('AddToCart', eventData, eventId);
        this.sendServerEvent('AddToCart', eventData, eventId);
        this.logLocalEvent('AddToCart', eventData);

        // Also flush event logger
        try {
            if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.flush === 'function') {
                window.__EVENT_LOGGER__.flush();
            }
        } catch (e) {}
    };

    TikTokTracker.prototype.trackAddToWishlist = function (wishlistData) {
        var eventId = generateEventId();
        var eventData = {
            content_id: wishlistData.product_id || '',
            content_type: 'product',
            content_name: wishlistData.name || '',
            price: wishlistData.price || 0,
            currency: wishlistData.currency || 'BDT'
        };
        this.sendClientEvent('AddToWishlist', eventData, eventId);
        this.sendServerEvent('AddToWishlist', eventData, eventId);
        this.logLocalEvent('AddToWishlist', eventData);
    };

    TikTokTracker.prototype.trackAddPaymentInfo = function (paymentData) {
        var eventId = generateEventId();
        var eventData = {
            method: paymentData.method || '',
            currency: paymentData.currency || 'BDT',
            value: paymentData.value || 0
        };
        this.sendClientEvent('AddPaymentInfo', eventData, eventId);
        this.sendServerEvent('AddPaymentInfo', eventData, eventId);
        this.logLocalEvent('AddPaymentInfo', eventData);
    };

    TikTokTracker.prototype.trackInitiateCheckout = function (checkoutData) {
        var eventId = generateEventId();
        var eventData = {
            price: checkoutData.total || checkoutData.value || 0,
            currency: checkoutData.currency || 'BDT',
            quantity: checkoutData.num_items || 0,
            content_type: 'product',
            contents: Array.isArray(checkoutData.product_ids) 
                ? checkoutData.product_ids.map(function (id) { return { content_id: id, quantity: 1 }; }) 
                : []
        };
        this.sendClientEvent('InitiateCheckout', eventData, eventId);
        this.sendServerEvent('InitiateCheckout', eventData, eventId);
        this.logLocalEvent('InitiateCheckout', eventData);
    };

    TikTokTracker.prototype.trackPurchase = function (purchaseData) {
        var eventId = generateEventId();
        var eventData = {
            value: purchaseData.total || purchaseData.value || 0,
            currency: purchaseData.currency || 'BDT',
            quantity: purchaseData.num_items || 0,
            content_type: 'product',
            order_id: purchaseData.order_id || '',
            contents: Array.isArray(purchaseData.product_ids)
                ? purchaseData.product_ids.map(function (id) { return { content_id: id, quantity: 1 }; })
                : []
        };
        this.sendClientEvent('Purchase', eventData, eventId);
        this.sendServerEvent('Purchase', eventData, eventId);
        this.logLocalEvent('Purchase', eventData);

        // Force flush on purchase
        try {
            if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.flush === 'function') {
                window.__EVENT_LOGGER__.flush();
            }
        } catch (e) {}
    };

    TikTokTracker.prototype.trackLead = function (leadData) {
        var eventId = generateEventId();
        var eventData = {
            lead_type: leadData.type || leadData.lead_type || '',
            value: leadData.value || 0,
            currency: leadData.currency || 'BDT'
        };
        this.sendClientEvent('Lead', eventData, eventId);
        this.sendServerEvent('Lead', eventData, eventId);
        this.logLocalEvent('Lead', eventData);
    };

    TikTokTracker.prototype.trackCompleteRegistration = function (regData) {
        var eventId = generateEventId();
        var eventData = {
            method: regData.method || regData.type || 'email'
        };
        this.sendClientEvent('CompleteRegistration', eventData, eventId);
        this.sendServerEvent('CompleteRegistration', eventData, eventId);
        this.logLocalEvent('CompleteRegistration', eventData);
    };

    TikTokTracker.prototype.trackContact = function (contactData) {
        var eventId = generateEventId();
        var eventData = {
            method: contactData.method || contactData.type || 'form',
            subject: contactData.subject || ''
        };
        this.sendClientEvent('Contact', eventData, eventId);
        this.sendServerEvent('Contact', eventData, eventId);
        this.logLocalEvent('Contact', eventData);
    };

    TikTokTracker.prototype.trackCustomizeProduct = function (customData) {
        var eventId = generateEventId();
        var eventData = {
            content_id: customData.product_id || '',
            content_name: customData.name || '',
            content_category: customData.category || '',
            price: customData.price || 0,
            currency: customData.currency || 'BDT',
            customization: customData.customization || ''
        };
        this.sendClientEvent('CustomizeProduct', eventData, eventId);
        this.sendServerEvent('CustomizeProduct', eventData, eventId);
        this.logLocalEvent('CustomizeProduct', eventData);
    };

    TikTokTracker.prototype.trackCustomEvent = function (eventName, eventData) {
        var eventId = generateEventId();
        var data = eventData || {};
        this.sendClientEvent(eventName, data, eventId);
        this.sendServerEvent(eventName, data, eventId);
        this.logLocalEvent(eventName, data);
    };

    // ──────────────────────────────────────────────
    // 5. DOM Event Binding (Enhanced E-commerce)
    // ──────────────────────────────────────────────
    TikTokTracker.prototype.bindDOMEvents = function () {
        var self = this;

        // Re-use facebook-tracking.js selectors for cross-platform tracking
        // ViewContent
        document.querySelectorAll('[data-product-id]').forEach(function (el) {
            if (el.getAttribute('data-tt-tracked')) return;
            el.setAttribute('data-tt-tracked', '1');
            el.addEventListener('click', function (e) {
                if (el.tagName === 'A' || el.closest('a')) return;
                self.trackViewContent({
                    product_id: el.getAttribute('data-product-id') || el.getAttribute('data-id'),
                    name: el.getAttribute('data-product-name') || el.getAttribute('data-name') || '',
                    price: el.getAttribute('data-product-price') || el.getAttribute('data-price') || 0,
                    category: el.getAttribute('data-product-category') || ''
                });
            });
        });

        // AddToCart
        document.querySelectorAll('[data-add-to-cart], .add-to-cart, .btn-add-cart, [data-action="add-to-cart"]').forEach(function (btn) {
            if (btn.getAttribute('data-tt-tracked')) return;
            btn.setAttribute('data-tt-tracked', '1');
            btn.addEventListener('click', function (e) {
                self.trackAddToCart({
                    product_id: btn.getAttribute('data-product-id') || btn.getAttribute('data-id') || '',
                    name: btn.getAttribute('data-product-name') || btn.getAttribute('data-name') || '',
                    price: btn.getAttribute('data-product-price') || btn.getAttribute('data-price') || 0,
                    quantity: btn.getAttribute('data-quantity') || btn.getAttribute('data-qty') || 1
                });
            });
        });

        // InitiateCheckout
        document.querySelectorAll('a[href*="checkout"], a[href*="check-out"], button[data-checkout], [data-action="checkout"]').forEach(function (el) {
            if (el.getAttribute('data-tt-tracked')) return;
            el.setAttribute('data-tt-tracked', '1');
            el.addEventListener('click', function (e) {
                self.trackInitiateCheckout({
                    total: el.getAttribute('data-cart-total') || el.getAttribute('data-total') || 0,
                    num_items: el.getAttribute('data-cart-count') || el.getAttribute('data-count') || 0,
                    product_ids: []
                });
            });
        });
    };

    // ──────────────────────────────────────────────
    // 6. Utility
    // ──────────────────────────────────────────────
    TikTokTracker.prototype.isEnabled = function () {
        return this.enabled && !!this.pixelId;
    };

    return TikTokTracker;
});

// ──────────────────────────────────────────────
// Auto-initialize if config is available
// ──────────────────────────────────────────────
(function () {
    if (typeof window === 'undefined') return;

    var config = window.tiktokTrackingConfig;
    if (!config) return;

    // Use same pattern as facebook-tracking.js
    var tracker = new TikTokTracker(config);
    window.__ttTracker = tracker;

    // Fire PageView on init
    if (tracker.isEnabled()) {
        tracker.trackPageView({ url: window.location.href, title: document.title });
    }

    // Bind DOM events
    if (typeof tracker.bindDOMEvents === 'function') {
        tracker.bindDOMEvents();
    }
})();

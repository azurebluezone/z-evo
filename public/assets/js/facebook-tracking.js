/**
 * Facebook & Social Tracking Integration
 * Modern e-commerce tracking with:
 * - Facebook Pixel (client-side)
 * - Facebook Conversions API (server-side)
 * - Local storage batching via __EVENT_LOGGER__
 * - User-agent parsing for device/OS/browser
 * - All standard Facebook e-commerce events
 * - Auto DOM event binding
 * - Event deduplication with event_id
 */

(function () {
    'use strict';

    // ──────────────────────────────────────────────
    // 1. User-Agent Parser
    // ──────────────────────────────────────────────
    var UAParser = {
        ua: navigator.userAgent || '',

        getDevice: function () {
            var ua = this.ua;
            if (/Mobile|Android|iPhone|iP(hone|od|ad)|IEMobile|WPDesktop|BlackBerry|Opera Mini/i.test(ua)) {
                if (/iPad|Tablet/i.test(ua)) return 'Tablet';
                return 'Mobile';
            }
            return 'Desktop';
        },

        getOS: function () {
            var ua = this.ua;
            if (/Windows NT 10/i.test(ua)) return 'Windows 10';
            if (/Windows NT 11/i.test(ua)) return 'Windows 11';
            if (/Windows NT 6\.3/i.test(ua)) return 'Windows 8.1';
            if (/Windows NT 6\.2/i.test(ua)) return 'Windows 8';
            if (/Windows NT 6\.1/i.test(ua)) return 'Windows 7';
            if (/Windows Phone/i.test(ua)) return 'Windows Phone';
            if (/Mac OS X (\d+[._]\d+)/.test(ua)) return 'macOS';
            if (/Android (\d+\.?\d*)/.test(ua)) return 'Android ' + RegExp.$1;
            if (/iOS (\d+[._]\d+)/.test(ua) || /iPhone OS (\d+[._]\d+)/.test(ua)) return 'iOS ' + RegExp.$1.replace('_', '.');
            if (/CrOS/.test(ua)) return 'Chrome OS';
            if (/Linux/i.test(ua)) return 'Linux';
            return 'Unknown';
        },

        getBrowser: function () {
            var ua = this.ua;
            if (/Edg\//i.test(ua)) return 'Edge';
            if (/OPR|Opera/i.test(ua)) return 'Opera';
            if (/Chrome\/(\d+)/.test(ua) && !/Edg\//i.test(ua)) return 'Chrome';
            if (/Firefox\/(\d+)/.test(ua)) return 'Firefox';
            if (/Safari\/(\d+)/.test(ua) && !/Chrome/.test(ua)) return 'Safari';
            if (/Trident|MSIE/.test(ua)) return 'Internet Explorer';
            return 'Unknown';
        },

        getIP: function () {
            // Best effort: return null, server will use request IP
            return null;
        }
    };

    // ──────────────────────────────────────────────
    // 2. Facebook Tracking SDK (Synchronous init)
    // ─────────��────────────────────────────────────
    function setupFacebookSDK(pixelId) {
        if (!pixelId || window.fbq) return;

        // Facebook Pixel base code
        window.fbq = function() {
            fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
        };
        if (!window._fbq) window._fbq = fbq;
        fbq.push = fbq;
        fbq.loaded = true;
        fbq.version = '2.0';
        fbq.queue = [];

        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://connect.facebook.net/en_US/fbevents.js';
        var firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(script, firstScript);

        fbq('init', pixelId);
        fbq('track', 'PageView');
    }

    // ──────────────────────────────────────────────
    // 3. Event ID Generator (for deduplication)
    // ──────────────────────────────────────────────
    function generateEventId() {
        var arr = new Uint8Array(16);
        crypto.getRandomValues(arr);
        return Array.from(arr, function (b) { return b.toString(16).padStart(2, '0'); }).join('');
    }

    // ──────────────────────────────────────────────
    // 4. Main FacebookTracker
    // ──────────────────────────────────────────────
    var instance = null;

    function FacebookTracker(config) {
        if (instance) return instance;

        this.config = config || {};
        this.enabled = this.config.enabled || false;
        this.pixelId = this.config.pixelId || null;
        this.accessToken = this.config.accessToken || null;
        this.apiVersion = this.config.apiVersion || 'v18.0';
        this.serverSide = this.config.serverSide || false;
        this.enhancedEcommerce = this.config.enhancedEcommerce || false;
        this.initialized = false;

        // Stored user data from registration/login
        this.userData = {};

        // Event queue for batching
        this.eventQueue = [];
        this.flushTimer = null;

        instance = this;
    }

    // ──────────────────────────────────────────────
    // 4a. User Data Management
    // ──────────────────────────────────────────────
    FacebookTracker.prototype.setUserData = function (userData) {
        this.userData = userData || {};
    };

    FacebookTracker.prototype.getUserData = function () {
        var data = {};
        var emailInput = document.querySelector('input[name="email"], input[type="email"], [data-user-email]');
        var phoneInput = document.querySelector('input[name="phone"], input[type="tel"], [data-user-phone]');

        // Merge stored data
        if (this.userData.email) data.email = this.userData.email;
        else if (emailInput && emailInput.value) data.email = emailInput.value;

        if (this.userData.phone) data.phone = this.userData.phone;
        else if (phoneInput && phoneInput.value) data.phone = phoneInput.value;

        if (this.userData.first_name) data.first_name = this.userData.first_name;
        if (this.userData.last_name) data.last_name = this.userData.last_name;
        if (this.userData.city) data.city = this.userData.city;
        if (this.userData.country) data.country = this.userData.country;
        if (this.userData.zip) data.zip = this.userData.zip;

        return data;
    };

    // ──────────────────────────────────────────────
    // 4b. Client-Side Event (Facebook Pixel)
    // ──────────────────────────────────────────────
    FacebookTracker.prototype.sendClientEvent = function (eventName, eventData, eventId) {
        if (!this.enabled || !window.fbq) return;

        try {
            var params = eventData || {};
            if (eventId) {
                // Deduplication: include eventID as 4th parameter
                fbq('track', eventName, params, { eventID: eventId });
            } else {
                fbq('track', eventName, params);
            }
        } catch (e) {
            // Silently fail
        }
    };

    // ──────────────────────────────────────────────
    // 4c. Server-Side Event (via /api/facebook/track)
    // ──────────────────────────────────────────────
    FacebookTracker.prototype.sendServerEvent = function (eventName, eventData, eventId) {
        if (!this.enabled || !this.serverSide) return;

        var userData = this.getUserData();
        var payload = {
            event_name: eventName,
            event_data: eventData || {},
            user_data: userData,
            event_id: eventId || generateEventId()
        };

        // Use sendBeacon for reliability on page exit
        try {
            if (navigator.sendBeacon) {
                var blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
                navigator.sendBeacon('/api/facebook/track', blob);
                return;
            }
        } catch (e) {}

        // Fallback to fetch
        try {
            fetch('/api/facebook/track', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
                keepalive: true
            }).catch(function () {});
        } catch (e) {}
    };

    // ──────────────────────────────────────────────
    // 4d. Local Event Log (via __EVENT_LOGGER__)
    // ──────────────────────────────────────────────
    FacebookTracker.prototype.logLocalEvent = function (eventName, eventData, eventId) {
        try {
            if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.queue === 'function') {
                window.__EVENT_LOGGER__.queue(eventName, eventData || {}, eventId);
            }
        } catch (e) {}
    };

    // ──────────────────────────────────────────────
    // 4e. Track Methods (All Standard Events)
    // ──────────────────────────────────────────────

    /**
     * PageView - Page visit
     */
    FacebookTracker.prototype.trackPageView = function (pageData) {
        var eventId = generateEventId();
        var eventData = {
            page_url: pageData && pageData.url ? pageData.url : window.location.href,
            page_title: document.title,
            page_referrer: document.referrer
        };

        this.sendClientEvent('PageView', eventData, eventId);
        this.sendServerEvent('PageView', eventData, eventId);
        this.logLocalEvent('PageView', eventData);
    };

    /**
     * ViewContent - Product/page viewed
     */
    FacebookTracker.prototype.trackViewContent = function (productData) {
        var eventId = generateEventId();
        var eventData = {
            currency: 'BDT',
            value: parseFloat(productData.price) || 0,
            content_ids: [String(productData.product_id || productData.id || '')],
            content_type: 'product',
            content_name: productData.name || '',
            content_category: productData.category || ''
        };

        this.sendClientEvent('ViewContent', eventData, eventId);
        this.sendServerEvent('ViewContent', eventData, eventId);
        this.logLocalEvent('ViewContent', eventData);
    };

    /**
     * Search - Site search
     */
    FacebookTracker.prototype.trackSearch = function (searchData) {
        var eventId = generateEventId();
        var eventData = {
            search_string: searchData.query || searchData.search_string || '',
            content_type: 'product',
            currency: 'BDT',
            value: parseFloat(searchData.value) || 0
        };

        this.sendClientEvent('Search', eventData, eventId);
        this.sendServerEvent('Search', eventData, eventId);
        this.logLocalEvent('Search', eventData);
    };

    /**
     * AddToCart - Product added to cart
     */
    FacebookTracker.prototype.trackAddToCart = function (productData) {
        var eventId = generateEventId();
        var eventData = {
            currency: 'BDT',
            value: parseFloat(productData.price) || 0,
            content_ids: [String(productData.product_id || productData.id || '')],
            content_type: 'product',
            content_name: productData.name || '',
            quantity: parseInt(productData.quantity) || 1
        };

        this.sendClientEvent('AddToCart', eventData, eventId);
        this.sendServerEvent('AddToCart', eventData, eventId);
        this.logLocalEvent('AddToCart', eventData);
    };

    /**
     * AddToWishlist - Product added to wishlist
     */
    FacebookTracker.prototype.trackAddToWishlist = function (productData) {
        var eventId = generateEventId();
        var eventData = {
            currency: 'BDT',
            value: parseFloat(productData.price) || 0,
            content_ids: [String(productData.product_id || productData.id || '')],
            content_type: 'product',
            content_name: productData.name || '',
            quantity: parseInt(productData.quantity) || 1
        };

        this.sendClientEvent('AddToWishlist', eventData, eventId);
        this.sendServerEvent('AddToWishlist', eventData, eventId);
        this.logLocalEvent('AddToWishlist', eventData);
    };

    /**
     * AddPaymentInfo - Payment info added
     */
    FacebookTracker.prototype.trackAddPaymentInfo = function (paymentData) {
        var eventId = generateEventId();
        var eventData = {
            currency: 'BDT',
            value: parseFloat(paymentData.value) || 0,
            content_ids: paymentData.content_ids || [],
            content_type: 'product'
        };

        this.sendClientEvent('AddPaymentInfo', eventData, eventId);
        this.sendServerEvent('AddPaymentInfo', eventData, eventId);
        this.logLocalEvent('AddPaymentInfo', eventData);
    };

    /**
     * InitiateCheckout - Checkout flow started
     */
    FacebookTracker.prototype.trackInitiateCheckout = function (cartData) {
        var eventId = generateEventId();
        var eventData = {
            currency: 'BDT',
            value: parseFloat(cartData.total) || 0,
            content_ids: cartData.product_ids || [],
            content_type: 'product',
            num_items: parseInt(cartData.num_items) || 0
        };

        this.sendClientEvent('InitiateCheckout', eventData, eventId);
        this.sendServerEvent('InitiateCheckout', eventData, eventId);
        this.logLocalEvent('InitiateCheckout', eventData);
    };

    /**
     * Purchase - Order completed
     */
    FacebookTracker.prototype.trackPurchase = function (orderData, eventId) {
        // The server mints this id when the order row is committed and prints it
        // into the confirmation page. Re-using it is the whole point: Meta drops
        // the browser hit when the Conversions API already sent the same id.
        var id = eventId || generateEventId();
        // Both order-success templates send `total`; the older callers sent
        // `amount`. Reading only `amount` made every browser Purchase worth 0.
        var rawValue = orderData.total != null ? orderData.total : orderData.amount;
        var eventData = {
            currency: 'BDT',
            value: parseFloat(rawValue) || 0,
            content_ids: orderData.product_ids || [],
            content_type: 'product',
            order_id: String(orderData.order_id || ''),
            num_items: parseInt(orderData.num_items) || 1
        };

        this.sendClientEvent('Purchase', eventData, id);
        this.sendServerEvent('Purchase', eventData, id);
        this.logLocalEvent('Purchase', eventData, id);
    };

    /**
     * Lead - Lead/signup completed
     */
    FacebookTracker.prototype.trackLead = function (leadData) {
        var eventId = generateEventId();
        var eventData = {
            currency: 'BDT',
            value: parseFloat(leadData.value) || 0
        };

        this.sendClientEvent('Lead', eventData, eventId);
        this.sendServerEvent('Lead', eventData, eventId);
        this.logLocalEvent('Lead', eventData);
    };

    /**
     * CompleteRegistration - Registration completed
     */
    FacebookTracker.prototype.trackCompleteRegistration = function (regData) {
        var eventId = generateEventId();
        var eventData = {
            currency: 'BDT',
            value: parseFloat(regData.value) || 0,
            status: regData.status !== undefined ? regData.status : true
        };

        this.sendClientEvent('CompleteRegistration', eventData, eventId);
        this.sendServerEvent('CompleteRegistration', eventData, eventId);
        this.logLocalEvent('CompleteRegistration', eventData);
    };

    /**
     * Contact - Contact initiated
     */
    FacebookTracker.prototype.trackContact = function (contactData) {
        var eventId = generateEventId();
        var eventData = {
            content_name: contactData.subject || 'Contact Form'
        };

        this.sendClientEvent('Contact', eventData, eventId);
        this.sendServerEvent('Contact', eventData, eventId);
        this.logLocalEvent('Contact', eventData);
    };

    /**
     * CustomizeProduct - Product customization
     */
    FacebookTracker.prototype.trackCustomizeProduct = function (customData) {
        var eventId = generateEventId();
        var eventData = {
            content_ids: [String(customData.product_id || '')],
            content_type: 'product',
            content_name: customData.name || '',
            currency: 'BDT',
            value: parseFloat(customData.price) || 0
        };

        this.sendClientEvent('CustomizeProduct', eventData, eventId);
        this.sendServerEvent('CustomizeProduct', eventData, eventId);
        this.logLocalEvent('CustomizeProduct', eventData);
    };

    /**
     * Track custom event
     */
    FacebookTracker.prototype.trackCustomEvent = function (eventName, eventData) {
        var eventId = generateEventId();

        this.sendClientEvent(eventName, eventData || {}, eventId);
        this.sendServerEvent(eventName, eventData || {}, eventId);
        this.logLocalEvent(eventName, eventData || {});
    };

    // ──────────────────────────────────────────────
    // 4f. DOM Event Binding (Enhanced E-commerce)
    // ──────────────────────────────────────────────
    FacebookTracker.prototype.bindDOMEvents = function () {
        var self = this;

        // ViewContent: elements with data-product-id
        var productElements = document.querySelectorAll('[data-product-id]');
        productElements.forEach(function (el) {
            // Don't re-bind
            if (el.getAttribute('data-fb-tracked')) return;
            el.setAttribute('data-fb-tracked', '1');

            el.addEventListener('click', function (e) {
                if (el.tagName === 'A' || el.closest('a')) return; // Let navigation happen

                var data = {
                    product_id: el.getAttribute('data-product-id') || el.getAttribute('data-id'),
                    name: el.getAttribute('data-product-name') || el.getAttribute('data-name') || '',
                    price: el.getAttribute('data-product-price') || el.getAttribute('data-price') || 0,
                    category: el.getAttribute('data-product-category') || el.getAttribute('data-category') || ''
                };

                if (data.product_id) {
                    self.trackViewContent(data);
                }
            });
        });

        // AddToCart: elements with data-add-to-cart or .add-to-cart buttons
        var addToCartButtons = document.querySelectorAll('[data-add-to-cart], .add-to-cart, .btn-add-cart, [data-action="add-to-cart"]');
        addToCartButtons.forEach(function (btn) {
            if (btn.getAttribute('data-fb-tracked')) return;
            btn.setAttribute('data-fb-tracked', '1');

            btn.addEventListener('click', function (e) {
                var data = {
                    product_id: btn.getAttribute('data-product-id') || btn.getAttribute('data-id') || '',
                    name: btn.getAttribute('data-product-name') || btn.getAttribute('data-name') || '',
                    price: btn.getAttribute('data-product-price') || btn.getAttribute('data-price') || 0,
                    quantity: btn.getAttribute('data-quantity') || btn.getAttribute('data-qty') || 1
                };

                if (data.product_id || data.name) {
                    self.trackAddToCart(data);
                }

                // Also flush the event logger
                try {
                    if (window.__EVENT_LOGGER__ && typeof window.__EVENT_LOGGER__.flush === 'function') {
                        window.__EVENT_LOGGER__.flush();
                    }
                } catch (e) {}
            });
        });

        // AddToWishlist: elements with data-add-to-wishlist
        var wishlistButtons = document.querySelectorAll('[data-add-to-wishlist], .add-to-wishlist, .btn-wishlist, [data-action="add-to-wishlist"]');
        wishlistButtons.forEach(function (btn) {
            if (btn.getAttribute('data-fb-tracked')) return;
            btn.setAttribute('data-fb-tracked', '1');

            btn.addEventListener('click', function (e) {
                var data = {
                    product_id: btn.getAttribute('data-product-id') || btn.getAttribute('data-id') || '',
                    name: btn.getAttribute('data-product-name') || btn.getAttribute('data-name') || '',
                    price: btn.getAttribute('data-product-price') || btn.getAttribute('data-price') || 0,
                    quantity: 1
                };

                if (data.product_id || data.name) {
                    self.trackAddToWishlist(data);
                }
            });
        });

        // Search: form with search input
        var searchForms = document.querySelectorAll('form[role="search"], form.search-form, form:has(input[type="search"]), form:has(input[name="search"]), form.header-search-form');
        searchForms.forEach(function (form) {
            if (form.getAttribute('data-fb-tracked')) return;
            form.setAttribute('data-fb-tracked', '1');

            form.addEventListener('submit', function (e) {
                var input = form.querySelector('input[name="q"], input[name="query"], input[name="search"], input[type="search"]');
                if (input && input.value.trim()) {
                    self.trackSearch({ query: input.value.trim() });
                }
            });
        });

        // Checkout initiation: links/buttons to checkout
        var checkoutLinks = document.querySelectorAll('a[href*="checkout"], a[href*="check-out"], button[data-checkout], [data-action="checkout"]');
        checkoutLinks.forEach(function (el) {
            if (el.getAttribute('data-fb-tracked')) return;
            el.setAttribute('data-fb-tracked', '1');

            el.addEventListener('click', function (e) {
                var data = {
                    total: el.getAttribute('data-cart-total') || el.getAttribute('data-total') || 0,
                    num_items: el.getAttribute('data-cart-count') || el.getAttribute('data-count') || 0,
                    product_ids: []
                };

                // Try to parse product IDs from cart data attribute
                var idsAttr = el.getAttribute('data-product-ids');
                if (idsAttr) {
                    try { data.product_ids = JSON.parse(idsAttr); } catch (ex) {}
                }

                self.trackInitiateCheckout(data);
            });
        });

        // Contact form submission
        var contactForms = document.querySelectorAll('form[action*="contact"], form.contact-form, #contactForm');
        contactForms.forEach(function (form) {
            if (form.getAttribute('data-fb-tracked')) return;
            form.setAttribute('data-fb-tracked', '1');

            form.addEventListener('submit', function (e) {
                var subjectInput = form.querySelector('input[name="subject"], input[name="title"], textarea[name="message"]');
                var subject = subjectInput ? subjectInput.value.substring(0, 50) : 'Contact Form';
                self.trackContact({ subject: subject });
            });
        });

        // Registration form submission
        var regForms = document.querySelectorAll('form[action*="register"], form.registration-form, form[action*="signup"], #registerForm');
        regForms.forEach(function (form) {
            if (form.getAttribute('data-fb-tracked')) return;
            form.setAttribute('data-fb-tracked', '1');

            form.addEventListener('submit', function (e) {
                // Track on successful submission (defer to let the form submit)
                setTimeout(function () {
                    self.trackCompleteRegistration({ status: true });
                }, 100);
            });
        });
    };

    // ���─────────────────────────────────────────────
    // 4g. Init
    // ──────────────────────────────────────────────
    FacebookTracker.prototype.init = function () {
        if (this.initialized) return;
        if (!this.enabled) return;

        // Setup Facebook SDK if pixel ID exists
        if (this.pixelId) {
            setupFacebookSDK(this.pixelId);
        }

        // Track initial page view
        this.trackPageView();

        // Bind DOM events if enhanced e-commerce is enabled
        if (this.enhancedEcommerce) {
            this.bindDOMEvents();
        }

        // Listen for custom events from other scripts
        this.listenToCustomEvents();

        this.initialized = true;
    };

    // ���─────────────────────────────────────────────
    // 4h. Custom Event Listener
    // ──────────────────────────────────────────────
    FacebookTracker.prototype.listenToCustomEvents = function () {
        var self = this;

        // Listen for dispatched custom DOM events
        document.addEventListener('fb-track-event', function (e) {
            if (e.detail && e.detail.eventName) {
                self.trackCustomEvent(e.detail.eventName, e.detail.eventData || {});
            }
        });

        // Expose global API
        window.fbTrack = function (eventName, eventData) {
            if (self.initialized) {
                self.trackCustomEvent(eventName, eventData);
            }
        };
    };

    // ──────────────────────────────────────────────
    // 5. Auto-Initialize
    // ──────────────────────────────────────────────
    function autoInit() {
        var config = window.facebookTrackingConfig || {
            enabled: false,
            pixelId: null,
            serverSide: false,
            enhancedEcommerce: false,
            apiVersion: 'v18.0'
        };

        var tracker = new FacebookTracker(config);

        if (config.enabled) {
            // Store user data if available from page
            if (config.user) {
                tracker.setUserData(config.user);
            }

            // Initialize on DOM ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function () {
                    tracker.init();
                });
            } else {
                tracker.init();
            }
        }

        // Expose globally
        window.facebookTracker = tracker;
    }

    // Start
    autoInit();

})();

/* ==========================================================================
   FM APP
   Front-end behaviour shared by every redesigned page.

   Everything here talks to endpoints that already existed in the Laravel
   backend (cart store / increment / decrement / remove / shortlist,
   add-to-wishlist / remove-from-wishlist / wishlist-count, livesearch).
   No fake in-memory state, no duplicated cart logic.
   ========================================================================== */

(function () {
    'use strict';

    var CFG = window.FM_CONFIG || {};

    /* ------------------------------------------------------------------ *
     * Toast
     *
     * The approved design already ships its own toast (#actionToast) with
     * index.js showToast(), so that is what shoppers see. The container
     * below is only a safety net for pages loaded without index.js.
     * ------------------------------------------------------------------ */
    function toast(message, kind) {
        if (!message) return;

        if (typeof window.showToast === 'function') {
            window.showToast(message);
            return;
        }

        var host = document.getElementById('fmToastContainer');
        if (!host) {
            host = document.createElement('div');
            host.id = 'fmToastContainer';
            host.className = 'fm-toast-host';
            document.body.appendChild(host);
        }

        var el = document.createElement('div');
        el.className = 'fm-toast' + (kind ? ' fm-toast--' + kind : '');
        el.setAttribute('role', 'status');

        var icon = document.createElement('i');
        icon.className = kind === 'error'
            ? 'bi bi-exclamation-circle-fill'
            : (kind === 'info' ? 'bi bi-info-circle-fill' : 'bi bi-check-circle-fill');

        var text = document.createElement('span');
        text.textContent = message;

        el.appendChild(icon);
        el.appendChild(text);
        host.appendChild(el);

        window.setTimeout(function () {
            el.style.transition = 'opacity .25s ease';
            el.style.opacity = '0';
            window.setTimeout(function () { el.remove(); }, 250);
        }, 2600);
    }

    /* ------------------------------------------------------------------ *
     * Fetch helper
     * ------------------------------------------------------------------ */
    function request(url, options) {
        options = options || {};
        var headers = Object.assign({
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        }, options.headers || {});

        if (options.method && options.method !== 'GET' && CFG.csrfToken) {
            headers['X-CSRF-TOKEN'] = CFG.csrfToken;
        }

        return fetch(url, {
            method: options.method || 'GET',
            credentials: 'same-origin',
            headers: headers,
            body: options.body,
        }).then(function (res) {
            return res.json().catch(function () { return {}; }).then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        });
    }

    function fail() {
        toast((CFG.messages && CFG.messages.error) || 'Something went wrong', 'error');
    }

    /* ------------------------------------------------------------------ *
     * Cart count in the header
     * ------------------------------------------------------------------ */
    /* ------------------------------------------------------------------ *
     * Cart / wishlist badges
     *
     * The approved header always renders the number, so the badge is only
     * repainted here - it is never hidden on zero, otherwise the badge would
     * flicker away the moment the page boots.
     * ------------------------------------------------------------------ */
    function paintCartCount(count) {
        document.querySelectorAll('[data-fm-cart-count]').forEach(function (el) {
            el.textContent = count;
        });
    }

    function paintWishlistCount(count) {
        document.querySelectorAll('[data-fm-wishlist-count]').forEach(function (el) {
            el.textContent = count;
        });
    }

    /* ------------------------------------------------------------------ *
     * Cart drawer
     *
     * The drawer body is filled from /cart/mini-items. That fragment carries
     * data-fm-cart-meta-subtotal / data-fm-cart-meta-count, so the header badge
     * and the drawer totals are always repainted from the same server rendered
     * value instead of being guessed on the client.
     * ------------------------------------------------------------------ */
    function renderCartHtml(html, total) {
        var host = document.querySelector('[data-fm-cart-items]');
        if (!host) return;
        host.innerHTML = html || '<div class="fm-cart-lines__empty"><i class="bi bi-basket"></i><p>Your cart is empty</p></div>';

        var subtotalMeta = host.querySelector('[data-fm-cart-meta-subtotal]');
        if (subtotalMeta && (total === undefined || total === null)) {
            total = Number(subtotalMeta.getAttribute('data-fm-cart-meta-subtotal'));
        }

        if (total !== undefined && total !== null && !isNaN(total)) {
            document.querySelectorAll('[data-fm-cart-total]').forEach(function (el) {
                el.textContent = (CFG.currency || 'Tk ') + Number(total).toLocaleString('en-US');
            });
        }

        var countMeta = host.querySelector('[data-fm-cart-meta-count]');
        if (countMeta) {
            paintCartCount(Number(countMeta.getAttribute('data-fm-cart-meta-count')));
        }
    }

    /**
     * The mini-cart fragment is HTML, not JSON, so it is fetched as text and
     * dropped straight into the drawer body.
     */
    function loadCart() {
        if (!CFG.cartItemsUrl) return;
        if (!document.querySelector('[data-fm-cart-items]')) return;

        fetch(CFG.cartItemsUrl + '?_=' + Date.now(), {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (res) { return res.ok ? res.text() : ''; })
            .then(renderCartHtml)
            .catch(function () { /* drawer keeps its previous content */ });
    }

    /**
     * One place that turns a cart endpoint response into screen updates.
     *
     * Only a response that carries both cart_html and cart_subtotal may be
     * rendered directly - the legacy controllers answer with their own markup
     * and no subtotal, and that markup belongs to the classic design, so in
     * every other case the drawer is re-read from the modern endpoint.
     */
    function applyCartResponse(res) {
        if (res && res.data && typeof res.data.cart_count !== 'undefined') {
            paintCartCount(res.data.cart_count);
        }

        if (res && res.data && typeof res.data.cart_html === 'string' && typeof res.data.cart_subtotal !== 'undefined') {
            renderCartHtml(res.data.cart_html, res.data.cart_subtotal);
        } else {
            loadCart();
        }
    }

    function refreshCart() {
        if (!CFG.cartCountUrl) return;
        request(CFG.cartCountUrl)
            .then(function (res) {
                if (res.data && typeof res.data.cart_count !== 'undefined') {
                    paintCartCount(res.data.cart_count);
                }
            })
            .catch(function () { /* badge is cosmetic */ });
    }

    /* ------------------------------------------------------------------ *
     * Add to cart
     *
     * Forms carry data-add-to-cart. The submit is intercepted so the shopper
     * never leaves the page, then the header badge and drawer are refreshed.
     * ------------------------------------------------------------------ */
    /* ------------------------------------------------------------------ *
     * Fly-to-cart
     *
     * The button's own icon is cloned into a fixed-position ghost and
     * animated along an arc into the header cart, so the shopper SEES the
     * item land. Everything here is decoration on top of the real flow:
     *
     *   - the ghost carries pointer-events:none and is removed on finish,
     *   - the badge number is still painted from the server response
     *     (paintCartCount), never counted on the client,
     *   - prefers-reduced-motion skips the flight entirely (CSS hides the
     *     ghost, the guards below stop the animation early).
     * ------------------------------------------------------------------ */
    function flyToCart(sourceEl) {
        if (!sourceEl || typeof sourceEl.animate !== 'function') return;

        var target = document.querySelector('.fm-header-cart')
            || document.querySelector('[data-fm-cart-count]')
            || document.getElementById('cartCountBadge');
        if (!target || !target.getClientRects().length) return;

        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        var from = sourceEl.getBoundingClientRect();
        var to = target.getBoundingClientRect();
        if (!from.width || !to.width) return;

        var ghost = document.createElement('span');
        ghost.className = 'fm-cart-ghost';
        ghost.setAttribute('aria-hidden', 'true');
        ghost.style.left = (from.left + from.width / 2) + 'px';
        ghost.style.top = (from.top + from.height / 2) + 'px';

        // A bigger basket icon that shrinks as it flies reads better than a
        // 14px icon travelling the whole screen.
        var icon = document.createElement('i');
        icon.className = 'bi bi-basket2-fill';
        icon.style.fontSize = '26px';
        ghost.appendChild(icon);
        document.body.appendChild(ghost);

        // From the button's icon centre to the header cart's centre.
        var dx = (to.left + to.width / 2) - (from.left + from.width / 2);
        var dy = (to.top + to.height / 2) - (from.top + from.height / 2);

        // The middle keyframe lifts the ghost up (negative Y) so the path
        // curves like a thrown grocery bag instead of sliding flat.
        var anim = ghost.animate([
            { transform: 'translate(-50%, -50%) translate(0px, 0px) scale(1)', opacity: 1 },
            { transform: 'translate(-50%, -50%) translate(' + (dx * 0.5) + 'px, ' + (dy * 0.5 - 120) + 'px) scale(1.15)', opacity: 1, offset: 0.5 },
            { transform: 'translate(-50%, -50%) translate(' + dx + 'px, ' + dy + 'px) scale(0.3)', opacity: 0.2 }
        ], {
            duration: 850,
            easing: 'cubic-bezier(0.22, 0.61, 0.36, 1)',
            fill: 'forwards',
        });

        anim.onfinish = function () {
            ghost.remove();
            // "Thud" on the badge the ghost just hit.
            document.querySelectorAll('[data-fm-cart-count], #cartCountBadge').forEach(function (el) {
                el.classList.add('is-bumping');
                window.setTimeout(function () { el.classList.remove('is-bumping'); }, 500);
            });
        };
    }

    function onAddToCartSubmit(event) {
        var form = event.target.closest('form[data-add-to-cart]');
        if (!form) return;

        event.preventDefault();

        var button = form.querySelector('[type="submit"]');
        if (button) button.disabled = true;

        // Fire on the press, not on the response: the flight is instant
        // feedback, the real confirmation is the server-driven toast.
        var pressedIcon = button
            ? (button.querySelector('i') || button.querySelector('svg') || button)
            : null;
        flyToCart(pressedIcon);

        var payload = new FormData(form);
        payload.delete('_token');

        request(CFG.cartStoreUrl, {
            method: 'POST',
            body: payload,
        }).then(function (res) {
            if (button) button.disabled = false;

            if (!res.ok) {
                if (res.data && res.data.message) toast(res.data.message, 'error');
                else fail();
                return;
            }

            toast(res.data.message || (CFG.messages && CFG.messages.added));
            applyCartResponse(res);
        }).catch(function () {
            if (button) button.disabled = false;
            fail();
        });
    }

    /* ------------------------------------------------------------------ *
     * Wishlist toggle
     * ------------------------------------------------------------------ */
    function toggleWishlist(button) {
        var id = button.getAttribute('data-wishlist-id');
        if (!id) return;

        var isActive = button.classList.contains('is-active');
        var url = (isActive ? CFG.wishlistRemoveUrl : CFG.wishlistAddUrl) + '/' + encodeURIComponent(id);

        button.disabled = true;

        request(url, { method: 'POST' }).then(function (res) {
            button.disabled = false;

            if (!res.ok || !res.data || res.data.status !== 'success') {
                fail();
                return;
            }

            button.classList.toggle('is-active', !isActive);
            var icon = button.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-heart-fill', !isActive);
                icon.classList.toggle('bi-heart', isActive);
            }

            if (typeof res.data.count !== 'undefined') {
                paintWishlistCount(res.data.count);
            } else if (typeof res.data.wishlist_count !== 'undefined') {
                paintWishlistCount(res.data.wishlist_count);
            } else {
                refreshWishlist();
            }

            toast(isActive ? (CFG.messages && CFG.messages.unsaved) : (CFG.messages && CFG.messages.saved));
        }).catch(function () {
            button.disabled = false;
            fail();
        });
    }

    function refreshWishlist() {
        if (!CFG.wishlistCountUrl) return;
        request(CFG.wishlistCountUrl)
            .then(function (res) { paintWishlistCount(res.data); })
            .catch(function () { /* badge is cosmetic */ });
    }

    /* ------------------------------------------------------------------ *
     * Newsletter opt-in
     *
     * The endpoint already answers JSON to an AJAX caller (FormController::
     * newsletterSubscribe), so the submit is intercepted and the result is
     * written under the input instead of being handed back by a redirect.
     *
     * A native POST used to be the whole interaction: the shopper sits at the
     * bottom of the home page, the browser reloads and jumps to the top, and
     * the layout's flash alert renders at the top of <main> - 7113px above
     * the form that was just used. Measured, not guessed.
     *
     * With JavaScript off the form still posts natively and that same
     * redirect()->back()->with('success') alert renders as before.
     * ------------------------------------------------------------------ */
    function setNewsletterStatus(form, message, kind) {
        var slot = form.querySelector('[data-fm-newsletter-status]');
        if (!slot) return;

        slot.textContent = message || '';
        slot.classList.toggle('is-error', kind === 'error');
        slot.classList.toggle('is-info', kind === 'info');
    }

    function onNewsletterSubmit(event) {
        var form = event.target.closest('form[data-fm-newsletter]');
        if (!form) return;

        // The action is the route() the Blade file rendered, so no path is
        // hardcoded here.
        var url = form.getAttribute('action');
        if (!url) return;

        event.preventDefault();

        var button = form.querySelector('[type="submit"]');
        if (button) button.disabled = true;
        setNewsletterStatus(form, 'Subscribing…', 'info');

        // The csrf token rides in the X-CSRF-TOKEN header that request()
        // sets, so it is dropped from the body - same as the cart form.
        var payload = new FormData(form);
        payload.delete('_token');

        request(url, { method: 'POST', body: payload })
            .then(function (res) {
                if (button) button.disabled = false;

                if (!res.ok) {
                    // A 422 carries Laravel's bag: {errors: {email: [msg]}}.
                    var bag = res.data && res.data.errors && res.data.errors.email;
                    var message = (bag && bag[0])
                        || (res.data && res.data.message)
                        || 'Please enter a valid email address.';

                    setNewsletterStatus(form, message, 'error');
                    toast(message, 'error');
                    return;
                }

                var ok = (res.data && res.data.message) || 'Subscribed successfully';
                setNewsletterStatus(form, ok, 'success');
                toast(ok);
                form.reset();
            })
            .catch(function () {
                if (button) button.disabled = false;
                setNewsletterStatus(form, '', null);
                fail();
            });
    }

    /* ------------------------------------------------------------------ *
     * Header type-ahead
     * ------------------------------------------------------------------ */
    function debounce(fn, wait) {
        var timer = null;
        return function () {
            var args = arguments, ctx = this;
            window.clearTimeout(timer);
            timer = window.setTimeout(function () { fn.apply(ctx, args); }, wait);
        };
    }

    function currency(value) {
        return (CFG.currency || 'Tk ') + Number(value || 0).toLocaleString('en-US');
    }

    /* One binder per search field: the desktop bar renders one and the phone
       search drawer renders another. Binding only the first (querySelector) left
       the drawer's field with no type-ahead at all, and pairing the panels by
       document order would break the moment one of them moved - so the panel
       carries data-fm-for with its own field's id. */
    function bindSearch(input, panel) {
        var run = debounce(function () {
            var term = input.value.trim();
            if (term.length < 2) {
                panel.hidden = true;
                return;
            }

            request(CFG.livesearchUrl + '?q=' + encodeURIComponent(term))
                .then(function (res) {
                    var results = (res.data && res.data.results) || [];
                    renderSuggestions(panel, results, term);
                })
                .catch(function () { panel.hidden = true; });
        }, 250);

        input.addEventListener('input', run);
        input.addEventListener('focus', function () { if (input.value.trim().length >= 2) run(); });

        document.addEventListener('click', function (event) {
            if (!panel.contains(event.target) && event.target !== input) {
                panel.hidden = true;
            }
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') panel.hidden = true;
        });
    }

    function initSearch() {
        if (!CFG.livesearchUrl) return;

        var inputs = document.querySelectorAll('[data-fm-search-input]');
        Array.prototype.forEach.call(inputs, function (input) {
            var panel = input.id
                ? document.querySelector('[data-fm-search-suggest][data-fm-for="' + input.id + '"]')
                : null;
            if (panel) bindSearch(input, panel);
        });
    }

    function renderSuggestions(panel, results, term) {
        panel.innerHTML = '';

        if (!results.length) {
            var empty = document.createElement('div');
            empty.className = 'fm-suggest__empty';
            empty.textContent = '“' + term + '” produced no results';
            panel.appendChild(empty);
            panel.hidden = false;
            return;
        }

        results.forEach(function (item) {
            var link = document.createElement('a');
            link.href = item.url;

            var img = document.createElement('img');
            img.src = item.image;
            img.alt = '';
            img.loading = 'lazy';
            link.appendChild(img);

            var name = document.createElement('span');
            name.className = 'fm-suggest__name';
            name.textContent = item.name;
            link.appendChild(name);

            var price = document.createElement('span');
            price.className = 'fm-suggest__price';
            price.textContent = currency(item.price);
            link.appendChild(price);

            panel.appendChild(link);
        });

        panel.hidden = false;
    }

    /* ------------------------------------------------------------------ *
     * Cart line actions (plus / minus / remove) inside the drawer
     * ------------------------------------------------------------------ */
    function handleCartAction(button) {
        var action = button.getAttribute('data-cart-action');
        var rowId = button.getAttribute('data-cart-row');
        var urls = {
            plus: CFG.cartIncrementUrl,
            minus: CFG.cartDecrementUrl,
            remove: CFG.cartRemoveUrl,
        };
        var url = urls[action];
        if (!url || !rowId) return;

        button.disabled = true;

        request(url + '?id=' + encodeURIComponent(rowId))
            .then(function (res) {
                button.disabled = false;

                if (!res.ok) {
                    if (res.data && res.data.message) toast(res.data.message, 'error');
                    else fail();
                    return;
                }

                applyCartResponse(res);

                if (action === 'remove') toast(CFG.messages && CFG.messages.removed);
            })
            .catch(function () {
                button.disabled = false;
                fail();
            });
    }

    /* ------------------------------------------------------------------ *
     * Bootstrap offcanvas refresh hook
     * ------------------------------------------------------------------ */
    document.addEventListener('DOMContentLoaded', function () {
        var drawer = document.querySelector('[data-fm-cart-drawer]')
            || document.getElementById('fmCartDrawer');
        if (drawer) {
            drawer.addEventListener('show.bs.offcanvas', loadCart);
        }
    });

    /* ------------------------------------------------------------------ *
     * Global delegation
     * ------------------------------------------------------------------ */
    document.addEventListener('submit', function (event) {
        if (event.target.matches('form[data-add-to-cart]')) {
            onAddToCartSubmit(event);
            return;
        }

        if (event.target.matches('form[data-fm-newsletter]')) {
            onNewsletterSubmit(event);
        }
    });

    document.addEventListener('click', function (event) {
        var wishButton = event.target.closest('[data-wishlist-id]');
        if (wishButton) {
            event.preventDefault();
            toggleWishlist(wishButton);
            return;
        }

        var cartAction = event.target.closest('[data-cart-action]');
        if (cartAction) {
            event.preventDefault();
            handleCartAction(cartAction);
        }
    });

    /* ------------------------------------------------------------------ *
     * Footer menu accordion
     *
     * The footer columns ship OPEN in the markup (see
     * partials/footer/footer-column). This function CLOSES them below the lg
     * breakpoint, which is what turns the footer into the khaasfood-style
     * mobile menu.
     *
     * It deliberately does not use Bootstrap's collapse plugin. That plugin
     * initialised on these panels and wrote inline `visibility: collapse` on
     * the closed ones, which a stylesheet cannot override - the footer
     * rendered its column headings and none of their links. The open/closed
     * state here is one class and one aria attribute, both owned by this file.
     *
     * Open-by-default is also the fallback: if this file never runs, the
     * visitor gets a long but fully usable footer instead of four empty
     * columns.
     * ------------------------------------------------------------------ */
    var FOOTER_LG = 992;

    function footerIsCompact() {
        return window.innerWidth < FOOTER_LG;
    }

    function setFooterColumnOpen(button, open) {
        var id = button.getAttribute('aria-controls');
        var panel = id ? document.getElementById(id) : null;

        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (!panel) return;
        panel.classList.toggle('is-open', open);
    }

    function initFooterMenu() {
        var heads = document.querySelectorAll('[data-fm-footer-toggle]');
        if (!heads.length) return;

        Array.prototype.forEach.call(heads, function (button) {
            // Closed on a phone, open on a desktop - reapplied on every boot
            // and every resize so rotating a phone or resizing a window can
            // never leave a column stuck shut.
            setFooterColumnOpen(button, !footerIsCompact());

            button.addEventListener('click', function () {
                // On a desktop the columns are always open (fm-footer.css
                // forces it), so the heading is inert there - clicking it
                // should do nothing rather than appear to toggle something
                // that did not change.
                if (!footerIsCompact()) return;

                var open = button.getAttribute('aria-expanded') === 'true';
                setFooterColumnOpen(button, !open);
            });
        });

        window.addEventListener('resize', function () {
            Array.prototype.forEach.call(heads, function (button) {
                if (!footerIsCompact()) {
                    setFooterColumnOpen(button, true);
                }
            });
        });
    }

    /* ------------------------------------------------------------------ *
     * Footer bottom bar: the payment strip discloses the legal block
     *
     * On a phone the bar ships thin - the accepted-payment strip alone - and
     * tapping that strip reveals the copyright + policy block underneath.
     * On a desktop the block is always visible and the trigger is inert.
     *
     * State lives in one class (.is-open) and aria-expanded, exactly like the
     * footer menu above. fm-footer.css force-shows the block at lg, so a
     * script failure never hides the copyright on a desktop.
     * ------------------------------------------------------------------ */
    function initFooterPayToggle() {
        var toggle = document.querySelector('[data-fm-footer-pay-toggle]');
        if (!toggle) return;

        var id = toggle.getAttribute('aria-controls');
        var panel = id ? document.getElementById(id) : null;
        var bar = toggle.closest('.fm-footer-bottom');
        var wasCompact = footerIsCompact();

        // Marks the bar as script-enhanced. The stylesheet only collapses the
        // legal block when this class is present, so a JS failure leaves the
        // copyright visible rather than hiding it behind a dead trigger.
        if (bar) bar.classList.add('fm-footer-bottom--js');

        function apply(open) {
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (panel) panel.classList.toggle('is-open', open);
        }

        // Desktop starts open; a phone starts closed (thin bar).
        apply(!wasCompact);

        toggle.addEventListener('click', function () {
            if (!footerIsCompact()) return;

            var open = toggle.getAttribute('aria-expanded') !== 'true';
            apply(open);

            // The trigger sits at the very bottom of the page, so the block
            // it reveals would otherwise open behind the fixed tab bar. Bring
            // it into the middle of the viewport on the way open.
            if (open && panel && typeof panel.scrollIntoView === 'function') {
                panel.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // Crossing the breakpoint is deliberate: open when entering desktop,
        // close when entering phone. Ordinary phone resizes (the address bar
        // appearing) must not reset a block the visitor just opened.
        window.addEventListener('resize', function () {
            var nowCompact = footerIsCompact();
            if (nowCompact === wasCompact) return;
            wasCompact = nowCompact;
            apply(!nowCompact);
        });
    }

    /* ------------------------------------------------------------------ *
     * Categories card animation
     *
     * Pressing the header's Categories card - the desktop pill, or the phone
     * hamburger that opens the same drawer - plays a pulse on that card, and
     * the drawer's "View all categories" link plays it too before it
     * navigates. The markup marks the triggers with data-fm-categories-card
     * (primary-header.blade.php) and the keyframes live in fm-interaction.css.
     *
     * Both cards carry the attribute because only one of them is on screen at
     * a time: the pill is xl-only, the hamburger is lg-and-down-only. The
     * visible one is the one that animates.
     * ------------------------------------------------------------------ */
    var CATEGORIES_PULSE_MS = 700;

    function pulseCategoriesCard() {
        var cards = document.querySelectorAll('[data-fm-categories-card]');

        Array.prototype.forEach.call(cards, function (card) {
            var box = card.getBoundingClientRect();
            var visible = box.width > 0 && box.height > 0;
            if (!visible) return;

            card.classList.remove('is-pulsing');
            // Force a reflow so re-adding the class restarts the animation
            // instead of being ignored as an unchanged value.
            void card.offsetWidth;
            card.classList.add('is-pulsing');

            window.setTimeout(function () {
                card.classList.remove('is-pulsing');
            }, CATEGORIES_PULSE_MS);
        });
    }

    function initCategoriesCard() {
        // Both header cards just open the drawer; they must still animate,
        // and they must NOT stop Bootstrap from doing its job, so this only
        // adds a class - no preventDefault.
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-fm-categories-card]'),
            function (card) {
                card.addEventListener('click', pulseCategoriesCard);
            }
        );

        // The drawer's "View all categories" link navigates immediately,
        // which would tear the page down before the animation could be seen.
        // It is held for the length of the pulse instead - and only when the
        // target is a real internal page, so a modified click still
        // navigates the ordinary way.
        Array.prototype.forEach.call(
            document.querySelectorAll('.fm-drawer-cat--all'),
            function (link) {
                link.addEventListener('click', function (event) {
                    if (event.metaKey || event.ctrlKey || event.shiftKey || event.button !== 0) {
                        return;
                    }

                    event.preventDefault();
                    pulseCategoriesCard();

                    var href = link.getAttribute('href');
                    if (!href) {
                        window.location.href = link.href;
                        return;
                    }

                    window.setTimeout(function () {
                        window.location.href = href;
                    }, CATEGORIES_PULSE_MS);
                });
            }
        );
    }

    /* ------------------------------------------------------------------ *
     * Boot
     *
     * Back to top and the header toast belong to index.js from the approved
     * design, so they are deliberately not bound twice here.
     * ------------------------------------------------------------------ */
    document.addEventListener('DOMContentLoaded', function () {
        initSearch();
        refreshCart();
        refreshWishlist();
        initFooterMenu();
        initFooterPayToggle();
        initCategoriesCard();
    });

    /* ------------------------------------------------------------------ *
     * Public helpers used by page level scripts
     * ------------------------------------------------------------------ */
    window.FM = {
        toast: toast,
        refreshCart: refreshCart,
        refreshWishlist: refreshWishlist,
        loadCart: loadCart,
        pulseCategoriesCard: pulseCategoriesCard,
    };
})();

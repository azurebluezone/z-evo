/*
 * fm-product-detail.js
 * ---------------------------------------------------------------------------
 * Extracted from resources/views/frontend/fm/pages/product-details.blade.php,
 * which used to carry it as an inline @push('script') block. Every handler and
 * selector is unchanged; only the nine values that used to be interpolated by
 * blade moved into window.FM_DETAIL_CONFIG, which the page writes out directly
 * above this file loads.
 *
 * Loaded at the end of <body>, in order, by the page's own @push('script').
 */

(function () {
    'use strict';

    /*
     * Everything this script needs about the product it is running on is
     * written out by the page as window.FM_DETAIL_CONFIG, immediately before
     * this file is loaded. The nine values the blade used to interpolate into
     * the middle of this function are all in there.
     *
     * No config means no product page, so the script returns rather than
     * throwing on a half-rendered document.
     */
    var CFG = window.FM_DETAIL_CONFIG;
    if (!CFG) return;

    function byId(id) { return document.getElementById(id); }
    function all(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }
    function money(value) { return CFG.currency + value; }

    /* ------------------------------------------------------------------ *
     * Elements
     * ------------------------------------------------------------------ */
    var mainPhoto = byId('fmDetailMainPhoto');
    var priceEl = byId('fmDetailPrice');
    var oldPriceEl = byId('fmDetailOldPrice');
    var saveEl = byId('fmDetailSave');
    var saveAmountEl = byId('fmDetailSaveAmount');
    var savePctEl = byId('fmDetailSavePct');
    var skuEl = byId('fmDetailSku');
    var stockPill = byId('fmDetailStockPill');
    var stockTextEl = byId('fmDetailStockText');
    var qtyInput = byId('fmDetailQty');
    var noteEl = byId('fmVariantNote');
    var mediaEl = byId('fmDetailMedia');

    /* ------------------------------------------------------------------ *
     * Thumbnails: the strip swaps the main photo, the active border
     * follows the click (fm-detail-thumb is styled in fm-product.css).
     * ------------------------------------------------------------------ */
    all('.fm-detail-thumb').forEach(function (thumb) {
        thumb.addEventListener('click', function () {
            all('.fm-detail-thumb').forEach(function (other) {
                other.classList.remove('is-active');
            });
            thumb.classList.add('is-active');
            if (mainPhoto) mainPhoto.src = thumb.getAttribute('data-full');
        });
    });

    /* ------------------------------------------------------------------ *
     * Quantity stepper: one visible readonly field, both forms carry a
     * hidden mirror (data-fm-qty-source) that is kept in step.
     * ------------------------------------------------------------------ */
    function syncQty() {
        all('[data-fm-qty-source]').forEach(function (input) {
            input.value = qtyInput.value;
        });
    }

    if (qtyInput) {
        all('[data-fm-qty]').forEach(function (button) {
            button.addEventListener('click', function () {
                var delta = parseInt(button.getAttribute('data-fm-qty'), 10) || 0;
                var value = parseInt(qtyInput.value, 10) || 1;
                value += delta;
                if (value < 1) value = 1;
                if (value > CFG.maxQty) value = CFG.maxQty;
                qtyInput.value = String(value);
                syncQty();
            });
        });
    }

    /* ------------------------------------------------------------------ *
     * Tabs: the nav buttons and the "N reviews" link both route
     * through openTab, exactly like the design's switchSpecTab.
     * ------------------------------------------------------------------ */
    function openTab(targetId) {
        all('.fm-detail-tab').forEach(function (tab) {
            var active = tab.getAttribute('data-fm-tab') === targetId;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        all('.fm-detail-panel').forEach(function (panel) {
            panel.classList.toggle('hidden', panel.id !== targetId);
        });
    }

    all('.fm-detail-tab').forEach(function (tab) {
        tab.addEventListener('click', function () {
            openTab(tab.getAttribute('data-fm-tab'));
        });
    });

    all('[data-fm-open-tab]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            openTab(link.getAttribute('data-fm-open-tab'));
            var anchor = document.getElementById('customer-reviews');
            if (anchor) anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    /* ------------------------------------------------------------------ *
     * Variant pills: single select per attribute group, mirrored into
     * both forms as hidden variant[] inputs. An empty set still means
     * the backend falls back to the DEFAULT variant.
     * ------------------------------------------------------------------ */
    var pillGroups = all('.fm-pill-group[data-attr-slug]');

    function selectedIds() {
        return pillGroups.map(function (group) {
            var active = group.querySelector('.fm-attr-pill.is-active');
            return active ? active.getAttribute('data-attr-id') : null;
        });
    }

    function syncVariantInputs() {
        var ids = selectedIds().filter(Boolean);
        all('[data-fm-variant-slot]').forEach(function (slot) {
            slot.innerHTML = '';
            ids.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'variant[]';
                input.value = id;
                slot.appendChild(input);
            });
        });
    }

    function showNote(text) {
        if (!noteEl) return;
        noteEl.textContent = text || '';
        noteEl.classList.toggle('hidden', !text);
    }

    function applyPrice(price) {
        if (!priceEl) return;
        priceEl.textContent = money(price);
        var showSave = CFG.baseOldPrice > 0 && CFG.baseOldPrice > price;
        if (showSave) {
            var amount = CFG.baseOldPrice - price;
            var pct = Math.round((amount * 100) / CFG.baseOldPrice);
            oldPriceEl.textContent = money(CFG.baseOldPrice);
            saveAmountEl.textContent = String(amount);
            savePctEl.textContent = String(pct);
            oldPriceEl.classList.remove('hidden');
            saveEl.classList.remove('hidden');
        } else {
            oldPriceEl.classList.add('hidden');
            saveEl.classList.add('hidden');
        }
    }

    function applyStock(qty) {
        var out = qty <= 0;
        if (stockPill) stockPill.classList.toggle('is-out', out);
        if (stockTextEl) {
            stockTextEl.textContent = out ? 'Out of stock' : 'In stock (' + qty + ' left)';
        }
        all('[data-fm-cta]').forEach(function (button) {
            button.disabled = out;
        });
    }

    function resetDisplay() {
        applyPrice(CFG.basePrice);
        if (skuEl) skuEl.textContent = CFG.baseSku;
        // Restore the exact server rendered stock state: a variant product
        // shows availability without a count until a pick fetches one.
        if (stockPill) stockPill.classList.toggle('is-out', CFG.baseStockOut);
        if (stockTextEl) stockTextEl.textContent = CFG.baseStockText;
        all('[data-fm-cta]').forEach(function (button) {
            button.disabled = CFG.baseStockOut;
        });
    }

    var stockTimer = null;

/* ================================================================== *
/* --------------------------------------------------------------
         * Pending state
         *
         * The shopper's wait is the request, so the effect lasts exactly as
         * long as the request. Three rules keep it honest:
         *
         *   1. It is released when the LAST outstanding answer arrives, not the
         *      first. Clicking a second colour before the first answer lands
         *      leaves two requests running; if the older one switched the
         *      effect off, the shopper would be left staring at a still
         *      loading page that no longer looks like it is loading.
         *   2. It is held for a short floor (MIN_PENDING_MS) so a fast answer
         *      does not flash a shimmer for 40ms and look like a glitch. Above
         *      the floor the page stops pretending and simply shows the result.
         *
         * The state is additive on <body>, so the styling lives in CSS and
         * this function only has to turn it on and off.
         * -------------------------------------------------------------- */
        var pendingStartedAt = 0;
        var pendingTimer = null;
        var pendingInFlight = 0;
        var MIN_PENDING_MS = 320;

        function pendingStart() {
            pendingInFlight += 1;
            if (pendingTimer) { clearTimeout(pendingTimer); pendingTimer = null; }
            if (pendingStartedAt) { return; }   // already showing the effect
            pendingStartedAt = (window.performance && performance.now) ? performance.now() : Date.now();

            document.body.classList.add('fm-detail-pending');
            if (mediaEl) mediaEl.setAttribute('data-fm-swapping', '1');
        }

        function now() {
            return (window.performance && performance.now) ? performance.now() : Date.now();
        }

        function pendingEnd() {
            pendingInFlight = Math.max(0, pendingInFlight - 1);
            // Another request is still on its way; keep the effect up so the
            // shopper is not left looking at a page that has stopped working.
            if (pendingInFlight > 0) { return; }
            if (!pendingStartedAt) { return; }

            var elapsed = now() - pendingStartedAt;
            var remaining = MIN_PENDING_MS - elapsed;

            if (remaining > 0) {
                // Answer came back early: finish the floor, then release.
                pendingTimer = setTimeout(pendingRelease, remaining);
                return;
            }
            pendingRelease();
        }

        function pendingRelease() {
            pendingTimer = null;
            pendingStartedAt = 0;
            pendingInFlight = 0;
            document.body.classList.remove('fm-detail-pending');
            if (mediaEl) mediaEl.removeAttribute('data-fm-swapping');
        }
    /* ------------------------------------------------------------------ *
     * fmVariant - one click, one complete variant
     * ================================================================== *
     * A variant is one thing: its photo, price, sku, stock and the words of
     * the chosen option all describe the same thing on screen. So one click
     * makes ONE request (products/{id}/variant-detail) and applies the whole
     * bundle in a single pass.
     *
     * Before this, the page asked variant-images for the photo and
     * check-stock for the price in two separate calls. That is why the picture
     * and the price could disagree for a moment: they were two different
     * answers to the same question, arriving whenever they arrived.
     *
     * Every public member is namespaced under fmVariant so it cannot collide
     * with anything another script defines on the page.
     */
    var fmVariant = (function () {
        var resolveTimer = null;
        var lastRequestId = 0;

        function selectedIds() {
            return pillGroups.map(function (group) {
                var active = group.querySelector('.fm-attr-pill.is-active');
                return active ? active.getAttribute('data-attr-id') : null;
            });
        }

        function money(value) { return CFG.currency + value; }

        /* Apply the whole bundle. Kept as one function so the page can never
           update the price without also updating the photo that belongs to it. */
        function apply(bundle) {
            if (bundle.image && mainPhoto) {
                mainPhoto.src = bundle.image;
            }
            applyPrice(bundle.price);
            if (bundle.sku && skuEl) {
                skuEl.textContent = bundle.sku;
            }
            applyStock(typeof bundle.stock_quantity === 'number' ? bundle.stock_quantity : 0);

            // Say the choice in words, e.g. "Colour: Forest Green", so the
            // shopper is never looking at a bare colour chip with no name.
            var chosen = (bundle.attributes || []).map(function (a) {
                return a.label;
            }).filter(Boolean).join(', ');
            if (chosen && noteEl && !noteEl.dataset.fmError) {
                noteEl.classList.remove('hidden');
                noteEl.classList.remove('text-red-600');
                noteEl.classList.add('text-emerald-800');
                noteEl.textContent = chosen;
            }
        }

        function clearNote() {
            if (!noteEl) return;
            noteEl.classList.remove('text-emerald-800');
            noteEl.classList.add('text-red-600');
            noteEl.dataset.fmError = '';
        }

        function showError(message) {
            if (!noteEl) return;
            noteEl.textContent = message;
            noteEl.classList.remove('hidden');
            noteEl.classList.add('text-red-600');
            noteEl.classList.remove('text-emerald-800');
            noteEl.dataset.fmError = '1';
        }

        /* Single network call for the whole bundle. */
        function request(ids) {
            var query = 'attribute_values=' + encodeURIComponent(JSON.stringify(ids));

            // The wait is the request, so the effect is tied to it.
            pendingStart();

            return fetch(CFG.variantDetailUrl + '?' + query, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function (response) {
                return response.json().catch(function () { return null; });
            }).then(function (data) {
                if (!data) {
                    showError(CFG.msg.offline);
                    return null;
                }
                if (!data.found) {
                    if (data.reason === 'no_selection') {
                        resetDisplay();
                        return null;
                    }
                    showError(CFG.msg.unavailable);
                    applyPrice(CFG.basePrice);
                    return null;
                }
                return data.variant;
            }).catch(function () {
                showError(CFG.msg.offline);
                return null;
            });
        }

        /* Called after every pill click. */
        function resolve() {
            var ids = selectedIds().filter(Boolean);
            var picked = ids.length;
            if (picked === 0) {
                resetDisplay();
                clearNote();
                return;
            }
            if (picked !== pillGroups.length) {
                showNote(CFG.msg.pickAll);
                return;
            }

            showNote(null);
            clearNote();

            /* Debounced so a shopper clicking quickly across a colour row only
               waits for the last choice, not every one of them. */
            var ticket = ++lastRequestId;
            clearTimeout(resolveTimer);
            resolveTimer = setTimeout(function () {
                request(ids).then(function (bundle) {
                    // Ignore an answer that a newer click has already made stale.
                    var stale = (ticket !== lastRequestId);
                    if (!stale && bundle) {
                        apply(bundle);
                    }
                    // Released here, not inside request(), so the effect is
                    // switched off only after the new photo, price and stock
                    // are on screen. Releasing it one tick earlier left a short
                    // gap where the page looked finished but had not changed yet.
                    pendingEnd();
                });
            }, 180);
        }

        return {
            resolve: resolve,
            apply: apply,
            selectedIds: selectedIds,
            request: request
        };
    })();

    function refreshVariantState() {
        fmVariant.resolve();
    }
    pillGroups.forEach(function (group) {
        var labelEl = group.parentElement ? group.parentElement.querySelector('[data-attr-selected]') : null;
        all('.fm-attr-pill', group).forEach(function (pill) {
            pill.addEventListener('click', function () {
                var alreadyActive = pill.classList.contains('is-active');
                all('.fm-attr-pill', group).forEach(function (other) {
                    other.classList.remove('is-active');
                });
                if (!alreadyActive) pill.classList.add('is-active');
                if (labelEl) {
                    labelEl.textContent = alreadyActive
                        ? 'Select options'
                        : pill.getAttribute('data-label') + ' (selected)';
                }
                syncVariantInputs();
                refreshVariantState();
            });
        });
    });

    syncVariantInputs();

    /* ------------------------------------------------------------------ *
     * Review form (signed in customers only): opens from the stats card
     * and submits over fetch so validation errors stay on the page and
     * the pending-approval notice can replace the form. Laravel answers
     * 422 JSON to XHR; messages are mapped to Bangla here because the
     * validator's own wording is English.
     * ------------------------------------------------------------------ */
    var reviewOpenBtn = document.querySelector('[data-fm-review-open]');
    var reviewForm = byId('fmReviewForm');
    if (reviewOpenBtn && reviewForm) {
        reviewOpenBtn.addEventListener('click', function () {
            reviewForm.classList.toggle('hidden');
        });
    }

    if (reviewForm) {
        var reviewNote = byId('fmReviewFormNote');
        reviewForm.addEventListener('submit', function (event) {
            event.preventDefault();
            fetch(reviewForm.action, {
                method: 'POST',
                body: new FormData(reviewForm),
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                if (response.status === 422) {
                    return response.json().catch(function () { return null; }).then(function (body) {
                        var errors = (body && body.errors) || {};
                        var firstKey = Object.keys(errors)[0] || '';
                        var map = { ratting: CFG.msg.reviewRatting, review: CFG.msg.reviewText };
                        throw new Error(map[firstKey] || CFG.msg.reviewFallback);
                    });
                }
                if (!response.ok) throw new Error(CFG.msg.reviewFallback);
                reviewForm.classList.add('hidden');
                var done = byId('fmReviewDone');
                if (done) done.classList.remove('hidden');
            }).catch(function (err) {
                if (reviewNote) {
                    reviewNote.textContent = err.message || CFG.msg.reviewFallback;
                    reviewNote.classList.remove('hidden');
                }
            });
        });
    }

    /* ------------------------------------------------------------------ *
     * ViewContent: pixel + CAPI, same block the classic design fires.
     * ------------------------------------------------------------------ */
    /*
     * product_id stays a string: the classic design sends it that way to
     * ViewContent and the pixel matches on the string.
     */
    var viewContentData = {
        product_id: String(CFG.productId),
        name: CFG.productName,
        price: CFG.viewPrice,
        currency: 'BDT',
        category: CFG.categoryName
    };

    function fireViewContent() {
        var tracker = window.facebookTracker;
        if (tracker && typeof tracker.trackViewContent === 'function') {
            tracker.trackViewContent({
                product_id: viewContentData.product_id,
                name: viewContentData.name,
                price: viewContentData.price,
                currency: viewContentData.currency,
                category: viewContentData.category
            });
        } else if (typeof fbq === 'function') {
            fbq('track', 'ViewContent', {
                content_ids: [viewContentData.product_id],
                content_type: 'product',
                value: viewContentData.price,
                currency: viewContentData.currency,
                content_name: viewContentData.name,
                content_category: viewContentData.category
            });
        }
    }

    if (document.readyState === 'complete') fireViewContent();
    else window.addEventListener('load', fireViewContent);
})();

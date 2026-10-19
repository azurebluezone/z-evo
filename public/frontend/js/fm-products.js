/*
 * fm-products.js
 * ---------------------------------------------------------------------------
 * Extracted verbatim from resources/views/frontend/fm/pages/products.blade.php,
 * which used to carry it as an inline @push('script') block. Moving it here
 * keeps the page template readable and lets the browser cache it instead of
 * re-downloading it on every page view.
 *
 * Loaded at the end of <body>, in order, by the page's own @push('script').
 */

// FM catalog page behaviour (work/code.html flavour).
//
// The filters are plain GET fields, so everything here is a convenience:
// instant sort changes, price pills, the ceiling slider, the grid/list
// switch and the mobile filter drawer. Turning JS off leaves a fully
// working page - the sidebar simply stays where it is instead of moving
// into a tray.
(function () {
    var form = document.getElementById('fmFilterForm');
    if (!form) return;

    // form.elements, not querySelector: the toolbar selects join this
    // form through the form= attribute and are not DOM children of it.
    var controls = Array.prototype.slice.call(form.elements);

    function syncAmountField(field) {
        // A cleared amount must leave the query string - disabled
        // controls are not submitted.
        field.disabled = field.value === '';
    }

    controls.forEach(function (control) {
        if (control.matches('select[data-fm-submit]')) {
            control.addEventListener('change', function () {
                form.requestSubmit();
            });
        }

        if (control.matches('input[name="min_price"], input[name="max_price"]')) {
            control.addEventListener('input', function () {
                syncAmountField(control);
            });
        }
    });

    // Before the GET request is built, drop every control that carries
    // no value (the "all departments" radio, an untouched amount field,
    // sort=Default), so the address bar only ever shows real filters.
    form.addEventListener('submit', function () {
        controls.forEach(function (control) {
            if (!control.name || control.disabled) return;
            if (control.value === '') {
                control.disabled = true;
            }
        });
    });

    var minInput = form.querySelector('input[name="min_price"]');
    var maxInput = form.querySelector('input[name="max_price"]');

    // Price pills fill the amount fields; nothing filters until the
    // visitor presses "Apply filters", so there is no surprise rerender.
    document.querySelectorAll('[data-fm-price-pill]').forEach(function (pill) {
        pill.addEventListener('click', function () {
            if (minInput) {
                minInput.value = pill.getAttribute('data-min') || '';
                syncAmountField(minInput);
            }
            if (maxInput) {
                maxInput.value = pill.getAttribute('data-max') || '';
                syncAmountField(maxInput);
            }
            syncSlider();
        });
    });

    // Ceiling slider mirrors into the max amount field. Sitting on the
    // very top of the range means "no upper bound", so the field stays
    // empty instead of gaining a pointless parameter.
    var slider = document.getElementById('fmPriceRange');
    var sliderValue = document.getElementById('fmPriceValue');

    function ceiling() {
        return slider ? parseInt(slider.getAttribute('max'), 10) || 0 : 0;
    }

    function paintSliderLabel(value) {
        if (sliderValue) {
            sliderValue.textContent = 'Tk ' + (value || 0).toLocaleString('en-US');
        }
    }

    function syncSlider() {
        if (!slider || !maxInput) return;
        var value = parseInt(maxInput.value, 10);
        if (!value || value >= ceiling()) {
            slider.value = ceiling();
        } else {
            slider.value = value;
        }
        paintSliderLabel(parseInt(slider.value, 10));
    }

    if (slider) {
        slider.addEventListener('input', function () {
            var atTop = parseInt(slider.value, 10) >= ceiling();
            if (maxInput) {
                maxInput.value = atTop ? '' : slider.value;
                syncAmountField(maxInput);
            }
            paintSliderLabel(parseInt(slider.value, 10));
        });
        syncSlider();
    }

    // Grid / list switch: pure presentation. The address bar and the
    // result set do not change, so the state lives on the wrapper only.
    var grid = document.querySelector('.fm-product-grid');

    document.querySelectorAll('.fm-view-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!grid) return;
            var mode = button.getAttribute('data-fm-view') === 'list' ? 'list' : 'grid';
            grid.setAttribute('data-view', mode);
            document.querySelectorAll('.fm-view-btn').forEach(function (other) {
                var isActive = other === button;
                other.classList.toggle('is-active', isActive);
                other.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });
        });
    });

    /* ------------------------------------------------------------------ *
     * Mobile / tablet filter drawer
     * ------------------------------------------------------------------
     * The panel is MOVED, never copied. One #fmFilterForm stays on the
     * page the whole time, so the toolbar's form="fmFilterForm" selects
     * keep working from inside the drawer and the state the visitor
     * already chose is still there when the drawer closes.
     */
    var panel = document.querySelector('[data-fm-drawer-panel]');
    var backdrop = document.querySelector('[data-fm-drawer-backdrop]');
    var slot = document.querySelector('[data-fm-drawer-slot]');
    var opener = document.querySelector('[data-fm-open-filters]');
    var closer = document.querySelector('[data-fm-drawer-close]');
    var applyBtn = document.querySelector('[data-fm-drawer-apply]');
    var home = document.querySelector('[data-fm-filter-home]');

    if (panel && backdrop && slot && opener && home) {
        var lastFocus = null;

        function openDrawer() {
            lastFocus = document.activeElement;

            slot.appendChild(form);
            panel.hidden = false;
            backdrop.hidden = false;

            // One frame with the panel in place, then transition, so the
            // slide-in actually animates instead of snapping.
            window.requestAnimationFrame(function () {
                backdrop.classList.add('is-open');
                panel.classList.add('is-open');
            });

            // The page behind must not scroll while the tray is open.
            document.body.style.overflow = 'hidden';
            opener.setAttribute('aria-expanded', 'true');

            var firstField = form.querySelector('input, select, button');
            if (firstField) firstField.focus();
        }

        function closeDrawer() {
            backdrop.classList.remove('is-open');
            panel.classList.remove('is-open');
            document.body.style.overflow = '';

            // Wait out the 300ms transition before hiding and moving the
            // form back, otherwise the panel blinks out mid-slide.
            window.setTimeout(function () {
                panel.hidden = true;
                backdrop.hidden = true;
                home.appendChild(form);
                opener.setAttribute('aria-expanded', 'false');
                if (lastFocus && lastFocus.focus) lastFocus.focus();
            }, 300);
        }

        opener.addEventListener('click', openDrawer);
        if (closer) closer.addEventListener('click', closeDrawer);
        backdrop.addEventListener('click', closeDrawer);

        // "Apply filters" in the tray footer is the reference's own bar.
        // requestSubmit runs the same validation and the same submit
        // listener as the sidebar button, so the two cannot diverge.
        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                form.requestSubmit();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && panel.classList.contains('is-open')) {
                closeDrawer();
            }
        });

        // Submitting from inside the drawer navigates away, so the form
        // must go home or the browser's back/forward cache would restore
        // the page with the filter panel stranded inside a hidden tray.
        form.addEventListener('submit', function () {
            if (panel.classList.contains('is-open')) {
                home.appendChild(form);
                panel.hidden = true;
                backdrop.hidden = true;
                backdrop.classList.remove('is-open');
                panel.classList.remove('is-open');
                document.body.style.overflow = '';
            }
        });

        // A resize past the lg breakpoint reveals the sidebar again; the
        // panel is display:none there, so the form has to be moved back
        // or the visitor would be looking at an empty column.
        var wide = window.matchMedia('(min-width: 1024px)');
        function onBreakpoint() {
            if (wide.matches && panel.classList.contains('is-open')) {
                closeDrawer();
            }
        }
        if (wide.addEventListener) {
            wide.addEventListener('change', onBreakpoint);
        } else if (wide.addListener) {
            wide.addListener(onBreakpoint);
        }
    }
})();

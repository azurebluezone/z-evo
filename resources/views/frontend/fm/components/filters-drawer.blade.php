{{--
    FM:COMPONENT filters-drawer
    =========================================================================
    The off-canvas tray for phones and tablets.

    It is EMPTY on purpose: public/frontend/js/fm-products.js moves the real
    filter form in here on open and back out on close. Keeping the shell in the
    markup means the reference's slide animation, the backdrop and the focus
    behaviour all work on the first open without waiting for a render - and it
    is the reason the page can ship ONE #fmFilterForm (see components/
    shop-sidebar, which owns that form).

    Together with <x-fm::shop-toolbar variant="v2"> it forms the pair: the
    toolbar's [data-fm-open-filters] button opens this panel. A page that
    renders the toolbar but not this shell gets a button that does nothing, so
    render them together.

    Usage
        <x-fm::filters-drawer />

    Props
        (none)
    =========================================================================
--}}

<div class="fm-drawer-backdrop" data-fm-drawer-backdrop hidden></div>

<div class="fm-drawer-panel" id="fmShopFiltersDrawer"
     data-fm-drawer-panel role="dialog" aria-modal="true"
     aria-label="Filter products" hidden>

    <div class="fm-drawer-panel__head">
        <strong>Filters</strong>
        <button type="button" class="fm-drawer-close" data-fm-drawer-close aria-label="Close filters">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="fm-drawer-panel__body" data-fm-drawer-slot></div>

    <div class="fm-drawer-panel__foot">
        {{-- The form's own submit button, cloned so the visitor gets the
             reference's "Apply" bar at the bottom of the tray. It is a
             real submit of the SAME form, not a JS-triggered request, so
             it also works if the script never ran. --}}
        <button type="button" class="fm-sidebar__apply" data-fm-drawer-apply>
            Apply filters
        </button>
    </div>
</div>

/* ==========================================================================
   FM SHARED SCRIPTS  -  index.js
   ==========================================================================
   Small, page-agnostic helpers that the approved design already relies on.
   The cart itself is NOT here any more: it lives in the Laravel session and is
   driven by fm-app.js against the real endpoints.
   ========================================================================== */

/**
 * Toast used by the design. Markup lives in the layout:
 *   #actionToast  .toast element
 *   #toastMessage .toast-body element whose icon + text we replace
 */
function showToast(message) {
    var toastEl = document.getElementById('actionToast');
    var toastMsg = document.getElementById('toastMessage');

    if (!toastEl || !toastMsg || typeof bootstrap === 'undefined') return;

    toastMsg.innerHTML = '<i class="bi bi-check-circle-fill text-base"></i> ' + message;
    new bootstrap.Toast(toastEl, { delay: 2500 }).show();
}

/**
 * Header search scope button label.
 * The button only relabels itself; the actual category filter is a hidden
 * field so the form still posts a plain GET to /search.
 */
function selectCategory(category) {
    var btn = document.getElementById('searchCategoryBtn');
    if (btn) btn.innerText = category;

    var field = document.getElementById('fmSearchCategoryValue');
    if (field) field.value = category;
}

/**
 * Client side product filter used by the home page pills.
 * Markup contract: #productFilterButtons .filter-btn + .product-card-wrapper[data-category]
 */
function filterProducts(category, btn) {
    var pills = document.querySelectorAll('#productFilterButtons .filter-btn');
    var activeClasses = ['bg-emerald-800', 'text-white'];
    var idleClasses = ['bg-gray-100', 'text-gray-700'];

    pills.forEach(function (pill) {
        pill.classList.remove.apply(pill.classList, activeClasses);
        pill.classList.add.apply(pill.classList, idleClasses);
    });

    if (btn) {
        btn.classList.remove.apply(btn.classList, idleClasses);
        btn.classList.add.apply(btn.classList, activeClasses);
    }

    document.querySelectorAll('.product-card-wrapper').forEach(function (card) {
        card.style.display = (category === 'all' || card.getAttribute('data-category') === category)
            ? ''
            : 'none';
    });
}

/** Smooth scroll back to the top of the document. */
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.addEventListener('DOMContentLoaded', function () {
    var backToTop = document.getElementById('backToTopBtn');

    if (backToTop) {
        window.addEventListener('scroll', function () {
            backToTop.classList.toggle('show', window.scrollY > 300);
        }, { passive: true });
    }
});

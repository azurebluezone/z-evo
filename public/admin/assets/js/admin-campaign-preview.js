// Admin campaign form: keep the product price/stock summary and the template
// preview link in sync with the two selects. Vanilla JS so it works with or
// without Select2 and with the plain edit form.
(function () {
    'use strict';

    var productSelect = document.getElementById('product_id');
    var productSummary = document.getElementById('campaign-product-summary');
    var templateSelect = document.getElementById('template_id');
    var templateLink = document.getElementById('campaign-template-preview');

    function money(value) {
        var number = Number(value);
        if (!isFinite(number)) {
            return '';
        }
        return number.toLocaleString('en-BD', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function syncProduct() {
        if (!productSelect || !productSummary) {
            return;
        }

        var option = productSelect.options[productSelect.selectedIndex];
        if (!option || !option.value) {
            productSummary.textContent = 'Select a product to see its current price and stock.';
            return;
        }

        var price = money(option.getAttribute('data-price'));
        var stock = option.getAttribute('data-stock');
        productSummary.textContent = 'Product price: ৳' + price + ' · Stock: ' + (stock === null ? 'n/a' : stock);
    }

    function syncTemplate() {
        if (!templateSelect || !templateLink) {
            return;
        }

        var id = String(templateSelect.value || '');
        var template = templateLink.getAttribute('data-preview-url') || '';

        if (!id || !template) {
            templateLink.classList.add('disabled');
            templateLink.setAttribute('aria-disabled', 'true');
            templateLink.removeAttribute('href');
            return;
        }

        templateLink.classList.remove('disabled');
        templateLink.removeAttribute('aria-disabled');
        templateLink.href = template.replace('__ID__', encodeURIComponent(id));
    }

    if (productSelect) {
        productSelect.addEventListener('change', syncProduct);
        syncProduct();
    }

    if (templateSelect) {
        templateSelect.addEventListener('change', syncTemplate);
        syncTemplate();
    }
})();

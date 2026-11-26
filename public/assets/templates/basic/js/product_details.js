(function ($) {
    'use strict';

    $.fn.productDetails = function ({ productId, totalAttributes, stockQuantity, showStockQuantity, trackInventory, variantDetailUrl }) {

        const addToCartButton = $(this).find($(".addToCart"));
        
        let pendingStockRequest = null;
        let pendingPreloaderTimer = null;

        // The route carries :productId, so resolve it once instead of on every
        // swatch click.
        const detailUrl = String(variantDetailUrl || '').replace(':productId', productId);

        var initialized = false;

        function init() {
            if (initialized) return;
            setStockBadge(trackInventory, stockQuantity, showStockQuantity);
            addEventListeners();
            initialized = true;
        }

        function setSku(sku) {
            $('#productSku').text(sku);
        }

        function setProductPrice(priceContent) {
            $('#productPrice').html(priceContent);
        }

        function setStockBadge(trackInventory, stockQuantity = 'INFINITY', showStockQuantity = false) {
            var stockBadge = $('#stockBadge');

            stockBadge.removeClass('badge badge--success badge--danger').empty();

            if(!trackInventory) {
                stockBadge.hide();
                return;
            }

            let content;
            let badge;

            // 1 unit left is still sellable. The old `> 1` test called it
            // "Out of Stock", so the last unit on the shelf could never be
            // bought from this page.
            const available = String(stockQuantity).toUpperCase() === 'INFINITY'
                ? Infinity
                : Number(stockQuantity);
            const sellable = Number.isFinite(available) ? available : 0;

            if (sellable > 0) {
                if (showStockQuantity && Number.isFinite(sellable) && sellable <= 5) {
                    content = `Only ${sellable} left`;
                } else if (showStockQuantity && Number.isFinite(sellable)) {
                    content = `In Stock: ${sellable}`;
                } else {
                    content = 'Available in Stock';
                }
                badge = 'badge badge--success';
            } else {
                content = `Out of Stock`;
                badge = 'badge badge--danger';
            }
            
            stockBadge.text(content).addClass(badge);
            stockBadge.show();
        }

        // ONE request for the whole buy box: photo, price and stock. Before
        // this the page asked variant-images for the photo and check-stock for
        // the price/stock, so on a slow connection the two answers arrived out
        // of order and the card showed one variant's photo with another
        // variant's price.
        function fetchVariantDetail(attributeArray) {
            return $.get(detailUrl, {
                attribute_values: JSON.stringify(attributeArray)
            });
        }

        // The stepper must never offer more units than the selected variant
        // can ship. The ceiling comes from the server's available-to-promise
        // and is mirrored on the input's max attribute so the shared
        // plus/minus handler in the layout obeys the same limit.
        function setQuantityCeiling(max) {
            const input = $('.product-quantity-input input[name="quantity"]');
            if (!input.length) return;

            const ceiling = Math.max(0, parseInt(max, 10) || 0);
            const wrapper = input.closest('.product-quantity-input');

            input.attr('max', ceiling > 0 ? ceiling : 1);
            wrapper.attr('data-max-stock', ceiling);

            let current = parseInt(input.val(), 10);
            if (!Number.isFinite(current) || current < 1) current = 1;
            if (ceiling > 0 && current > ceiling) {
                current = ceiling;
                input.val(current);
            }

            wrapper.find('.qtyButton.plus').prop('disabled', ceiling <= 0 || current >= ceiling);
            wrapper.find('.qtyButton.minus').prop('disabled', current <= 1);
        }

        function attributeClickHandler(e) {
            const button = $(e.currentTarget);

            const mediaId = button.data('media_id');
            
            if(mediaId) {
                $(`.media-item-nav[data-media_id=${mediaId}]`).click();
            }
            
            // Toggle active class
            button.closest('.attributeValueArea').find('.attributeBtn').removeClass('active');
            button.addClass('active');
            
            const activeAttributes = $(document).find('.attributeBtn.active');
            
            // Check if all attributes are selected
            if (activeAttributes.length === (totalAttributes*1)) {
                
                const preloader = $('.product-attribute .ajax-preloader');

                if (pendingPreloaderTimer) {
                    clearTimeout(pendingPreloaderTimer);
                }

                pendingPreloaderTimer = setTimeout(() => {
                    preloader.removeClass('d-none');
                }, 150);

                if (pendingStockRequest && pendingStockRequest.readyState !== 4) {
                    pendingStockRequest.abort();
                }

                const attributeArray = activeAttributes.map(function (i, element) {
                    const attr = $(element).data('attribute') || {};
                    return attr.id ?? null;
                }).get();

                const safeAttributeArray = attributeArray.filter(function (v) {
                    return v !== null && v !== undefined && v !== '';
                });

                if (safeAttributeArray.length !== (totalAttributes * 1)) {
                    clearTimeout(pendingPreloaderTimer);
                    preloader.addClass('d-none');
                    return;
                }

                pendingStockRequest = fetchVariantDetail(safeAttributeArray)
                .done((response) => {
                    if (!response || response.found === false) {
                        // An unavailable combination is not an error to shout
                        // about: show it as sold out and stop the click.
                        addToCartButton.attr('disabled', true);
                        setStockBadge(true, 0);
                        setSku('Not available');
                        setQuantityCeiling(0);
                        return;
                    }

                    const variant = response.variant || {};

                    if (response.images) {
                        $('#variantImages').html(response.images);
                        initializeXZOOM();
                        initImgSlider();
                    }

                    setProductPrice(variant.formatted_price || variant.price);
                    setSku(variant.sku || 'Not available');
                    setStockBadge(true, variant.stock_quantity, showStockQuantity);
                    setQuantityCeiling(variant.stock_quantity);

                    addToCartButton.attr('disabled', !variant.in_stock || variant.price == null);
                })
                .fail((_, status) => {
                    if (status === 'abort') {
                        return;
                    }
                })
                .always(() => {
                    clearTimeout(pendingPreloaderTimer);
                    preloader.addClass('d-none');
                    pendingStockRequest = null;
                });
            }
        }

        function addEventListeners() {
            $(document).off('click', '.attributeBtn').on('click', '.attributeBtn', (e) => attributeClickHandler(e));
            $(document).off('click', '.addToCart').on('click', '.addToCart', addToCartClickHandler);
        }

        init();
        return this;
    };
})(jQuery);

(function ($) {
    'use strict';

    $.fn.productDetails = function ({ productId, totalAttributes, stockQuantity, showStockQuantity, trackInventory, variantImageLoadUrl, checkStockUrl }) {

        const addToCartButton = $(this).find($(".addToCart"));
        
        let pendingStockRequest = null;
        let pendingPreloaderTimer = null;

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

            if (stockQuantity > 1) {
                content = showStockQuantity ? `In Stock: ${stockQuantity}` : 'Available in Stock';
                badge = 'badge badge--success';
            } else {
                content = `Out of Stock`;
                badge = 'badge badge--danger';
            }
            
            stockBadge.text(content).addClass(badge);
            stockBadge.show();
        }

        function checkStockAvailability(attributeArray) {
            return $.get(checkStockUrl, {
                variant: attributeArray.sort()
            });
        }

        function loadVariantImages(attributeArray, productId) {
            const url = variantImageLoadUrl.replace(':productId', productId);
            const previousMediaIds = [];

            $(document).find(`.media-item-nav`).each((index, element)=>{
                previousMediaIds.push($(element).data('media_id'));
            });

            $.get(url, { attribute_values: JSON.stringify(attributeArray) }).done((response) => {
                if (!response.error) {
                    if(JSON.stringify(response.media_ids) != JSON.stringify(previousMediaIds)){
                        $('#variantImages').html(response.images);
                        initializeXZOOM();
                        initImgSlider();
                        let activeElements = $('.attributeBtn.active');
                        let filteredElements = activeElements.filter(function () {
                            return $(this).data('media_id') > 0;
                        });
    
                        $(document).find(`.media-item-nav[data-media_id=${filteredElements.data('media_id') }]`).click();
                    }
                }
            });
        }

        function attributeClickHandler(e) {
            const button = $(e.currentTarget);
            const attributes = [];
            
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

                loadVariantImages(safeAttributeArray, productId);

                activeAttributes.each(function (_, attr) {
                    const meta = $(attr).data('attribute') || {};
                    if (meta.id === null || meta.id === undefined || meta.id === '') {
                        return;
                    }
                    attributes.push(meta.id.toString());
                });

                pendingStockRequest = checkStockAvailability(attributes)
                .done((response) => {

                    if(response.message) {
                        notify(response.status, response.message);
                    }
                    
                    if(response.status == 'error') {
                        addToCartButton.attr('disabled', true);
                        setStockBadge(true, 0);
                        setSku(response.sku??'Not Available');
                    }else{
                        setProductPrice(response.formatted_price);
                        if(!response.track_inventory){
                            setStockBadge(response.track_inventory);
                            addToCartButton.attr('disabled', false);
                        }else{
                            setStockBadge(response.track_inventory, response.stock_quantity, response.show_stock);
                            addToCartButton.attr('disabled', response.price == null);
                        }
                    }
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

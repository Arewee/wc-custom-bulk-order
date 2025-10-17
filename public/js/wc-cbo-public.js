/**
 * Public-facing JavaScript for WC Custom Bulk Order.
 *
 * @version 2.1.4
 */
jQuery(document).ready(function($) {
    'use strict';

    if (typeof wc_cbo_params === 'undefined') {
        console.error('WC CBO: Missing localization data (wc_cbo_params).');
        return;
    }

    const $productForm = $('form.cart').first();
    const $matrixWrapper = $('.wc-cbo-matrix-wrapper');

    // All inputs that can trigger a price change
    const $acfInputs = $('.wc-cbo-acf-fields-wrapper input, .wc-cbo-acf-fields-wrapper select, .wc-cbo-acf-fields-wrapper textarea');
    const $quantityInputs = $('.wc-cbo-quantity-input');
    const $allInputs = $quantityInputs.add($acfInputs);

    // --- Matrix and Price Calculation Logic ---
    if ($matrixWrapper.length) {
        const $summaryDetails = $matrixWrapper.find('#wc-cbo-summary-details');
        const $addToCartButton = $matrixWrapper.find('#wc-cbo-add-to-cart-button');

        const variationData = {};
        if (wc_cbo_params.variations && Array.isArray(wc_cbo_params.variations)) {
            wc_cbo_params.variations.forEach(v => {
                variationData[v.variation_id] = {
                    price: v.display_price
                };
            });
        }

        function formatPrice(price) {
            const priceString = price.toFixed(wc_cbo_params.price_decimals);
            const parts = priceString.split('.');
            let intPart = parts[0];
            const decPart = parts.length > 1 ? wc_cbo_params.price_decimal_separator + parts[1] : '';
            intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, wc_cbo_params.price_thousand_separator);
            return wc_cbo_params.currency_symbol + intPart + decPart;
        }

        function getAcfFieldValue($field) {
            const fieldKey = $field.data('key');
            let selectedValue = null;

            if ($field.hasClass('acf-field-file')) {
                const $input = $field.find('input[type="hidden"]');
                if ($input.length && $input.val()) {
                    selectedValue = $input.val();
                }
                return {
                    key: fieldKey,
                    value: selectedValue
                };
            }

            const $input = $field.find('input[name^="acf["], textarea[name^="acf["], select[name^="acf["]').not('[type=hidden]');

            if ($input.is('[type=radio]')) {
                const $checked = $field.find('input[type=radio]:checked');
                if ($checked.length) {
                    selectedValue = $checked.val();
                }
            } else if ($input.is('[type=checkbox]')) {
                selectedValue = [];
                $field.find('input[type=checkbox]:checked').each(function() {
                    selectedValue.push($(this).val());
                });
            } else if ($input.length) {
                selectedValue = $input.val();
            }

            return {
                key: fieldKey,
                value: selectedValue
            };
        }

        function updateCalculations() {
            let totalQuantity = 0;
            let basePrice = 0;

            let acfExtraCost = 0;
            const $acfFields = $('.wc-cbo-acf-fields-wrapper .acf-field');
            $acfFields.each(function() {
                const $field = $(this);
                const acfData = getAcfFieldValue($field);

                if (wc_cbo_params.acf_prices[acfData.key] && wc_cbo_params.acf_prices[acfData.key][acfData.value]) {
                    acfExtraCost += wc_cbo_params.acf_prices[acfData.key][acfData.value];
                }
            });

            $quantityInputs.each(function() {
                const $input = $(this);
                const quantity = parseInt($input.val(), 10) || 0;

                if (quantity > 0) {
                    const variationId = $input.data('variation-id');
                    let itemPrice = 0;

                    if (variationData[variationId]) {
                        itemPrice += variationData[variationId].price;
                    }

                    itemPrice += acfExtraCost;

                    totalQuantity += quantity;
                    basePrice += quantity * itemPrice;
                }
            });

            let discountPercent = 0;
            let discountAmount = 0;
            if (wc_cbo_params.discount_tiers && wc_cbo_params.discount_tiers.length > 0) {
                const applicableTier = wc_cbo_params.discount_tiers.slice().sort((a, b) => b.min - a.min).find(tier => totalQuantity >= tier.min);
                if (applicableTier) {
                    discountPercent = parseFloat(applicableTier.discount) || 0;
                }
            }
            if (discountPercent > 0) {
                discountAmount = basePrice * (discountPercent / 100);
            }

            const finalPrice = basePrice - discountAmount;
            const minQuantityMet = totalQuantity >= wc_cbo_params.min_quantity;
            $addToCartButton.prop('disabled', !minQuantityMet);

            let summaryHtml = '';
            if (totalQuantity > 0) {
                summaryHtml += `<p><strong>Totalt antal:</strong> ${totalQuantity}</p>`;
                summaryHtml += `<p><strong>Pris (före rabatt):</strong> ${formatPrice(basePrice)}</p>`;
                if (discountAmount > 0) {
                    summaryHtml += `<p class="discount"><strong>Rabatt (${discountPercent}%):</strong> -${formatPrice(discountAmount)}</p>`;
                }
                summaryHtml += `<p class="total-price"><strong>Att betala:</strong> ${formatPrice(finalPrice)}</p>`;
                if (!minQuantityMet && wc_cbo_params.min_quantity > 0) {
                    summaryHtml += `<p class="min-quantity-notice">Minsta antal är ${wc_cbo_params.min_quantity}.</p>`;
                }
            } else {
                summaryHtml = $matrixWrapper.find('.wc-cbo-summary-wrapper .price').prop('outerHTML');
            }

            $summaryDetails.html(summaryHtml);
        }

        $allInputs.on('change keyup', function() {
            updateCalculations();
        });

        $addToCartButton.on('click', function(e) {
            e.preventDefault();
            const $button = $(this);

            if ($button.is(':disabled') || $button.hasClass('loading')) {
                return;
            }

            const originalButtonText = $button.text();
            $button.addClass('loading').text('Lägger till...');

            const sharedAcfData = {};
            const $acfFields = $('.wc-cbo-acf-fields-wrapper .acf-field');
            $acfFields.each(function() {
                const $field = $(this);
                const acfData = getAcfFieldValue($field);

                if (acfData.value !== null && acfData.value !== '' && !(Array.isArray(acfData.value) && acfData.value.length === 0)) {
                    sharedAcfData[acfData.key] = acfData.value;
                }
            });

            const cartItems = [];
            $quantityInputs.each(function() {
                const $input = $(this);
                const quantity = parseInt($input.val(), 10) || 0;

                if (quantity > 0) {
                    cartItems.push({
                        variation_id: $input.data('variation-id'),
                        quantity: quantity,
                        acf_data: sharedAcfData
                    });
                }
            });

            if (cartItems.length === 0) {
                $button.removeClass('loading').text(originalButtonText);
                return;
            }

            const data = {
                action: 'wc_cbo_add_to_cart',
                nonce: wc_cbo_params.nonce,
                product_id: wc_cbo_params.product_id,
                cart_items: cartItems
            };

            $.ajax({
                type: 'POST',
                url: wc_cbo_params.ajax_url,
                data: data,
                success: function(response) {
                    if (response.success) {
                        window.location.href = response.data.cart_url;
                    } else {
                        alert('Ett fel uppstod: ' + (response.data.message || 'Okänt fel'));
                        $button.removeClass('loading').text(originalButtonText);
                    }
                },
                error: function() {
                    alert('Ett okänt serverfel uppstod. Försök igen.');
                    $button.removeClass('loading').text(originalButtonText);
                }
            });
        });

        updateCalculations();
    }

    // --- File Upload Logic ---
    function handleFileUpload(e) {
        const $input = $(e.target);
        const $field = $input.closest('.acf-field-file');
        const file = e.target.files[0];

        if (!file) return;

        const $uploadUI = $field.find('.acf-input-append');
        const originalUI = $uploadUI.html();
        $uploadUI.html('Laddar upp...');

        const formData = new FormData();
        formData.append('action', 'wc_cbo_upload_file');
        formData.append('nonce', wc_cbo_params.file_upload_nonce);
        formData.append('async-upload', file, file.name);

        $.ajax({
            url: wc_cbo_params.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    const attachmentId = response.data.attachment_id;
                    $field.find('input[type="hidden"]').val(attachmentId).trigger('change');
                    $uploadUI.html(`<span>${file.name}</span> <a href="#" class="wc-cbo-remove-file">Ta bort</a>`);
                } else {
                    alert('Fel: ' + response.data.message);
                    $uploadUI.html(originalUI);
                }
            },
            error: function() {
                alert('Ett serverfel uppstod vid uppladdning.');
                $uploadUI.html(originalUI);
            }
        });
    }

    function handleRemoveFile(e) {
        e.preventDefault();
        const $field = $(e.target).closest('.acf-field-file');
        $field.find('input[type="hidden"]').val('').trigger('change');
        $field.find('.acf-input-append').html('Välj fil');
        $field.find('input[type="file"]').val('');
    }

    $(document).on('change', '.wc-cbo-acf-fields-wrapper .acf-field-file input[type="file"]', handleFileUpload);
    $(document).on('click', '.wc-cbo-remove-file', handleRemoveFile);


    // --- ACF Color Field <-> Gallery Image Sync Logic (v5 - FlexSlider API) ---
    function initializeAcfImageSync() {
        const imageMap = wc_cbo_params.gallery_images_map;
        if (!imageMap || Object.keys(imageMap).length === 0) {
            return; // No data to work with
        }

        const $fieldWrapper = $('.acf-field[data-name="fargval"]');
        if (!$fieldWrapper.length) {
            return; // The color field is not on this page
        }

        const $gallery = $('.woocommerce-product-gallery');
        if (!$gallery.length || !$gallery.data('flexslider')) {
            // Wait a bit and try again, FlexSlider might not be ready.
            setTimeout(initializeAcfImageSync, 200);
            return;
        }

        const flexslider = $gallery.data('flexslider');
        const $radioInputs = $fieldWrapper.find('input[type="radio"]');
        const $selectInput = $fieldWrapper.find('select');

        // Create a reverse map for quick lookups (slideIndex -> colorName)
        const indexToColorMap = {};
        for (const colorName in imageMap) {
            if (imageMap.hasOwnProperty(colorName)) {
                const slideIndex = imageMap[colorName].slideIndex;
                indexToColorMap[slideIndex] = colorName;
            }
        }

        // 1. User changes the ACF color field (radio or select)
        $fieldWrapper.on('change.wc-cbo-sync', 'input[type="radio"], select', function() {
            const colorName = $(this).val();
            if (colorName && imageMap[colorName] !== undefined) {
                const slideIndex = imageMap[colorName].slideIndex;
                flexslider.flexAnimate(slideIndex);
            }
        });

        // 2. Gallery slide changes (user clicks thumbnails or arrows)
        flexslider.vars.after = function(slider) {
            const currentSlideIndex = slider.currentSlide;
            const colorName = indexToColorMap[currentSlideIndex];

            if (colorName) {
                // Block our own change handler to prevent a loop
                $fieldWrapper.off('change.wc-cbo-sync');

                // Update the ACF field to match the new slide
                const $matchingRadio = $radioInputs.filter(`[value="${colorName}"]`);
                if ($matchingRadio.length && !$matchingRadio.is(':checked')) {
                    $matchingRadio.prop('checked', true).trigger('change'); // Trigger change for other plugins/calcs
                }

                const $matchingOption = $selectInput.find(`option[value="${colorName}"]`);
                if ($matchingOption.length && $selectInput.val() !== colorName) {
                    $selectInput.val(colorName).trigger('change'); // Trigger change for other plugins/calcs
                }

                // Re-enable our change handler
                $fieldWrapper.on('change.wc-cbo-sync', 'input[type="radio"], select', function() {
                    const colorName = $(this).val();
                    if (colorName && imageMap[colorName] !== undefined) {
                        flexslider.flexAnimate(imageMap[colorName].slideIndex);
                    }
                });
            }
        };

        // 3. Initial State Sync on page load
        const $initialSelected = $fieldWrapper.find('input[type="radio"]:checked, select').first();
        if ($initialSelected.length) {
            const initialColor = $initialSelected.val();
            if (initialColor && imageMap[initialColor] !== undefined) {
                const initialSlideIndex = imageMap[initialColor].slideIndex;
                flexslider.flexAnimate(initialSlideIndex, true);
            }
        }
    }

    // The FlexSlider might initialize after the DOM is ready, so we wait for the window to be fully loaded.
    $(window).on('load', function() {
        initializeAcfImageSync();
    });

});
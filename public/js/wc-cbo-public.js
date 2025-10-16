/**
 * Public-facing JavaScript for WC Custom Bulk Order.
 *
 * @version 2.1.1
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

    // Only run the matrix logic if the matrix wrapper exists
    if ($matrixWrapper.length) {
        // ... (matrix logic remains the same)
    }

    // File upload handlers
    // ... (file upload logic remains the same)

    /**
     * ACF Color Field <-> Gallery Image Swap Logic (v4 - Final)
     */
    function initializeAcfImageSwap() {
        const imageMap = wc_cbo_params.gallery_images_map;
        if (!imageMap || Object.keys(imageMap).length === 0) {
            return;
        }

        const $fieldWrapper = $('.acf-field[data-name="fargval"]');
        if (!$fieldWrapper.length) {
            return;
        }

        const $productImage = $('.woocommerce-product-gallery .wp-post-image');
        if (!$productImage.length) {
            return;
        }

        const $radioInputs = $fieldWrapper.find('input[type="radio"]');
        const $selectInput = $fieldWrapper.find('select');

        function updateImage(colorName) {
            if (colorName && imageMap[colorName]) {
                const imageData = imageMap[colorName];
                $productImage.attr('src', imageData.src).attr('srcset', imageData.srcset || '');
            }
        }

        // 1. Listen for changes on the ACF field (user clicks a radio/select)
        $fieldWrapper.on('change', 'input[type="radio"], select', function() {
            updateImage($(this).val());
        });

        // 2. Listen for clicks on gallery thumbnails
        $(document).on('click', '.woocommerce-product-gallery .flex-control-thumbs li', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const thumbAlt = $(this).find('img').attr('alt');

            if (thumbAlt && imageMap[thumbAlt]) {
                // A color-matched thumbnail was clicked. Update the ACF field.
                // This will trigger the 'change' event we listen for above.
                const $matchingRadio = $radioInputs.filter(`[value="${thumbAlt}"]`);
                if ($matchingRadio.length) {
                    $matchingRadio.prop('checked', true).trigger('change');
                }

                const $matchingOption = $selectInput.find(`option[value="${thumbAlt}"]`);
                if ($matchingOption.length) {
                    $selectInput.val(thumbAlt).trigger('change');
                }
            }
        });

        // 3. Initial State Sync
        // On page load, sync the image to reflect the default checked radio button.
        const $initialSelected = $fieldWrapper.find('input[type="radio"]:checked, select').first();
        if ($initialSelected.length) {
            updateImage($initialSelected.val());
        }
    }

    initializeAcfImageSwap();

});
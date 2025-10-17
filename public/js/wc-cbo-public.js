/**
 * Public-facing JavaScript for WC Custom Bulk Order.
 *
 * @version 2.1.3
 */
jQuery(document).ready(function($) {
    'use strict';

    if (typeof wc_cbo_params === 'undefined') {
        console.error('WC CBO: Missing localization data (wc_cbo_params).');
        return;
    }

    /**
     * ACF Color Field <-> Gallery Image Sync Logic (v5 - FlexSlider API)
     *
     * This version uses the FlexSlider API for robust synchronization.
     */
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
            return; // No gallery or FlexSlider not initialized
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

        // --- Event Handlers ---

        // 1. User changes the ACF color field (radio or select)
        $fieldWrapper.on('change', 'input[type="radio"], select', function() {
            const colorName = $(this).val();
            if (colorName && imageMap[colorName]) {
                const slideIndex = imageMap[colorName].slideIndex;
                // Move the gallery to the correct slide
                flexslider.flexAnimate(slideIndex);
            }
        });

        // 2. Gallery slide changes (user clicks thumbnails or arrows)
        // We hook into FlexSlider's 'after' event, which fires after a slide transition.
        flexslider.vars.after = function(slider) {
            const currentSlideIndex = slider.currentSlide;
            const colorName = indexToColorMap[currentSlideIndex];

            if (colorName) {
                // Block change events to prevent a loop
                $fieldWrapper.off('change.wc-cbo-sync');

                // Update the ACF field to match the new slide
                const $matchingRadio = $radioInputs.filter(`[value="${colorName}"]`);
                if ($matchingRadio.length) {
                    $matchingRadio.prop('checked', true);
                }

                const $matchingOption = $selectInput.find(`option[value="${colorName}"]`);
                if ($matchingOption.length) {
                    $selectInput.val(colorName);
                }

                // Re-enable the change event handler after a short delay
                setTimeout(() => {
                    $fieldWrapper.on('change.wc-cbo-sync', 'input[type="radio"], select', function() {
                        const colorName = $(this).val();
                        if (colorName && imageMap[colorName]) {
                            flexslider.flexAnimate(imageMap[colorName].slideIndex);
                        }
                    });
                }, 50);
            }
        };

        // 3. Initial State Sync on page load
        const $initialSelected = $fieldWrapper.find('input[type="radio"]:checked, select').first();
        if ($initialSelected.length) {
            const initialColor = $initialSelected.val();
            if (initialColor && imageMap[initialColor]) {
                const initialSlideIndex = imageMap[initialColor].slideIndex;
                // Set the starting slide without animation
                flexslider.vars.startAt = initialSlideIndex;
                flexslider.flexAnimate(initialSlideIndex, true); // The second param `true` is for no animation
            }
        }
    }

    // The FlexSlider might initialize after the DOM is ready, so we wait for the window to be fully loaded.
    $(window).on('load', function() {
        initializeAcfImageSync();
    });

});

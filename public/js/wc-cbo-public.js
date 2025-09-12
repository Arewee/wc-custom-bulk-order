jQuery(document).ready(function($) {
    'use strict';

    const $wrapper = $('.wc-cbo-matrix-wrapper');
    if (!$wrapper.length) {
        return;
    }

    const $quantityInputs = $wrapper.find('.wc-cbo-quantity-input');
    const $summaryDetails = $wrapper.find('#wc-cbo-summary-details');
    const $addToCartButton = $wrapper.find('#wc-cbo-add-to-cart-button');

    function updateCalculations() {
        let totalQuantity = 0;

        $quantityInputs.each(function() {
            const quantity = parseInt($(this).val(), 10) || 0;
            if (quantity > 0) {
                totalQuantity += quantity;
            }
        });

        // TODO: Här kommer all logik för prisberäkning, rabatter etc.

        console.log('Total quantity:', totalQuantity);

        // Uppdatera sammanfattningen
        // TODO: Uppdatera pris, rabatter etc. i $summaryDetails
    }

    // Händelselyssnare för när ett antal-fält ändras
    $quantityInputs.on('change keyup', function() {
        updateCalculations();
    });

    // Händelselyssnare för "Lägg till i varukorg"-knappen
    $addToCartButton.on('click', function(e) {
        e.preventDefault();
        console.log('Add to cart clicked!');
        // TODO: Samla in all data och skicka via AJAX
    });

    // Initial beräkning ifall fält är förifyllda
    updateCalculations();
});

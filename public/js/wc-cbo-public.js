jQuery(document).ready(function($) {
    'use strict';

    if (typeof wc_cbo_params === 'undefined') {
        console.error('WC CBO: Missing localization data (wc_cbo_params).');
        return;
    }

    const $wrapper = $('.wc-cbo-matrix-wrapper');
    if (!$wrapper.length) {
        return;
    }

    // All inputs that can trigger a price change
    const $allInputs = $wrapper.find('.wc-cbo-quantity-input, .wc-cbo-global-options input, .wc-cbo-global-options select, .wc-cbo-global-options textarea');
    const $quantityInputs = $wrapper.find('.wc-cbo-quantity-input');
    const $summaryDetails = $wrapper.find('#wc-cbo-summary-details');
    const $addToCartButton = $wrapper.find('#wc-cbo-add-to-cart-button');

    const variationData = {};
    wc_cbo_params.variations.forEach(v => {
        variationData[v.variation_id] = {
            price: v.display_price
        };
    });

    function formatPrice(price) {
        const priceString = price.toFixed(wc_cbo_params.price_decimals);
        const parts = priceString.split('.');
        let intPart = parts[0];
        const decPart = parts.length > 1 ? wc_cbo_params.price_decimal_separator + parts[1] : '';
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, wc_cbo_params.price_thousand_separator);
        return wc_cbo_params.currency_symbol + intPart + decPart;
    }

    function updateCalculations() {
        let totalQuantity = 0;
        let basePrice = 0;

        // 1. Calculate the combined extra cost from global ACF fields
        let acfExtraCost = 0;
        const $acfFields = $('.wc-cbo-global-options .acf-field');
        $acfFields.each(function() {
            const $field = $(this);
            const fieldKey = $field.data('key');
            let selectedValue = '';

            // Find the selected input within the field
            const $input = $field.find('input:not([type=checkbox],[type=radio]), input:checked, textarea, select');
            if ($input.is('[type=checkbox]')) {
                const values = [];
                $field.find('input:checked').each(function() { values.push($(this).val()); });
                selectedValue = values.length ? values : '';
            } else if ($input.length) {
                selectedValue = $input.val();
            }
            
            // If this selection has a price, add it
            if (wc_cbo_params.acf_prices[fieldKey] && wc_cbo_params.acf_prices[fieldKey][selectedValue]) {
                acfExtraCost += wc_cbo_params.acf_prices[fieldKey][selectedValue];
            }
        });

        // 2. Calculate price per row
        $quantityInputs.each(function() {
            const $input = $(this);
            const quantity = parseInt($input.val(), 10) || 0;

            if (quantity > 0) {
                const variationId = $input.data('variation-id');
                let itemPrice = 0;

                if (variationData[variationId]) {
                    itemPrice += variationData[variationId].price;
                }

                // Add the shared ACF cost to each item
                itemPrice += acfExtraCost;
                
                totalQuantity += quantity;
                basePrice += quantity * itemPrice;
            }
        });

        // 3. Calculate discount
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

        // 4. Update summary
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
            summaryHtml = $wrapper.find('.wc-cbo-summary-wrapper .price').prop('outerHTML');
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

        // 1. Get the shared ACF data
        const sharedAcfData = {};
        const $acfFields = $('.wc-cbo-global-options .acf-field');
        $acfFields.each(function() {
            const $field = $(this);
            const fieldKey = $field.data('key');
            let selectedValue = null;

            const $input = $field.find('input:not([type=checkbox],[type=radio]), input:checked, textarea, select');
            if ($input.is('[type=checkbox]')) {
                selectedValue = [];
                $field.find('input:checked').each(function(){ selectedValue.push($(this).val()); });
            } else if ($input.length) {
                selectedValue = $input.val();
            }

            if (selectedValue !== null && selectedValue !== '' && !(Array.isArray(selectedValue) && selectedValue.length === 0)) {
                sharedAcfData[fieldKey] = selectedValue;
            }
        });

        // 2. Create cart items from quantity rows
        const cartItems = [];
        $quantityInputs.each(function() {
            const $input = $(this);
            const quantity = parseInt($input.val(), 10) || 0;

            if (quantity > 0) {
                cartItems.push({
                    variation_id: $input.data('variation-id'),
                    quantity: quantity,
                    acf_data: sharedAcfData // Apply the same ACF data to all items
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
});
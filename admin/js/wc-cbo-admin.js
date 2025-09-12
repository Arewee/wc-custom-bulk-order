jQuery(document).ready(function($) {
    'use strict';

    // Funktion för att lägga till en ny rad för rabattstege
    function addTierRow(min = '', max = '', discount = '') {
        const rowIndex = $('#wc_cbo_discount_tiers_rows .wc_cbo_tier_row').length;
        const rowHtml = `
            <div class="wc_cbo_tier_row">
                <input type="number" name="_wc_cbo_discount_tier_min[${rowIndex}]" value="${min}" placeholder="<?php _e( 'Från (antal)', 'wc-custom-bulk-order' ); ?>" />
                <input type="number" name="_wc_cbo_discount_tier_max[${rowIndex}]" value="${max}" placeholder="<?php _e( 'Till (antal)', 'wc-custom-bulk-order' ); ?>" />
                <input type="number" step="any" name="_wc_cbo_discount_tier_discount[${rowIndex}]" value="${discount}" placeholder="<?php _e( 'Rabatt (%)', 'wc-custom-bulk-order' ); ?>" />
                <button type="button" class="button wc_cbo_remove_tier_button">×</button>
            </div>
        `;
        $('#wc_cbo_discount_tiers_rows').append(rowHtml);
    }

    // Lägg till en ny rad när man klickar på knappen
    $('#wc_cbo_add_tier_button').on('click', function() {
        addTierRow();
    });

    // Ta bort en rad
    $('#wc_cbo_discount_tiers_rows').on('click', '.wc_cbo_remove_tier_button', function() {
        $(this).closest('.wc_cbo_tier_row').remove();
    });

    // Ladda befintliga rader (detta kräver att vi skickar datan från PHP till JS)
    // Denna del kommer att expanderas senare.
});

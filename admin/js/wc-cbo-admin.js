jQuery(document).ready(function($) {
    'use strict';

    const placeholders = wc_cbo_admin_meta.placeholders || {};

    // Funktion för att lägga till en ny rad för rabattstege
    function addTierRow(min = '', max = '', discount = '') {
        const rowIndex = $('#wc_cbo_discount_tiers_rows .wc_cbo_tier_row').length;
        // Om max är *, byt till tomt för input-fältet
        const max_val = (max === '*') ? '' : max;

        const rowHtml = `
            <div class="wc_cbo_tier_row">
                <input type="number" name="_wc_cbo_discount_tier_min[${rowIndex}]" value="${min}" placeholder="${placeholders.min}" />
                <input type="number" name="_wc_cbo_discount_tier_max[${rowIndex}]" value="${max_val}" placeholder="${placeholders.max}" />
                <input type="number" step="any" name="_wc_cbo_discount_tier_discount[${rowIndex}]" value="${discount}" placeholder="${placeholders.discount}" />
                <button type="button" class="button wc_cbo_remove_tier_button">×</button>
            </div>
        `;
        $('#wc_cbo_discount_tiers_rows').append(rowHtml);
    }

    // Ladda befintliga rader när sidan laddas
    function loadInitialRows() {
        const tiers = wc_cbo_admin_meta.discount_tiers || [];
        if (tiers.length > 0) {
            tiers.forEach(tier => {
                addTierRow(tier.min, tier.max, tier.discount);
            });
        }
    }

    // Lägg till en ny rad när man klickar på knappen
    $('#wc_cbo_add_tier_button').on('click', function() {
        addTierRow();
    });

    // Ta bort en rad
    $('#wc_cbo_discount_tiers_rows').on('click', '.wc_cbo_remove_tier_button', function() {
        $(this).closest('.wc_cbo_tier_row').remove();
    });

    // Kör funktionen för att ladda initial data
    loadInitialRows();
});

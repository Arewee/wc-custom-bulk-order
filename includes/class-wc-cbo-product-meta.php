<?php

/**
 * WC_CBO_Product_Meta Class
 *
 * @class       WC_CBO_Product_Meta
 * @version     1.0.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Meta {

    /**
     * Konstruktor.
     */
    public function __construct() {
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_bulk_settings_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'render_bulk_settings_panel' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_bulk_settings' ) );
    }

    /**
     * Lägg till en ny flik för bulk-inställningar.
     */
    public function add_bulk_settings_tab( $tabs ) {
        $tabs['wc_cbo_bulk_settings'] = array(
            'label'    => __( 'Bulk-inställningar', 'wc-custom-bulk-order' ),
            'target'   => 'wc_cbo_bulk_settings_panel',
            'class'    => array( 'show_if_variable' ), // Visa bara för variabla produkter
            'priority' => 80,
        );
        return $tabs;
    }

    /**
     * Rendera innehållet för vår nya flik.
     */
    public function render_bulk_settings_panel() {
        global $post;

        echo '<div id="wc_cbo_bulk_settings_panel" class="panel woocommerce_options_panel">';

        // Hämta sparad data
        $min_quantity = get_post_meta( $post->ID, '_wc_cbo_min_quantity', true );
        $prod_time    = get_post_meta( $post->ID, '_wc_cbo_prod_time', true );

        // Fält för Minimiantal
        woocommerce_wp_text_input( array(
            'id'          => '_wc_cbo_min_quantity',
            'label'       => __( 'Minsta totala antal', 'wc-custom-bulk-order' ),
            'description' => __( 'Ange det minsta totala antalet produkter som måste beställas. Lämna tomt för att inaktivera.', 'wc-custom-bulk-order' ),
            'desc_tip'    => true,
            'type'        => 'number',
            'value'       => $min_quantity,
        ) );

        // Fält för Produktionstid
        woocommerce_wp_text_input( array(
            'id'          => '_wc_cbo_prod_time',
            'label'       => __( 'Produktionstid (dagar)', 'wc-custom-bulk-order' ),
            'description' => __( 'Ange produktionstiden i hela dagar. Används för att visa beräknat leveransdatum.', 'wc-custom-bulk-order' ),
            'desc_tip'    => true,
            'type'        => 'number',
            'value'       => $prod_time,
        ) );

        // Här kommer vi att rendera fälten för rabattstegen
        $this->render_discount_tiers_field( $post->ID );

        echo '</div>';
    }

    /**
     * Rendera fält för rabattstege.
     */
    private function render_discount_tiers_field( $product_id ) {
        $discount_tiers = get_post_meta( $product_id, '_wc_cbo_discount_tiers', true );
        ?>
        <div class="options_group wc_cbo_discount_tiers_wrapper">
            <p class="form-field">
                <label><?php _e( 'Rabattstege', 'wc-custom-bulk-order' ); ?></label>
                <span class="description"><?php _e( 'Definiera kvantitetsrabatter. Rabatten baseras på det totala antalet av denna produkt i varukorgen.', 'wc-custom-bulk-order' ); ?></span>
            </p>
            <div id="wc_cbo_discount_tiers_rows">
                <!-- JS kommer att fylla på rader här -->
            </div>
            <button type="button" class="button" id="wc_cbo_add_tier_button"><?php _e( 'Lägg till nivå', 'wc-custom-bulk-order' ); ?></button>
        </div>
        <?php
    }

    /**
     * Spara vår anpassade data.
     */
    public function save_bulk_settings( $post_id ) {
        // Spara minimiantal
        $min_quantity = isset( $_POST['_wc_cbo_min_quantity'] ) ? absint( $_POST['_wc_cbo_min_quantity'] ) : '';
        update_post_meta( $post_id, '_wc_cbo_min_quantity', $min_quantity );

        // Spara produktionstid
        $prod_time = isset( $_POST['_wc_cbo_prod_time'] ) ? absint( $_POST['_wc_cbo_prod_time'] ) : '';
        update_post_meta( $post_id, '_wc_cbo_prod_time', $prod_time );

        // Spara rabattstege (detta kommer kräva mer logik när JS är på plats)
        // Vi förbereder för en array av rader
        if ( isset( $_POST['_wc_cbo_discount_tier_min'] ) ) {
            // Logik för att spara rabattstegen kommer att implementeras här
        }
    }
}

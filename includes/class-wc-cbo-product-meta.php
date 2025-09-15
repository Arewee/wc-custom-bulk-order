<?php

/**
 * WC_CBO_Product_Meta Class
 *
 * @class       WC_CBO_Product_Meta
 * @version     1.4.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Meta {

    public function __construct() {
        add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_bulk_settings_tab' ) );
        add_action( 'woocommerce_product_data_panels', array( $this, 'render_bulk_settings_panel' ) );
        add_action( 'woocommerce_process_product_meta', array( $this, 'save_bulk_settings' ) );
    }

    public function add_bulk_settings_tab( $tabs ) {
        $tabs['wc_cbo_bulk_settings'] = array(
            'label'    => __( 'Bulk-inställningar', 'wc-custom-bulk-order' ),
            'target'   => 'wc_cbo_bulk_settings_panel',
            'class'    => array( 'show_if_variable' ),
            'priority' => 80,
        );
        return $tabs;
    }

    public function render_bulk_settings_panel() {
        global $post, $product_object;

        echo '<div id="wc_cbo_bulk_settings_panel" class="panel woocommerce_options_panel">';

        // --- Fält för Matris-attribut ---
        $this->render_matrix_attribute_selector( $product_object );

        // --- Övriga fält ---
        woocommerce_wp_text_input( array(
            'id'          => '_wc_cbo_min_quantity',
            'label'       => __( 'Minsta totala antal', 'wc-custom-bulk-order' ),
            'description' => __( 'Ange det minsta totala antalet produkter som måste beställas. Lämna tomt för att inaktivera.', 'wc-custom-bulk-order' ),
            'desc_tip'    => true,
            'type'        => 'number',
            'value'       => get_post_meta( $post->ID, '_wc_cbo_min_quantity', true ),
        ) );

        woocommerce_wp_text_input( array(
            'id'          => '_wc_cbo_prod_time',
            'label'       => __( 'Produktionstid (dagar)', 'wc-custom-bulk-order' ),
            'description' => __( 'Ange produktionstiden i hela dagar. Används för att visa beräknat leveransdatum.', 'wc-custom-bulk-order' ),
            'desc_tip'    => true,
            'type'        => 'number',
            'value'       => get_post_meta( $post->ID, '_wc_cbo_prod_time', true ),
        ) );

        $this->render_discount_tiers_field( $post->ID );

        echo '</div>';
    }

    private function render_matrix_attribute_selector( $product ) {
        if ( ! $product || ! $product->is_type('variable') ) {
            return;
        }

        $attributes = $product->get_variation_attributes();
        $options = [];
        foreach ( $attributes as $slug => $values ) {
            $options[$slug] = wc_attribute_label($slug);
        }

        woocommerce_wp_select( array(
            'id'          => '_wc_cbo_matrix_row_attribute',
            'label'       => __( 'Attribut för Antalsmatris', 'wc-custom-bulk-order' ),
            'description' => __( 'Välj det attribut som ska användas för att bygga raderna i antalsmatrisen (t.ex. Storlek).', 'wc-custom-bulk-order' ),
            'desc_tip'    => true,
            'options'     => $options,
            'value'       => get_post_meta( $product->get_id(), '_wc_cbo_matrix_row_attribute', true ),
        ) );
    }

    private function render_discount_tiers_field( $product_id ) {
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

    public function save_bulk_settings( $post_id ) {
        // Spara valt matris-attribut
        $matrix_attr = isset( $_POST['_wc_cbo_matrix_row_attribute'] ) ? sanitize_text_field( $_POST['_wc_cbo_matrix_row_attribute'] ) : '';
        update_post_meta( $post_id, '_wc_cbo_matrix_row_attribute', $matrix_attr );

        // Spara minimiantal
        $min_quantity = isset( $_POST['_wc_cbo_min_quantity'] ) ? absint( $_POST['_wc_cbo_min_quantity'] ) : '';
        update_post_meta( $post_id, '_wc_cbo_min_quantity', $min_quantity );

        // Spara produktionstid
        $prod_time = isset( $_POST['_wc_cbo_prod_time'] ) ? absint( $_POST['_wc_cbo_prod_time'] ) : '';
        update_post_meta( $post_id, '_wc_cbo_prod_time', $prod_time );

        // Spara rabattstege
        $discount_tiers = array();
        $tier_min_quantities = isset( $_POST['_wc_cbo_discount_tier_min'] ) ? (array) $_POST['_wc_cbo_discount_tier_min'] : array();
        $tier_discounts      = isset( $_POST['_wc_cbo_discount_tier_discount'] ) ? (array) $_POST['_wc_cbo_discount_tier_discount'] : array();

        if ( ! empty( $tier_min_quantities ) ) {
            $tier_count = count( $tier_min_quantities );
            for ( $i = 0; $i < $tier_count; $i++ ) {
                $min_qty = ! empty( $tier_min_quantities[$i] ) ? absint( $tier_min_quantities[$i] ) : null;

                if ( is_null( $min_qty ) ) {
                    continue;
                }

                $discount_tiers[] = array(
                    'min'      => $min_qty,
                    'discount' => ! empty( $tier_discounts[$i] ) ? floatval( $tier_discounts[$i] ) : 0,
                );
            }
        }

        usort($discount_tiers, function($a, $b) {
            return $a['min'] <=> $b['min'];
        });

        update_post_meta( $post_id, '_wc_cbo_discount_tiers', $discount_tiers );
    }
}

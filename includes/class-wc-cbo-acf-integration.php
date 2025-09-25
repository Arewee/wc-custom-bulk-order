<?php
/**
 * WC_CBO_ACF_Integration Class
 *
 * Handles the dynamic rendering of ACF fields on the single product page based on location rules.
 *
 * @class       WC_CBO_ACF_Integration
 * @version     1.7.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class WC_CBO_ACF_Integration {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_matching_acf_groups' ), 25 );
    }

    /**
     * Finds all ACF Field Groups that match the current product and renders them.
     */
    public function render_matching_acf_groups() {
        global $product;

        if ( ! is_a( $product, 'WC_Product' ) || ! function_exists( 'acf_get_field_groups' ) ) {
            return;
        }

        $product_id = $product->get_id();
        
        // Get all field groups and check their location rules manually.
        $all_field_groups = acf_get_field_groups();
        $matching_groups = array();

        foreach ( $all_field_groups as $field_group ) {
            if ( wc_cbo_check_acf_location_rules( $field_group, $product_id ) ) {
                $matching_groups[] = $field_group;
            }
        }

        if ( empty( $matching_groups ) ) {
            return;
        }

        // Render the fields from all matching groups
        echo '<div class="cbo-options">';

        foreach ( $matching_groups as $group ) {
            $fields_in_group = acf_get_fields( $group['key'] );

            if ( empty($fields_in_group) ) {
                continue;
            }

            foreach ( $fields_in_group as $field ) {
                // acf_render_field_wrap() correctly renders the full field input (label, input, instructions)
                // with the correct `name` attribute (e.g., `acf[field_key]`) so it can be saved to the cart.
                acf_render_field_wrap( $field );
            }
        }

        echo '</div>';
    }

}

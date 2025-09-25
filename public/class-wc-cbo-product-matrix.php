<?php

/**
 * WC_CBO_Product_Matrix Class
 *
 * @class       WC_CBO_Product_Matrix
 * @version     1.8.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Matrix {

    public function __construct() {
        add_action( 'woocommerce_before_single_product', array( $this, 'setup_matrix_display' ) );
    }

    public function setup_matrix_display() {
        global $product;
        if ( $product && $product->is_type( 'variable' ) ) {
            // Remove the default WooCommerce variations form
            remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );

            // Add our custom matrix display
            add_action( 'woocommerce_variable_add_to_cart', array( $this, 'render_bulk_order_matrix' ), 30 );
        }
    }

    public function render_bulk_order_matrix() {
        global $product;

        // Ensure variations are visible, which can be an issue in custom loops.
        add_filter( 'woocommerce_product_is_visible', '__return_true' );
        
        $variations = $product->get_available_variations('objects');
        if ( empty( $variations ) ) {
            // Clean up filter before exiting.
            remove_filter( 'woocommerce_product_is_visible', '__return_true' );
            return;
        }

        echo '<div class="wc-cbo-matrix-wrapper">';
        $this->render_new_layout( $product, $variations );
        $this->render_summary_and_button( $product );
        echo '</div>';
        
        // Clean up filter.
        remove_filter( 'woocommerce_product_is_visible', '__return_true' );
    }

    /**
     * Gets the primary attribute slug to be used for the matrix rows.
     * It prioritizes a taxonomy-based attribute (global) used for variations.
     *
     * @param WC_Product_Variable $product
     * @return string|null
     */
    private function get_primary_variation_attribute_slug( $product ) {
        $attributes = $product->get_attributes();

        // First, look for a taxonomy attribute used for variations (e.g., global "Size").
        foreach ( $attributes as $attribute ) {
            if ( $attribute->get_variation() && $attribute->is_taxonomy() ) {
                return $attribute->get_name(); // This is the slug, e.g., 'pa_size'
            }
        }

        // Fallback: if no taxonomy attribute is found, use the first available attribute used for variations.
        foreach ( $attributes as $attribute ) {
            if ( $attribute->get_variation() ) {
                return $attribute->get_name();
            }
        }

        return null;
    }

    private function render_new_layout( $product, $variations ) {
        // 1. Render ACF fields based on correct location rules
        if ( function_exists('acf_get_field_groups') && function_exists('wc_cbo_check_acf_location_rules') ) {
            $all_field_groups = acf_get_field_groups();
            $matching_groups = array();

            foreach ( $all_field_groups as $field_group ) {
                if ( wc_cbo_check_acf_location_rules( $field_group, $product->get_id() ) ) {
                    $matching_groups[] = $field_group;
                }
            }

            if ( !empty($matching_groups) ) {
                echo '<div class="wc-cbo-global-options">';
                foreach ( $matching_groups as $group ) {
                    $fields_in_group = acf_get_fields( $group['key'] );
                    if ( !empty($fields_in_group) ) {
                        foreach ( $fields_in_group as $field ) {
                            acf_render_field_wrap( $field );
                        }
                    }
                }
                echo '</div>';
            }
        }

        // 2. Render the simple size/quantity table
        $attribute_slug = $this->get_primary_variation_attribute_slug( $product );

        if ( ! $attribute_slug ) {
            // Optional: show a message if no suitable attribute is found
            echo '<p>' . esc_html__( 'Inga lämpliga variationer hittades för att bygga matrisen.', 'wc-custom-bulk-order' ) . '</p>';
            return;
        }
        ?>
        <table class="wc-cbo-quantity-table">
            <thead>
                <tr>
                    <th><?php echo esc_html( wc_attribute_label( $attribute_slug, $product ) ); ?></th>
                    <th class="quantity-col"><?php _e( 'Antal', 'wc-custom-bulk-order' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $variations as $variation ) : ?>
                    <?php
                    // Ensure the variation has a value for the chosen attribute
                    $attribute_value = $variation->get_attribute( $attribute_slug );
                    if ( empty( $attribute_value ) && $attribute_value !== '0' ) continue;
                    ?>
                    <tr class="wc-cbo-matrix-row" data-variation-id="<?php echo esc_attr( $variation->get_id() ); ?>">
                        <td class="variation-details">
                            <?php echo esc_html( $attribute_value ); ?>
                        </td>
                        <td class="quantity-col">
                            <input type="number" class="wc-cbo-quantity-input" min="0" placeholder="0" data-variation-id="<?php echo esc_attr( $variation->get_id() ); ?>" />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_summary_and_button( $product ) {
        $discount_tiers = get_post_meta( $product->get_id(), '_wc_cbo_discount_tiers', true );

        if ( ! empty( $discount_tiers ) && is_array( $discount_tiers ) ) {
            echo '<div class="wc-cbo-discount-ladder">';
            echo '<h4>' . esc_html__( 'Volymrabatt', 'wc-custom-bulk-order' ) . '</h4>';
            echo '<table class="wc-cbo-discount-table"><thead><tr><th>' . esc_html__( 'Minst antal', 'wc-custom-bulk-order' ) . '</th><th>' . esc_html__( 'Rabatt', 'wc-custom-bulk-order' ) . '</th></tr></thead><tbody>';
            foreach ( $discount_tiers as $tier ) {
                if ( ! empty( $tier['min'] ) && ! empty( $tier['discount'] ) ) {
                    echo '<tr><td>' . esc_html( $tier['min'] ) . '</td><td>' . esc_html( $tier['discount'] ) . '%</td></tr>';
                }
            }
            echo '</tbody></table></div>';
        }

        ?>
        <div class="wc-cbo-summary-wrapper">
            <div id="wc-cbo-summary-details">
                <p class="price"><?php echo $product->get_price_html(); ?></p>
            </div>
            <button type="submit" class="single_add_to_cart_button button alt" id="wc-cbo-add-to-cart-button"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
        </div>
        <?php
    }
}
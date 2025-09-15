<?php

/**
 * WC_CBO_Product_Matrix Class
 *
 * @class       WC_CBO_Product_Matrix
 * @version     1.4.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Matrix {

    public function __construct() {
        add_action( 'woocommerce_variable_add_to_cart', array( $this, 'replace_variable_add_to_cart' ), 30 );
    }

    public function replace_variable_add_to_cart() {
        global $product;
        if ( ! $product->is_type( 'variable' ) ) return;

        // Ensure variations are visible, which can be an issue in custom loops.
        add_filter( 'woocommerce_product_is_visible', '__return_true' );
        
        remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );

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
        // 1. Render ACF fields first, as global options
        if ( function_exists('get_field_objects') ) {
            $acf_fields = get_field_objects( $product->get_id() );
            if ( !empty($acf_fields) ) {
                echo '<div class="wc-cbo-global-options">';
                foreach ($acf_fields as $field) {
                    if (in_array($field['type'], ['radio', 'select', 'checkbox', 'text', 'textarea'])) {
                        // Clean up labels for price-fields before rendering
                        if (isset($field['choices'])) {
                            foreach ($field['choices'] as $value => &$label) {
                                $parts = explode(':', $label);
                                if (count($parts) === 2 && is_numeric(trim($parts[1]))) {
                                    $label = sprintf(
                                        '%s (+%s)',
                                        trim($parts[0]),
                                        wp_strip_all_tags(wc_price(trim($parts[1])))
                                    );
                                }
                            }
                        }
                        acf_render_field_wrap($field);
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
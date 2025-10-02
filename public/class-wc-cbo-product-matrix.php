<?php
/**
 * WC_CBO_Product_Matrix Class
 *
 * Renders the bulk order matrix for variable products using an output buffer.
 *
 * @class       WC_CBO_Product_Matrix
 * @version     2.0.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Matrix {

    /**
     * Constructor.
     *
     * Hooks in the output buffer methods to replace the default variations form.
     */
    public function __construct() {
        // Start buffering before the default form is rendered.
        add_action( 'woocommerce_variable_add_to_cart', [ $this, 'start_buffer' ], 9 );
        // End buffering after the default form, then render our matrix.
        add_action( 'woocommerce_variable_add_to_cart', [ $this, 'render_matrix_and_end_buffer' ], 31 );
    }

    /**
     * Starts the output buffer for variable products.
     *
     * This captures the default WooCommerce variation form HTML so it can be discarded.
     */
    public function start_buffer() {
        global $product;
        if ( $product && $product->is_type( 'variable' ) ) {
            ob_start();
        }
    }

    /**
     * Cleans the buffer (discarding the default form) and renders the bulk order matrix.
     */
    public function render_matrix_and_end_buffer() {
        global $product;
        if ( $product && $product->is_type( 'variable' ) ) {
            // Discard the default WooCommerce form that was captured in the buffer.
            ob_get_clean();

            // Render our custom matrix.
            $this->render_bulk_order_matrix();
        }
    }

    /**
     * Main render function for the bulk order matrix.
     */
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
        $this->render_matrix_table( $product, $variations );
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
        // 1. Get the saved attribute from product meta
        $saved_attribute_slug = get_post_meta( $product->get_id(), '_wc_cbo_matrix_row_attribute', true );
        $attributes = $product->get_attributes();

        // 2. Validate the saved attribute
        if ( ! empty( $saved_attribute_slug ) && isset( $attributes[ $saved_attribute_slug ] ) ) {
            $attribute_object = $attributes[ $saved_attribute_slug ];
            // Ensure it's used for variations.
            if ( $attribute_object && $attribute_object->get_variation() ) {
                return $saved_attribute_slug;
            }
        }

        // 3. Fallback to auto-detection if saved attribute is invalid or not set
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

    /**
     * Renders the main table for quantity inputs.
     * Renamed from render_new_layout to be more specific.
     *
     * @param WC_Product_Variable $product
     * @param array $variations
     */
    private function render_matrix_table( $product, $variations ) {
        $attribute_slug = $this->get_primary_variation_attribute_slug( $product );

        if ( ! $attribute_slug ) {
            // Optional: show a message if no suitable attribute is found
            echo '<p>' . esc_html__( 'Inga lämpliga variationer hittades för att bygga matrisen.', 'wc-custom-bulk-order' ) . '</p>';
            return;
        }
        ?>
        <h4 class="wc-cbo-matrix-title"><?php _e( 'Välj storlekar', 'wc-custom-bulk-order' ); ?></h4>
        <table class="wc-cbo-quantity-table">
            <tbody>
                <?php foreach ( $variations as $variation ) : ?>
                    <?php
                    // Ensure the variation has a value for the chosen attribute
                    $attribute_value = $variation->get_attribute( $attribute_slug );
                    if ( empty( $attribute_value ) && $attribute_value !== '0' ) continue;
                    ?>
                    <tr class="wc-cbo-matrix-row" data-variation-id="<?php echo esc_attr( $variation->get_id() ); ?>">
                        <td class="variation-details">
                            <strong><?php echo esc_html( $attribute_value ); ?></strong>
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

    /**
     * Renders the discount ladder, summary, and add to cart button.
     *
     * @param WC_Product_Variable $product
     */
    private function render_summary_and_button( $product ) {
        $discount_tiers = get_post_meta( $product->get_id(), '_wc_cbo_discount_tiers', true );

        if ( ! empty( $discount_tiers ) && is_array( $discount_tiers ) ) {
            $ladder_string = '';
            foreach ( $discount_tiers as $tier ) {
                if ( ! empty( $tier['min'] ) && ! empty( $tier['discount'] ) ) {
                    $ladder_string .= sprintf(
                        '<span>%s+ %s%%</span>',
                        esc_html( $tier['min'] ),
                        esc_html( $tier['discount'] )
                    );
                }
            }

            if ( ! empty( $ladder_string ) ) {
                echo '<div class="wc-cbo-discount-ladder-horizontal">';
                echo '<strong>' . esc_html__( 'Volymrabatt:', 'wc-custom-bulk-order' ) . '</strong> ';
                echo str_replace( '</span><span>', '</span> | <span>', $ladder_string );
                echo '</div>';
            }
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

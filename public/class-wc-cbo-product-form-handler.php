<?php
/**
 * WC_CBO_Product_Form_Handler Class
 *
 * Renders the bulk order matrix for variable products.
 *
 * @class       WC_CBO_Product_Form_Handler
 * @version     2.0.5
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Form_Handler {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_before_single_product', array( $this, 'setup_matrix_display' ) );
    }

    /**
     * Set up the matrix display for variable products.
     */
    public function setup_matrix_display() {
        global $product;
        if ( $product && $product->is_type( 'variable' ) ) {
            // Remove the default WooCommerce variations form
            remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );

            // Add our custom matrix display
            add_action( 'woocommerce_variable_add_to_cart', array( $this, 'render_bulk_order_matrix' ), 30 );
        }
    }

    /**
     * Main render function for the bulk order matrix.
     */
    public function render_bulk_order_matrix() {
        global $product;

        add_filter( 'woocommerce_product_is_visible', '__return_true' );
        
        $variations = $product->get_available_variations('objects');
        
        echo '<form class="cart" action="' . esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ) . '" method="post" enctype="multipart/form-data">';

        if ( empty( $variations ) ) {
            echo '<p>' . esc_html__( 'Inga lämpliga variationer hittades för att bygga matrisen.', 'wc-custom-bulk-order' ) . '</p>';
        } else {
            echo '<div class="wc-cbo-matrix-wrapper">';
            $this->render_matrix_table( $product, $variations );
            $this->render_summary_and_button( $product );
            echo '</div>';
        }
        
        echo '</form>';
        
        remove_filter( 'woocommerce_product_is_visible', '__return_true' );
    }

    /**
     * Gets the primary attribute slug to be used for the matrix rows.
     */
    private function get_primary_variation_attribute_slug( $product ) {
        $saved_attribute_slug = get_post_meta( $product->get_id(), '_wc_cbo_matrix_row_attribute', true );
        $attributes = $product->get_attributes();

        if ( ! empty( $saved_attribute_slug ) && isset( $attributes[ $saved_attribute_slug ] ) ) {
            $attribute_object = $attributes[ $saved_attribute_slug ];
            if ( $attribute_object && $attribute_object->get_variation() ) {
                return $saved_attribute_slug;
            }
        }

        foreach ( $attributes as $attribute ) {
            if ( $attribute->get_variation() && $attribute->is_taxonomy() ) {
                return $attribute->get_name();
            }
        }

        foreach ( $attributes as $attribute ) {
            if ( $attribute->get_variation() ) {
                return $attribute->get_name();
            }
        }

        return null;
    }

    /**
     * Renders the main table for quantity inputs.
     */
    private function render_matrix_table( $product, $variations ) {
        $attribute_slug = $this->get_primary_variation_attribute_slug( $product );

        if ( ! $attribute_slug ) {
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

    /**
     * Renders the discount ladder, summary, and add to cart button.
     */
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
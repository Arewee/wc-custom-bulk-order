<?php

/**
 * WC_CBO_Product_Matrix Class
 *
 * @class       WC_CBO_Product_Matrix
 * @version     1.1.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Matrix {

    /**
     * Constructor.
     */
    public function __construct() {
        // Main hook to replace the standard variation form
        add_action( 'woocommerce_variable_add_to_cart', array( $this, 'replace_variable_add_to_cart' ), 30 );
    }

    /**
     * Replaces the standard form with our product matrix.
     */
    public function replace_variable_add_to_cart() {
        global $product;

        if ( ! $product->is_type( 'variable' ) ) {
            return;
        }

        // Remove the standard dropdowns and button
        remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );

        $variations = $product->get_available_variations();
        if ( empty( $variations ) ) {
            return;
        }

        // Start our form wrapper
        echo '<div class="wc-cbo-matrix-wrapper">';

        // Render the matrix
        $this->render_matrix_table( $product, $variations );

        // Render summary and Add to Cart button
        $this->render_summary_and_button( $product );

        echo '</div>';
    }

    /**
     * Renders the main HTML table for the matrix.
     * This version uses a simple row-per-variation structure to accommodate ACF fields.
     *
     * @param WC_Product_Variable $product
     * @param array $variations
     */
    private function render_matrix_table( $product, $variations ) {
        // Get ACF field objects associated with this product
        $acf_fields = function_exists('get_field_objects') ? get_field_objects( $product->get_id() ) : array();

        // Filter to only include fields we want in the matrix
        $matrix_acf_fields = array();
        if ( ! empty( $acf_fields ) ) {
            foreach ( $acf_fields as $field ) {
                // Only include choice and text fields for now
                if ( in_array( $field['type'], array('radio', 'select', 'checkbox', 'text', 'textarea') ) ) {
                    $matrix_acf_fields[] = $field;
                }
            }
        }
        ?>
        <table class="wc-cbo-matrix-table">
            <thead>
                <tr>
                    <th><?php _e( 'Variation', 'wc-custom-bulk-order' ); ?></th>
                    <?php foreach ( $matrix_acf_fields as $field ) : ?>
                        <th><?php echo esc_html( $field['label'] ); ?></th>
                    <?php endforeach; ?>
                    <th class="quantity-col"><?php _e( 'Antal', 'wc-custom-bulk-order' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $variations as $variation ) : ?>
                    <tr class="wc-cbo-matrix-row" data-variation-id="<?php echo esc_attr( $variation['variation_id'] ); ?>">
                        <td class="variation-details">
                            <?php echo esc_html( implode( ', ', $variation['attributes'] ) ); ?>
                        </td>

                        <?php
                        // Render ACF fields for this row
                        if ( ! empty( $matrix_acf_fields ) ) {
                            foreach ( $matrix_acf_fields as $field ) {
                                echo '<td>';
                                // IMPORTANT: We modify the field name to make it unique per variation row.
                                // This groups the data nicely in the POST submission.
                                $field['name'] = sprintf( 'cbo_acf[%d][%s]', $variation['variation_id'], $field['key'] );

                                // Render the field using ACF's function
                                acf_render_field( $field );
                                echo '</td>';
                            }
                        }
                        ?>

                        <td class="quantity-col">
                            <?php
                            // Render the quantity input field
                            printf(
                                '<input type="number" class="wc-cbo-quantity-input" min="0" placeholder="0" data-variation-id="%d" />',
                                $variation['variation_id']
                            );
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Renders the summary and button area.
     */
    private function render_summary_and_button( $product ) {
        ?>
        <div class="wc-cbo-summary-wrapper">
            <div id="wc-cbo-summary-details">
                <!-- JS will populate price info here -->
                <p class="price"><?php echo $product->get_price_html(); ?></p>
            </div>
            <button type="submit" class="single_add_to_cart_button button alt" id="wc-cbo-add-to-cart-button"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
        </div>
        <?php
    }
}
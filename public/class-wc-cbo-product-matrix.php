<?php

/**
 * WC_CBO_Product_Matrix Class
 *
 * @class       WC_CBO_Product_Matrix
 * @version     1.0.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Product_Matrix {

    /**
     * Konstruktor.
     */
    public function __construct() {
        // Huvud-hook för att ersätta standardformuläret för varianter
        add_action( 'woocommerce_variable_add_to_cart', array( $this, 'replace_variable_add_to_cart' ), 30 );
    }

    /**
     * Ersätter standardformuläret med vår produktmatris.
     */
    public function replace_variable_add_to_cart() {
        global $product;

        // Säkerställ att vi bara kör på variabla produkter
        if ( ! $product->is_type( 'variable' ) ) {
            return;
        }

        // Ta bort standard-dropdowns och knapp
        remove_action( 'woocommerce_variable_add_to_cart', 'woocommerce_variable_add_to_cart', 30 );

        // Hämta variationer och attribut
        $variations = $product->get_available_variations();
        $attributes = $product->get_variation_attributes();

        // Om det inte finns några variationer, gör inget
        if ( empty( $variations ) ) {
            return;
        }

        // Starta vår egen formulär-wrapper
        echo '<div class="wc-cbo-matrix-wrapper">';

        // Rendera matrisen (denna funktion blir komplex)
        $this->render_matrix_table( $product, $variations, $attributes );

        // Rendera sammanfattning och Lägg till i varukorg-knapp
        $this->render_summary_and_button( $product );

        echo '</div>';
    }

    /**
     * Ritar upp själva HTML-tabellen för matrisen.
     *
     * @param WC_Product $product
     * @param array $variations
     * @param array $attributes
     */
    private function render_matrix_table( $product, $variations, $attributes ) {
        // För enkelhetens skull antar vi två attribut (t.ex. Färg och Storlek)
        // En mer avancerad version skulle hantera 1 eller 3+ attribut dynamiskt
        if ( count( $attributes ) < 1 ) return; // Behöver minst ett attribut

        // Hämta nycklarna för attributen
        $attr_keys = array_keys( $attributes );
        $row_attr_key = $attr_keys[0];
        $col_attr_key = isset( $attr_keys[1] ) ? $attr_keys[1] : null;

        $row_attr_name = wc_attribute_label( $row_attr_key );
        $col_attr_name = $col_attr_key ? wc_attribute_label( $col_attr_key ) : __( 'Antal', 'wc-custom-bulk-order' );

        // Organisera variationer för enkel åtkomst
        $matrix_data = array();
        foreach ( $variations as $variation ) {
            $row_val = $variation['attributes']['attribute_' . $row_attr_key];
            $col_val = $col_attr_key ? $variation['attributes']['attribute_' . $col_attr_key] : 'quantity';
            $matrix_data[$row_val][$col_val] = $variation;
        }

        ?>
        <table class="wc-cbo-matrix-table">
            <thead>
                <tr>
                    <th><?php echo esc_html( $row_attr_name ); ?></th>
                    <?php if ( $col_attr_key ) : ?>
                        <?php foreach ( $attributes[$col_attr_key] as $col_value ) : ?>
                            <th><?php echo esc_html( $col_value ); ?></th>
                        <?php endforeach; ?>
                    <?php else: ?>
                         <th><?php echo esc_html( $col_attr_name ); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $attributes[$row_attr_key] as $row_value ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row_value ); ?></td>
                        <?php 
                        if ( $col_attr_key ) {
                            foreach ( $attributes[$col_attr_key] as $col_value ) {
                                echo '<td>';
                                if ( isset( $matrix_data[$row_value][$col_value] ) ) {
                                    $variation = $matrix_data[$row_value][$col_value];
                                    // Rendera input-fält för antal
                                    printf( '<input type="number" class="wc-cbo-quantity-input" min="0" placeholder="0" data-variation-id="%d" />', $variation['variation_id'] );
                                } else {
                                    echo '-'; // Kombinationen existerar inte
                                }
                                echo '</td>';
                            }
                        } else {
                             echo '<td>';
                             $variation = $matrix_data[$row_value]['quantity'];
                             printf( '<input type="number" class="wc-cbo-quantity-input" min="0" placeholder="0" data-variation-id="%d" />', $variation['variation_id'] );
                             echo '</td>';
                        }
                        ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Ritar upp sammanfattning och knapp.
     */
    private function render_summary_and_button( $product ) {
        ?>
        <div class="wc-cbo-summary-wrapper">
            <div id="wc-cbo-summary-details">
                <!-- JS kommer att fylla på med pris-info här -->
                <p class="price"><?php echo $product->get_price_html(); ?></p>
            </div>
            <button type="submit" class="single_add_to_cart_button button alt" id="wc-cbo-add-to-cart-button"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
        </div>
        <?php
    }
}

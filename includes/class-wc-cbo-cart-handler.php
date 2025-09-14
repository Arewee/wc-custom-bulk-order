<?php
/**
 * WC_CBO_Cart_Handler Class
 *
 * Handles adding products to the cart via AJAX and modifying cart item data.
 *
 * @class       WC_CBO_Cart_Handler
 * @version     1.1.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class WC_CBO_Cart_Handler {

    /**
     * Constructor.
     */
    public function __construct() {
        // AJAX actions
        add_action( 'wp_ajax_wc_cbo_add_to_cart', array( $this, 'ajax_add_to_cart_handler' ) );
        add_action( 'wp_ajax_nopriv_wc_cbo_add_to_cart', array( $this, 'ajax_add_to_cart_handler' ) );

        // Cart item data hooks
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_custom_data_to_cart_item' ), 10, 3 );
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_custom_item_data' ), 10, 2 );
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'set_custom_cart_item_price' ), 1000, 1 );
    }

    /**
     * Handles the AJAX request to add matrix products to the cart.
     */
    public function ajax_add_to_cart_handler() {
        check_ajax_referer( 'wc-cbo-ajax-nonce', 'nonce' );

        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        $cart_items = isset( $_POST['cart_items'] ) ? (array) $_POST['cart_items'] : array();

        if ( empty( $product_id ) || empty( $cart_items ) ) {
            wp_send_json_error( array( 'message' => __( 'Missing required data.', 'wc-custom-bulk-order' ) ) );
        }

        // Optional: Clear previous entries of this product to avoid duplicates
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( $cart_item['product_id'] == $product_id ) {
                WC()->cart->remove_cart_item( $cart_item_key );
            }
        }

        foreach ( $cart_items as $item ) {
            $variation_id = absint( $item['variation_id'] );
            $quantity     = absint( $item['quantity'] );
            $acf_data     = isset( $item['acf_data'] ) ? (array) $item['acf_data'] : array();

            if ( $quantity <= 0 || $variation_id <= 0 ) {
                continue;
            }
            
            $cart_item_data = array(
                'cbo_acf_data' => $acf_data
            );

            WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, array(), $cart_item_data );
        }
        
        wp_send_json_success( array(
            'cart_url' => wc_get_cart_url(),
        ) );

        wp_die();
    }

    /**
     * Adds our custom data to the cart item.
     */
    public function add_custom_data_to_cart_item( $cart_item_data, $product_id, $variation_id ) {
        // This function is now simpler as we pass data directly to add_to_cart
        // However, we keep it in case other logic needs to be added here later.
        return $cart_item_data;
    }

    /**
     * Displays the custom data in the cart and checkout.
     */
    public function display_custom_item_data( $item_data, $cart_item ) {
        if ( empty( $cart_item['cbo_acf_data'] ) ) {
            return $item_data;
        }

        foreach ( $cart_item['cbo_acf_data'] as $field_key => $value ) {
            $field = get_field_object( $field_key );
            if ( $field ) {
                $display_value = is_array( $value ) ? implode( ', ', $value ) : $value;
                
                if( !empty($field['choices']) && isset($field['choices'][$display_value]) ){
                    $display_value = $field['choices'][$display_value];
                }

                $item_data[] = array(
                    'key'     => $field['label'],
                    'value'   => $display_value,
                    'display' => '',
                );
            }
        }

        return $item_data;
    }

    /**
     * Sets the custom calculated price on the cart item.
     */
    public function set_custom_cart_item_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        foreach ( $cart->get_cart() as $cart_item ) {
            // Only act on items that have our custom data
            if ( empty( $cart_item['cbo_acf_data'] ) ) {
                continue;
            }

            $final_price = 0;
            
            $product_variation = wc_get_product( $cart_item['variation_id'] );
            $final_price += (float) $product_variation->get_price();

            if ( ! empty( $cart_item['cbo_acf_data'] ) ) {
                foreach ( $cart_item['cbo_acf_data'] as $field_key => $value ) {
                    $field = get_field_object( $field_key );
                    if ( ! empty( $field['wc_cbo_price_options'] ) ) {
                        $price_options = array();
                        $options_pairs = explode( '|', $field['wc_cbo_price_options'] );
                        foreach ( $options_pairs as $pair ) {
                            $parts = explode( ':', $pair );
                            if ( count( $parts ) === 2 ) {
                                $price_options[ trim( $parts[0] ) ] = (float) trim( $parts[1] );
                            }
                        }
                        
                        $values = is_array($value) ? $value : array($value);
                        foreach ($values as $single_value) {
                            if ( isset( $price_options[$single_value] ) ) {
                                $final_price += $price_options[$single_value];
                            }
                        }
                    }
                }
            }
            
            $cart_item['data']->set_price( $final_price );
        }
        
        $this->apply_bulk_discount( $cart );
    }

    /**
     * Applies the overall bulk discount based on total quantity of a product.
     */
    private function apply_bulk_discount( $cart ) {
        $cart_contents = $cart->get_cart();
        $product_quantities = array();

        foreach ( $cart_contents as $key => $item ) {
            // Only consider items that are part of a bulk order
            if( empty( $item['cbo_acf_data'] ) ) continue;

            $product_id = $item['product_id'];
            if ( ! isset( $product_quantities[$product_id] ) ) {
                $product_quantities[$product_id] = 0;
            }
            $product_quantities[$product_id] += $item['quantity'];
        }

        foreach ( $product_quantities as $product_id => $total_quantity ) {
            $discount_tiers = get_post_meta( $product_id, '_wc_cbo_discount_tiers', true );
            if ( empty( $discount_tiers ) ) {
                continue;
            }

            $discount_percent = 0;
            $applicable_tier = null;
            
            usort($discount_tiers, function($a, $b) { return $b['min'] <=> $a['min']; });
            foreach($discount_tiers as $tier){
                if($total_quantity >= $tier['min']){
                    $applicable_tier = $tier;
                    break;
                }
            }

            if ( $applicable_tier ) {
                $discount_percent = (float) $applicable_tier['discount'];
            }

            if ( $discount_percent > 0 ) {
                $discount_amount = 0;
                foreach ( $cart_contents as $key => $item ) {
                    if ( $item['product_id'] == $product_id ) {
                        $discount_amount += $item['line_total'] * ( $discount_percent / 100 );
                    }
                }
                
                if( $discount_amount > 0 ){
                     $cart->add_fee(
                        sprintf( __( 'Mängdrabatt (%s%%)', 'wc-custom-bulk-order' ), $discount_percent ),
                        - $discount_amount
                    );
                }
            }
        }
    }
}
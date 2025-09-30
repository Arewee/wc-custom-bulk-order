<?php

/**
 * WC_CBO_Cart_Handler Class
 *
 * @class       WC_CBO_Cart_Handler
 * @version     2.0.1
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Cart_Handler {

    public function __construct() {
        // AJAX handler for adding items from the matrix to the cart
        add_action( 'wp_ajax_wc_cbo_add_to_cart', array( $this, 'ajax_add_to_cart' ) );
        add_action( 'wp_ajax_nopriv_wc_cbo_add_to_cart', array( $this, 'ajax_add_to_cart' ) );

        // Add custom ACF data to cart item for simple products
        add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_acf_data_to_cart_item' ), 10, 2 );

        // Display custom data in cart and checkout
        add_filter( 'woocommerce_get_item_data', array( $this, 'display_cbo_data_in_cart' ), 10, 2 );

        // Save custom data to the order itself
        add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_cbo_data_to_order_item' ), 10, 4 );

        // Apply ACF price surcharges before other calculations.
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_acf_price_surcharges' ), 10, 1 );

        // Apply volume discounts after surcharges.
        add_action( 'woocommerce_before_calculate_totals', array( $this, 'apply_volume_discounts' ), 20, 1 );
    }

    /**
     * Add ACF data to the cart item for simple products.
     *
     * @param array $cart_item_data
     * @param int   $product_id
     * @return array
     */
    public function add_acf_data_to_cart_item( $cart_item_data, $product_id ) {
        if ( isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ) {
            $acf_data = $_POST['acf'];
            $sanitized_acf_data = [];

            foreach ( $acf_data as $field_key => $value ) {
                if ( is_array( $value ) ) {
                    $sanitized_acf_data[ $field_key ] = array_map( 'sanitize_text_field', $value );
                } else {
                    $sanitized_acf_data[ $field_key ] = sanitize_text_field( $value );
                }
            }

            if ( ! empty( $sanitized_acf_data ) ) {
                $cart_item_data['cbo_acf_data'] = $sanitized_acf_data;
            }
        }

        return $cart_item_data;
    }

    /**
     * Handle the AJAX request to add multiple variations to the cart.
     */
    public function ajax_add_to_cart() {
        check_ajax_referer( 'wc-cbo-ajax-nonce', 'nonce' );

        if ( ! isset( $_POST['product_id'] ) || ! isset( $_POST['cart_items'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Ogiltig frfrgan.', 'wc-custom-bulk-order' ) ) );
            return;
        }

        $product_id = absint( $_POST['product_id'] );
        $cart_items = $_POST['cart_items'];

        foreach ( $cart_items as $item ) {
            $variation_id = absint( $item['variation_id'] );
            $quantity = absint( $item['quantity'] );
            $variation = wc_get_product( $variation_id );
            $variation_attributes = $variation->get_variation_attributes();

            // Sanitize ACF data
            $acf_data = isset($item['acf_data']) ? $item['acf_data'] : array();
            $cart_item_data = array(
                'cbo_acf_data' => $acf_data
            );

            WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $variation_attributes, $cart_item_data );
        }

        wp_send_json_success( array( 'cart_url' => wc_get_cart_url() ) );
    }

    /**
     * Display custom data on cart and checkout pages.
     *
     * @param array $other_data
     * @param array $cart_item
     * @return array
     */
    public function display_cbo_data_in_cart( $other_data, $cart_item ) {
        if ( ! empty( $cart_item['cbo_acf_data'] ) ) {
            foreach ( $cart_item['cbo_acf_data'] as $field_key => $value ) {
                if ( empty( $value ) ) continue;

                $field = acf_get_field( $field_key );

                if ( $field ) {
                    $display_values = [];
                    $values = is_array($value) ? $value : [ $value ];

                    foreach ($values as $single_value) {
                        // Clean up price from value if it exists (e.g., "Guld:50" -> "Guld")
                        $parts = explode(':', $single_value);
                        if (count($parts) === 2 && is_numeric(trim($parts[1]))) {
                            $display_values[] = trim($parts[0]);
                        } else {
                            $display_values[] = $single_value;
                        }
                    }

                    $other_data[] = array(
                        'name'  => $field['label'],
                        'value' => implode(', ', $display_values),
                    );
                }
            }
        }
        return $other_data;
    }

    /**
     * Save custom data to the order line items.
     *
     * @param WC_Order_Item_Product $item
     * @param string                $cart_item_key
     * @param array                 $values
     * @param WC_Order              $order
     */
    public function save_cbo_data_to_order_item( $item, $cart_item_key, $values, $order ) {
        if ( ! empty( $values['cbo_acf_data'] ) ) {
            foreach ( $values['cbo_acf_data'] as $field_key => $value ) {
                if ( empty( $value ) ) continue;

                $field = acf_get_field( $field_key );
                if ( $field ) {
                    $display_values = [];
                    $raw_values = is_array($value) ? $value : [ $value ];

                    foreach ($raw_values as $single_value) {
                        $parts = explode(':', $single_value);
                        if (count($parts) === 2 && is_numeric(trim($parts[1]))) {
                            $display_values[] = trim($parts[0]);
                        } else {
                            $display_values[] = $single_value;
                        }
                    }
                    $item->add_meta_data( $field['label'], implode(', ', $display_values) );
                }
            }
        }
    }

    /**
     * Apply price surcharges from ACF fields before final calculations.
     *
     * @param WC_Cart $cart
     */
    public function apply_acf_price_surcharges( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( ! empty( $cart_item['cbo_acf_data'] ) ) {
                $surcharge = 0;

                foreach ( $cart_item['cbo_acf_data'] as $field_key => $value ) {
                    if ( empty( $value ) ) continue;

                    // Handle both single values and arrays of values (e.g., from checkboxes)
                    $values = is_array($value) ? $value : [ $value ];

                    foreach ($values as $single_value) {
                        $parts = explode( ':', $single_value );
                        if ( count( $parts ) === 2 && is_numeric( trim( $parts[1] ) ) ) {
                            $surcharge += (float) trim( $parts[1] );
                        }
                    }
                }

                if ( $surcharge > 0 ) {
                    $product = $cart_item['data'];
                    $original_price = $product->get_price();
                    $product->set_price( $original_price + $surcharge );
                }
            }
        }
    }

    /**
     * Apply volume discounts to the cart using a negative fee.
     */
    public function apply_volume_discounts( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        // Only proceed if the cart isn't empty
        if ( $cart->is_empty() ) {
            return;
        }

        $product_id_with_discount = 0;
        $total_quantity = 0;
        $total_value_of_discounted_items = 0;

        // First loop: Find if a product with discounts exists and calculate total quantity/value.
        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            // Check for discount tiers on the parent product
            $tiers = get_post_meta( $cart_item['product_id'], '_wc_cbo_discount_tiers', true );
            
            if ( ! empty( $tiers ) ) {
                if ( $product_id_with_discount === 0 ) {
                    $product_id_with_discount = $cart_item['product_id'];
                }
                
                // Only aggregate for the specific product that has the discount tiers
                if ( $cart_item['product_id'] === $product_id_with_discount ) {
                    $total_quantity += $cart_item['quantity'];
                    // Note: We use get_price() here to get the price *after* surcharges have been applied.
                    $total_value_of_discounted_items += $cart_item['data']->get_price() * $cart_item['quantity'];
                }
            }
        }

        if ( ! $product_id_with_discount || $total_quantity === 0 ) {
            return; // No products with discounts or zero quantity.
        }

        $discount_tiers = get_post_meta( $product_id_with_discount, '_wc_cbo_discount_tiers', true );
        $discount_percent = 0;

        if ( ! empty( $discount_tiers ) && is_array( $discount_tiers ) ) {
            usort($discount_tiers, function($a, $b) {
                return $b['min'] <=> $a['min'];
            });

            foreach ( $discount_tiers as $tier ) {
                if ( $total_quantity >= $tier['min'] ) {
                    $discount_percent = (float) $tier['discount'];
                    break;
                }
            }
        }

        if ( $discount_percent > 0 ) {
            $discount_amount = $total_value_of_discounted_items * ( $discount_percent / 100 );

            if( $discount_amount > 0 ){
                $cart->add_fee(
                    sprintf( __( 'Volymrabatt (%s%%)', 'wc-custom-bulk-order' ), $discount_percent ),
                    - $discount_amount
                );
            }
        }
    }
}
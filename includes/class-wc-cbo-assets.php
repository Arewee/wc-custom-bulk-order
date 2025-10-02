<?php

/**
 * WC_CBO_Assets Class
 *
 * @class       WC_CBO_Assets
 * @version     2.0.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Assets {

    /**
     * Konstruktor.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
    }

    /**
     * Ladda in skript och stilar för admin-panelen.
     */
    public function enqueue_admin_assets( $hook ) {
        // Ladda bara på produktsidan i admin
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }

        global $post;
        if ( ! $post || 'product' !== $post->post_type ) {
            return;
        }

        wp_enqueue_style(
            'wc-cbo-admin-style',
            WC_CBO_PLUGIN_URL . 'admin/css/wc-cbo-admin.css',
            array(),
            WC_CBO_VERSION
        );

        wp_enqueue_script(
            'wc-cbo-admin-script',
            WC_CBO_PLUGIN_URL . 'admin/js/wc-cbo-admin.js',
            array( 'jquery' ),
            WC_CBO_VERSION,
            true
        );

        // Skicka data från PHP till JavaScript
        $discount_tiers = get_post_meta( $post->ID, '_wc_cbo_discount_tiers', true );
        wp_localize_script(
            'wc-cbo-admin-script',
            'wc_cbo_admin_meta',
            array(
                'discount_tiers' => ! empty( $discount_tiers ) ? $discount_tiers : array(),
                'placeholders' => array(
                    'min' => __( 'Från (antal)', 'wc-custom-bulk-order' ),
                    'max' => __( 'Till (antal)', 'wc-custom-bulk-order' ),
                    'discount' => __( 'Rabatt (%)', 'wc-custom-bulk-order' ),
                )
            )
        );
    }

    /**
     * Ladda in skript och stilar för den publika sidan.
     */
    public function enqueue_public_assets() {
        // Ladda bara på enskilda produktsidor
        if ( ! is_product() ) {
            return;
        }

        $product = wc_get_product();

        wp_enqueue_style(
            'wc-cbo-public-style',
            WC_CBO_PLUGIN_URL . 'public/css/wc-cbo-public.css',
            array(),
            WC_CBO_VERSION
        );

        wp_enqueue_script(
            'wc-cbo-public-script',
            WC_CBO_PLUGIN_URL . 'public/js/wc-cbo-public.js',
            array( 'jquery', 'acf-input' ), // Added acf-input dependency
            WC_CBO_VERSION,
            true
        );

        // --- Data för JavaScript ---
        $script_params = array(
            'product_id'     => $product->get_id(),
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'nonce'          => wp_create_nonce( 'wc-cbo-ajax-nonce' ),
            'file_upload_nonce' => wp_create_nonce( 'wc-cbo-file-upload-nonce' ),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'price_decimals' => wc_get_price_decimals(),
            'price_thousand_separator' => wc_get_price_thousand_separator(),
            'price_decimal_separator' => wc_get_price_decimal_separator(),
            // Initialize variable-specific keys to be safe
            'min_quantity'   => 0,
            'prod_time'      => 0,
            'discount_tiers' => array(),
            'variations'     => array(),
        );

        // Only add variable-specific data for variable products
        if ( $product && $product->is_type('variable') ) {
            $script_params['min_quantity']   = get_post_meta( $product->get_id(), '_wc_cbo_min_quantity', true );
            $script_params['prod_time']      = get_post_meta( $product->get_id(), '_wc_cbo_prod_time', true );
            $script_params['discount_tiers'] = get_post_meta( $product->get_id(), '_wc_cbo_discount_tiers', true );
            $script_params['variations']     = $product->get_available_variations();
        }

        // Hämta och behandla ACF prispåslag
        $acf_prices = array();
        if ( function_exists('get_field_objects') ) {
            $acf_fields = get_field_objects( $product->get_id() );

            if ( ! empty( $acf_fields ) ) {
                foreach ( $acf_fields as $field ) {
                    if ( isset( $field['choices'] ) && is_array( $field['choices'] ) ) {
                        $price_options = array();
                        foreach ( $field['choices'] as $value => $label ) {
                            $target_string = $value;
                            $parts = explode( ':', $target_string );
                            if ( count( $parts ) === 2 && is_numeric( trim( $parts[1] ) ) ) {
                                $price_options[ $value ] = (float) trim( $parts[1] );
                            }
                        }
                        if ( ! empty( $price_options ) ) {
                            $acf_prices[ $field['key'] ] = $price_options;
                        }
                    }
                }
            }
        }
        $script_params['acf_prices'] = $acf_prices;

        // Skicka all data
        wp_localize_script(
            'wc-cbo-public-script',
            'wc_cbo_params',
            $script_params
        );
    }
}
<?php

/**
 * WC_CBO_Assets Class
 *
 * @class       WC_CBO_Assets
 * @version     1.1.0
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
        // Ladda bara på enskilda produktsidor för variabla produkter
        if ( ! is_product() || ! wc_get_product()->is_type( 'variable' ) ) {
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

        // 1. Grundläggande produktdata
        $min_quantity   = get_post_meta( $product->get_id(), '_wc_cbo_min_quantity', true );
        $prod_time      = get_post_meta( $product->get_id(), '_wc_cbo_prod_time', true );
        $discount_tiers = get_post_meta( $product->get_id(), '_wc_cbo_discount_tiers', true );
        $variations     = $product->get_available_variations();

        // 2. Hämta och behandla ACF prispåslag
        $acf_prices = array();
        if ( function_exists('get_field_objects') ) {
            $acf_fields = get_field_objects( $product->get_id() );
            if ( ! empty( $acf_fields ) ) {
                foreach ( $acf_fields as $field ) {
                    if ( ! empty( $field['wc_cbo_price_options'] ) ) {
                        $price_options = array();
                        $options_pairs = explode( '|', $field['wc_cbo_price_options'] );
                        foreach ( $options_pairs as $pair ) {
                            $parts = explode( ':', $pair );
                            if ( count( $parts ) === 2 ) {
                                $price_options[ trim( $parts[0] ) ] = (float) trim( $parts[1] );
                            }
                        }
                        if ( ! empty( $price_options ) ) {
                            $acf_prices[ $field['key'] ] = $price_options;
                        }
                    }
                }
            }
        }

        // 3. Skicka all data
        wp_localize_script(
            'wc-cbo-public-script',
            'wc_cbo_params',
            array(
                'product_id'     => $product->get_id(),
                'ajax_url'       => admin_url( 'admin-ajax.php' ),
                'nonce'          => wp_create_nonce( 'wc-cbo-ajax-nonce' ),
                'min_quantity'   => ! empty( $min_quantity ) ? absint( $min_quantity ) : 0,
                'prod_time'      => ! empty( $prod_time ) ? absint( $prod_time ) : 0,
                'discount_tiers' => ! empty( $discount_tiers ) ? $discount_tiers : array(),
                'variations'     => $variations,
                'acf_prices'     => $acf_prices, // <-- Ny data
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'price_decimals' => wc_get_price_decimals(),
                'price_thousand_separator' => wc_get_price_thousand_separator(),
                'price_decimal_separator' => wc_get_price_decimal_separator(),
            )
        );
    }
}
<?php

/**
 * WC_CBO_Assets Class
 *
 * @class       WC_CBO_Assets
 * @version     1.0.0
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
    }

    /**
     * Ladda in skript och stilar för den publika sidan.
     */
    public function enqueue_public_assets() {
        // Denna kommer vi att fylla i senare
    }
}

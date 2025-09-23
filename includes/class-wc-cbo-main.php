<?php

/**
 * WC_CBO_Main Class
 *
 * @class       WC_CBO_Main
 * @version     1.0.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_Main {

    /**
     * Plugin version.
     *
     * @var string
     */
    public $version = WC_CBO_VERSION;

    /**
     * The single instance of the class.
     *
     * @var WC_CBO_Main
     */
    protected static $_instance = null;

    /**
     * Main-instansen av pluginet.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Konstruktor.
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();

        // Initiera klasserna här när de behövs
        new WC_CBO_Assets();
        new WC_CBO_ACF_Integration();
        new WC_CBO_Product_Meta();
        new WC_CBO_Cart_Handler();
        // new WC_CBO_Order_Handler(); // Tom klass, orsakar fel
        new WC_CBO_Product_Matrix();
    }

    /**
     * Inkluderar nödvändiga filer.
     */
    private function includes() {
        require_once WC_CBO_PLUGIN_DIR . 'includes/class-wc-cbo-assets.php';
        require_once WC_CBO_PLUGIN_DIR . 'includes/class-wc-cbo-acf-integration.php';
        require_once WC_CBO_PLUGIN_DIR . 'includes/class-wc-cbo-product-meta.php';
        require_once WC_CBO_PLUGIN_DIR . 'includes/class-wc-cbo-cart-handler.php';
        // require_once WC_CBO_PLUGIN_DIR . 'includes/class-wc-cbo-order-handler.php'; // Tom fil, orsakar fel
        require_once WC_CBO_PLUGIN_DIR . 'public/class-wc-cbo-product-matrix.php';
    }

    /**
     * Initierar hooks.
     */
    private function init_hooks() {
        // Ladda textdomain för översättningar
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    /**
     * Ladda textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'wc-custom-bulk-order', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }
}

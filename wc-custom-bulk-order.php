<?php
/**
 * Plugin Name:       WC Custom Bulk Order
 * Plugin URI:        https://example.com/
 * Description:       Ett skräddarsytt plugin för bulk-beställningar i WooCommerce med anpassade fält, prispåslag och kvantitetsrabatter.
 * Version:           1.8.0
 * Author:            AreWee
 * Author URI:        https://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-custom-bulk-order
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Definiera konstanter
 */
define( 'WC_CBO_VERSION', '1.8.0' );
define( 'WC_CBO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WC_CBO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Ladda in huvudklassen för pluginet.
 */
require_once WC_CBO_PLUGIN_DIR . 'includes/acf-helper.php';
require_once WC_CBO_PLUGIN_DIR . 'includes/class-wc-cbo-main.php';

/**
 * Starta pluginet.
 */
function run_wc_custom_bulk_order() {
	new WC_CBO_Main();
}
add_action( 'plugins_loaded', 'run_wc_custom_bulk_order' );

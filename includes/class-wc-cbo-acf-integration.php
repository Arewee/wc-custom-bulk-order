<?php
/**
 * WC Custom Bulk Order - ACF Integration
 *
 * This class handles the integration of Advanced Custom Fields (ACF)
 * with WooCommerce product pages for the bulk order functionality.
 *
 * @package WC_Custom_Bulk_Order
 * @version 2.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WC_CBO_ACF_Integration {

    /**
     * A flag to prevent fields from rendering more than once on a single page load.
     *
     * @var bool
     */
    private $fields_rendered = false;

    /**
     * Constructor.
     *
     * Hooks the rendering function into the appropriate WooCommerce actions.
     */
    public function __construct() {
        // Add a shortcode for manual placement in page builders.
        add_shortcode( 'wc_cbo_acf_fields', [ $this, 'acf_shortcode' ] );
    }

    /**
     * Shortcode handler to render the ACF fields.
     *
     * @return string The HTML output of the fields.
     */
    public function acf_shortcode() {
        ob_start();
        $this->render_acf_fields_conditionally();
        return ob_get_clean();
    }

    /**
     * Checks if we should render fields and prevents double rendering.
     * This function is hooked to multiple actions to support different themes and product types.
     */
    public function render_acf_fields_conditionally() {
        global $product;

        // If fields have already been rendered by another hook on this page load, exit.
        if ( $this->fields_rendered ) {
            return;
        }

        // Ensure we have a valid product object.
        if ( ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        // Ensure our helper function and ACF core functions are available.
        if ( ! function_exists( 'acf_get_field_groups' ) || ! function_exists( 'wc_cbo_check_acf_location_rules' ) ) {
            return;
        }

        // Get all field groups registered in ACF.
        $field_groups = acf_get_field_groups();
        if ( empty( $field_groups ) ) {
            return;
        }

        $this->render_matching_acf_fields( $product, $field_groups );
    }

    /**
     * Renders the fields from ACF field groups whose location rules match the current product.
     *
     * @param WC_Product $product The current WooCommerce product object.
     * @param array      $field_groups All available ACF field groups.
     */
    private function render_matching_acf_fields( $product, $field_groups ) {
        $post_id = $product->get_id();
        $fields_to_render = [];

        // Loop through all field groups and check their location rules against the current product.
        foreach ( $field_groups as $field_group ) {
            if ( wc_cbo_check_acf_location_rules( $field_group, $post_id ) ) {
                $fields = acf_get_fields( $field_group['ID'] );
                if ( ! empty( $fields ) ) {
                    $fields_to_render = array_merge( $fields_to_render, $fields );
                }
            }
        }

        // If we found any fields that should be rendered, display them.
        if ( ! empty( $fields_to_render ) ) {
            echo '<div class="wc-cbo-acf-fields-wrapper">';

            // Use ACF's built-in function to render the collected fields.
            acf_render_fields( $post_id, $fields_to_render );

            echo '</div>';

            // Enqueue ACF's scripts and styles to make fields interactive (e.g., date pickers).
            acf_enqueue_scripts();

            // Set the flag to true to prevent this from running again.
            $this->fields_rendered = true;
        }
    }
}
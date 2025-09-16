<?php
/**
 * WC_CBO_ACF_Integration Class
 *
 * Handles the dynamic rendering of ACF fields on the single product page based on location rules.
 *
 * @class       WC_CBO_ACF_Integration
 * @version     1.5.1
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class WC_CBO_ACF_Integration {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'render_matching_acf_groups' ), 25 );
    }

    /**
     * Finds all ACF Field Groups that match the current product and renders them.
     */
    public function render_matching_acf_groups() {
        global $product;

        if ( ! is_a( $product, 'WC_Product' ) || ! function_exists( 'acf_get_field_groups' ) || ! function_exists('acf_match_location_rules') ) {
            return;
        }

        $product_id = $product->get_id();
        $all_groups = acf_get_field_groups();
        $matching_fields = [];

        // Loop through all field groups to find which ones should appear on this product page
        foreach ( $all_groups as $group ) {
            $is_visible = acf_match_location_rules( $group['location'], array('post_id' => $product_id) );
            
            if ( $is_visible ) {
                $fields_in_group = acf_get_fields( $group['key'] );
                if( $fields_in_group ) {
                    $matching_fields = array_merge($matching_fields, $fields_in_group);
                }
            }
        }

        if ( empty($matching_fields) ) {
            return;
        }

        // Render the fields
        echo '<div class="cbo-options">';

        foreach ( $matching_fields as $field ) {
            // Use the field key to get the value for the current product
            $field_value = get_field( $field['key'], $product_id );

            // Skip empty fields, unless it's our special color swatch field which should always show options
            if ( empty($field_value) && (!is_string($field['name']) || strpos($field['name'], 'farg') === false) ) {
                continue;
            }
            
            $field_classes = 'cbo-options__field cbo-options__field--' . esc_attr($field['type']);
            echo '<div class="' . $field_classes . '">';

            // --- Special handling for Color Swatch Radio Buttons ---
            if ( $field['type'] === 'radio' && is_string($field['name']) && strpos( $field['name'], 'farg' ) !== false ) {
                echo '<label class="cbo-options__label">' . esc_html( $field['label'] ) . ( !empty($field['required']) ? ' <span class="required">*</span>' : '' ) . '</label>';
                
                echo '<div class="cbo-color-swatches">';
                foreach ( $field['choices'] as $value => $label ) {
                    $id = esc_attr( $field['key'] . '-' . $value );
                    echo '<div class="cbo-color-swatches__option">';
                    echo '<input class="cbo-color-swatches__input" type="radio" id="' . $id . '" name="acf[' . esc_attr($field['key']) . ']" value="' . esc_attr( $value ) . '" ' . ( $field['required'] ? 'required' : '' ) . ' />';
                    echo '<label for="' . $id . '" class="cbo-color-swatches__label">';
                    echo '<span class="cbo-color-swatches__visual" style="background-color: ' . esc_attr( $value ) . ';"></span>';
                    echo '<span class="cbo-color-swatches__name">' . esc_html( $label ) . '</span>';
                    echo '</label>';
                    echo '</div>';
                }
                echo '</div>';

                if ( ! empty( $field['instructions'] ) ) {
                    echo '<p class="cbo-options__instructions">' . wp_kses_post( $field['instructions'] ) . '</p>';
                }

            } else { // Default display for other fields
                echo '<label class="cbo-options__label">' . esc_html($field['label']) . ( !empty($field['required']) ? ' <span class="required">*</span>' : '' ) . '</label>';
                
                if ( $field_value ) {
                    echo '<div class="cbo-options__value">';
                    if (is_array($field_value)) {
                        echo '<ul>';
                        foreach ($field_value as $item) {
                            if (is_object($item) && isset($item->post_title)) {
                                echo '<li>' . esc_html($item->post_title) . '</li>';
                            } else {
                                echo '<li>' . esc_html($item) . '</li>';
                            }
                        }
                        echo '</ul>';
                    } else {
                        echo wp_kses_post( $field_value );
                    }
                    echo '</div>';
                }
                
                 if ( ! empty( $field['instructions'] ) ) {
                    echo '<p class="cbo-options__instructions">' . wp_kses_post( $field['instructions'] ) . '</p>';
                }
            }
            
            echo '</div>';
        }

        echo '</div>';
    }
}

// Instantiate the class
new WC_CBO_ACF_Integration();
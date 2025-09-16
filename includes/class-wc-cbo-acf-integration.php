<?php
/**
 * WC_CBO_ACF_Integration Class
 *
 * Handles the dynamic rendering of ACF fields on the single product page.
 *
 * @class       WC_CBO_ACF_Integration
 * @version     1.4.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class WC_CBO_ACF_Integration {

    /**
     * The name of our custom field for selecting the ACF group.
     * @var string
     */
    private $field_name_for_group_selector = 'wc_cbo_acf_field_group_to_display';

    /**
     * Constructor.
     */
    public function __construct() {
        // Action to add the ACF Field Group to manage this feature
        add_action( 'acf/include_fields', array( $this, 'create_admin_field_group' ) );

        // Filter to dynamically populate the choices of our selector field
        add_filter( 'acf/load_field/name=' . $this->field_name_for_group_selector, array( $this, 'load_field_group_choices' ) );

        // Action to display the selected field group on the frontend
        add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'display_selected_field_group' ), 25 );
    }

    /**
     * Creates the necessary ACF Field Group and Field for the admin interface.
     * This group will be shown on the Product admin page.
     */
    public function create_admin_field_group() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }

        acf_add_local_field_group(array(
            'key' => 'group_wc_cbo_acf_integration',
            'title' => __( 'Custom Fields Display', 'wc-custom-bulk-order' ),
            'fields' => array(
                array(
                    'key' => 'field_' . $this->field_name_for_group_selector,
                    'label' => __( 'ACF Field Group to Display', 'wc-custom-bulk-order' ),
                    'name' => $this->field_name_for_group_selector,
                    'type' => 'select',
                    'instructions' => __( 'Choose an ACF Field Group to display on the frontend for this product. The list is automatically filtered based on the field group's location rules.', 'wc-custom-bulk-order' ),
                    'choices' => array(), // Will be populated dynamically
                    'allow_null' => 1,
                    'ui' => 1,
                    'ajax' => 0,
                    'placeholder' => __( 'None', 'wc-custom-bulk-order' ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ),
                ),
            ),
            'menu_order' => 20,
            'position' => 'side',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
        ));
    }

    /**
     * Dynamically loads the available ACF Field Groups into our select field.
     * It respects the location rules of the field groups.
     *
     * @param array $field The field settings.
     * @return array The modified field settings.
     */
    public function load_field_group_choices( $field ) {
        if ( ! function_exists( 'acf_get_field_groups' ) || ! isset( $GLOBALS['post'] ) ) {
            return $field;
        }

        $post_id = $GLOBALS['post']->ID;
        $field['choices'] = array();

        $field_groups = acf_get_field_groups();

        foreach ( $field_groups as $group ) {
            // Don't allow selecting our own settings group
            if ( $group['key'] === 'group_wc_cbo_acf_integration' ) {
                continue;
            }

            // Check if the group should be visible for the current post
            $location_rules = $group['location'];
            $is_visible = false;
            if( function_exists('acf_match_location_rules') ){ // ACF 5.9+
                 $is_visible = acf_match_location_rules( $location_rules, array('post_id' => $post_id) );
            } else { // Fallback for older versions
                // This is a simplified check. A full implementation would be more complex.
                // For now, we assume it's visible if it's for 'product' post type.
                foreach($location_rules as $rule_group){
                    foreach($rule_group as $rule){
                        if($rule['param'] === 'post_type' && $rule['operator'] === '==' && $rule['value'] === 'product'){
                            $is_visible = true;
                            break 2;
                        }
                    }
                }
            }

            if ( $is_visible ) {
                $field['choices'][ $group['key'] ] = $group['title'];
            }
        }

        return $field;
    }

    /**
     * Renders the selected ACF field group on the single product page.
     */
    public function display_selected_field_group() {
        global $product;
        $group_key = get_post_meta( $product->get_id(), $this->field_name_for_group_selector, true );

        if ( ! $group_key || ! function_exists( 'acf_get_fields' ) ) {
            return;
        }

        $fields = acf_get_fields( $group_key );

        if ( ! $fields ) {
            return;
        }

        // Main block
        echo '<div class="cbo-options">';

        foreach ( $fields as $field ) {
            $field_value = get_field( $field['key'] );

            // Skip if field has no value and is not a color swatch (we want to show color options even if none is selected)
            if ( ($field_value === null || $field_value === '') && strpos($field['name'], 'farg') === false ) {
                continue;
            }
            
            // Field element
            $field_classes = 'cbo-options__field cbo-options__field--' . esc_attr($field['type']);
            echo '<div class="' . $field_classes . '">';

            // --- Special handling for Color Swatch Radio Buttons ---
            if ( $field['type'] === 'radio' && strpos( $field['name'], 'farg' ) !== false ) {
                echo '<label class="cbo-options__label">' . esc_html( $field['label'] ) . ( !empty($field['required']) ? ' <span class="required">*</span>' : '' ) . '</label>';
                
                // Nested block for color swatches
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

            } else {
                 // Default ACF field rendering
                echo '<label class="cbo-options__label" for="acf-' . esc_attr($field['key']) . '">' . esc_html($field['label']) . ( !empty($field['required']) ? ' <span class="required">*</span>' : '' ) . '</label>';
                acf_render_field( $field );
                 if ( ! empty( $field['instructions'] ) ) {
                    echo '<p class="cbo-options__instructions">' . wp_kses_post( $field['instructions'] ) . '</p>';
                }
            }
            
            echo '</div>'; // .cbo-options__field
        }

        echo '</div>'; // .cbo-options
    }
}

// Instantiate the class
new WC_CBO_ACF_Integration();
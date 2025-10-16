<?php
/**
 * WC_CBO_Dynamic_Styling Class
 *
 * Handles the generation of dynamic CSS for the frontend.
 *
 * @class       WC_CBO_Dynamic_Styling
 * @version     2.1.0
 * @author      Gemini
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class WC_CBO_Dynamic_Styling {

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'wp_head', [ $this, 'print_dynamic_styles' ] );
    }

    /**
     * Gets the saved styling options.
     *
     * @return array
     */
    private function get_styling_options() {
        $defaults = [
            'heading_color'       => '',
            'heading_typography'  => '',
            'option_color'        => '',
            'option_typography'   => '',
        ];
        $options = get_option( 'wc_cbo_styling_options', $defaults );
        return wp_parse_args( $options, $defaults );
    }

    /**
     * Generates and prints the dynamic CSS in the site header.
     */
    public function print_dynamic_styles() {
        $options = $this->get_styling_options();

        // Only print styles if at least one option is set.
        if ( empty( array_filter( $options ) ) ) {
            return;
        }

        $css = "";

        // --- HEADING STYLES --- //
        $heading_color_var = ! empty( $options['heading_color'] ) ? "var(--e-global-color-" . esc_attr($options['heading_color']) . ")" : 'inherit';
        if ( ! empty( $options['heading_typography'] ) ) {
            $heading_typo_id = esc_attr($options['heading_typography']);
            $heading_font_family = "var(--e-global-typography-{$heading_typo_id}-font-family)";
            $heading_font_weight = "var(--e-global-typography-{$heading_typo_id}-font-weight)";
            $heading_font_size = "var(--e-global-typography-{$heading_typo_id}-font-size)";
            $heading_line_height = "var(--e-global-typography-{$heading_typo_id}-line-height)";
            $heading_text_transform = "var(--e-global-typography-{$heading_typo_id}-text-transform)";
            $heading_font_style = "var(--e-global-typography-{$heading_typo_id}-font-style)";
            $heading_text_decoration = "var(--e-global-typography-{$heading_typo_id}-text-decoration)";
        } else {
            $heading_font_family = $heading_font_weight = $heading_font_size = $heading_line_height = $heading_text_transform = $heading_font_style = $heading_text_decoration = 'inherit';
        }

        $css .= "
            .wc-cbo-matrix-title,
            .wc-cbo-acf-fields-wrapper .acf-field .acf-label label {
                color: {$heading_color_var} !important;
                font-family: {$heading_font_family} !important;
                font-weight: {$heading_font_weight} !important;
                font-size: {$heading_font_size} !important;
                line-height: {$heading_line_height} !important;
                text-transform: {$heading_text_transform} !important;
                font-style: {$heading_font_style} !important;
                text-decoration: {$heading_text_decoration} !important;
            }
        ";

        // --- OPTION/INPUT STYLES --- //
        $option_color_var = ! empty( $options['option_color'] ) ? "var(--e-global-color-" . esc_attr($options['option_color']) . ")" : 'inherit';
        if ( ! empty( $options['option_typography'] ) ) {
            $option_typo_id = esc_attr($options['option_typography']);
            $option_font_family = "var(--e-global-typography-{$option_typo_id}-font-family)";
            $option_font_weight = "var(--e-global-typography-{$option_typo_id}-font-weight)";
            $option_font_size = "var(--e-global-typography-{$option_typo_id}-font-size)";
            $option_line_height = "var(--e-global-typography-{$option_typo_id}-line-height)";
            $option_text_transform = "var(--e-global-typography-{$option_typo_id}-text-transform)";
            $option_font_style = "var(--e-global-typography-{$option_typo_id}-font-style)";
            $option_text_decoration = "var(--e-global-typography-{$option_typo_id}-text-decoration)";
        } else {
            $option_font_family = $option_font_weight = $option_font_size = $option_line_height = $option_text_transform = $option_font_style = $option_text_decoration = 'inherit';
        }

        $css .= "
            .wc-cbo-acf-fields-wrapper .acf-input input,
            .wc-cbo-acf-fields-wrapper .acf-input textarea,
            .wc-cbo-acf-fields-wrapper .acf-input select,
            .wc-cbo-acf-fields-wrapper .acf-radio-list label,
            .wc-cbo-matrix-row .variation-details strong {
                color: {$option_color_var};
                font-family: {$option_font_family};
                font-weight: {$option_font_weight};
                font-size: {$option_font_size};
                line-height: {$option_line_height};
                text-transform: {$option_text_transform};
                font-style: {$option_font_style};
                text-decoration: {$option_text_decoration};
            }
        ";

        // --- PLACEHOLDER STYLES --- //
        $css .= "
            .wc-cbo-acf-fields-wrapper .acf-field input::placeholder,
            .wc-cbo-acf-fields-wrapper .acf-field textarea::placeholder,
            .wc-cbo-quantity-input::placeholder {
                color: {$option_color_var};
                opacity: 0.6;
            }
        ";

        if ( ! empty( $css ) ) {
            echo '<style type="text/css" id="wc-cbo-dynamic-styles">' . $css . '</style>';
        }
    }
}

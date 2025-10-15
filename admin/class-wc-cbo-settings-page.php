<?php
/**
 * WC_CBO_Settings_Page Class
 *
 * Handles the creation of the plugin's settings page in the WP admin area.
 *
 * @class       WC_CBO_Settings_Page
 * @version     2.1.0
 * @author      Gemini
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

class WC_CBO_Settings_Page {

    /**
     * Option group name.
     * @var string
     */
    private $option_group = 'wc_cbo_styling_options_group';

    /**
     * Option name in wp_options.
     * @var string
     */
    private $option_name = 'wc_cbo_styling_options';

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    /**
     * Adds the submenu page to the WordPress admin menu.
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'WC Bulk Order Styling', 'wc-custom-bulk-order' ),
            __( 'WC Bulk Order Styling', 'wc-custom-bulk-order' ),
            'manage_options',
            'wc-cbo-styling',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Registers the settings, sections, and fields.
     */
    public function register_settings() {
        register_setting( $this->option_group, $this->option_name, [ $this, 'sanitize_settings' ] );

        add_settings_section(
            'wc_cbo_styling_section',
            __( 'Elementor Global Style Mapping', 'wc-custom-bulk-order' ),
            [ $this, 'render_section_text' ],
            'wc-cbo-styling'
        );

        add_settings_field(
            'heading_color',
            __( 'Heading Color Style', 'wc-custom-bulk-order' ),
            [ $this, 'render_dropdown_field' ],
            'wc-cbo-styling',
            'wc_cbo_styling_section',
            [ 'name' => 'heading_color', 'type' => 'color' ]
        );

        add_settings_field(
            'heading_typography',
            __( 'Heading Typography Style', 'wc-custom-bulk-order' ),
            [ $this, 'render_dropdown_field' ],
            'wc-cbo-styling',
            'wc_cbo_styling_section',
            [ 'name' => 'heading_typography', 'type' => 'typography' ]
        );

        add_settings_field(
            'option_color',
            __( 'Option/Input Color Style', 'wc-custom-bulk-order' ),
            [ $this, 'render_dropdown_field' ],
            'wc-cbo-styling',
            'wc_cbo_styling_section',
            [ 'name' => 'option_color', 'type' => 'color' ]
        );

        add_settings_field(
            'option_typography',
            __( 'Option/Input Typography Style', 'wc-custom-bulk-order' ),
            [ $this, 'render_dropdown_field' ],
            'wc-cbo-styling',
            'wc_cbo_styling_section',
            [ 'name' => 'option_typography', 'type' => 'typography' ]
        );
    }

    /**
     * Renders the main settings page wrapper.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( $this->option_group );
                do_settings_sections( 'wc-cbo-styling' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Renders the text for the settings section.
     */
    public function render_section_text() {
        echo '<p>' . esc_html__( 'Select the Elementor global styles to apply to the bulk order form elements.', 'wc-custom-bulk-order' ) . '</p>';
    }

    /**
     * Renders a dropdown field for a setting.
     *
     * @param array $args Arguments passed from add_settings_field.
     */
    public function render_dropdown_field( $args ) {
        $options = get_option( $this->option_name );
        $name = $args['name'];
        $type = $args['type'];
        $value = isset( $options[$name] ) ? $options[$name] : '';

        $global_styles = $this->get_elementor_global_styles();
        $items = ($type === 'color') ? $global_styles['colors'] : $global_styles['fonts'];

        echo '<select id="' . esc_attr( $name ) . '" name="' . esc_attr( $this->option_name . '[' . $name . ']' ) . '">';
        echo '<option value="">' . esc_html__( '-- Select Style --', 'wc-custom-bulk-order' ) . '</option>';

        if ( ! empty( $items ) ) {
            foreach ( $items as $id => $title ) {
                echo '<option value="' . esc_attr( $id ) . '" ' . selected( $value, $id, false ) . '>' . esc_html( $title ) . '</option>';
            }
        }

        echo '</select>';
    }

    /**
     * Sanitizes the settings before saving.
     *
     * @param array $input The input from the settings form.
     * @return array The sanitized input.
     */
    public function sanitize_settings( $input ) {
        $sanitized_input = [];
        if ( is_array( $input ) ) {
            foreach ( $input as $key => $value ) {
                $sanitized_input[$key] = sanitize_text_field( $value );
            }
        }
        return $sanitized_input;
    }

    /**
     * Retrieves and formats Elementor's global styles.
     *
     * @return array
     */
    private function get_elementor_global_styles() {
        $global_styles = [
            'colors' => [],
            'fonts'  => [],
        ];

        $active_kit_id = get_option( 'elementor_active_kit' );
        if ( ! $active_kit_id ) {
            return $global_styles;
        }

        $kit_settings = get_post_meta( $active_kit_id, '_elementor_page_settings', true );
        if ( empty( $kit_settings ) || ! is_array( $kit_settings ) ) {
            return $global_styles;
        }

        // Colors
        if ( ! empty( $kit_settings['system_colors'] ) ) {
            foreach ( $kit_settings['system_colors'] as $color ) {
                if (isset($color['_id']) && isset($color['title'])) {
                    $global_styles['colors'][$color['_id']] = $color['title'];
                }
            }
        }
        if ( ! empty( $kit_settings['custom_colors'] ) ) {
            foreach ( $kit_settings['custom_colors'] as $color ) {
                if (isset($color['_id']) && isset($color['title'])) {
                    $global_styles['colors'][$color['_id']] = $color['title'];
                }
            }
        }

        // Typography
        if ( ! empty( $kit_settings['system_typography'] ) ) {
            foreach ( $kit_settings['system_typography'] as $font ) {
                if (isset($font['_id']) && isset($font['title'])) {
                    $global_styles['fonts'][$font['_id']] = $font['title'];
                }
            }
        }
        if ( ! empty( $kit_settings['custom_typography'] ) ) {
            foreach ( $kit_settings['custom_typography'] as $font ) {
                if (isset($font['_id']) && isset($font['title'])) {
                    $global_styles['fonts'][$font['_id']] = $font['title'];
                }
            }
        }

        return $global_styles;
    }
}

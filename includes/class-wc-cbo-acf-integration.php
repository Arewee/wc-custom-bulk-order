<?php

/**
 * WC_CBO_ACF_Integration Class
 *
 * @class       WC_CBO_ACF_Integration
 * @version     1.0.0
 * @author      Gemini & Richard Viitanen
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class WC_CBO_ACF_Integration {

    /**
     * Konstruktor.
     */
    public function __construct() {
        // Lägg till pris-inställning för fälttyper som har val (choices)
        add_action( 'acf/render_field_settings', array( $this, 'add_price_setting_to_choices' ) );

        // Modifiera fältets "choices" för att inkludera priset i labeln i frontend
        add_filter( 'acf/load_field', array( $this, 'modify_choices_label_with_price' ) );
    }

    /**
     * Lägger till ett anpassat "Pris"-inställningsfält för varje val i ACF.
     *
     * @param array $field Det aktuella fältet som renderas.
     */
    public function add_price_setting_to_choices( $field ) {
        // Vi vill bara lägga till detta på fält som har val (choices)
        if ( ! in_array( $field['type'], array( 'radio', 'select', 'checkbox' ) ) ) {
            return;
        }

        acf_render_field_setting( $field, array(
            'label'         => __( 'Prispåslag', 'wc-custom-bulk-order' ),
            'instructions'  => __( 'Ange prispåslag för val. Lämna tomt för inget påslag. T.ex. "Guld:10|Silver:5" för att sätta pris på valen Guld och Silver.', 'wc-custom-bulk-order' ),
            'type'          => 'text',
            'name'          => 'wc_cbo_price_options',
            'placeholder'   => 'val_key:pris|annat_val_key:pris',
        ) );
    }

    /**
     * Laddar fältet och modifierar dess labels om priser finns angivna.
     *
     * @param array $field Fält-arrayen.
     * @return array
     */
    public function modify_choices_label_with_price( $field ) {
        // Kör bara i frontend och om fältet har våra prisinställningar
        if ( is_admin() || empty( $field['wc_cbo_price_options'] ) || empty( $field['choices'] ) ) {
            return $field;
        }

        // Parse our price settings string (e.g., "gold:10|silver:5")
        $price_options = array();
        $options = explode( '|', $field['wc_cbo_price_options'] );
        foreach ( $options as $option ) {
            $pair = explode( ':', $option );
            if ( count( $pair ) === 2 ) {
                $price_options[ trim( $pair[0] ) ] = (float) trim( $pair[1] );
            }
        }

        if ( empty( $price_options ) ) {
            return $field;
        }

        // Loop through choices and append price to the label
        foreach ( $field['choices'] as $key => &$label ) {
            if ( isset( $price_options[ $key ] ) ) {
                $price = $price_options[ $key ];
                if ( $price > 0 ) {
                    $label .= sprintf( ' (+%s)', wc_price( $price ) );
                }
            }
        }

        return $field;
    }
}

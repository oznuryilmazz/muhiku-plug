<?php
/**
 * @package MuhikuPlug\Fields
 */

defined( 'ABSPATH' ) || exit;

class MHK_Field_Last_Name extends MHK_Form_Fields {

	public function __construct() {
		$this->name     = esc_html__( 'Soyisim', 'muhiku-plug' );
		$this->type     = 'last-name';
		$this->icon     = 'mhk-icon mhk-icon-last-name';
		$this->order    = 20;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'placeholder',
					'label_hide',
					'default_value',
					'css',
				),
			),
		);

		parent::__construct();
	}

	/**
	 * @param array $field 
	 */
	public function field_preview( $field ) {

	
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';


		$this->field_preview_option( 'label', $field );


		echo '<input type="text" placeholder="' . esc_attr( $placeholder ) . '" class="widefat" disabled>';


		$this->field_preview_option( 'description', $field );
	}

	/**
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$primary = $field['properties']['inputs']['primary'];

		printf(
			'<input type="text" %s %s>',
			mhk_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}
}

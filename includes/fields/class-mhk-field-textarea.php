<?php
/**
 * Textarea field.
 *
 * @package MuhikuPlug\Fields0
 */

defined( 'ABSPATH' ) || exit;

class MHK_Field_Textarea extends MHK_Form_Fields {

	public function __construct() {
		$this->name     = esc_html__( 'Paragraf Metin', 'muhiku-plug' );
		$this->type     = 'textarea';
		$this->icon     = 'mhk-icon mhk-icon-paragraph';
		$this->order    = 40;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message',
					'readonly',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'size',
					'placeholder',
					'label_hide',
					'limit_length',
					'default_value',
					'css',
				),
			),
		);

		parent::__construct();
	}

	public function init_hooks() {
		add_action( 'muhiku_forms_shortcode_scripts', array( $this, 'load_assets' ) );
	}

	/**
	 * @param array $atts Shortcode Attributes.
	 */
	public function load_assets( $atts ) {
		$form_id   = isset( $atts['id'] ) ? wp_unslash( $atts['id'] ) : ''; // WPCS: CSRF ok, input var ok, sanitization ok.
		$form_obj  = mhk()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

		// Leave only fields with limit.
		if ( ! empty( $form_data['form_fields'] ) ) {
			$form_fields = array_filter( $form_data['form_fields'], array( $this, 'field_is_limit' ) );

			if ( count( $form_fields ) ) {
				wp_enqueue_script( 'muhiku-plug-text-limit' );
			}
		}
	}

	/**
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

		$this->field_preview_option( 'label', $field );

		echo '<textarea placeholder="' . esc_attr( $placeholder ) . '" class="widefat" disabled></textarea>';

		$this->field_preview_option( 'description', $field );
	}

	/**
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$value   = '';
		$primary = $field['properties']['inputs']['primary'];

		if ( isset( $primary['attr']['value'] ) ) {
			$value = mhk_sanitize_textarea_field( $primary['attr']['value'] );
			unset( $primary['attr']['value'] );
		}

		if ( isset( $field['limit_enabled'] ) ) {
			$limit_count = isset( $field['limit_count'] ) ? absint( $field['limit_count'] ) : 0;
			$limit_mode  = isset( $field['limit_mode'] ) ? sanitize_key( $field['limit_mode'] ) : 'characters';

			$primary['data']['form-id']  = $form_data['id'];
			$primary['data']['field-id'] = $field['id'];

			if ( 'characters' === $limit_mode ) {
				$primary['class'][]            = 'muhiku-plug-limit-characters-enabled';
				$primary['attr']['maxlength']  = $limit_count;
				$primary['data']['text-limit'] = $limit_count;
			} else {
				$primary['class'][]            = 'muhiku-plug-limit-words-enabled';
				$primary['data']['text-limit'] = $limit_count;
			}
		}

		printf(
			'<textarea %s %s >%s</textarea>',
			mhk_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] ),
			esc_html( $value )
		);
	}
}

<?php
/**
 * Text field.
 *
 * @package MuhikuPlug\Fields
 */

defined( 'ABSPATH' ) || exit;

class MHK_Field_Text extends MHK_Form_Fields {

	public function __construct() {
		$this->name     = esc_html__( 'Başlık', 'muhiku-plug' );
		$this->type     = 'text';
		$this->icon     = 'mhk-icon mhk-icon-text';
		$this->order    = 30;
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
					'limit_length',
					'default_value',
					'css',
					'input_mask',
				),
			),
		);

		parent::__construct();
	}

	public function init_hooks() {
		add_action( 'muhiku_forms_shortcode_scripts', array( $this, 'load_assets' ) );
		add_filter( 'muhiku_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * @param array $atts Shortcode Attributes.
	 */
	public function load_assets( $atts ) {
		$form_id   = isset( $atts['id'] ) ? wp_unslash( $atts['id'] ) : ''; 
		$form_obj  = mhk()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			$form_fields = array_filter( $form_data['form_fields'], array( $this, 'field_is_limit' ) );

			if ( count( $form_fields ) ) {
				wp_enqueue_script( 'muhiku-plug-text-limit' );
			}
		}
	}

	/**
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		if ( ! empty( $field['input_mask'] ) ) {
			$properties['inputs']['primary']['class'][] = 'mhk-masked-input';

			$field['input_mask'] = mhk_string_translation( $form_data['id'], $field['id'], $field['input_mask'], '-input-mask' );

			if ( false !== strpos( $field['input_mask'], 'alias:' ) ) {
				$mask = str_replace( 'alias:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-alias'] = $mask;
			} elseif ( false !== strpos( $field['input_mask'], 'regex:' ) ) {
				$mask = str_replace( 'regex:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-regex'] = $mask;
			} elseif ( false !== strpos( $field['input_mask'], 'date:' ) ) {
				$mask = str_replace( 'date:', '', $field['input_mask'] );
				$properties['inputs']['primary']['data']['inputmask-alias']       = 'datetime';
				$properties['inputs']['primary']['data']['inputmask-inputformat'] = $mask;
			} else {
				$properties['inputs']['primary']['data']['inputmask-mask'] = $field['input_mask'];
			}
		}

		return $properties;
	}

	/**
	 * @param array $field Field data and settings.
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
			'<input type="text" %s %s>',
			mhk_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
			esc_attr( $primary['required'] )
		);
	}
}

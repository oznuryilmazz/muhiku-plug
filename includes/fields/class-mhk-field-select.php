<?php
/**
 * @package MuhikuPlug\Fields
 */

defined( 'ABSPATH' ) || exit;

class MHK_Field_Select extends MHK_Form_Fields {

	public function __construct() {
		$this->name     = esc_html__( 'Dropdown', 'muhiku-plug' );
		$this->type     = 'select';
		$this->icon     = 'mhk-icon mhk-icon-dropdown';
		$this->order    = 50;
		$this->group    = 'general';
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( 'Seçenek 1', 'muhiku-plug' ),
				'value'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( 'Seçenek 2', 'muhiku-plug' ),
				'value'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( 'Seçenek 3', 'muhiku-plug' ),
				'value'   => '',
				'default' => '',
			),
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'choices',
					'enhanced_select',
					'description',
					'required',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'size',
					'placeholder',
					'label_hide',
					'css',
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
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		$form_id  = absint( $form_data['id'] );
		$field_id = $field['id'];
		$choices  = $field['choices'];

		unset( $properties['inputs']['primary'] );

		$properties['input_container'] = array(
			'class' => array( 'input-text' ),
			'data'  => array(),
			'id'    => "mhk-{$form_id}-field_{$field_id}",
			'attr'  => array(
				'name' => "muhiku_forms[form_fields][{$field_id}]",
			),
		);

		foreach ( $choices as $key => $choice ) {
			$depth = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;

			$properties['inputs'][ $key ] = array(
				'container' => array(
					'attr'  => array(),
					'class' => array( "choice-{$key}", "depth-{$depth}" ),
					'data'  => array(),
					'id'    => '',
				),
				'label'     => array(
					'attr'  => array(
						'for' => "mhk-{$form_id}-field_{$field_id}_{$key}",
					),
					'class' => array( 'muhiku-plug-field-label-inline' ),
					'data'  => array(),
					'id'    => '',
					'text'  => mhk_string_translation( $form_id, $field_id, $choice['label'], '-choice-' . $key ),
				),
				'attr'      => array(
					'name'  => "muhiku_forms[form_fields][{$field_id}][]",
					'value' => isset( $field['show_values'] ) ? $choice['value'] : $choice['label'],
				),
				'class'     => array(),
				'data'      => array(),
				'id'        => "mhk-{$form_id}-field_{$field_id}_{$key}",
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);
		}

		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'mhk-field-required';
		}

		return $properties;
	}

	/**
	 * @param array $atts Shortcode attributes.
	 */
	public static function load_assets( $atts ) {
		$form_data = mhk()->form->get( $atts['id'], array( 'content_only' => true ) );

		if ( ! empty( $form_data['form_fields'] ) ) {
			$is_enhanced_select = wp_list_filter(
				$form_data['form_fields'],
				array(
					'type'            => 'select',
					'enhanced_select' => 1,
				)
			);

			if ( ! empty( $is_enhanced_select ) ) {
				wp_enqueue_style( 'mhk_select2' );
				wp_enqueue_script( 'selectWoo' );
			}
		}
	}

	/**
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$args = array();

		if (
			! empty( $field['enhanced_select'] )
			&& ! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices']
		) {
			$args['class'] = 'mhk-enhanced-select';
		}

		$this->field_preview_option( 'label', $field );

		$this->field_preview_option( 'choices', $field, $args );

		$this->field_preview_option( 'description', $field );
	}

	/**
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$container         = $field['properties']['input_container'];
		$choices           = $field['properties']['inputs'];
		$field             = apply_filters( 'muhiku_forms_select_field_display', $field, $field_atts, $form_data );
		$field_placeholder = ! empty( $field['placeholder'] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field['placeholder'], '-placeholder' ) : '';
		$plan              = mhk_get_license_plan();
		$has_default       = false;
		$is_multiple       = false;
		$select_all        = isset( $field['select_all'] ) ? $field['select_all'] : '0';

		if ( ! empty( $field['required'] ) ) {
			$container['attr']['required'] = 'required';
		}

		if ( false !== $plan && ! empty( $field['enhanced_select'] ) && '1' === $field['enhanced_select'] ) {
			$container['class'][] = 'mhk-enhanced-select';

			if ( empty( $field_placeholder ) ) {
				$first_choices     = reset( $choices );
				$field_placeholder = $first_choices['label']['text'];
			}

			$container['data']['placeholder'] = esc_attr( $field_placeholder );
		}

		if ( false !== $plan && ! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices'] ) {
			$is_multiple                   = true;
			$container['attr']['multiple'] = 'multiple';

			if ( ! empty( $container['attr']['name'] ) ) {
				$container['attr']['name'] .= '[]';
			}
		}

		foreach ( $choices as $choice ) {
			if ( ! empty( $choice['default'] ) ) {
				$has_default = true;
				break;
			}
		}

		if ( isset( $choices['primary'] ) ) {
			$container['attr']['conditional_id'] = $choices['primary']['attr']['conditional_id'];

			if ( isset( $choices['primary']['attr']['conditional_rules'] ) ) {
				$container['attr']['conditional_rules'] = $choices['primary']['attr']['conditional_rules'];
			}
		}
		printf(
			'<select %s >',
			mhk_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
		);

		if ( ! empty( $field_placeholder ) ) {
			printf( '<option value="" class="placeholder" disabled %s>%s</option>', selected( false, $has_default || $is_multiple, false ), esc_html( $field_placeholder ) );
		}

		foreach ( $choices as $choice ) {
			if ( empty( $choice['container'] ) ) {
				continue;
			}

			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $choice['attr']['value'] ),
				selected( true, ! empty( $choice['default'] ), false ),
				esc_html( $choice['label']['text'] )
			);
		}

		echo '</select>';
	}

	/**
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {
		$value_choices = ! empty( $entry_field['value_raw'] ) ? $entry_field['value_raw'] : array();

		$this->remove_field_choices_defaults( $field, $field['properties'] );

		if ( is_array( $value_choices ) ) {
			foreach ( $value_choices as $input => $single_value ) {
				$field['properties'] = $this->get_single_field_property_value( $single_value, sanitize_key( $input ), $field['properties'], $field );
			}
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * @param int    $field_id     Field ID.
	 * @param mixed  $field_submit Submitted field value.
	 * @param array  $form_data    Form data and settings.
	 * @param string $meta_key     Field meta key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field = $form_data['form_fields'][ $field_id ];
		$name  = make_clickable( $field['label'] );
		$value = array();

		if ( ! is_array( $field_submit ) ) {
			$field_submit = array( $field_submit );
		}

		$value_raw = mhk_sanitize_array_combine( $field_submit );

		$data = array(
			'name'      => $name,
			'value'     => '',
			'value_raw' => $value_raw,
			'id'        => $field_id,
			'type'      => $this->type,
			'meta_key'  => $meta_key,
		);

		if ( ! empty( $field['show_values'] ) && '1' === $field['show_values'] ) {
			foreach ( $field_submit as $item ) {
				foreach ( $field['choices'] as $choice ) {
					if ( $item === $choice['value'] ) {
						$value[] = $choice['label'];
						break;
					}
				}
			}

			$data['value'] = ! empty( $value ) ? mhk_sanitize_array_combine( $value ) : '';
		} else {
			$data['value'] = $value_raw;
		}

		mhk()->task->form_fields[ $field_id ] = $data;
	}
}

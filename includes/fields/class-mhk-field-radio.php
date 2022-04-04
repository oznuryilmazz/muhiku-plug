<?php
/**
 * Radio field.
 * @package MuhikuPlug\Fields
 */

defined( 'ABSPATH' ) || exit;


class MHK_Field_Radio extends MHK_Form_Fields {


	public function __construct() {
		$this->name     = esc_html__( 'Radio Buton', 'muhiku-plug' );
		$this->type     = 'radio';
		$this->icon     = 'mhk-icon mhk-icon-multiple-choices-radio';
		$this->order    = 60;
		$this->group    = 'general';
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( 'Birinci Seçenek', 'muhiku-plug' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( 'İkinci Seçenek', 'muhiku-plug' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( 'Üçüncü Seçenek', 'muhiku-plug' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
		);
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'choices',
					'choices_images',
					'description',
					'required',
					'required_field_message',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'randomize',
					'show_values',
					'input_columns',
					'label_hide',
					'css',
				),
			),
		);

		parent::__construct();
	}

	public function init_hooks() {
		add_filter( 'muhiku_forms_html_field_value', array( $this, 'html_field_value' ), 10, 4 );
		add_filter( 'muhiku_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
	}

	/**
	 * @param string $value     Field value.
	 * @param array  $field     Field settings.
	 * @param array  $form_data Form data and settings.
	 * @param string $context   Value display context.
	 *
	 * @return string
	 */
	public function html_field_value( $value, $field, $form_data = array(), $context = '' ) {
		if ( is_serialized( $field ) || in_array( $context, array( 'email-html', 'export-pdf' ), true ) ) {
			$field_value = maybe_unserialize( $field );
			$field_type  = isset( $field_value['type'] ) ? sanitize_text_field( $field_value['type'] ) : 'radio';

			if ( $field_type === $this->type ) {
				if (
					'entry-table' !== $context
					&& ! empty( $field_value['label'] )
					&& ! empty( $field_value['image'] )
					&& apply_filters( 'muhiku_forms_checkbox_field_html_value_images', true, $context )
				) {
					return sprintf(
						'<span style="max-width:200px;display:block;margin:0 0 5px 0;"><img src="%s" style="max-width:100%%;display:block;margin:0;"></span>%s',
						esc_url( $field_value['image'] ),
						esc_html( $field_value['label'] )
					);
				} elseif ( isset( $field_value['label'] ) ) {
					return esc_html( $field_value['label'] );
				}
			}
		}

		return $value;
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
			'class' => array( ! empty( $field['random'] ) ? 'muhiku-plug-randomize' : '' ),
			'data'  => array(),
			'attr'  => array(),
			'id'    => "mhk-{$form_id}-field_{$field_id}",
		);

		foreach ( $choices as $key => $choice ) {
			$depth                        = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;
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
					'name'  => "muhiku_forms[form_fields][{$field_id}]",
					'value' => isset( $field['show_values'] ) ? $choice['value'] : $choice['label'],
				),
				'class'     => array( 'input-text' ),
				'data'      => array(),
				'id'        => "mhk-{$form_id}-field_{$field_id}_{$key}",
				'image'     => isset( $choice['image'] ) ? $choice['image'] : '',
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);
		}

		if ( ! empty( $field['required'] ) ) {
			$properties['input_container']['class'][] = 'mhk-field-required';
		}

		if ( ! empty( $field['choices_images'] ) ) {
			$properties['input_container']['class'][] = 'muhiku-plug-image-choices';

			foreach ( $properties['inputs'] as $key => $inputs ) {
				$properties['inputs'][ $key ]['container']['class'][] = 'muhiku-plug-image-choices-item';
			}
		}

		foreach ( $properties['inputs'] as $key => $inputs ) {
			if ( ! empty( $inputs['default'] ) ) {
				$properties['inputs'][ $key ]['container']['class'][] = 'muhiku-plug-selected';
			}
		}

		return $properties;
	}

	/**
	 * @param array $field Field Data.
	 */
	public function show_values( $field ) {
		if ( ! empty( $field['show_values'] ) || apply_filters( 'muhiku_forms_fields_show_options_setting', false ) ) {
			$args = array(
				'slug'    => 'show_values',
				'content' => $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'show_values',
						'value'   => isset( $field['show_values'] ) ? $field['show_values'] : '0',
						'desc'    => __( 'Show Values', 'muhiku-plug' ),
						'tooltip' => __( 'Check this to manually set form field values.', 'muhiku-plug' ),
					),
					false
				),
			);
			$this->field_element( 'row', $field, $args );
		}
	}

	/**
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$this->field_preview_option( 'label', $field );

		$this->field_preview_option( 'choices', $field );

		$this->field_preview_option( 'description', $field );
	}

	/**
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$container = $field['properties']['input_container'];
		$choices   = $field['properties']['inputs'];

		printf( '<ul %s>', mhk_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] ) );

		foreach ( $choices as $choice ) {
			if ( empty( $choice['container'] ) ) {
				continue;
			}

			if ( isset( $choices['primary'] ) ) {
				$choice['attr']['conditional_id'] = $choices['primary']['attr']['conditional_id'];

				if ( isset( $choices['primary']['attr']['conditional_rules'] ) ) {
					$choice['attr']['conditional_rules'] = $choices['primary']['attr']['conditional_rules'];
				}
			}

			printf( '<li %s>', mhk_html_attributes( $choice['container']['id'], $choice['container']['class'], $choice['container']['data'], $choice['container']['attr'] ) );

			if ( ! empty( $field['choices_images'] ) ) {
				$choice['label']['attr']['tabindex'] = 0;

				printf( '<label %s>', mhk_html_attributes( $choice['label']['id'], $choice['label']['class'], $choice['label']['data'], $choice['label']['attr'] ) );

				if ( ! empty( $choice['image'] ) ) {
					printf(
						'<span class="muhiku-plug-image-choices-image"><img src="%s" alt="%s"%s></span>',
						esc_url( $choice['image'] ),
						esc_attr( $choice['label']['text'] ),
						! empty( $choice['label']['text'] ) ? ' title="' . esc_attr( $choice['label']['text'] ) . '"' : ''
					);
				}

				echo '<br>';

				$choice['attr']['tabindex'] = '-1';

				printf( '<input type="radio" %s %s %s >', mhk_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				echo '<label class="muhiku-plug-image-choices-label">' . wp_kses_post( $choice['label']['text'] ) . '</label>';
				echo '</label>';
			} else {
				printf( '<input type="radio" %s %s %s>', mhk_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				printf( '<label %s>%s</label>', mhk_html_attributes( $choice['label']['id'], $choice['label']['class'], $choice['label']['data'], $choice['label']['attr'] ), wp_kses_post( $choice['label']['text'] ) );
			}

			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {
		$value = isset( $entry_field['value_raw'] ) ? $entry_field['value_raw'] : '';

		$this->remove_field_choices_defaults( $field, $field['properties'] );

		if ( '' !== $value ) {
			$field['properties'] = $this->get_single_field_property_value( $value, 'primary', $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Field.
	 * @param array  $form_data All Form Data.
	 * @param string $meta_key Field Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field      = $form_data['form_fields'][ $field_id ];
		$name       = make_clickable( $field['label'] );
		$value_raw  = sanitize_text_field( $field_submit );
		$choice_key = '';

		$data = array(
			'id'        => $field_id,
			'type'      => $this->type,
			'value'     => array(
				'name' => $name,
				'type' => $this->type,
			),
			'meta_key'  => $meta_key,
			'value_raw' => $value_raw,
		);

		if ( ! empty( $field['show_values'] ) && '1' === $field['show_values'] ) {
			foreach ( $field['choices'] as $key => $choice ) {
				if ( $choice['value'] === $field_submit ) {
					$data['value']['label'] = sanitize_text_field( $choice['label'] );
					$choice_key             = $key;
					break;
				}
			}
		} else {
			$data['value']['label'] = $value_raw;

			foreach ( $field['choices'] as $key => $choice ) {
				if ( $choice['label'] === $field_submit ) {
					$choice_key = $key;
					break;
				}
			}
		}

		if ( ! empty( $choice_key ) && ! empty( $field['choices_images'] ) ) {
			$data['value']['image'] = ! empty( $field['choices'][ $choice_key ]['image'] ) ? esc_url_raw( $field['choices'][ $choice_key ]['image'] ) : '';
		}

		mhk()->task->form_fields[ $field_id ] = $data;
	}
}

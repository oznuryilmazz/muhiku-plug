<?php
/**
 * @package MuhikuPlug\Fields
 */

defined( 'ABSPATH' ) || exit;

class MHK_Field_Checkbox extends MHK_Form_Fields {

	public function __construct() {
		$this->name     = esc_html__( 'Onay Kutuları', 'muhiku-plug' );
		$this->type     = 'checkbox';
		$this->icon     = 'mhk-icon mhk-icon-checkbox';
		$this->order    = 70;
		$this->group    = 'general';
		$this->defaults = array(
			1 => array(
				'label'   => esc_html__( '1. Seçenek', 'muhiku-plug' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
			2 => array(
				'label'   => esc_html__( '2. Seçenek', 'muhiku-plug' ),
				'value'   => '',
				'image'   => '',
				'default' => '',
			),
			3 => array(
				'label'   => esc_html__( '3. Seçenek', 'muhiku-plug' ),
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
					'choice_limit',
					'label_hide',
					'css',
					'select_all',
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
	 * @param string $value     
	 * @param array  $field     
	 * @param array  $form_data 
	 * @param string $context   
	 *
	 * @return string
	 */
	public function html_field_value( $value, $field, $form_data = array(), $context = '' ) {
		if ( is_serialized( $field ) || in_array( $context, array( 'email-html', 'export-pdf' ), true ) ) {
			$field_value = maybe_unserialize( $field );
			$field_type  = isset( $field_value['type'] ) ? sanitize_text_field( $field_value['type'] ) : 'checkbox';

			if ( $field_type === $this->type ) {
				if (
					'entry-table' !== $context
					&& ! empty( $field_value['label'] )
					&& ! empty( $field_value['images'] )
					&& apply_filters( 'muhiku_forms_checkbox_field_html_value_images', true, $context )
				) {
					$items = array();

					if ( ! empty( $field_value['label'] ) ) {
						foreach ( $field_value['label'] as $key => $value ) {
							if ( ! empty( $field_value['images'][ $key ] ) ) {
								$items[] = sprintf(
									'<span style="max-width:200px;display:block;margin:0 0 5px 0;"><img src="%s" style="max-width:100%%;display:block;margin:0;"></span>%s',
									esc_url( $field_value['images'][ $key ] ),
									esc_html( $value )
								);
							} else {
								$items[] = esc_html( $value );
							}
						}
					}

					return implode( 'export-csv' !== $context ? '<br><br>' : '|', $items );
				}
			}
		}

		return $value;
	}

	/**
	 * @param array $properties 
	 * @param array $field      
	 * @param array $form_data 
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

		$field['choice_limit'] = empty( $field['choice_limit'] ) ? 0 : (int) $field['choice_limit'];
		if ( $field['choice_limit'] > 0 ) {
			$properties['input_container']['data']['choice-limit'] = $field['choice_limit'];
		}

		foreach ( $choices as $key => $choice ) {
			$depth = isset( $choice['depth'] ) ? absint( $choice['depth'] ) : 1;

			$value = isset( $field['show_values'] ) ? $choice['value'] : $choice['label'];
			if ( '' === $value ) {
				if ( 1 === count( $choices ) ) {
					$value = esc_html__( 'Checked', 'muhiku-plug' );
				} else {
					$value = sprintf( esc_html__( 'Choice %s', 'muhiku-plug' ), $key );
				}
			}

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
					'value' => $value,
				),
				'class'     => array( 'input-text' ),
				'data'      => array(),
				'id'        => "mhk-{$form_id}-field_{$field_id}_{$key}",
				'image'     => isset( $choice['image'] ) ? $choice['image'] : '',
				'required'  => ! empty( $field['required'] ) ? 'required' : '',
				'default'   => isset( $choice['default'] ),
			);

			if ( $field['choice_limit'] > 0 ) {
				$properties['inputs'][ $key ]['data']['rule-check-limit'] = 'true';
			}
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
	 *
	 * @param array $field 
	 */
	public function field_preview( $field ) {
		$this->field_preview_option( 'label', $field );

		$this->field_preview_option( 'choices', $field );

		$this->field_preview_option( 'description', $field );
	}

	/**
	 * @param array $field 
	 * @param array $field_atts 
	 * @param array $form_data
	 */
	public function field_display( $field, $field_atts, $form_data ) {
		$container  = $field['properties']['input_container'];
		$choices    = $field['properties']['inputs'];
		$select_all = isset( $field['select_all'] ) ? $field['select_all'] : '0';

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
				printf( '<input type="checkbox" %s %s %s>', mhk_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				echo '<label class="muhiku-plug-image-choices-label">' . wp_kses_post( $choice['label']['text'] ) . '</label>';
				echo '</label>';
			} else {
				printf( '<input type="checkbox" %s %s %s>', mhk_html_attributes( $choice['id'], $choice['class'], $choice['data'], $choice['attr'] ), esc_attr( $choice['required'] ), checked( '1', $choice['default'], false ) );
				printf( '<label %s>%s</label>', mhk_html_attributes( $choice['label']['id'], $choice['label']['class'], $choice['label']['data'], $choice['label']['attr'] ), wp_kses_post( $choice['label']['text'] ) );
			}

			echo '</li>';
		}

		echo '</ul>';
	}

	/**
	 * @param array $entry_field 
	 * @param array $field       
	 * @param array $form_data   
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {
		$value_choices = ! empty( $entry_field['value_raw'] ) ? $entry_field['value_raw'] : array();

		$this->remove_field_choices_defaults( $field, $field['properties'] );

		foreach ( $value_choices as $input => $single_value ) {
			$field['properties'] = $this->get_single_field_property_value( $single_value, sanitize_key( $input ), $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * @param int   $field_id     
	 * @param array $field_submit 
	 * @param array $form_data    
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$field_submit       = (array) $field_submit;
		$form_id            = $form_data['id'];
		$fields             = $form_data['form_fields'];
		$choice_limit       = empty( $fields[ $field_id ]['choice_limit'] ) ? 0 : (int) $fields[ $field_id ]['choice_limit'];
		$conditional_status = isset( $form_data['form_fields'][ $field_id ]['conditional_logic_status'] ) ? $form_data['form_fields'][ $field_id ]['conditional_logic_status'] : 0;

		if ( $choice_limit > 0 && $choice_limit < count( $field_submit ) ) {
			$error = get_option( 'muhiku_forms_check_limit_validation', esc_html__( 'You have exceeded number of allowed selections: {#}.', 'muhiku-plug' ) );
			$error = str_replace( '{#}', $choice_limit, $error );
		}

		if ( ! empty( $fields[ $field_id ]['required'] ) && '1' !== $conditional_status && ( empty( $field_submit ) || ( 1 === count( $field_submit ) && empty( $field_submit[0] ) ) ) ) {
			$error = mhk_get_required_label();
		}

		if ( ! empty( $error ) ) {
			mhk()->task->errors[ $form_id ][ $field_id ] = $error;
		}
	}

	/**
	 * @param string $field_id Field Id.
	 * @param array  $field_submit Submitted Field.
	 * @param array  $form_data All Form Data.
	 * @param string $meta_key Field Meta Key.
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		$field_submit = (array) $field_submit;
		$field        = $form_data['form_fields'][ $field_id ];
		$name         = make_clickable( $field['label'] );
		$value_raw    = mhk_sanitize_array_combine( $field_submit );
		$choice_keys  = array();

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
			foreach ( $field_submit as $item ) {
				foreach ( $field['choices'] as $key => $choice ) {
					if ( $item === $choice['value'] || ( empty( $choice['value']['label'] ) && (int) str_replace( 'Choice ', '', $item ) === $key ) ) {
						$value[]       = $choice['label'];
						$choice_keys[] = $key;
						break;
					}
				}
			}

			$data['value']['label'] = ! empty( $value ) ? mhk_sanitize_array_combine( $value ) : '';
		} else {
			$data['value']['label'] = $value_raw;

			foreach ( $field_submit as $item ) {
				foreach ( $field['choices'] as $key => $choice ) {
					if ( $item === $choice['label'] ) {
						$choice_keys[] = $key;
						break;
					}
				}
			}
		}

		if ( ! empty( $choice_keys ) && ! empty( $field['choices_images'] ) ) {
			$data['value']['images'] = array();

			foreach ( $choice_keys as $key ) {
				$data['value']['images'][] = ! empty( $field['choices'][ $key ]['image'] ) ? esc_url_raw( $field['choices'][ $key ]['image'] ) : '';
			}
		}

		mhk()->task->form_fields[ $field_id ] = $data;
	}
}

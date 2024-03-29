<?php
/**
 * @package MuhikuFroms/Abstracts
 */

defined( 'ABSPATH' ) || exit;

abstract class MHK_Form_Fields {

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $type;

	/**
	 * @var mixed
	 */
	public $icon = '';

	/**
	 * @var string
	 */
	public $class = '';

	/**
	 * @var int|mixed
	 */
	public $form_id;

	/**
	 * @var string
	 */
	public $group = 'general';

	/**
	 * @var boolean
	 */
	public $is_pro = false;

	/**
	 * @var mixed
	 */
	public $defaults;

	/**
	 * @var array
	 */
	public $form_data;

	/**
	 * @var array
	 */
	protected $settings = array();

	public function __construct() {
		$this->class   = $this->is_pro ? 'upgrade-modal' : $this->class;
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false; 

		$this->init_hooks();
		add_action( 'muhiku_forms_builder_fields_options_' . $this->type, array( $this, 'field_options' ) );
		add_action( 'muhiku_forms_builder_fields_preview_' . $this->type, array( $this, 'field_preview' ) );
		add_action( 'wp_ajax_muhiku_forms_new_field_' . $this->type, array( $this, 'field_new' ) );
		add_action( 'muhiku_forms_display_field_' . $this->type, array( $this, 'field_display' ), 10, 3 );
		add_action( 'muhiku_forms_display_edit_form_field_' . $this->type, array( $this, 'edit_form_field_display' ), 10, 3 );
		add_action( 'muhiku_forms_process_validate_' . $this->type, array( $this, 'validate' ), 10, 3 );
		add_action( 'muhiku_forms_process_format_' . $this->type, array( $this, 'format' ), 10, 4 );
		add_filter( 'muhiku_forms_field_properties', array( $this, 'field_prefill_value_property' ), 10, 3 );
		add_filter( 'muhiku_forms_field_exporter_' . $this->type, array( $this, 'field_exporter' ) );
	}

	public function init_hooks() {}

	/**
	 * @param array $properties 
	 * @param array $field      
	 * @param array $form_data  
	 *
	 * @return array Modified field properties.
	 */
	public function field_prefill_value_property( $properties, $field, $form_data ) {
		if ( $this->type !== $field['type'] ) {
			return $properties;
		}

		$this->form_data = $form_data;

		return $properties;
	}

	/**
	 * @return array of options
	 */
	public function get_field_settings() {
		return apply_filters( 'muhiku_forms_get_field_settings_' . $this->type, $this->settings );
	}

	/**
	 * @param array $field Field data.
	 */
	public function field_options( $field ) {
		$settings = $this->get_field_settings();

		foreach ( $settings as $option_key => $option ) {
			$this->field_option(
				$option_key,
				$field,
				array(
					'markup' => 'open',
				)
			);

			if ( ! empty( $option['field_options'] ) ) {
				foreach ( $option['field_options'] as $option_name ) {
					$this->field_option( $option_name, $field );
				}
			}

			$this->field_option(
				$option_key,
				$field,
				array(
					'markup' => 'close',
				)
			);
		}
	}

	/**
	 * @param array $field 
	 */
	public function field_preview( $field ) {}

	/**
	 * @param string  $option 
	 * @param array   $field  
	 * @param array   $args   
	 * @param boolean $echo  
	 *
	 * @return mixed echo or return string
	 */
	public function field_element( $option, $field, $args = array(), $echo = true ) {
		$id     = (string) $field['id'];
		$class  = ! empty( $args['class'] ) && is_string( $args['class'] ) ? esc_attr( $args['class'] ) : '';
		$slug   = ! empty( $args['slug'] ) ? sanitize_title( $args['slug'] ) : '';
		$data   = '';
		$output = '';

		if ( ! empty( $args['data'] ) ) {
			foreach ( $args['data'] as $key => $val ) {
				if ( is_array( $val ) ) {
					$val = wp_json_encode( $val );
				}
				$data .= ' data-' . $key . '=\'' . $val . '\'';
			}
		}

		if ( ! empty( $args['min'] ) ) {
			$args['attrs']['min'] = esc_attr( $args['min'] );
			unset( $args['min'] );
		}
		if ( ! empty( $args['max'] ) ) {
			$args['attrs']['max'] = esc_attr( $args['max'] );
			unset( $args['min'] );
		}
		if ( ! empty( $args['required'] ) && $args['required'] ) {
			$args['attrs']['required'] = 'required';
			unset( $args['required'] );
		}

		if ( ! empty( $args['attrs'] ) ) {
			foreach ( $args['attrs'] as $arg_key => $val ) {
				if ( is_array( $val ) ) {
					$val = wp_json_encode( $val );
				}
				$data .= $arg_key . '=\'' . $val . '\'';
			}
		}

		switch ( $option ) {
			case 'row':
				$output = sprintf( '<div class="muhiku-plug-field-option-row muhiku-plug-field-option-row-%s %s" id="muhiku-plug-field-option-row-%s-%s" data-field-id="%s" %s>%s</div>', $slug, $class, $id, $slug, $id, $data, $args['content'] );
				break;

			case 'icon':
				$element_tooltip = isset( $args['tooltip'] ) ? $args['tooltip'] : 'Edit Label';
				$icon            = isset( $args['icon'] ) ? $args['icon'] : 'dashicons-edit';
				$output         .= sprintf( ' <i class="dashicons %s muhiku-plug-icon %s" title="%s" %s></i>', esc_attr( $icon ), $class, esc_attr( $element_tooltip ), $data );
				break;

			case 'label':
				$output = sprintf( '<label for="muhiku-plug-field-option-%s-%s" class="%s" %s>%s', $id, $slug, $class, $data, esc_html( $args['value'] ) );
				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help muhiku-plug-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
				}
				if ( isset( $args['after_tooltip'] ) && ! empty( $args['after_tooltip'] ) ) {
					$output .= $args['after_tooltip'];
				}
				$output .= '</label>';
				break;

			case 'text':
				$type        = ! empty( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';
				$placeholder = ! empty( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '';
				$before      = ! empty( $args['before'] ) ? '<span class="before-input">' . esc_html( $args['before'] ) . '</span>' : '';
				if ( ! empty( $before ) ) {
					$class .= ' has-before';
				}

				$output = sprintf( '%s<input type="%s" class="widefat %s" id="muhiku-plug-field-option-%s-%s" name="form_fields[%s][%s]" value="%s" placeholder="%s" %s>', $before, $type, $class, $id, $slug, $id, $slug, esc_attr( $args['value'] ), $placeholder, $data );
				break;

			case 'textarea':
				$rows   = ! empty( $args['rows'] ) ? (int) $args['rows'] : '3';
				$output = sprintf( '<textarea class="widefat %s" id="muhiku-plug-field-option-%s-%s" name="form_fields[%s][%s]" rows="%s" %s>%s</textarea>', $class, $id, $slug, $id, $slug, $rows, $data, $args['value'] );
				break;

			case 'checkbox':
				$checked = checked( '1', $args['value'], false );
				$output  = sprintf( '<input type="checkbox" class="widefat %s" id="muhiku-plug-field-option-%s-%s" name="form_fields[%s][%s]" value="1" %s %s>', $class, $id, $slug, $id, $slug, $checked, $data );
				$output .= sprintf( '<label for="muhiku-plug-field-option-%s-%s" class="inline">%s', $id, $slug, $args['desc'] );
				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help muhiku-plug-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
				}
				$output .= '</label>';
				break;

			case 'toggle':
				$checked = checked( '1', $args['value'], false );
				$icon    = $args['value'] ? 'fa-toggle-on' : 'fa-toggle-off';
				$cls     = $args['value'] ? 'muhiku-plug-on' : 'muhiku-plug-off';
				$status  = $args['value'] ? __( 'On', 'muhiku-plug' ) : __( 'Off', 'muhiku-plug' );
				$output  = sprintf( '<span class="muhiku-plug-toggle-icon %s"><i class="fa %s" aria-hidden="true"></i> <span class="muhiku-plug-toggle-icon-label">%s</span>', $cls, $icon, $status );
				$output .= sprintf( '<input type="checkbox" class="widefat %s" id="muhiku-plug-field-option-%s-%s" name="form_fields[%s][%s]" value="1" %s %s></span>', $class, $id, $slug, $id, $slug, $checked, $data );
				break;

			case 'select':
				$options     = $args['options'];
				$value       = isset( $args['value'] ) ? $args['value'] : '';
				$is_multiple = isset( $args['multiple'] ) && true === $args['multiple'];

				if ( true === $is_multiple ) {
					$output = sprintf( '<select class="widefat %s" id="muhiku-plug-field-option-%s-%s" name="form_fields[%s][%s]" %s multiple>', $class, $id, $slug, $id, $slug, $data );

				} else {
					$output = sprintf( '<select class="widefat %s" id="muhiku-plug-field-option-%s-%s" name="form_fields[%s][%s]" %s >', $class, $id, $slug, $id, $slug, $data );
				}

				foreach ( $options as $key => $option_value ) {

					if ( true === $is_multiple && is_array( $value ) ) {
						$selected_value = in_array( $key, $value, true ) ? 'selected="selected"' : '';
					} else {
						$selected_value = ( $value === $key ) ? 'selected="selected"' : '';
					}
					$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), $selected_value, $option_value );
				}
				$output .= '</select>';
				break;

			case 'radio':
				$options = $args['options'];
				$default = isset( $args['default'] ) ? $args['default'] : '';
				$output  = '<label>' . $args['desc'];

				if ( isset( $args['tooltip'] ) && ! empty( $args['tooltip'] ) ) {
					$output .= ' ' . sprintf( '<i class="dashicons dashicons-editor-help muhiku-plug-help-tooltip" title="%s"></i></label>', esc_attr( $args['tooltip'] ) );
				} else {
					$output .= '</label>';
				}
				$output .= '<ul>';

				foreach ( $options as $key => $option ) {
					$output .= '<li>';
					$output .= sprintf( '<label><input type="radio" class="widefat %s" id="muhiku-plug-field-option-%s-%s-%s" value="%s" name="form_fields[%s][%s]" %s %s>%s</label>', $class, $id, $slug, $key, $key, $id, $slug, $data, checked( $key, $default, false ), $option );
					$output .= '</li>';
				}
				$output .= '</ul>';
				break;
		}

		if ( $echo ) {
			echo wp_kses( $output, mhk_get_allowed_html_tags( 'builder' ) );
		} else {
			return $output;
		}
	}

	/**
	 * @param string  $option 
	 * @param array   $field  
	 * @param array   $args   
	 * @param boolean $echo   
	 *
	 * @return mixed echo or return string
	 */
	public function field_option( $option, $field, $args = array(), $echo = true ) {
		$output = '';
		$markup = ! empty( $args['markup'] ) ? $args['markup'] : 'open';
		$class  = ! empty( $args['class'] ) ? esc_html( $args['class'] ) : '';

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "muhiku_forms_field_options_before_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "muhiku_forms_field_options_bottom_{$option}", $field, $this );
		}

		switch ( $option ) {

			case 'basic-options':
				if ( 'open' === $markup ) {
					if ( $echo ) {
						echo sprintf( '<div class="muhiku-plug-field-option-group muhiku-plug-field-option-group-basic open" id="muhiku-plug-field-option-basic-%s">', esc_attr( $field['id'] ) );
						echo sprintf( '<a href="#" class="muhiku-plug-field-option-group-toggle">%s<span> (ID #%s)</span> <i class="handlediv"></i></a>', esc_html( $this->name ), esc_html( $field['id'] ) );
						echo sprintf( '<div class="muhiku-plug-field-option-group-inner %s">', esc_attr( $class ) );
					} else {
						$output  = sprintf( '<div class="muhiku-plug-field-option-group muhiku-plug-field-option-group-basic open" id="muhiku-plug-field-option-basic-%s">', $field['id'] );
						$output .= sprintf( '<a href="#" class="muhiku-plug-field-option-group-toggle">%s<span> (ID #%s)</span> <i class="handlediv"></i></a>', $this->name, $field['id'] );
						$output .= sprintf( '<div class="muhiku-plug-field-option-group-inner %s">', $class );
					}
				} else {
					if ( $echo ) {
						echo '</div></div>';
					} else {
						$output = '</div></div>';
					}
				}
				break;

			case 'label':
				$value   = ! empty( $field['label'] ) ? esc_attr( $field['label'] ) : '';
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'label',
						'value'   => esc_html__( 'Alan İsmi', 'muhiku-plug' ),
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'label',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'label',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'meta':
				$value   = ! empty( $field['meta-key'] ) ? esc_attr( $field['meta-key'] ) : mhk_get_meta_key_field_option( $field );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'meta-key',
						'value'   => esc_html__( 'Kısa İsim', 'muhiku-plug' ),
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'meta-key',
						'class' => 'mhk-input-meta-key',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'meta-key',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'description':
				$value   = ! empty( $field['description'] ) ? esc_attr( $field['description'] ) : '';
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'description',
						'value'   => esc_html__( 'Açıklama Metni', 'muhiku-plug' ),
					),
					false
				);
				$output .= $this->field_element(
					'textarea',
					$field,
					array(
						'slug'  => 'description',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'description',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'required':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = isset( $field['required'] ) ? $field['required'] : $default;
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'required',
						'value'   => $value,
						'desc'    => esc_html__( 'Gerekli', 'muhiku-plug' ),
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'required',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'required_field_message':
				$has_sub_fields = false;
				$sub_fields     = array();

				$required_validation = get_option( 'muhiku_forms_required_validation' );
				if ( in_array( $field['type'], array( 'number', 'email', 'url', 'phone' ), true ) ) {
					$required_validation = get_option( 'muhiku_forms_' . $field['type'] . '_validation' );
				}

				if ( 'likert' === $field['type'] ) {
					$has_sub_fields = true;
					$likert_rows    = isset( $field['likert_rows'] ) ? $field['likert_rows'] : array();
					foreach ( $likert_rows as $row_number => $row_label ) {
						$row_slug                = 'required-field-message-' . $row_number;
						$sub_fields[ $row_slug ] = array(
							'label' => array(
								'value'   => $row_label,
								'tooltip' => esc_html__( 'Bu Alan Gereklidir.', 'muhiku-plug' ),
							),
							'text'  => array(
								'value' => isset( $field[ $row_slug ] ) ? esc_attr( $field[ $row_slug ] ) : esc_attr( $required_validation ),
							),
						);
					}
				} 
				break;

			case 'field_visiblity':
				$default        = ! empty( $args['default'] ) ? $args['default'] : '0';
				$readonly_value = isset( $field['readonly_field_visibility'] ) ? $field['readonly_field_visibility'] : $default;
				$hidden_value   = isset( $field['hidden_field_visibility'] ) ? $field['hidden_field_visibility'] : $default;
				$tooltip        = esc_html__( 'Check this option to mark the field readonly and hidden.', 'muhiku-plug' );
				$label          = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'field_visibility',
						'value'   => esc_html__( 'Field Visibility', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$readonly       = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'  => 'readonly_field_visibility',
						'value' => $readonly_value,
						'class' => 'field_visibility_readonly',
						'desc'  => esc_html__( 'Readonly ', 'muhiku-plug' ),
					),
					false
				);
				$hidden         = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'  => 'hidden_field_visibility',
						'value' => $hidden_value,
						'class' => 'field_visibility_hidden',
						'desc'  => esc_html__( 'Hidden', 'muhiku-plug' ),
					),
					false
				);
				$output         = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'field_visiblity',
						'content' => $label . '' . $readonly . ' ' . $hidden,
					),
					$echo
				);
				break;

			case 'no_duplicates':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = ! empty( $field['no_duplicates'] ) ? esc_attr( $field['no_duplicates'] ) : '';
				$tooltip = esc_html__( 'Select this option to limit user input to unique values only. This will require that a value entered in a field does not currently exist in the entry database for that field..', 'muhiku-plug' );
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'no_duplicates',
						'value'   => $value,
						'desc'    => esc_html__( 'No Duplicates', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'no_duplicates',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'autocomplete_address':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = ! empty( $field['autocomplete_address'] ) ? esc_attr( $field['autocomplete_address'] ) : '';
				$tooltip = esc_html__( 'Check this option to autofill address field.', 'muhiku-plug' );
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'autocomplete_address',
						'value'   => $value,
						'desc'    => esc_html__( 'Enable Autocomplete Address Field', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'autocomplete_address',
						'content' => $output,
					),
					$echo
				);
				break;
			case 'address_style':
				$default = ! empty( $args['default'] ) ? $args['default'] : 'none';
				$tooltip = esc_html__( 'Select the style', 'muhiku-plug' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'address_style',
						'value'   => esc_html__( 'Style', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'select',
					$field,
					array(
						'slug'    => 'address_style',
						'value'   => esc_html__( 'style', 'muhiku-plug' ),
						'tooltip' => $tooltip,
						'options' => array(
							'address'          => esc_html__( 'Address', 'muhiku-plug' ),
							'map'              => esc_html__( 'Map', 'muhiku-plug' ),
							'address_with_map' => esc_html( 'Address With Map' ),
						),
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'address_style',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'code':
				$value   = ! empty( $field['code'] ) ? esc_attr( $field['code'] ) : '';
				$tooltip = esc_html__( 'Enter code for the form field.', 'muhiku-plug' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'code',
						'value'   => esc_html__( 'Code', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'textarea',
					$field,
					array(
						'slug'  => 'code',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'code',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'choices':
				$class      = array();
				$label      = ! empty( $args['label'] ) ? esc_html( $args['label'] ) : esc_html__( 'Choices', 'muhiku-plug' );
				$choices    = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
				$input_type = in_array( $field['type'], array( 'radio', 'select', 'payment-multiple' ), true ) ? 'radio' : 'checkbox';

				if ( ! empty( $field['show_values'] ) ) {
					$class[] = 'show-values';
				}

				if ( ! empty( $field['choices_images'] ) ) {
					$class[] = 'show-images';
				}

				if ( ! empty( $field['multiple_choices'] ) ) {
					$input_type = 'checkbox';
				}
				$field_content = '';


				if ( 'select' === $field['type'] ) {
					$selection_btn   = array();
					$selection_types = array(
						'single'   => array(
							'type'  => 'radio',
							'label' => esc_html__( 'Tekli Seçim', 'muhiku-plug' ),
						),
					);

					$active_type = ! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices'] ? 'multiple' : 'single';
					foreach ( $selection_types as $key => $selection_type ) {
						$selection_btn[ $key ] = '<span data-selection="' . esc_attr( $key ) . '" data-type="' . esc_attr( $selection_type['type'] ) . '" class="flex muhiku-plug-btn ' . ( $active_type === $key ? 'is-active' : '' ) . '" data-feature="' . esc_html__( 'Multiple selection', 'muhiku-plug' ) . '">' . esc_html( $selection_type['label'] ) . '</span>';
					}

					$field_content .= sprintf(
						'<div class="flex muhiku-plug-btn-group muhiku-plug-btn-group--inline"><input type="hidden" id="muhiku-plug-field-option-%1$s-multiple_choices" name="form_fields[%1$s][multiple_choices]" value="%2$s" />%3$s</div>',
						esc_attr( $field['id'] ),
						! empty( $field['multiple_choices'] ) && '1' === $field['multiple_choices'] ? 1 : 0,
						implode( '', $selection_btn )
					);
				}

				$field_content .= sprintf(
					'<ul data-next-id="%s" class="mhk-choices-list %s" data-field-id="%s" data-field-type="%s">',
					max( array_keys( $choices ) ) + 1,
					mhk_sanitize_classes( $class, true ),
					$field['id'],
					$this->type
				);
				foreach ( $choices as $key => $choice ) {
					$default = ! empty( $choice['default'] ) ? $choice['default'] : '';
					$name    = sprintf( 'form_fields[%s][choices][%s]', $field['id'], $key );
					$image   = ! empty( $choice['image'] ) ? $choice['image'] : '';

					if ( ! empty( $field['amount'][ $key ]['value'] ) ) {
						$choice['value'] = mhk_format_amount( mhk_sanitize_amount( $field['amount'][ $key ]['value'] ) );
					}

					$field_content .= sprintf( '<li data-key="%1$d">', absint( $key ) );
					$field_content .= '<span class="sort"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" role="img" aria-hidden="true" focusable="false"><path d="M13,8c0.6,0,1-0.4,1-1s-0.4-1-1-1s-1,0.4-1,1S12.4,8,13,8z M5,6C4.4,6,4,6.4,4,7s0.4,1,1,1s1-0.4,1-1S5.6,6,5,6z M5,10 c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S5.6,10,5,10z M13,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S13.6,10,13,10z M9,6 C8.4,6,8,6.4,8,7s0.4,1,1,1s1-0.4,1-1S9.6,6,9,6z M9,10c-0.6,0-1,0.4-1,1s0.4,1,1,1s1-0.4,1-1S9.6,10,9,10z"></path></svg></span>';
					$field_content .= sprintf( '<input type="%1$s" name="%2$s[default]" class="default" value="1" %3$s>', $input_type, $name, checked( '1', $default, false ) );
					$field_content .= '<div class="mhk-choice-list-input">';
					$field_content .= sprintf( '<input type="text" name="%1$s[label]" value="%2$s" class="label" data-key="%3$s">', $name, esc_attr( $choice['label'] ), absint( $key ) );
					if ( in_array( $field['type'], array( 'payment-multiple', 'payment-checkbox' ), true ) ) {
						$field_content .= sprintf( '<input type="text" name="%1$s[value]" value="%2$s" class="value mhk-money-input" placeholder="%3$s">', $name, esc_attr( $choice['value'] ), mhk_format_amount( 0 ) );
					} else {
						$field_content .= sprintf( '<input type="text" name="%1$s[value]" value="%2$s" class="value">', $name, esc_attr( $choice['value'] ) );
					}
					$field_content .= '</div>';
					$field_content .= '<a class="add" href="#"><i class="dashicons dashicons-plus-alt"></i></a>';
					$field_content .= '<a class="remove" href="#"><i class="dashicons dashicons-dismiss"></i></a>';
					$field_content .= '<div class="muhiku-plug-attachment-media-view">';
					$field_content .= sprintf( '<input type="hidden" class="source" name="%s[image]" value="%s">', $name, esc_url_raw( $image ) );
					$field_content .= sprintf( '<button type="button" class="upload-button button-add-media"%s>%s</button>', ! empty( $image ) ? ' style="display:none;"' : '', esc_html__( 'Upload Image', 'muhiku-plug' ) );
					$field_content .= '<div class="thumbnail thumbnail-image">';
					if ( ! empty( $image ) ) {
						$field_content .= sprintf( '<img class="attachment-thumb" src="%1$s">', esc_url_raw( $image ) );
					}
					$field_content .= '</div>';
					$field_content .= sprintf( '<div class="actions"%s>', empty( $image ) ? ' style="display:none;"' : '' );
					$field_content .= sprintf( '<button type="button" class="button remove-button">%1$s</button>', esc_html__( 'Remove', 'muhiku-plug' ) );
					$field_content .= sprintf( '<button type="button" class="button upload-button">%1$s</button>', esc_html__( 'Change image', 'muhiku-plug' ) );
					$field_content .= '</div>';
					$field_content .= '</div>';
					$field_content .= '</li>';
				}
				$field_content .= '</ul>';

				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'choices',
						'content' =>  $field_content,
					),
					$echo
				);
				break;

			case 'choices_images':
				$field_content = sprintf(
					'<div class="notice notice-warning%s"><p>%s</p></div>',
					empty( $field['choices_images'] ) ? ' hidden' : '',
					esc_html__( 'For best results, images should be square and at least 200 × 160 pixels or smaller.', 'muhiku-plug' )
				);

				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'choices_images',
						'content' => $field_content,
					),
					$echo
				);
				break;

			case 'add_bulk_options':
				$class = ! empty( $args['class'] ) ? esc_attr( $args['class'] ) : '';
				$label = ! empty( $args['label'] ) ? esc_html( $args['label'] ) : esc_html__( 'Add Bulk Options', 'muhiku-plug' );

				$field_label = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'add_bulk_options',
						'value'         => $label,
						'tooltip'       => esc_html__( 'Add multiple options at once.', 'muhiku-plug' ),
						'after_tooltip' => sprintf( '<a class="mhk-toggle-presets-list" href="#">%s</a>', esc_html__( 'Presets', 'muhiku-plug' ) ),
					),
					false
				);

				$presets      = array(
					array(
						'label'   => esc_html__( 'Months', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-months',
						'options' => array(
							esc_html__( 'January', 'muhiku-plug' ),
							esc_html__( 'February', 'muhiku-plug' ),
							esc_html__( 'March', 'muhiku-plug' ),
							esc_html__( 'April', 'muhiku-plug' ),
							esc_html__( 'May', 'muhiku-plug' ),
							esc_html__( 'June', 'muhiku-plug' ),
							esc_html__( 'July', 'muhiku-plug' ),
							esc_html__( 'August', 'muhiku-plug' ),
							esc_html__( 'September', 'muhiku-plug' ),
							esc_html__( 'October', 'muhiku-plug' ),
							esc_html__( 'November', 'muhiku-plug' ),
							esc_html__( 'December', 'muhiku-plug' ),
						),
					),
					array(
						'label'   => esc_html__( 'Week Days', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-week-days',
						'options' => array(
							esc_html__( 'Sunday', 'muhiku-plug' ),
							esc_html__( 'Monday', 'muhiku-plug' ),
							esc_html__( 'Tuesday', 'muhiku-plug' ),
							esc_html__( 'Wednesday', 'muhiku-plug' ),
							esc_html__( 'Thursday', 'muhiku-plug' ),
							esc_html__( 'Friday', 'muhiku-plug' ),
							esc_html__( 'Saturday', 'muhiku-plug' ),
						),
					),
					array(
						'label'   => esc_html__( 'Countries', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-countries',
						'options' => array_values( mhk_get_countries() ),
					),
					array(
						'label'   => esc_html__( 'Countries Postal Code', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-countries-postal-code',
						'options' => array_keys( mhk_get_countries() ),
					),
					array(
						'label'   => esc_html__( 'U.S. States', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-states',
						'options' => array_values( mhk_get_states() ),
					),
					array(
						'label'   => esc_html__( 'U.S. States Postal Code', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-states-postal-code',
						'options' => array_keys( mhk_get_states() ),
					),
					array(
						'label'   => esc_html__( 'Age Groups', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-age-groups',
						'options' => array(
							esc_html__( 'Under 18', 'muhiku-plug' ),
							esc_html__( '18-24', 'muhiku-plug' ),
							esc_html__( '25-34', 'muhiku-plug' ),
							esc_html__( '35-44', 'muhiku-plug' ),
							esc_html__( '45-54', 'muhiku-plug' ),
							esc_html__( '55-64', 'muhiku-plug' ),
							esc_html__( '65 or Above', 'muhiku-plug' ),
							esc_html__( 'Prefer Not to Answer', 'muhiku-plug' ),
						),
					),
					array(
						'label'   => esc_html__( 'Satisfaction', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-satisfaction',
						'options' => array(
							esc_html__( 'Very Satisfied', 'muhiku-plug' ),
							esc_html__( 'Satisfied', 'muhiku-plug' ),
							esc_html__( 'Neutral', 'muhiku-plug' ),
							esc_html__( 'Unsatisfied', 'muhiku-plug' ),
							esc_html__( 'Very Unsatisfied', 'muhiku-plug' ),
							esc_html__( 'N/A', 'muhiku-plug' ),
						),
					),
					array(
						'label'   => esc_html__( 'Importance', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-importance',
						'options' => array(
							esc_html__( 'Very Important', 'muhiku-plug' ),
							esc_html__( 'Important', 'muhiku-plug' ),
							esc_html__( 'Neutral', 'muhiku-plug' ),
							esc_html__( 'Somewhat Important', 'muhiku-plug' ),
							esc_html__( 'Not at all Important', 'muhiku-plug' ),
							esc_html__( 'N/A', 'muhiku-plug' ),
						),
					),
					array(
						'label'   => esc_html__( 'Agreement', 'muhiku-plug' ),
						'class'   => 'mhk-options-preset-agreement',
						'options' => array(
							esc_html__( 'Strongly Agree', 'muhiku-plug' ),
							esc_html__( 'Agree', 'muhiku-plug' ),
							esc_html__( 'Neutral', 'muhiku-plug' ),
							esc_html__( 'Disagree', 'muhiku-plug' ),
							esc_html__( 'Strongly Disagree', 'muhiku-plug' ),
							esc_html__( 'N/A', 'muhiku-plug' ),
						),
					),
				);
				$presets_html = '<div class="mhk-options-presets" hidden>';
				foreach ( $presets as $preset ) {
					$presets_html .= sprintf( '<div class="mhk-options-preset %s">', esc_attr( $preset['class'] ) );
					$presets_html .= sprintf( '<a class="mhk-options-preset-label" href="#">%s</a>', $preset['label'] );
					$presets_html .= sprintf( '<textarea hidden class="mhk-options-preset-value">%s</textarea>', implode( "\n", $preset['options'] ) );
					$presets_html .= '</div>';
				}
				$presets_html .= '</div>';

				$field_content  = $this->field_element(
					'textarea',
					$field,
					array(
						'slug'  => 'add_bulk_options',
						'value' => '',
					),
					false
				);
				$field_content .= sprintf( '<a class="button button-small mhk-add-bulk-options" href="#">%s</a>', esc_html__( 'Add New Choices', 'muhiku-plug' ) );

				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'add_bulk_options',
						'content' => $field_label . $presets_html . $field_content,
						'class'   => $class,
					),
					$echo
				);
				break;

			case 'default_value':
				$value   = ! empty( $field['default_value'] ) || ( isset( $field['default_value'] ) && '0' === (string) $field['default_value'] ) ? esc_attr( $field['default_value'] ) : '';
				$toggle  = '';
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'          => 'default_value',
						'value'         => esc_html__( 'Varsayılan Değer', 'muhiku-plug' ),
						'after_tooltip' => $toggle,
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'default_value',
						'value' => $value,
					),
					false
				);

				$exclude_fields = array( 'rating', 'number', 'range-slider', 'payment-quantity' );

				if ( ! in_array( $field['type'], $exclude_fields, true ) ) {
					$output .= '<a href="#" class="mhk-toggle-smart-tag-display" data-type="other"><span class="dashicons dashicons-editor-code"></span></a>';
					$output .= '<div class="mhk-smart-tag-lists" style="display: none">';
					$output .= '<div class="smart-tag-title other-tag-title">Others</div><ul class="mhk-others"></ul></div>';
				}

				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'default_value',
						'content' => $output,
						'class'   => in_array( $field['type'], $exclude_fields, true ) ? '' : 'mhk_smart_tag',
					),
					$echo
				);
				break;

			case 'advanced-options':
				$markup = ! empty( $args['markup'] ) ? $args['markup'] : 'open';

				if ( 'open' === $markup ) {
					$override = apply_filters( 'muhiku_forms_advanced_options_override', false );
					$override = ! empty( $override ) ? 'style="display:' . $override . ';"' : '';
					if ( $echo ) {
						echo sprintf( '<div class="muhiku-plug-field-option-group muhiku-plug-field-option-group-advanced muhiku-plug-hide closed" id="muhiku-plug-field-option-advanced-%s" %s>', esc_attr( $field['id'] ), ( ! empty( $override ) ? 'style="display:' . esc_attr( apply_filters( 'muhiku_forms_advanced_options_override', false ) ) . ';"' : '' ) );
						echo sprintf( '<a href="#" class="muhiku-plug-field-option-group-toggle">%s<i class="handlediv"></i></a>', esc_html__( 'Geliştirilmiş Seçenekler', 'muhiku-plug' ) );
						echo '<div class="muhiku-plug-field-option-group-inner">';
					} else {
						$output  = sprintf( '<div class="muhiku-plug-field-option-group muhiku-plug-field-option-group-advanced muhiku-plug-hide closed" id="muhiku-plug-field-option-advanced-%s" %s>', $field['id'], $override );
						$output .= sprintf( '<a href="#" class="muhiku-plug-field-option-group-toggle">%s<i class="handlediv"></i></a>', __( 'Geliştirilmiş Seçenekler', 'muhiku-plug' ) );
						$output .= '<div class="muhiku-plug-field-option-group-inner">';
					}
				} else {
					if ( $echo ) {
						echo '</div></div>';
					} else {
						$output = '</div></div>';
					}
				}

				break;

			case 'placeholder':
				$value   = ! empty( $field['placeholder'] ) || ( isset( $field['placeholder'] ) && '0' === (string) $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'placeholder',
						'value'   => esc_html__( 'Yer Tutucu Metin', 'muhiku-plug' ),
					),
					false
				);
				$output .= $this->field_element(
					'text',
					$field,
					array(
						'slug'  => 'placeholder',
						'value' => $value,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'placeholder',
						'content' => $output,
					),
					$echo
				);
				break;
			case 'enable_prepopulate':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = ! empty( $field['enable_prepopulate'] ) ? esc_attr( $field['enable_prepopulate'] ) : '';
				$tooltip = esc_html__( 'Enable this option to allow field to be populated dynamically', 'muhiku-plug' );
				$output  = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'enable_prepopulate',
						'value'   => $value,
						'desc'    => esc_html__( 'Enable Autopoupulate ', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'enable_prepopulate',
						'content' => $output,
					),
					$echo
				);
				break;
			case 'parameter_name':
					$toggle  = '';
					$tooltip = esc_html__( 'Enter name of the parameter to populate the field.', 'muhiku-plug' );
					$value   = ! empty( $field['parameter_name'] ) ? esc_attr( $field['parameter_name'] ) : '';

					$output  = $this->field_element(
						'label',
						$field,
						array(
							'slug'          => 'parameter_name',
							'value'         => esc_html__( 'Parameter Name', 'muhiku-plug' ),
							'tooltip'       => $tooltip,
							'after_tooltip' => $toggle,
						),
						false
					);
					$output .= $this->field_element(
						'text',
						$field,
						array(
							'slug'  => 'parameter_name',
							'value' => $value,
						),
						false
					);
					$output  = $this->field_element(
						'row',
						$field,
						array(
							'slug'    => 'parameter_name',
							'content' => $output,
							'class'   => isset( $field['enable_prepopulate'] ) ? '' : 'hidden',
						),
						$echo
					);
				break;

			case 'label_hide':
				$value   = isset( $field['label_hide'] ) ? $field['label_hide'] : '0';

				$output = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'label_hide',
						'value'   => $value,
						'desc'    => esc_html__( 'Alanı Gizle', 'muhiku-plug' ),
					),
					false
				);
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'label_hide',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'sublabel_hide':
				$value   = isset( $field['sublabel_hide'] ) ? $field['sublabel_hide'] : '0';
				$tooltip = esc_html__( 'Check this option to hide the form field sub-label.', 'muhiku-plug' );

				$output = $this->field_element(
					'checkbox',
					$field,
					array(
						'slug'    => 'sublabel_hide',
						'value'   => $value,
						'desc'    => esc_html__( 'Hide Sub-Labels', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'sublabel_hide',
						'content' => $output,
					),
					$echo
				);
				break;

			case 'whitelist_domain':
				$default = ! empty( $args['default'] ) ? $args['default'] : '0';
				$value   = ! empty( $field['whitelist_domain'] ) ? esc_attr( $field['whitelist_domain'] ) : '';
				$style   = ! empty( $field['select_whitelist'] ) ? esc_attr( $field['select_whitelist'] ) : 'Allowed Domains';
				$tooltip = esc_html__( 'You can list the email domains in the Whitelisted Domains', 'muhiku-plug' );
				$output  = $this->field_element(
					'label',
					$field,
					array(
						'slug'    => 'whitelist_domain',
						'value'   => esc_html__( 'Whitelisted Domains', 'muhiku-plug' ),
						'tooltip' => $tooltip,
					),
					false
				);
				$output .= $this->field_element(
					'select',
					$field,
					array(
						'slug'    => 'select_whitelist',
						'value'   => $style,
						'options' => array(
							'allow' => esc_html__( 'Allowed Domains', 'muhiku-plug' ),
							'deny'  => esc_html__( 'Denied Domains', 'muhiku-plug' ),
						),
					),
					false
				);
				$output  = $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'whitelist_domain',
						'content' => $output,
					),
					$echo
				);

				$output .= $this->field_element(
					'row',
					$field,
					array(
						'slug'    => 'whitelist_domain',
						'content' => $this->field_element(
							'text',
							$field,
							array(
								'slug'        => 'whitelist_domain',
								'value'       => esc_attr( $value ),
								'placeholder' => esc_attr__( 'for eg. gmail.com', 'muhiku-plug' ),
							),
							false
						),
					),
					$echo
				);
				break;

			default:
				if ( is_callable( array( $this, $option ) ) ) {
					$this->{$option}( $field );
				}
				do_action( 'muhiku_forms_field_options_' . $option, $this, $field, $args );
				break;

		}

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "muhiku_forms_field_options_top_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "muhiku_forms_field_options_after_{$option}", $field, $this );
		}

		if ( ! $echo ) {
			return $output;
		}
	}

	/**
	 * @param string  $option 
	 * @param array   $field  
	 * @param array   $args   
	 * @param boolean $echo   
	 *
	 * @return mixed Print or return a string.
	 */
	public function field_preview_option( $option, $field, $args = array(), $echo = true ) {
		$output    = '';
		$class     = ! empty( $args['class'] ) ? mhk_sanitize_classes( $args['class'] ) : '';
		$form_id   = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;  
		$form_data = mhk()->form->get( absint( $form_id ), array( 'content_only' => true ) );
		$markup    = '';

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "muhiku_forms_field_options_before_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "muhiku_forms_field_options_bottom_{$option}", $field, $this );
		}

		switch ( $option ) {
			case 'label':
				$label = isset( $field['label'] ) && ! empty( $field['label'] ) ? $field['label'] : '';
				if ( $echo ) {
					echo sprintf( '<label class="label-title %s"><span class="text">%s</span><span class="required">%s</span></label>', esc_attr( $class ), esc_html( $label ), esc_html( apply_filters( 'muhiku_form_get_required_type', '*', $field, $form_data ) ) );
				} else {
					$output = sprintf( '<label class="label-title %s"><span class="text">%s</span><span class="required">%s</span></label>', $class, $label, apply_filters( 'muhiku_form_get_required_type', '*', $field, $form_data ) );
				}
				break;

			case 'description':
				$description = isset( $field['description'] ) && ! empty( $field['description'] ) ? $field['description'] : '';
				$description = false !== strpos( $class, 'nl2br' ) ? nl2br( $description ) : $description;
				if ( $echo ) {
					echo sprintf( '<div class="description %s">%s</div>', esc_attr( $class ), esc_html( $description ) );
				} else {
					$output = sprintf( '<div class="description %s">%s</div>', $class, $description );
				}
				break;

			case 'repeater_fields':
				$repeater_fields = isset( $field['repeater_fields'] ) && ! empty( $field['repeater_fields'] ) ? $field['repeater_fields'] : '';
				if ( $echo ) {
					echo sprintf( '<div>%s</div>', esc_html( $repeater_fields ) );
				} else {
					$output = sprintf( '<div>%s</div>', $repeater_fields );
				}
				break;

			case 'repeater_button_add_remove_label':
				$add_new_label = isset( $field['repeater_button_add_new_label'] ) && ! empty( $field['repeater_button_add_new_label'] ) ? $field['repeater_button_add_new_label'] : 'Add';
				$remove_label  = isset( $field['repeater_button_remove_label'] ) && ! empty( $field['repeater_button_remove_label'] ) ? $field['repeater_button_remove_label'] : 'Remove';
				if ( $echo ) {
					echo sprintf( '<div style="margin-right: %s" class="mhk-add-row repeater_button_add_remove_label %s"><span class="muhiku-plug-btn muhiku-plug-btn-primary dashicons dashicons-plus">%s</span>&nbsp;<span class="muhiku-plug-btn muhiku-plug-btn-primary dashicons dashicons-minus">%s</span></div>', '65%', esc_attr( $class ), esc_html( $add_new_label ), esc_html( $remove_label ) );
				} else {
					$output = sprintf( '<div style="margin-right: %s" class="mhk-add-row repeater_button_add_remove_label %s"><span class="muhiku-plug-btn muhiku-plug-btn-primary dashicons dashicons-plus">%s</span>&nbsp;<span class="muhiku-plug-btn muhiku-plug-btn-primary dashicons dashicons-minus">%s</span></div>', '65%', $class, $add_new_label, $remove_label );
				}
				break;

			case 'choices':
				$values         = ! empty( $field['choices'] ) ? $field['choices'] : $this->defaults;
				$choices_fields = array( 'select', 'radio', 'checkbox', 'payment-multiple', 'payment-checkbox' );

				if ( empty( $values ) ) {
					$values = array(
						'label' => esc_html__( '(empty)', 'muhiku-plug' ),
					);
				}

				if ( ! in_array( $field['type'], $choices_fields, true ) ) {
					break;
				}

				switch ( $field['type'] ) {
					case 'checkbox':
					case 'payment-checkbox':
						$type = 'checkbox';
						break;

					case 'select':
						$type = 'select';
						break;

					default:
						$type = 'radio';
						break;
				}

				$list_class     = array( 'widefat', 'primary-input' );
				$choices_images = ! empty( $field['choices_images'] );

				if ( $choices_images ) {
					$list_class[] = 'muhiku-plug-image-choices';
				}

				if ( ! empty( $class ) ) {
					$list_class[] = $class;
				}

				if ( 'select' === $type ) {
					$multiple    = ! empty( $field['multiple_choices'] ) ? ' multiple' : '';
					$placeholder = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';

					if ( $echo ) {
						echo sprintf( '<select class="%s" %s data-placeholder="%s" disabled>', esc_attr( mhk_sanitize_classes( $list_class, true ) ), esc_attr( $multiple ), esc_attr( $placeholder ) );

						if ( ! empty( $placeholder ) ) {
							echo sprintf( '<option value="" class="placeholder">%s</option>', esc_html( $placeholder ) );
						}

						foreach ( $values as $value ) {
							$default  = isset( $value['default'] ) ? (bool) $value['default'] : false;
							$selected = ! empty( $placeholder ) && empty( $multiple ) ? '' : selected( true, $default, false );
							echo sprintf( '<option %s>%s</option>', esc_attr( $selected ), esc_html( $value['label'] ) );
						}

						echo '</select>';
					} else {
						$output = sprintf( '<select class="%s" %s data-placeholder="%s" disabled>', mhk_sanitize_classes( $list_class, true ), esc_attr( $multiple ), esc_attr( $placeholder ) );

						if ( ! empty( $placeholder ) ) {
							$output .= sprintf( '<option value="" class="placeholder">%s</option>', esc_html( $placeholder ) );
						}

						foreach ( $values as $value ) {
							$default  = isset( $value['default'] ) ? (bool) $value['default'] : false;
							$selected = ! empty( $placeholder ) && empty( $multiple ) ? '' : selected( true, $default, false );
							$output  .= sprintf( '<option %s>%s</option>', $selected, esc_html( $value['label'] ) );
						}

						$output .= '</select>';
					}
				} else {

					if ( $echo ) {
						echo sprintf( '<ul class="%s">', esc_attr( mhk_sanitize_classes( $list_class, true ) ) );

						foreach ( $values as $value ) {
							$default     = isset( $value['default'] ) ? $value['default'] : '';
							$selected    = checked( '1', $default, false );
							$placeholder = wp_remote_get( mhk()->plugin_url( 'assets/images/muhiku-plug-placeholder.png' ), array( 'sslverify' => false ) );
							$image_src   = ! empty( $value['image'] ) ? esc_url( $value['image'] ) : $placeholder;
							$item_class  = array();

							if ( ! empty( $value['default'] ) ) {
								$item_class[] = 'muhiku-plug-selected';
							}

							if ( $choices_images ) {
								$item_class[] = 'muhiku-plug-image-choices-item';
							}

							echo sprintf( '<li class="%s">', esc_attr( mhk_sanitize_classes( $item_class, true ) ) );

							if ( $choices_images ) {
								echo '<label>';
								echo sprintf( '<span class="muhiku-plug-image-choices-image"><img src="%s" alt="%s"%s></span>', esc_url( $image_src ), esc_attr( $value['label'] ), ( ! empty( $value['label'] ) ? ' title="' . esc_attr( $value['label'] ) . '"' : '' ) );
								echo sprintf( '<input type="%s" %s disabled>', esc_attr( $type ), esc_attr( $selected ) );
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									echo '<span class="muhiku-plug-image-choices-label">' . esc_html( $value['label'] . '-' . mhk_format_amount( mhk_sanitize_amount( $value['value'] ), true ) ) . '</span>';
								} else {
									echo '<span class="muhiku-plug-image-choices-label">' . esc_html( $value['label'] ) . '</span>';
								}
								echo '</label>';
							} else {
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									echo sprintf( '<input type="%s" %s disabled>%s - %s', esc_attr( $type ), esc_attr( $selected ), esc_html( $value['label'] ), esc_attr( mhk_format_amount( mhk_sanitize_amount( $value['value'] ) ), true ) );
								} else {
									echo sprintf( '<input type="%s" %s disabled>%s', esc_attr( $type ), esc_attr( $selected ), esc_html( $value['label'] ) );
								}
							}

							echo '</li>';
						}

						echo '</ul>';

					} else {
						$output = sprintf( '<ul class="%s">', mhk_sanitize_classes( $list_class, true ) );

						foreach ( $values as $value ) {
							$default     = isset( $value['default'] ) ? $value['default'] : '';
							$selected    = checked( '1', $default, false );
							$placeholder = wp_remote_get( mhk()->plugin_url( 'assets/images/muhiku-plug-placeholder.png' ), array( 'sslverify' => false ) );
							$image_src   = ! empty( $value['image'] ) ? esc_url( $value['image'] ) : $placeholder;
							$item_class  = array();

							if ( ! empty( $value['default'] ) ) {
								$item_class[] = 'muhiku-plug-selected';
							}

							if ( $choices_images ) {
								$item_class[] = 'muhiku-plug-image-choices-item';
							}

							$output .= sprintf( '<li class="%s">', mhk_sanitize_classes( $item_class, true ) );

							if ( $choices_images ) {
								$output .= '<label>';
								$output .= sprintf( '<span class="muhiku-plug-image-choices-image"><img src="%s" alt="%s"%s></span>', $image_src, esc_attr( $value['label'] ), ! empty( $value['label'] ) ? ' title="' . esc_attr( $value['label'] ) . '"' : '' );
								$output .= sprintf( '<input type="%s" %s disabled>', $type, $selected );
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									$output .= '<span class="muhiku-plug-image-choices-label">' . wp_kses_post( $value['label'] ) . '-' . mhk_format_amount( mhk_sanitize_amount( $value['value'] ), true ) . '</span>';
								} else {
									$output .= '<span class="muhiku-plug-image-choices-label">' . wp_kses_post( $value['label'] ) . '</span>';
								}
								$output .= '</label>';
							} else {
								if ( ( 'payment-checkbox' === $field['type'] ) || ( 'payment-multiple' === $field['type'] ) ) {
									$output .= sprintf( '<input type="%s" %s disabled>%s - %s', $type, $selected, $value['label'], mhk_format_amount( mhk_sanitize_amount( $value['value'] ), true ) );
								} else {
									$output .= sprintf( '<input type="%s" %s disabled>%s', $type, $selected, $value['label'] );
								}
							}

							$output .= '</li>';
						}

						$output .= '</ul>';
					}
				}
				break;
		}

		if ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'open' === $markup ) {
			do_action( "muhiku_forms_field_options_top_{$option}", $field, $this );
		} elseif ( $echo && in_array( $option, array( 'basic-options', 'advanced-options' ), true ) && 'close' === $markup ) {
			do_action( "muhiku_forms_field_options_after_{$option}", $field, $this );
		}

		if ( ! $echo ) {
			return $output;
		}
	}

	public function field_new() {
		check_ajax_referer( 'muhiku_forms_field_drop', 'security' );

		if ( ! isset( $_POST['form_id'] ) || empty( $_POST['form_id'] ) ) {
			die( esc_html__( 'No form ID found', 'muhiku-plug' ) );
		}

		if ( ! current_user_can( 'muhiku_forms_edit_form', (int) $_POST['form_id'] ) ) {
			die( esc_html__( 'You do no have permission.', 'muhiku-plug' ) );
		}

		if ( ! isset( $_POST['field_type'] ) || empty( $_POST['field_type'] ) ) {
			die( esc_html__( 'No field type found', 'muhiku-plug' ) );
		}

		$field_args     = ! empty( $_POST['defaults'] ) && is_array( $_POST['defaults'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['defaults'] ) ) : array();
		$field_type     = esc_attr( sanitize_text_field( wp_unslash( $_POST['field_type'] ) ) );
		$field_id       = mhk()->form->field_unique_key( sanitize_text_field( wp_unslash( $_POST['form_id'] ) ) );
		$field          = array(
			'id'          => $field_id,
			'type'        => $field_type,
			'label'       => $this->name,
			'description' => '',
		);
		$field          = wp_parse_args( $field_args, $field );
		$field          = apply_filters( 'muhiku_forms_field_new_default', $field );
		$field_required = apply_filters( 'muhiku_forms_field_new_required', '', $field );
		$field_class    = apply_filters( 'muhiku_forms_field_new_class', '', $field );

		if ( ! empty( $field_required ) ) {
			$field_required    = 'required';
			$field['required'] = '1';
		}

		ob_start();
		$this->field_preview( $field );
		$preview  = sprintf( '<div class="muhiku-plug-field muhiku-plug-field-%s %s %s" id="muhiku-plug-field-%s" data-field-id="%s" data-field-type="%s">', $field_type, $field_required, $field_class, $field['id'], $field['id'], $field_type );
		$preview .= sprintf( '<div class="mhk-field-action">' );
		if ( 'repeater-fields' !== $field_type ) {
			$preview .= sprintf( '<a href="#" class="muhiku-plug-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', __( 'Duplicate Field', 'muhiku-plug' ) );
			$preview .= sprintf( '<a href="#" class="muhiku-plug-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', __( 'Delete Field', 'muhiku-plug' ) );
			$preview .= sprintf( '<a href="#" class="muhiku-plug-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', __( 'Settings', 'muhiku-plug' ) );
		} else {
			$preview .= sprintf( '<a href="#" class="mhk-duplicate-row" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Field', 'muhiku-plug' ) );
			$preview .= sprintf( '<a href="#" class="mhk-delete-row" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Field', 'muhiku-plug' ) );
		}
		$preview .= sprintf( '</div>' );
		$preview .= ob_get_clean();
		$preview .= '</div>';

		$options      = sprintf( '<div class="muhiku-plug-field-option muhiku-plug-field-option-%s" id="muhiku-plug-field-option-%s" data-field-id="%s">', esc_attr( $field['type'] ), $field['id'], $field['id'] );
			$options .= sprintf( '<input type="hidden" name="form_fields[%s][id]" value="%s" class="muhiku-plug-field-option-hidden-id">', $field['id'], $field['id'] );
			$options .= sprintf( '<input type="hidden" name="form_fields[%s][type]" value="%s" class="muhiku-plug-field-option-hidden-type">', $field['id'], esc_attr( $field['type'] ) );
			ob_start();
			$this->field_options( $field );
			$options .= ob_get_clean();
		$options     .= '</div>';

		$form_field_array = explode( '-', $field_id );
		$field_id_int     = absint( $form_field_array[ count( $form_field_array ) - 1 ] );

		wp_send_json_success(
			array(
				'form_id'       => (int) $_POST['form_id'],
				'field'         => $field,
				'preview'       => $preview,
				'options'       => $options,
				'form_field_id' => ( $field_id_int + 1 ),
			)
		);
	}

	/**
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {}

	/**
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {

		if ( 'repeater_fields' === $field['type'] ) {
			return;
		}

		$value = isset( $entry_field['value'] ) ? $entry_field['value'] : '';

		if ( '' !== $value ) {
			$field['properties'] = $this->get_single_field_property_value( $value, 'primary', $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * @param string $raw_value  
	 * @param string $input      
	 * @param array  $properties 
	 * @param array  $field      
	 *
	 * @return array Modified field properties.
	 */
	public function get_single_field_property_value( $raw_value, $input, $properties, $field ) {
		if ( ! is_string( $raw_value ) ) {
			return $properties;
		}

		$get_value = wp_unslash( sanitize_text_field( $raw_value ) );

		if ( ! empty( $field['choices'] ) && is_array( $field['choices'] ) ) {
			$properties = $this->get_single_field_property_value_choices( $get_value, $properties, $field );
		} else {
			if (
				! empty( $input ) &&
				isset( $properties['inputs'][ $input ] )
			) {
				$properties['inputs'][ $input ]['attr']['value'] = $get_value;

				if ( isset( $field['type'] ) && 'range-slider' === $field['type'] ) {
					$properties['inputs'][ $input ]['data']['from'] = $get_value;
				}
			}
		}

		return $properties;
	}

	/**
	 * @param string $get_value  Requested value.
	 * @param array  $properties Field properties.
	 * @param array  $field      Field specific data.
	 *
	 * @return array Modified field properties.
	 */
	protected function get_single_field_property_value_choices( $get_value, $properties, $field ) {
		$default_key = null;

		// For fields with normal choices, we need dafault key.
		foreach ( $field['choices'] as $choice_key => $choice_arr ) {
			$choice_value_key = isset( $field['show_values'] ) ? 'value' : 'label';
			if (
				isset( $choice_arr[ $choice_value_key ] ) &&
				strtoupper( sanitize_text_field( $choice_arr[ $choice_value_key ] ) ) === strtoupper( $get_value )
			) {
				$default_key = $choice_key;
				break;
			}
		}

		if ( null !== $default_key ) {
			foreach ( $field['choices'] as $choice_key => $choice_arr ) {
				if ( $choice_key === $default_key ) {
					$properties['inputs'][ $choice_key ]['default']              = true;
					$properties['inputs'][ $choice_key ]['container']['class'][] = 'muhiku-plug-selected';
					break;
				}
			}
		}

		return $properties;
	}

	/**
	 * @param array $field     
	 * @param array $properties 
	 */
	protected function remove_field_choices_defaults( $field, &$properties ) {
		if ( ! empty( $field['choices'] ) ) {
			array_walk_recursive(
				$properties['inputs'],
				function ( &$value, $key ) {
					if ( 'default' === $key ) {
						$value = false;
					}
					if ( 'muhiku-plug-selected' === $value ) {
						$value = '';
					}
				}
			);
		}
	}

	/**
	 * @param string $key   
	 * @param array  $field 
	 */
	public function field_display_error( $key, $field ) {
		// Need an error.
		if ( empty( $field['properties']['error']['value'][ $key ] ) ) {
			return;
		}

		printf(
			'<label class="muhiku-plug-error mhk-error" for="%s">%s</label>',
			esc_attr( $field['properties']['inputs'][ $key ]['id'] ),
			esc_html( $field['properties']['error']['value'][ $key ] )
		);
	}

	/**
	 * @param string $key      
	 * @param string $position 
	 * @param array  $field    
	 */
	public function field_display_sublabel( $key, $position, $field ) {
		if ( empty( $field['properties']['inputs'][ $key ]['sublabel']['value'] ) ) {
			return;
		}

		$pos    = ! empty( $field['properties']['inputs'][ $key ]['sublabel']['position'] ) ? $field['properties']['inputs'][ $key ]['sublabel']['position'] : 'after';
		$hidden = ! empty( $field['properties']['inputs'][ $key ]['sublabel']['hidden'] ) ? 'muhiku-plug-sublabel-hide' : '';

		if ( $pos !== $position ) {
			return;
		}

		printf(
			'<label for="%s" class="muhiku-plug-field-sublabel %s %s">%s</label>',
			esc_attr( $field['properties']['inputs'][ $key ]['id'] ),
			sanitize_html_class( $pos ),
			esc_html( $hidden ),
			esc_html( mhk_string_translation( (int) $this->form_data['id'], $field['id'], $field['properties']['inputs'][ $key ]['sublabel']['value'], '-sublabel-' . $key ) )
		);
	}

	/**
	 * @param string $field_id 
	 * @param array  $field_submit 
	 * @param array  $form_data 
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$field_type         = isset( $form_data['form_fields'][ $field_id ]['type'] ) ? $form_data['form_fields'][ $field_id ]['type'] : '';
		$required_field     = isset( $form_data['form_fields'][ $field_id ]['required'] ) ? $form_data['form_fields'][ $field_id ]['required'] : false;
		$conditional_status = isset( $form_data['form_fields'][ $field_id ]['conditional_logic_status'] ) ? $form_data['form_fields'][ $field_id ]['conditional_logic_status'] : 0;

		if ( false !== $required_field && '1' !== $conditional_status && ( empty( $field_submit ) && '0' !== $field_submit ) ) {
			mhk()->task->errors[ $form_data['id'] ][ $field_id ] = mhk_get_required_label();
			update_option( 'mhk_validation_error', 'yes' );
		}

		switch ( $field_type ) {
			case 'url':
				if ( ! empty( $_POST['muhiku_forms']['form_fields'][ $field_id ] ) && filter_var( $field_submit, FILTER_VALIDATE_URL ) === false ) {  
					$validation_text = get_option( 'mhk_' . $field_type . '_validation', esc_html__( 'Please enter a valid url', 'muhiku-plug' ) );
				}
				break;
			case 'email':
				if ( is_array( $field_submit ) ) {
					$value = ! empty( $field_submit['primary'] ) ? $field_submit['primary'] : '';
				} else {
					$value = ! empty( $field_submit ) ? $field_submit : '';
				}
				if ( ! empty( $_POST['muhiku_forms']['form_fields'][ $field_id ] ) && ! is_email( $value ) ) {  
					$validation_text = get_option( 'mhk_' . $field_type . '_validation', esc_html__( 'Please enter a valid email address', 'muhiku-plug' ) );
				}
				break;
			case 'number':
				if ( ! empty( $_POST['muhiku_forms']['form_fields'][ $field_id ] ) && ! is_numeric( $field_submit ) ) {  
					$validation_text = get_option( 'mhk_' . $field_type . '_validation', esc_html__( 'Please enter a valid number', 'muhiku-plug' ) );
				}
				break;
		}

		if ( isset( $validation_text ) ) {
			mhk()->task->errors[ $form_data['id'] ][ $field_id ] = apply_filters( 'muhiku_forms_type_validation', $validation_text );
			update_option( 'mhk_validation_error', 'yes' );
		}
	}

	/**
	 * @param int    $field_id     
	 * @param mixed  $field_submit 
	 * @param array  $form_data    
	 * @param string $meta_key    
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		if ( is_array( $field_submit ) ) {
			$field_submit = array_filter( $field_submit );
			$field_submit = implode( "\r\n", $field_submit );
		}

		$name = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? make_clickable( $form_data['form_fields'][ $field_id ]['label'] ) : '';

		$value = mhk_sanitize_textarea_field( $field_submit );

		mhk()->task->form_fields[ $field_id ] = array(
			'name'     => $name,
			'value'    => $value,
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}

	/**
	 * @param  array $field 
	 * @return boolean
	 */
	protected function field_is_limit( $field ) {
		if ( in_array( $field['type'], array( 'text', 'textarea' ), true ) ) {
			return isset( $field['limit_enabled'] ) && ! empty( $field['limit_count'] );
		}
	}

	/**
	 * @param array $field 
	 */
	public function field_exporter( $field ) {
		$export = array();

		switch ( $this->type ) {
			case 'radio':
			case 'signature':
			case 'payment-multiple':
				$value  = '';
				$image  = ! empty( $field['value']['image'] ) ? sprintf( '<img src="%s" style="width:75px;height:75px;max-height:75px;max-width:75px;"  /><br>', $field['value']['image'] ) : '';
				$value  = ! empty( $field['value']['label'] ) ? $image . $field['value']['label'] : '';
				$export = array(
					'label' => ! empty( $field['value']['name'] ) ? $field['value']['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
					'value' => ! empty( $value ) ? $value : false,
				);
				break;
			case 'checkbox':
			case 'payment-checkbox':
				$value = array();

				if ( count( $field['value'] ) ) {
					foreach ( $field['value']['label'] as $key => $choice ) {
						$image = ! empty( $field['value']['images'][ $key ] ) ? sprintf( '<img src="%s" style="width:75px;height:75px;max-height:75px;max-width:75px;"  /><br>', $field['value']['images'][ $key ] ) : '';

						if ( ! empty( $choice ) ) {
							$value[ $key ] = $image . $choice;
						}
					}
				}
				$export = array(
					'label' => ! empty( $field['value']['name'] ) ? $field['value']['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
					'value' => is_array( $value ) ? implode( '<br>', array_values( $value ) ) : false,
				);
				break;
			default:
				$export = array(
					'label' => ! empty( $field['name'] ) ? $field['name'] : ucfirst( str_replace( '_', ' ', $field['type'] ) ) . " - {$field['id']}",
					'value' => ! empty( $field['value'] ) ? ( is_array( $field['value'] ) ? $this->implode_recursive( $field['value'] ) : $field['value'] ) : false,
				);
		}

		return $export;
	}

	/**
	 * @param  array  $array     
	 * @param  string $delimiter 
	 *
	 * @return string $output 
	 */
	protected function implode_recursive( $array, $delimiter = '<br>' ) {
		$output = '';

		foreach ( $array as $tuple ) {
			if ( is_array( $tuple ) ) {
				$output .= $this->implode_recursive( $tuple, ' ' );
			} elseif ( ! empty( $tuple ) ) {
				$output .= $delimiter . $tuple;
			}
		}

		return $output;
	}
}

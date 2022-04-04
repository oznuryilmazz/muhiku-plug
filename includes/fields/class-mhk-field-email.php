<?php
/**
 * @package MuhikuPlug\Fields
 */

defined( 'ABSPATH' ) || exit;

class MHK_Field_Email extends MHK_Form_Fields {

	public function __construct() {
		$this->name     = esc_html__( 'Mail Adresiniz', 'muhiku-plug' );
		$this->type     = 'email';
		$this->icon     = 'mhk-icon mhk-icon-email';
		$this->order    = 90;
		$this->group    = 'general';
		$this->settings = array(
			'basic-options'    => array(
				'field_options' => array(
					'label',
					'meta',
					'description',
					'required',
					'required_field_message',
					'confirmation',
				),
			),
			'advanced-options' => array(
				'field_options' => array(
					'size',
					'placeholder',
					'confirmation_placeholder',
					'label_hide',
					'sublabel_hide',
					'default_value',
					'css',
				),
			),
		);

		parent::__construct();
	}

	public function init_hooks() {
		add_filter( 'muhiku_forms_field_properties_' . $this->type, array( $this, 'field_properties' ), 5, 3 );
		add_filter( 'muhiku_forms_field_new_required', array( $this, 'field_default_required' ), 5, 3 );
		add_filter( 'muhiku_forms_builder_field_option_class', array( $this, 'field_option_class' ), 10, 2 );
	}

	/**
	 * @param array $properties Field properties.
	 * @param array $field      Field settings.
	 * @param array $form_data  Form data and settings.
	 *
	 * @return array of additional field properties.
	 */
	public function field_properties( $properties, $field, $form_data ) {
		if ( empty( $field['confirmation'] ) ) {
			return $properties;
		}

		$form_id  = absint( $form_data['id'] );
		$field_id = $field['id'];

		$props      = array(
			'inputs' => array(
				'primary'   => array(
					'block'    => array(
						'muhiku-plug-field-row-block',
						'muhiku-plug-one-half',
						'muhiku-plug-first',
					),
					'class'    => array(
						'muhiku-plug-field-email-primary',
					),
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => esc_html__( 'Email', 'muhiku-plug' ),
					),
				),
				'secondary' => array(
					'attr'     => array(
						'name'        => "muhiku_forms[form_fields][{$field_id}][secondary]",
						'value'       => '',
						'placeholder' => ! empty( $field['confirmation_placeholder'] ) ? mhk_string_translation( $form_id, $field_id, $field['confirmation_placeholder'], '-confirm-placeholder' ) : '',
					),
					'block'    => array(
						'muhiku-plug-field-row-block',
						'muhiku-plug-one-half',
					),
					'class'    => array(
						'input-text',
						'muhiku-plug-field-email-secondary',
					),
					'data'     => array(
						'rule-confirm' => '#' . $properties['inputs']['primary']['id'],
					),
					'id'       => "mhk-{$form_id}-field_{$field_id}-secondary",
					'required' => ! empty( $field['required'] ) ? 'required' : '',
					'sublabel' => array(
						'hidden' => ! empty( $field['sublabel_hide'] ),
						'value'  => esc_html__( 'Confirm Email', 'muhiku-plug' ),
					),
					'value'    => '',
				),
			),
		);
		$properties = array_merge_recursive( $properties, $props );

		$properties['inputs']['primary']['attr']['name'] = "muhiku_forms[form_fields][{$field_id}][primary]";

		$properties['inputs']['primary']['class'] = array_diff(
			$properties['inputs']['primary']['class'],
			array(
				'mhk-error',
			)
		);

		if ( ! empty( $properties['error']['value']['primary'] ) ) {
			$properties['inputs']['primary']['class'][] = 'mhk-error';
		}

		if ( ! empty( $properties['error']['value']['secondary'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'mhk-error';
		}

		if ( ! empty( $field['required'] ) ) {
			$properties['inputs']['secondary']['class'][] = 'mhk-field-required';
		}

		return $properties;
	}

	/**
	 * @param bool  $required Required status, true is required.
	 * @param array $field    Field settings.
	 *
	 * @return bool
	 */
	public function field_default_required( $required, $field ) {
		if ( 'email' === $field['type'] ) {
			return true;
		}

		return $required;
	}

	/**
	 * @param  array $class Field class.
	 * @param  array $field Field option data.
	 * @return array
	 */
	public function field_option_class( $class, $field ) {
		if ( 'email' === $field['type'] ) {
			if ( isset( $field['confirmation'] ) ) {
				$class[] = 'muhiku-plug-confirm-enabled';
			} else {
				$class[] = 'muhiku-plug-confirm-disabled';
			}
		}

		return $class;
	}

	/**
	 * @param array $field Field Data.
	 */
	public function confirmation_placeholder( $field ) {
		$lbl  = $this->field_element(
			'label',
			$field,
			array(
				'slug'    => 'confirmation_placeholder',
				'value'   => esc_html__( 'Confirmation Placeholder Text', 'muhiku-plug' ),
				'tooltip' => esc_html__( 'Enter text for the confirmation field placeholder.', 'muhiku-plug' ),
			),
			false
		);
		$fld  = $this->field_element(
			'text',
			$field,
			array(
				'slug'  => 'confirmation_placeholder',
				'value' => ! empty( $field['confirmation_placeholder'] ) ? esc_attr( $field['confirmation_placeholder'] ) : '',
			),
			false
		);
		$args = array(
			'slug'    => 'confirmation_placeholder',
			'content' => $lbl . $fld,
		);
		$this->field_element( 'row', $field, $args );
	}

	/**
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$placeholder         = ! empty( $field['placeholder'] ) ? esc_attr( $field['placeholder'] ) : '';
		$confirm_placeholder = ! empty( $field['confirmation_placeholder'] ) ? esc_attr( $field['confirmation_placeholder'] ) : '';
		$confirm             = ! empty( $field['confirmation'] ) ? 'enabled' : 'disabled';

		$this->field_preview_option( 'label', $field );
		?>
		<div class="muhiku-plug-confirm muhiku-plug-confirm-<?php echo esc_attr( $confirm ); ?>">
			<div class="muhiku-plug-confirm-primary">
				<input type="email" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="widefat primary-input" disabled>
				<label class="muhiku-plug-sub-label"><?php esc_html_e( 'Email', 'muhiku-plug' ); ?></label>

			</div>
			<div class="muhiku-plug-confirm-confirmation">
				<input type="email" placeholder="<?php echo esc_attr( $confirm_placeholder ); ?>" class="widefat secondary-input" disabled>
				<label class="muhiku-plug-sub-label"><?php esc_html_e( 'Confirm Email', 'muhiku-plug' ); ?></label>
			</div>
		</div>
		<?php
		$this->field_preview_option( 'description', $field );
	}

	/**
	 * @param array $field Field Data.
	 * @param array $field_atts Field attributes.
	 * @param array $form_data All Form Data.
	 */
	public function field_display( $field, $field_atts, $form_data ) {
	
		$form_id      = absint( $form_data['id'] );
		$confirmation = ! empty( $field['confirmation'] );
		$primary      = $field['properties']['inputs']['primary'];
		$secondary    = ! empty( $field['properties']['inputs']['secondary'] ) ? $field['properties']['inputs']['secondary'] : '';

		if ( ! $confirmation ) {

			printf(
				'<input type="email" %s %s >',
				mhk_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);

		} else {

			echo '<div class="muhiku-plug-field-row muhiku-plug-field">';
			echo '<div ' . mhk_html_attributes( false, $primary['block'] ) . '>';
			printf(
				'<input type="email" %s %s>',
				mhk_html_attributes( $primary['id'], $primary['class'], $primary['data'], $primary['attr'] ),
				esc_attr( $primary['required'] )
			);
			$this->field_display_sublabel( 'primary', 'after', $field );
			$this->field_display_error( 'primary', $field );
			echo '</div>';

			echo '<div ' . mhk_html_attributes( false, $secondary['block'] ) . '>';
			printf(
				'<input type="email" %s %s>',
				mhk_html_attributes( $secondary['id'], $secondary['class'], $secondary['data'], $secondary['attr'] ),
				esc_attr( $secondary['required'] )
			);
			$this->field_display_sublabel( 'secondary', 'after', $field );
			$this->field_display_error( 'secondary', $field );
			echo '</div>';

			echo '</div>';
		}
	}

	/**
	 * @param array $entry_field Entry field data.
	 * @param array $field       Field data.
	 * @param array $form_data   Form data and settings.
	 */
	public function edit_form_field_display( $entry_field, $field, $form_data ) {
		$value = isset( $entry_field['value'] ) ? $entry_field['value'] : '';

		unset( $field['confirmation'] );

		if ( '' !== $value ) {
			$field['properties'] = $this->get_single_field_property_value( $value, 'primary', $field['properties'], $field );
		}

		$this->field_display( $field, null, $form_data );
	}

	/**
	 * @param int   $field_id     Field ID.
	 * @param array $field_submit Submitted data.
	 * @param array $form_data    Form data.
	 */
	public function validate( $field_id, $field_submit, $form_data ) {
		$form_id            = (int) $form_data['id'];
		$conditional_status = isset( $form_data['form_fields'][ $field_id ]['conditional_logic_status'] ) ? $form_data['form_fields'][ $field_id ]['conditional_logic_status'] : 0;

		if ( ! empty( $form_data['form_fields'][ $field_id ]['required'] ) && '1' !== $conditional_status ) {
			$required = mhk_get_required_label();

			if ( empty( $form_data['form_fields'][ $field_id ]['confirmation'] ) ) {
				if ( empty( $field_submit ) && '0' !== $field_submit ) {
					mhk()->task->errors[ $form_id ][ $field_id ] = $required;
					update_option( 'mhk_validation_error', 'yes' );
				}
			} else {
				if ( empty( $field_submit['primary'] ) && '0' !== $field_submit ) {
					mhk()->task->errors[ $form_id ][ $field_id ]['primary'] = $required;
					update_option( 'mhk_validation_error', 'yes' );
				}

				if ( empty( $field_submit['secondary'] ) && '0' !== $field_submit ) {
					mhk()->task->errors[ $form_id ][ $field_id ]['secondary'] = $required;
					update_option( 'mhk_validation_error', 'yes' );
				}
			}
		}

		if ( ! is_array( $field_submit ) && ! empty( $field_submit ) ) {
			$field_submit = array(
				'primary' => $field_submit,
			);
		}

		if ( ! empty( $field_submit['primary'] ) && ! is_email( $field_submit['primary'] ) ) {
			$invalid_email = esc_html__( 'Please enter a valid email address.', 'muhiku-plug' );
			if ( empty( $form_data['form_fields'][ $field_id ]['confirmation'] ) ) {
				mhk()->task->errors[ $form_id ][ $field_id ] = $invalid_email;
			} else {
				mhk()->task->errors[ $form_id ][ $field_id ]['primary'] = $invalid_email;
			}
			update_option( 'mhk_validation_error', 'yes' );
		} elseif ( isset( $field_submit['primary'], $field_submit['secondary'] ) && $field_submit['secondary'] !== $field_submit['primary'] ) {
			mhk()->task->errors[ $form_id ][ $field_id ]['secondary'] = esc_html__( 'Confirmation Email do not match.', 'muhiku-plug' );
			update_option( 'mhk_validation_error', 'yes' );
		}

		do_action( 'muhiku_forms_email_validation', $field_id, $field_submit, $form_data );
	}

	/**
	 * @param int    $field_id    
	 * @param array  $field_submit 
	 * @param array  $form_data    
	 * @param string $meta_key    
	 */
	public function format( $field_id, $field_submit, $form_data, $meta_key ) {
		if ( is_array( $field_submit ) ) {
			$value = ! empty( $field_submit['primary'] ) ? $field_submit['primary'] : '';
		} else {
			$value = ! empty( $field_submit ) ? $field_submit : '';
		}

		$name = ! empty( $form_data['form_fields'][ $field_id ]['label'] ) ? $form_data['form_fields'][ $field_id ]['label'] : '';

		mhk()->task->form_fields[ $field_id ] = array(
			'name'     => make_clickable( $name ),
			'value'    => sanitize_text_field( $value ),
			'id'       => $field_id,
			'type'     => $this->type,
			'meta_key' => $meta_key,
		);
	}
}

<?php
/**
 * @package MuhikuPlug\Classes\Fields
 */

defined( 'ABSPATH' ) || exit;

class MHK_Fields {

	/**
	 * @var array
	 */
	public $form_fields = array();

	/**
	 * @var MHK_Fields
	 */
	protected static $instance = null;

	/**
	 * @return MHK_Fields Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __clone() {
		mhk_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'muhiku-plug' ), '1.2.0' );
	}

	public function __wakeup() {
		mhk_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'muhiku-plug' ), '1.2.0' );
	}

	public function __construct() {
		$this->init();
	}

	public function init() {
		$load_fields = apply_filters(
			'muhiku_forms_fields',
			array(
				'MHK_Field_First_Name',
				'MHK_Field_Last_Name',
				'MHK_Field_Text',
				'MHK_Field_Textarea',
				'MHK_Field_Select',
				'MHK_Field_Radio',
				'MHK_Field_Checkbox',
				'MHK_Field_Email',
				'MHK_Field_URL',
			)
		);

		$order_end = 999;

		foreach ( $load_fields as $field ) {
			$load_field = is_string( $field ) ? new $field() : $field;

			if ( isset( $load_field->order ) && is_numeric( $load_field->order ) ) {
				$this->form_fields[ $load_field->group ][ $load_field->order ] = $load_field;
			} else {
				$this->form_fields[ $load_field->group ][ $order_end ] = $load_field;
				$order_end++;
			}

			ksort( $this->form_fields[ $load_field->group ] );
		}
	}

	/**
	 * @return array
	 */
	public function form_fields() {
		$_available_fields = array();

		if ( count( $this->form_fields ) > 0 ) {
			foreach ( $this->form_fields as $group => $field ) {
				$_available_fields[ $group ] = $field;
			}
		}

		return $_available_fields;
	}

	/**
	 * @return array of strings
	 */
	public function get_form_field_types() {
		$_available_fields = array();

		if ( count( $this->form_fields ) > 0 ) {
			foreach ( array_values( $this->form_fields ) as $form_field ) {
				foreach ( $form_field as $field ) {
					$_available_fields[] = $field->type;
				}
			}
		}

		return $_available_fields;
	}

	/**
	 * @return array of strings
	 */
	public function get_pro_form_field_types() {
		$_available_fields = array();

		if ( count( $this->form_fields ) > 0 ) {
			foreach ( array_values( $this->form_fields ) as $form_field ) {
				foreach ( $form_field as $field ) {
					if ( $field->is_pro ) {
						$_available_fields[] = $field->type;
					}
				}
			}
		}

		return $_available_fields;
	}
}

<?php
/**
 *  Password field.
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Password Class.
 */
class EVF_Field_Password extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Password', 'muhiku-plug' );
		$this->type   = 'password';
		$this->icon   = 'mhk-icon mhk-icon-password';
		$this->order  = 70;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}

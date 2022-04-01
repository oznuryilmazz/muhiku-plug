<?php
/**
 * Privacy Policy field
 *
 * @package MuhikuPlug\Fields
 * @since   1.7.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Privacy_Policy Class.
 */
class EVF_Field_Privacy_Policy extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Privacy Policy', 'muhiku-plug' );
		$this->type   = 'privacy-policy';
		$this->icon   = 'mhk-icon mhk-icon-privacy-policy';
		$this->order  = 150;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}

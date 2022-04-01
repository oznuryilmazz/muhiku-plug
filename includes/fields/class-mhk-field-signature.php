<?php
/**
 *  Signature field.
 *
 * @package MuhikuPlug\Fields
 * @since   1.4.7
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_Signature Class.
 */
class MHK_Field_Signature extends MHK_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Signature', 'muhiku-plug' );
		$this->type   = 'signature';
		$this->icon   = 'mhk-icon mhk-icon-signature';
		$this->order  = 100;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}

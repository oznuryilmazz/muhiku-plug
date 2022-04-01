<?php
/**
 * Payment checkbox field
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_Payment_Checkbox Class.
 */
class MHK_Field_Payment_Checkbox extends MHK_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Checkboxes', 'muhiku-plug' );
		$this->type   = 'payment-checkbox';
		$this->icon   = 'mhk-icon mhk-icon-checkbox';
		$this->order  = 30;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}

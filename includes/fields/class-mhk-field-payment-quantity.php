<?php
/**
 * Payment Quantity field
 *
 * @package MuhikuPlug\Fields
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Quantity Class.
 */
class EVF_Field_Payment_Quantity extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Quantity', 'muhiku-plug' );
		$this->type   = 'payment-quantity';
		$this->icon   = 'mhk-icon mhk-icon-single-item';
		$this->order  = 40;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}

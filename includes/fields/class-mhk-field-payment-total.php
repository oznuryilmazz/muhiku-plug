<?php
/**
 * Payment Total field
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Total Class.
 */
class EVF_Field_Payment_Total extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Total', 'muhiku-plug' );
		$this->type   = 'payment-total';
		$this->icon   = 'mhk-icon mhk-icon-total';
		$this->order  = 60;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}

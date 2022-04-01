<?php
/**
 * Payment Radio field
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Payment_Radio Class.
 */
class EVF_Field_Payment_Radio extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Multiple Choice', 'muhiku-plug' );
		$this->type   = 'payment-multiple';
		$this->icon   = 'mhk-icon mhk-icon-multiple-choices';
		$this->order  = 20;
		$this->group  = 'payment';
		$this->is_pro = true;

		parent::__construct();
	}
}

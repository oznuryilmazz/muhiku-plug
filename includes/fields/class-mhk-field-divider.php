<?php
/**
 * Divider field.
 *
 * @package MuhikuPlug\Fields
 * @since   1.7.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Divider Class.
 */
class EVF_Field_Divider extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Divider', 'muhiku-plug' );
		$this->type   = 'divider';
		$this->icon   = 'mhk-icon mhk-icon-divider';
		$this->order  = 85;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}
<?php
/**
 * Country field
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Country Class.
 */
class EVF_Field_Country extends EVF_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Country', 'muhiku-plug' );
		$this->type   = 'country';
		$this->icon   = 'mhk-icon mhk-icon-flag';
		$this->order  = 120;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}

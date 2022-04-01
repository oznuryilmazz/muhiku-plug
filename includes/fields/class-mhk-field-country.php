<?php
/**
 * Country field
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_Country Class.
 */
class MHK_Field_Country extends MHK_Form_Fields {

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

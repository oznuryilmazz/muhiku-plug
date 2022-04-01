<?php
/**
 * HTML block text field
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_HTML Class.
 */
class MHK_Field_HTML extends MHK_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Custom HTML', 'muhiku-plug' );
		$this->type   = 'html';
		$this->icon   = 'mhk-icon mhk-icon-custom-html';
		$this->order  = 80;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}

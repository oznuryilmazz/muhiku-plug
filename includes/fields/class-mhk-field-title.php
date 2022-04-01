<?php
/**
 * Section Title field.
 *
 * @package MuhikuPlug\Fields
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_Title Class.
 */
class MHK_Field_Title extends MHK_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Section Title', 'muhiku-plug' );
		$this->type   = 'title';
		$this->icon   = 'mhk-icon mhk-icon-section-divider';
		$this->order  = 90;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}

<?php
/**
 * Likert field
 *
 * @package MuhikuPlug\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_Likert Class.
 */
class MHK_Field_Likert extends MHK_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Likert', 'muhiku-plug' );
		$this->type   = 'likert';
		$this->icon   = 'mhk-icon mhk-icon-likert';
		$this->order  = 20;
		$this->group  = 'survey';
		$this->is_pro = true;

		parent::__construct();
	}
}

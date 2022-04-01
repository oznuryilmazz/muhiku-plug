<?php
/**
 * Likert field
 *
 * @package MuhikuPlug\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Field_Likert Class.
 */
class EVF_Field_Likert extends EVF_Form_Fields {

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

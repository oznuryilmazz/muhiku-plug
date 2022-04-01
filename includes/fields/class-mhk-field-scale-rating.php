<?php
/**
 * Score Rating field
 *
 * @package MuhikuPlug\Fields
 * @since   1.4.3
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_Scale_Rating Class.
 */
class MHK_Field_Scale_Rating extends MHK_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Scale Rating', 'muhiku-plug' );
		$this->type   = 'scale-rating';
		$this->icon   = 'mhk-icon mhk-icon-scale-rating';
		$this->order  = 30;
		$this->group  = 'survey';
		$this->is_pro = true;

		parent::__construct();
	}
}

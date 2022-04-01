<?php
/**
 * Captcha field
 *
 * @package MuhikuPlug\Fields
 * @since   1.6.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Field_Captcha Class.
 */
class MHK_Field_Captcha extends MHK_Form_Fields {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->name   = esc_html__( 'Captcha', 'muhiku-plug' );
		$this->type   = 'captcha';
		$this->icon   = 'mhk-icon mhk-icon-captcha';
		$this->order  = 160;
		$this->group  = 'advanced';
		$this->is_pro = true;

		parent::__construct();
	}
}

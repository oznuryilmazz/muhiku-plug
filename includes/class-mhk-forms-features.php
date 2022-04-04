<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

class MHK_Forms_Features {

	public function __construct() {
		add_filter( 'muhiku_forms_fields', array( $this, 'form_fields' ) );
	}

	/**
	 * @param  array $fields Registered form fields.
	 * @return array
	 */
	public function form_fields( $fields ) {
		$pro_fields = array(
		);

		return array_merge( $fields, $pro_fields );
	}
}

new MHK_Forms_Features();

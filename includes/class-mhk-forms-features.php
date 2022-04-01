<?php
/**
 * MuhikuPlug features
 *
 * @package MuhikuPlug\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Features Class.
 */
class MHK_Forms_Features {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'muhiku_forms_fields', array( $this, 'form_fields' ) );
	}

	/**
	 * Load additional fields available in the Pro version.
	 *
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

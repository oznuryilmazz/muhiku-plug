<?php
/**
 * @package MuhikuPlug/Admin
 */

defined( 'ABSPATH' ) || exit;

class MHK_Plugin_Updates {

	/**
	 * @var string
	 */
	const VERSION_REQUIRED_HEADER = 'MHK requires at least';

	/**
	 * @var string
	 */
	const VERSION_TESTED_HEADER = 'MHK tested up to';

	/**
	 * @param string $header 
	 * @return array Array of plugins that contain the searched header.
	 */
	protected function get_plugins_with_header( $header ) {
		$plugins = get_plugins();
		$matches = array();

		foreach ( $plugins as $file => $plugin ) {
			if ( ! empty( $plugin[ $header ] ) ) {
				$matches[ $file ] = $plugin;
			}
		}

		return apply_filters( 'muhiku_forms_get_plugins_with_header', $matches, $header, $plugins );
	}
}

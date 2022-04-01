<?php
/**
 * Class for displaying plugin warning notifications and determining 3rd party plugin compatibility.
 *
 * @package MuhikuPlug/Admin
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MHK_Plugin_Updates Class.
 */
class MHK_Plugin_Updates {

	/**
	 * This is the header used by extensions to show requirements.
	 *
	 * @var string
	 */
	const VERSION_REQUIRED_HEADER = 'MHK requires at least';

	/**
	 * This is the header used by extensions to show testing.
	 *
	 * @var string
	 */
	const VERSION_TESTED_HEADER = 'MHK tested up to';

	/*
	|--------------------------------------------------------------------------
	| Data Helpers
	|--------------------------------------------------------------------------
	|
	| Methods for getting & manipulating data.
	*/

	/**
	 * Get plugins that have a valid value for a specific header.
	 *
	 * @param string $header Plugin header to search for.
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

<?php
/**
 * MuhikuPlug Integrations class
 *
 * Loads Integrations into MuhikuPlug.
 *
 * @package MuhikuPlug/Classes/Integrations
 * @version 1.2.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integrations class.
 */
class MHK_Integrations {

	/**
	 * Array of integrations.
	 *
	 * @var array
	 */
	public $integrations = array();

	/**
	 * Initialize integrations.
	 */
	public function __construct() {

		do_action( 'muhiku_forms_integrations_init' );

		$load_integrations = apply_filters( 'muhiku_forms_integrations', array() );

		// Load integration classes.
		foreach ( $load_integrations as $integration ) {

			$load_integration = new $integration();

			$this->integrations[ $load_integration->id ] = $load_integration;
		}
	}

	/**
	 * Return loaded integrations.
	 *
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}
}

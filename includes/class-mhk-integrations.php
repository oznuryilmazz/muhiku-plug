<?php
/**
 * @package MuhikuPlug/Classes/Integrations
 */

defined( 'ABSPATH' ) || exit;

class MHK_Integrations {

	/**
	 * @var array
	 */
	public $integrations = array();

	public function __construct() {

		do_action( 'muhiku_forms_integrations_init' );

		$load_integrations = apply_filters( 'muhiku_forms_integrations', array() );

		foreach ( $load_integrations as $integration ) {

			$load_integration = new $integration();

			$this->integrations[ $load_integration->id ] = $load_integration;
		}
	}

	/**
	 * @return array
	 */
	public function get_integrations() {
		return $this->integrations;
	}
}

<?php
/**
 * @package MuhikuPlug\Abstracts
 */

defined( 'ABSPATH' ) || exit;

abstract class MHK_Integration extends MHK_Settings_API {

	/**
	 * @var string
	 */
	public $enabled = 'yes';

	/**
	 * @var string
	 */
	public $icon = '';

	/**
	 * @var string
	 */
	public $method_title = '';

	/**
	 * @var string
	 */
	public $method_description = '';

	/**
	 * @return array Integration stored data.
	 */
	public function get_integration() {
		$integrations = get_option( 'muhiku_forms_integrations', array() );

		return in_array( $this->id, array_keys( $integrations ), true ) ? $integrations[ $this->id ] : array();
	}

	/**
	 * @return string
	 */
	public function get_method_title() {
		return apply_filters( 'muhiku_forms_integration_title', $this->method_title, $this );
	}

	/**
	 * @return string
	 */
	public function get_method_description() {
		return apply_filters( 'muhiku_forms_integration_description', $this->method_description, $this );
	}

	public function admin_options() {
		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );
		echo '<div><input type="hidden" name="section" value="' . esc_attr( $this->id ) . '" /></div>';
		parent::admin_options();
	}

	public function init_settings() {
		parent::init_settings();
		$this->enabled = ! empty( $this->settings['enabled'] ) && 'yes' === $this->settings['enabled'] ? 'yes' : 'no';
	}

	/**
	 * @return bool
	 */
	public function is_integration_page() {
		return isset( $_GET['page'], $_GET['tab'], $_GET['section'] ) && 'mhk-settings' === $_GET['page'] && 'integration' === $_GET['tab'] && (string) $this->id === $_GET['section'];  
	}
}

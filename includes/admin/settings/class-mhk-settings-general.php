<?php
/**
 * MuhikuPlug General Settings
 *
 * @package MuhikuPlug\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Settings_General', false ) ) {
	return new MHK_Settings_General();
}

/**
 * MHK_Settings_General.
 */
class MHK_Settings_General extends MHK_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'general';
		$this->label = esc_html__( 'General', 'muhiku-plug' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'muhiku_forms_general_settings',
			array(
				array(
					'title' => esc_html__( 'General Options', 'muhiku-plug' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'general_options',
				),
				array(
					'title'   => esc_html__( 'Disable User Details', 'muhiku-plug' ),
					'desc'    => esc_html__( 'Disable storing the IP address and User Agent on all forms.', 'muhiku-plug' ),
					'id'      => 'muhiku_forms_disable_user_details',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'title'   => esc_html__( 'Enable Log', 'muhiku-plug' ),
					'desc'    => esc_html__( 'Enable storing the logs.', 'muhiku-plug' ),
					'id'      => 'muhiku_forms_enable_log',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'type' => 'sectionend',
					'id'   => 'general_options',
				),
			)
		);

		return apply_filters( 'muhiku_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		MHK_Admin_Settings::save_fields( $settings );
	}
}

return new MHK_Settings_General();

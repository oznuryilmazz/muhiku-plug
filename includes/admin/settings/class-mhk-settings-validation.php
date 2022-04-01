<?php
/**
 * MuhikuPlug Validation Settings
 *
 * @package MuhikuPlug\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Settings_Validation', false ) ) {
	return new MHK_Settings_Validation();
}

/**
 * MHK_Settings_Validation.
 */
class MHK_Settings_Validation extends MHK_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'validation';
		$this->label = __( 'Validations', 'muhiku-plug' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'everest_forms_validation_settings',
			array(
				array(
					'title' => esc_html__( 'Validation Messages', 'muhiku-plug' ),
					'type'  => 'title',
					'desc'  => 'Validation Messages for Form Fields.',
					'id'    => 'validation_options',
				),
				array(
					'title'    => esc_html__( 'Required', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enter the message for the required form field', 'muhiku-plug' ),
					'id'       => 'everest_forms_required_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'This field is required.', 'muhiku-plug' ),
				),
				array(
					'title'    => esc_html__( 'Website URL', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enter the message for the valid website url', 'muhiku-plug' ),
					'id'       => 'everest_forms_url_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Please enter a valid URL.', 'muhiku-plug' ),
				),
				array(
					'title'    => esc_html__( 'Email', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enter the message for the valid email', 'muhiku-plug' ),
					'id'       => 'everest_forms_email_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Please enter a valid email address.', 'muhiku-plug' ),
				),
				array(
					'title'    => esc_html__( 'Email Suggestion', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enter the message for the valid email suggestion', 'muhiku-plug' ),
					'id'       => 'everest_forms_email_suggestion',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Did you mean {suggestion}?', 'muhiku-plug' ),
				),
				array(
					'title'    => esc_html__( 'Confirm Value', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enter the message for confirm field value.', 'muhiku-plug' ),
					'id'       => 'everest_forms_confirm_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Field values do not match.', 'muhiku-plug' ),
				),
				array(
					'title'    => esc_html__( 'Checkbox Selection Limit', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enter the message for the checkbox selection limit.', 'muhiku-plug' ),
					'id'       => 'everest_forms_check_limit_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'You have exceeded number of allowed selections: {#}.', 'muhiku-plug' ),
				),
				array(
					'title'    => esc_html__( 'Number', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enter the message for the valid number', 'muhiku-plug' ),
					'id'       => 'everest_forms_number_validation',
					'type'     => 'text',
					'desc_tip' => true,
					'css'      => 'min-width: 350px;',
					'default'  => esc_html__( 'Please enter a valid number.', 'muhiku-plug' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'validation_options',
				),
			)
		);

		return apply_filters( 'everest_forms_get_settings_' . $this->id, $settings );
	}

	/**
	 * Save settings.
	 */
	public function save() {
		$settings = $this->get_settings();

		MHK_Admin_Settings::save_fields( $settings );
	}
}

return new MHK_Settings_Validation();

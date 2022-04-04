<?php
/**
 * MuhikuPlug Email Settings
 *
 * @package MuhikuPlug\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Settings_Email', false ) ) {
	return new MHK_Settings_Email();
}

/**
 * MHK_Settings_Email.
 */
class MHK_Settings_Email extends MHK_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'email';
		$this->label = esc_html__( 'Email', 'muhiku-plug' );

		parent::__construct();
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = apply_filters(
			'muhiku_forms_email_settings',
			array(
				array(
					'title' => esc_html__( 'Template Settings', 'muhiku-plug' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'email_template_options',
				),
				array(
					'title'   => esc_html__( 'Template', 'muhiku-plug' ),
					'type'    => 'radio-image',
					'id'      => 'muhiku_forms_email_template',
					'desc'    => esc_html__( 'Determine which format of email to send. HTML Template is default.', 'muhiku-plug' ),
					'default' => 'default',
					'options' => array(
						'default' => array(
							'name'  => esc_html__( 'HTML Template', 'muhiku-plug' ),
							'image' => plugins_url( 'assets/images/email-template-html.png', MHK_PLUGIN_FILE ),
						),
						'none'    => array(
							'name'  => esc_html__( 'Plain text', 'muhiku-plug' ),
							'image' => plugins_url( 'assets/images/email-template-plain.png', MHK_PLUGIN_FILE ),
						),
					),
				),
				array(
					'title'    => esc_html__( 'Enable copies', 'muhiku-plug' ),
					'desc'     => esc_html__( 'Enable the use of Cc and Bcc email addresses', 'muhiku-plug' ),
					'desc_tip' => esc_html__( 'Email addresses for Cc and Bcc can be applied from the form notification settings.', 'muhiku-plug' ),
					'id'       => 'muhiku_forms_enable_email_copies',
					'default'  => 'no',
					'type'     => 'checkbox',
				),
				array(
					'title'       => esc_html__( 'Send Test Email To', 'muhiku-plug' ),
					'desc'        => esc_html__( 'Enter email address where test email will be sent.', 'muhiku-plug' ),
					'id'          => 'muhiku_forms_email_send_to',
					'type'        => 'email',
					'placeholder' => 'eg. testemail@gmail.com',
					'value'       => esc_attr( get_bloginfo( 'admin_email' ) ),
					'desc_tip'    => true,
				),
				array(
					'title'    => __( 'Send Test Email', 'muhiku-plug' ),
					'desc'     => __( 'Click to send test email.', 'muhiku-plug' ),
					'id'       => 'muhiku_forms_email_test',
					'type'     => 'link',
					'buttons'  => array(
						array(
							'title' => __( 'Send Test Email', 'muhiku-plug' ),
							'href'  => 'javascript:;',
							'class' => 'muhiku_forms_send_email_test',
						),
					),
					'desc_tip' => true,
				),
				array(
					'type' => 'sectionend',
					'id'   => 'email_template_options',
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

return new MHK_Settings_Email();

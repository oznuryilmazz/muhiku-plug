<?php
/**
 * MuhikuPlug Builder Settings
 *
 * @package MuhikuPlug\Admin
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Builder_Settings', false ) ) {
	return new MHK_Builder_Settings();
}

/**
 * MHK_Builder_Settings class.
 */
class MHK_Builder_Settings extends MHK_Builder_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id      = 'settings';
		$this->label   = esc_html__( 'Settings', 'muhiku-plug' );
		$this->sidebar = true;

		add_action( 'everest_forms_settings_connections_email', array( $this, 'output_connections_list' ) );

		parent::__construct();
	}

	/**
	 * Outputs the builder sidebar.
	 */
	public function output_sidebar() {
		$sections = apply_filters(
			'everest_forms_builder_settings_section',
			array(
				'general' => esc_html__( 'General', 'muhiku-plug' ),
				'email'   => esc_html__( 'Email', 'muhiku-plug' ),
			),
			$this->form_data
		);

		if ( ! empty( $sections ) ) {
			foreach ( $sections as $slug => $section ) {
				$this->add_sidebar_tab( $section, $slug );
				do_action( 'everest_forms_settings_connections_' . $slug, $section );
			}
		}
	}

	/**
	 * Get form data
	 *
	 * @return array form data.
	 */
	private function form_data() {
		$form_data = array();

		if ( ! empty( $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$form_data = mhk()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		return $form_data;
	}

	/**
	 * Outputs the connection lists on sidebar.
	 */
	public function output_connections_list() {
		$form_data = $this->form_data();
		$email     = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();

		if ( empty( $email ) ) {
			$email['connection_1'] = array( 'connection_name' => __( 'Admin Notification', 'muhiku-plug' ) );
		}

		?>
			<div class="muhiku-plug-active-email">
				<button class="muhiku-plug-btn muhiku-plug-btn-primary muhiku-plug-email-add" data-form_id="<?php echo isset( $_GET['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification ?>" data-source="email" data-type="<?php echo esc_attr( 'connection' ); ?>">
					<?php printf( esc_html__( 'Add New Email', 'muhiku-plug' ) ); ?>
				</button>
					<ul class="muhiku-plug-active-email-connections-list">
					<?php if ( ! empty( $email ) ) { ?>
						<h4><?php echo esc_html__( 'Email Notifications', 'muhiku-plug' ); ?> </h4>
						<?php
					}
					if ( ! empty( $email ) ) {
						foreach ( $email as $connection_id => $connection_data ) {
							if ( preg_match( '/connection_/', $connection_id ) ) {
								$connection_name = ! empty( $connection_data['connection_name'] ) ? $connection_data['connection_name'] : '';
								if ( 'connection_1' !== $connection_id ) {
									$remove_class = 'email-remove';
								} else {
									$remove_class = 'email-default-remove';
								}
								?>
									<li class="connection-list" data-connection-id="<?php echo esc_attr( $connection_id ); ?>">
										<a class="user-nickname" href="#"><?php echo esc_html( $connection_name ); ?></a>
										<a href="#"><span class="<?php echo esc_attr( $remove_class ); ?>"><?php esc_html_e( 'Remove', 'muhiku-plug' ); ?></a>
									</li>
								<?php
							}
						}
					}
					?>
					</ul>
			</div>
			<?php
	}

	/**
	 * Outputs the builder content.
	 */
	public function output_content() {
		$settings = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();

		// --------------------------------------------------------------------//
		// General
		// --------------------------------------------------------------------//
		echo '<div class="mhk-content-section mhk-content-general-settings">';
		echo '<div class="mhk-content-section-title">';
		esc_html_e( 'General', 'muhiku-plug' );
		echo '</div>';
		everest_forms_panel_field(
			'text',
			'settings',
			'form_title',
			$this->form_data,
			esc_html__( 'Form Name', 'muhiku-plug' ),
			array(
				'default' => isset( $this->form->post_title ) ? $this->form->post_title : '',
				'tooltip' => esc_html__( 'Give a name to this form', 'muhiku-plug' ),
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'form_description',
			$this->form_data,
			esc_html__( 'Form description', 'muhiku-plug' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->form_description ) ? $this->form->form_description : '',
				'tooltip'     => sprintf( esc_html__( 'Give the description to this form', 'muhiku-plug' ) ),
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'form_disable_message',
			$this->form_data,
			esc_html__( 'Form disabled message', 'muhiku-plug' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->form_disable_message ) ? $this->form->form_disable_message : __( 'This form is disabled.', 'muhiku-plug' ),
				'tooltip'     => sprintf( esc_html__( 'Message that shows up if the form is disabled.', 'muhiku-plug' ) ),
			)
		);
		everest_forms_panel_field(
			'textarea',
			'settings',
			'successful_form_submission_message',
			$this->form_data,
			esc_html__( 'Successful form submission message', 'muhiku-plug' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->successful_form_submission_message ) ? $this->form->successful_form_submission_message : __( 'Thanks for contacting us! We will be in touch with you shortly', 'muhiku-plug' ),
				/* translators: %1$s - general settings docs url */
				'tooltip'     => sprintf( esc_html__( 'Success message that shows up after submitting form. <a href="%1$s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/general-settings/#successful-form-submission-message' ) ),
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'submission_message_scroll',
			$this->form_data,
			__( 'Automatically scroll to the submission message', 'muhiku-plug' ),
			array(
				'default' => '1',
			)
		);

		echo '<div class="muhiku-plug-border-container"><h4 class="muhiku-plug-border-container-title">' . esc_html__( 'Submission Redirection', 'muhiku-plug' ) . '</h4>';

		everest_forms_panel_field(
			'select',
			'settings',
			'redirect_to',
			$this->form_data,
			esc_html__( 'Redirect To', 'muhiku-plug' ),
			array(
				'default' => 'same',
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Choose where to redirect after form submission. <a href="%s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/general-settings/#redirect-to' ) ),
				'options' => array(
					'same'         => esc_html__( 'Same Page', 'muhiku-plug' ),
					'custom_page'  => esc_html__( 'Custom Page', 'muhiku-plug' ),
					'external_url' => esc_html__( 'External URL', 'muhiku-plug' ),
				),
			)
		);

		everest_forms_panel_field(
			'select',
			'settings',
			'custom_page',
			$this->form_data,
			esc_html__( 'Custom Page', 'muhiku-plug' ),
			array(
				'default' => '0',
				'options' => $this->get_all_pages(),
			)
		);

		everest_forms_panel_field(
			'text',
			'settings',
			'external_url',
			$this->form_data,
			esc_html__( 'External URL', 'muhiku-plug' ),
			array(
				'default' => isset( $this->form->external_url ) ? $this->form->external_url : '',
			)
		);

		do_action( 'everest_forms_submission_redirection_settings', $this, 'submission_redirection' );

		echo '</div>';

		everest_forms_panel_field(
			'select',
			'settings',
			'layout_class',
			$this->form_data,
			esc_html__( 'Layout Design', 'muhiku-plug' ),
			array(
				'default' => '0',
				'tooltip' => esc_html__( 'Choose design template for the Form', 'muhiku-plug' ),
				'options' => array(
					'default'    => esc_html__( 'Default', 'muhiku-plug' ),
					'layout-two' => esc_html__( 'Classic Layout', 'muhiku-plug' ),
				),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'form_class',
			$this->form_data,
			esc_html__( 'Form Class', 'muhiku-plug' ),
			array(
				'default' => isset( $this->form->form_class ) ? $this->form->form_class : '',
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Enter CSS class names for the form wrapper. Multiple class names should be separated with spaces. <a href="%s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/general-settings/#form-class' ) ),
			)
		);

		do_action( 'everest_forms_field_required_indicators', $this->form_data, $settings );

		echo '<div class="muhiku-plug-border-container"><h4 class="muhiku-plug-border-container-title">' . esc_html__( 'Submit Button', 'muhiku-plug' ) . '</h4>';
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_text',
			$this->form_data,
			esc_html__( 'Submit button text', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'muhiku-plug' ),
				'tooltip' => esc_html__( 'Enter desired text for submit button.', 'muhiku-plug' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_processing_text',
			$this->form_data,
			__( 'Submit button processing text', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['submit_button_processing_text'] ) ? $settings['submit_button_processing_text'] : __( 'Processing&hellip;', 'muhiku-plug' ),
				'tooltip' => esc_html__( 'Enter the submit button text that you would like the button to display while the form submission is processing.', 'muhiku-plug' ),
			)
		);
		everest_forms_panel_field(
			'text',
			'settings',
			'submit_button_class',
			$this->form_data,
			esc_html__( 'Submit button class', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['submit_button_class'] ) ? $settings['submit_button_class'] : '',
				'tooltip' => esc_html__( 'Enter CSS class names for submit button. Multiple class names should be separated with spaces.', 'muhiku-plug' ),
			)
		);
		do_action( 'everest_forms_inline_submit_settings', $this, 'submit', 'connection_1' );
		echo '</div>';
		do_action( 'everest_forms_inline_integrations_settings', $this->form_data, $settings );
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'honeypot',
			$this->form_data,
			esc_html__( 'Enable anti-spam honeypot', 'muhiku-plug' ),
			array(
				'default' => '1',
			)
		);
		$recaptcha_type   = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$recaptcha_key    = get_option( 'everest_forms_recaptcha_' . $recaptcha_type . '_site_key' );
		$recaptcha_secret = get_option( 'everest_forms_recaptcha_' . $recaptcha_type . '_secret_key' );
		switch ( $recaptcha_type ) {
			case 'v2':
				$recaptcha_label = esc_html__( 'Enable Google Invisible reCAPTCHA v2', 'muhiku-plug' );
				break;

			case 'v3':
				$recaptcha_label = esc_html__( 'Enable Google reCAPTCHA v3', 'muhiku-plug' );
				break;

			case 'hcaptcha':
				$recaptcha_label = esc_html__( 'Enable hCaptcha', 'muhiku-plug' );
				break;
		}
		$recaptcha_label = 'yes' === get_option( 'everest_forms_recaptcha_v2_invisible' ) && 'v2' === $recaptcha_type ? esc_html__( 'Enable Google Invisible reCAPTCHA v2', 'muhiku-plug' ) : $recaptcha_label;

		if ( ! empty( $recaptcha_key ) && ! empty( $recaptcha_secret ) ) {
			everest_forms_panel_field(
				'checkbox',
				'settings',
				'recaptcha_support',
				$this->form_data,
				$recaptcha_label,
				array(
					'default' => '0',
					/* translators: %1$s - general settings docs url */
					'tooltip' => sprintf( esc_html__( 'Enable reCaptcha. Make sure the site key and secret key is set in settings page. <a href="%s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/general-settings/#enable-recaptcha-support' ) ),
				)
			);
		}
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'ajax_form_submission',
			$this->form_data,
			esc_html__( 'Enable Ajax Form Submission', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['ajax_form_submission'] ) ? $settings['ajax_form_submission'] : 0,
				'tooltip' => esc_html__( 'Enables form submission without reloading the page.', 'muhiku-plug' ),
			)
		);
		everest_forms_panel_field(
			'checkbox',
			'settings',
			'disabled_entries',
			$this->form_data,
			esc_html__( 'Disable storing entry information', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['disabled_entries'] ) ? $settings['disabled_entries'] : 0,
				/* translators: %1$s - general settings docs url */
				'tooltip' => sprintf( esc_html__( 'Disable storing form entries. <a href="%1$s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/general-settings/#disable-storing-entry-information' ) ),
			)
		);

		do_action( 'everest_forms_inline_general_settings', $this );

		echo '</div>';

		// --------------------------------------------------------------------//
		// Email
		// --------------------------------------------------------------------//
		$form_name = isset( $settings['form_title'] ) ? ' - ' . $settings['form_title'] : '';
		if ( ! isset( $settings['email']['connection_1'] ) ) {
			$settings['email']['connection_1']                   = array( 'connection_name' => __( 'Admin Notification', 'muhiku-plug' ) );
			$settings['email']['connection_1']['mhk_to_email']   = isset( $settings['email']['mhk_to_email'] ) ? $settings['email']['mhk_to_email'] : '{admin_email}';
			$settings['email']['connection_1']['mhk_from_name']  = isset( $settings['email']['mhk_from_name'] ) ? $settings['email']['mhk_from_name'] : get_bloginfo( 'name', 'display' );
			$settings['email']['connection_1']['mhk_from_email'] = isset( $settings['email']['mhk_from_email'] ) ? $settings['email']['mhk_from_email'] : '{admin_email}';
			$settings['email']['connection_1']['mhk_reply_to']   = isset( $settings['email']['mhk_reply_to'] ) ? $settings['email']['mhk_reply_to'] : '';
			/* translators: %s: Form Name */
			$settings['email']['connection_1']['mhk_email_subject'] = isset( $settings['email']['mhk_email_subject'] ) ? $settings['email']['mhk_email_subject'] : sprintf( esc_html__( 'New Form Entry %s', 'muhiku-plug' ), $form_name );
			$settings['email']['connection_1']['mhk_email_message'] = isset( $settings['email']['mhk_email_message'] ) ? $settings['email']['mhk_email_message'] : '{all_fields}';

			$email_settings = array( 'attach_pdf_to_admin_email', 'show_header_in_attachment_pdf_file', 'conditional_logic_status', 'conditional_option', 'conditionals' );
			foreach ( $email_settings as $email_setting ) {
				$settings['email']['connection_1'][ $email_setting ] = isset( $settings['email'][ $email_setting ] ) ? $settings['email'][ $email_setting ] : '';
			}

			// Backward compatibility.
			$unique_connection_id = sprintf( 'connection_%s', uniqid() );
			if ( isset( $settings['email']['mhk_send_confirmation_email'] ) && '1' === $settings['email']['mhk_send_confirmation_email'] ) {
				$settings['email'][ $unique_connection_id ] = array( 'connection_name' => esc_html__( 'User Notification', 'muhiku-plug' ) );

				foreach ( $email_settings as $email_setting ) {
					$settings['email'][ $unique_connection_id ][ $email_setting ] = isset( $settings['email'][ $email_setting ] ) ? $settings['email'][ $email_setting ] : '';
				}
			}
		}

		echo "<div class = 'mhk-email-settings-wrapper'>";

		foreach ( $settings['email'] as $connection_id => $connection ) :
			if ( preg_match( '/connection_/', $connection_id ) ) {
				// Backward Compatibility.
				if ( isset( $settings['email']['enable_email_notification'] ) && '0' === $settings['email']['enable_email_notification'] ) {
					$email_status = isset( $settings['email']['enable_email_notification'] ) ? $settings['email']['enable_email_notification'] : '1';
				} else {
					$email_status = isset( $settings['email'][ $connection_id ]['enable_email_notification'] ) ? $settings['email'][ $connection_id ]['enable_email_notification'] : '1';
				}
				$hidden_class       = '1' !== $email_status ? 'muhiku-plug-hidden' : '';
				$toggler_hide_class = isset( $toggler_hide_class ) ? 'style=display:none;' : '';
				echo '<div class="mhk-content-section mhk-content-email-settings">';
				echo '<div class="mhk-content-section-title" ' . esc_attr( $toggler_hide_class ) . '>';
				echo '<div class="mhk-title">' . esc_html__( 'Email', 'muhiku-plug' ) . '</div>';
				?>
				<div class="mhk-toggle-section">
					<label class="mhk-toggle-switch">
						<input type="hidden" name="settings[email][<?php echo esc_attr( $connection_id ); ?>][enable_email_notification]" value="0" class="widefat">
						<input type="checkbox" name="settings[email][<?php echo esc_attr( $connection_id ); ?>][enable_email_notification]" value="1" data-connection-id="<?php echo esc_attr( $connection_id ); ?>" <?php echo checked( '1', $email_status, false ); ?> >
						<span class="mhk-toggle-switch-wrap"></span>
						<span class="mhk-toggle-switch-control"></span>
					</label>
				</div></div>
				<?php

				echo '<div class="mhk-content-email-settings-inner ' . esc_attr( $hidden_class ) . '" data-connection_id=' . esc_attr( $connection_id ) . '>';

				everest_forms_panel_field(
					'text',
					'email',
					'connection_name',
					$this->form_data,
					'',
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['connection_name'] ) ? $settings['email'][ $connection_id ]['connection_name'] : __( 'Admin Notification', 'muhiku-plug' ),
						'class'      => 'muhiku-plug-email-name',
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);

				everest_forms_panel_field(
					'text',
					'email',
					'mhk_to_email',
					$this->form_data,
					esc_html__( 'To Address', 'muhiku-plug' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['mhk_to_email'] ) ? $settings['email'][ $connection_id ]['mhk_to_email'] : '{admin_email}',
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the recipient\'s email address (comma separated) to receive form entry notifications. <a href="%s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/email-settings/#to-address' ) ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'email',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				if ( 'yes' === get_option( 'everest_forms_enable_email_copies' ) ) {
					everest_forms_panel_field(
						'text',
						'email',
						'mhk_carboncopy',
						$this->form_data,
						esc_html__( 'Cc Address', 'muhiku-plug' ),
						array(
							'default'    => isset( $settings['email'][ $connection_id ]['mhk_carboncopy'] ) ? $settings['email'][ $connection_id ]['mhk_carboncopy'] : '',
							'tooltip'    => esc_html__( 'Enter Cc recipient\'s email address (comma separated) to receive form entry notifications.', 'muhiku-plug' ),
							'smarttags'  => array(
								'type'        => 'fields',
								'form_fields' => 'email',
							),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);
					everest_forms_panel_field(
						'text',
						'email',
						'mhk_blindcarboncopy',
						$this->form_data,
						esc_html__( 'Bcc Address', 'muhiku-plug' ),
						array(
							'default'    => isset( $settings['email'][ $connection_id ]['mhk_blindcarboncopy'] ) ? $settings['email'][ $connection_id ]['mhk_blindcarboncopy'] : '',
							'tooltip'    => esc_html__( 'Enter Bcc recipient\'s email address (comma separated) to receive form entry notifications.', 'muhiku-plug' ),
							'smarttags'  => array(
								'type'        => 'fields',
								'form_fields' => 'email',
							),
							'parent'     => 'settings',
							'subsection' => $connection_id,
						)
					);
				}
				everest_forms_panel_field(
					'text',
					'email',
					'mhk_from_name',
					$this->form_data,
					esc_html__( 'From Name', 'muhiku-plug' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['mhk_from_name'] ) ? $settings['email'][ $connection_id ]['mhk_from_name'] : get_bloginfo( 'name', 'display' ),
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the From Name to be displayed in Email. <a href="%1$s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/email-settings/#from-name' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'text',
					'email',
					'mhk_from_email',
					$this->form_data,
					esc_html__( 'From Address', 'muhiku-plug' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['mhk_from_email'] ) ? $settings['email'][ $connection_id ]['mhk_from_email'] : '{admin_email}',
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the Email address from which you want to send Email. <a href="%s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/email-settings/#from-address' ) ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'email',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'text',
					'email',
					'mhk_reply_to',
					$this->form_data,
					esc_html__( 'Reply To', 'muhiku-plug' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['mhk_reply_to'] ) ? $settings['email'][ $connection_id ]['mhk_reply_to'] : '',
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the reply to email address where you want the email to be received when this email is replied. <a href="%1$s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/email-settings/#reply-to' ) ),
						'smarttags'  => array(
							'type'        => 'fields',
							'form_fields' => 'email',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'text',
					'email',
					'mhk_email_subject',
					$this->form_data,
					esc_html__( 'Email Subject', 'muhiku-plug' ),
					array(
						/* translators: %s: Form Name */
						'default'    => isset( $settings['email'][ $connection_id ]['mhk_email_subject'] ) ? $settings['email'][ $connection_id ]['mhk_email_subject'] : sprintf( esc_html__( 'New Form Entry %s', 'muhiku-plug' ), $form_name ),
						/* translators: %1$s - General Settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the subject of the email. <a href="%1$s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/email-settings/#email-subject' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
					)
				);
				everest_forms_panel_field(
					'tinymce',
					'email',
					'mhk_email_message',
					$this->form_data,
					esc_html__( 'Email Message', 'muhiku-plug' ),
					array(
						'default'    => isset( $settings['email'][ $connection_id ]['mhk_email_message'] ) ? $settings['email'][ $connection_id ]['mhk_email_message'] : __( '{all_fields}', 'muhiku-plug' ),
						/* translators: %1$s - general settings docs url */
						'tooltip'    => sprintf( esc_html__( 'Enter the message of the email. <a href="%1$s" target="_blank">Learn More</a>', 'muhiku-plug' ), esc_url( 'https://docs.wpeverest.com/docs/muhiku-plug/individual-form-settings/email-settings/#email-message' ) ),
						'smarttags'  => array(
							'type'        => 'all',
							'form_fields' => 'all',
						),
						'parent'     => 'settings',
						'subsection' => $connection_id,
						/* translators: %s - all fields smart tag. */
						'after'      => '<p class="desc">' . sprintf( esc_html__( 'To display all form fields, use the %s Smart Tag.', 'muhiku-plug' ), '<code>{all_fields}</code>' ) . '</p>',
					)
				);

				do_action( 'everest_forms_inline_email_settings', $this, $connection_id );

				echo '</div></div>';
			}

		endforeach;

		echo '</div>';
		do_action( 'everest_forms_settings_panel_content', $this );
	}

	/**
	 * Get all pages.
	 */
	public function get_all_pages() {
		$pages = array();
		foreach ( get_pages() as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}

		return $pages;
	}
}

return new MHK_Builder_Settings();

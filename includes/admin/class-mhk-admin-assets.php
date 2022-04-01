<?php
/**
 * Load assets
 *
 * @package MuhikuPlug/Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'EVF_Admin_Assets', false ) ) {
	return new EVF_Admin_Assets();
}

/**
 * EVF_Admin_Assets Class.
 */
class EVF_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'muhiku-plug-admin', mhk()->plugin_url() . '/assets/css/admin.css', array(), EVF_VERSION );
		wp_register_style( 'muhiku-plug-admin-menu', mhk()->plugin_url() . '/assets/css/menu.css', array(), EVF_VERSION );
		wp_register_style( 'jquery-ui-style', mhk()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), EVF_VERSION );
		wp_register_style( 'jquery-confirm', mhk()->plugin_url() . '/assets/css/jquery-confirm/jquery-confirm.min.css', array(), '3.3.0' );
		wp_register_style( 'perfect-scrollbar', mhk()->plugin_url() . '/assets/css/perfect-scrollbar/perfect-scrollbar.css', array(), '1.4.0' );
		wp_register_style( 'flatpickr', mhk()->plugin_url() . '/assets/css/flatpickr.css', array(), EVF_VERSION );

		// Add RTL support for admin styles.
		wp_style_add_data( 'muhiku-plug-admin', 'rtl', 'replace' );
		wp_style_add_data( 'muhiku-plug-admin-menu', 'rtl', 'replace' );

		// Sitewide menu CSS.
		wp_enqueue_style( 'muhiku-plug-admin-menu' );

		// Admin styles for EVF pages only.
		if ( in_array( $screen_id, mhk_get_screen_ids(), true ) ) {
			wp_enqueue_style( 'muhiku-plug-admin' );
			wp_enqueue_style( 'jquery-confirm' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'flatpickr' );

			if ( 'muhiku-plug_page_mhk-tools' !== $screen_id ) {
				wp_enqueue_style( 'perfect-scrollbar' );
			}
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		global $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts.
		wp_register_script( 'muhiku-plug-admin', mhk()->plugin_url() . '/assets/js/admin/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'tooltipster', 'wp-color-picker', 'perfect-scrollbar' ), EVF_VERSION, true );
		wp_register_script( 'muhiku-plug-extensions', mhk()->plugin_url() . '/assets/js/admin/extensions' . $suffix . '.js', array( 'jquery', 'updates' ), EVF_VERSION, true );
		wp_register_script( 'muhiku-plug-email-admin', mhk()->plugin_url() . '/assets/js/admin/mhk-admin-email' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'tooltipster', 'wp-color-picker', 'perfect-scrollbar' ), EVF_VERSION, true );
		wp_register_script( 'muhiku-plug-editor', mhk()->plugin_url() . '/assets/js/admin/editor' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'jquery-blockui', mhk()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'jquery-confirm', mhk()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm' . $suffix . '.js', array( 'jquery' ), '3.3.0', true );
		wp_register_script( 'jquery-tiptip', mhk()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true ); // @deprecated
		wp_register_script( 'tooltipster', mhk()->plugin_url() . '/assets/js/tooltipster/tooltipster.bundle' . $suffix . '.js', array( 'jquery' ), '4.6.2', true );
		wp_register_script( 'perfect-scrollbar', mhk()->plugin_url() . '/assets/js/perfect-scrollbar/perfect-scrollbar' . $suffix . '.js', array( 'jquery' ), '1.5.0', true );
		wp_register_script( 'mhk-clipboard', mhk()->plugin_url() . '/assets/js/admin/mhk-clipboard' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'selectWoo', mhk()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.8', true );
		wp_register_script( 'mhk-enhanced-select', mhk()->plugin_url() . '/assets/js/admin/mhk-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), EVF_VERSION, true );
		wp_register_script( 'mhk-template-controller', mhk()->plugin_url() . '/assets/js/admin/form-template-controller' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
		wp_register_script( 'flatpickr', mhk()->plugin_url() . '/assets/js/flatpickr/flatpickr' . $suffix . '.js', array( 'jquery' ), '4.6.3', true );
		wp_register_script( 'mhk-file-uploader', mhk()->plugin_url() . '/assets/js/admin/mhk-file-uploader' . $suffix . '.js', array(), EVF_VERSION, true );
		wp_localize_script(
			'mhk-file-uploader',
			'mhk_file_uploader',
			array(
				'upload_file' => __( 'Upload Image', 'muhiku-plug' ),
			)
		);
		wp_localize_script(
			'mhk-template-controller',
			'mhk_templates',
			array(
				'mhk_template_all' => EVF_Admin_Forms::get_template_data(),
				'i18n_get_started' => esc_html__( 'Get Started', 'muhiku-plug' ),
				'i18n_get_preview' => esc_html__( 'Preview', 'muhiku-plug' ),
				'i18n_pro_feature' => esc_html__( 'Pro', 'muhiku-plug' ),
				'template_refresh' => esc_html__( 'Updating Templates', 'muhiku-plug' ),
				'mhk_plugin_url'   => esc_url( mhk()->plugin_url() ),
			)
		);
		wp_localize_script(
			'mhk-enhanced-select',
			'mhk_enhanced_select_params',
			array(
				'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'muhiku-plug' ),
				'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'muhiku-plug' ),
				'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'muhiku-plug' ),
				'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'muhiku-plug' ),
				'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'muhiku-plug' ),
				'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'muhiku-plug' ),
			)
		);
		wp_register_script( 'mhk-form-builder', mhk()->plugin_url() . '/assets/js/admin/form-builder' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'tooltipster', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-datepicker', 'jquery-confirm', 'mhk-clipboard', 'flatpickr' ), EVF_VERSION, true );
		wp_localize_script(
			'mhk-form-builder',
			'mhk_data',
			apply_filters(
				'everest_forms_builder_strings',
				array(
					'post_id'                      => isset( $post->ID ) ? $post->ID : '',
					'ajax_url'                     => admin_url( 'admin-ajax.php' ),
					'tab'                          => isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.
					'mhk_field_drop_nonce'         => wp_create_nonce( 'everest_forms_field_drop' ),
					'mhk_save_form'                => wp_create_nonce( 'everest_forms_save_form' ),
					'mhk_get_next_id'              => wp_create_nonce( 'everest_forms_get_next_id' ),
					'mhk_enabled_form'             => wp_create_nonce( 'everest_forms_enabled_form' ),
					'form_id'                      => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0, // phpcs:ignore WordPress.Security.NonceVerification
					'field'                        => esc_html__( 'field', 'muhiku-plug' ),
					'i18n_ok'                      => esc_html__( 'OK', 'muhiku-plug' ),
					'i18n_installing'              => esc_html__( 'Installing', 'muhiku-plug' ),
					'i18n_activating'              => esc_html__( 'Activating', 'muhiku-plug' ),
					'i18n_install_activate'        => esc_html__( 'Install & Activate', 'muhiku-plug' ),
					'i18n_install_only'            => esc_html__( 'Activate Plugins', 'muhiku-plug' ),
					'i18n_copy'                    => esc_html__( '(copy)', 'muhiku-plug' ),
					'i18n_close'                   => esc_html__( 'Close', 'muhiku-plug' ),
					'i18n_cancel'                  => esc_html__( 'Cancel', 'muhiku-plug' ),
					'i18n_row_locked'              => esc_html__( 'Row Locked', 'muhiku-plug' ),
					'i18n_row_locked_msg'          => esc_html__( 'Single row cannot be deleted.', 'muhiku-plug' ),
					'i18n_field_locked'            => esc_html__( 'Field Locked', 'muhiku-plug' ),
					'i18n_field_locked_msg'        => esc_html__( 'This field cannot be deleted or duplicated.', 'muhiku-plug' ),
					'i18n_row_locked_msg'          => esc_html__( 'This row cannot be deleted or duplicated.', 'muhiku-plug' ),
					'i18n_field_error_choice'      => esc_html__( 'This item must contain at least one choice.', 'muhiku-plug' ),
					'i18n_delete_row_confirm'      => esc_html__( 'Are you sure you want to delete this row?', 'muhiku-plug' ),
					'i18n_delete_field_confirm'    => esc_html__( 'Are you sure you want to delete this field?', 'muhiku-plug' ),
					'i18n_duplicate_field_confirm' => esc_html__( 'Are you sure you want to duplicate this field?', 'muhiku-plug' ),
					'i18n_duplicate_row_confirm'   => esc_html__( 'Are you sure you want to duplicate this row?', 'muhiku-plug' ),
					'i18n_email_disable_message'   => esc_html__( 'Turn on Email settings to manage your email notification.', 'muhiku-plug' ),
					'i18n_upload_image_title'      => esc_html__( 'Choose an image', 'muhiku-plug' ),
					'i18n_upload_image_button'     => esc_html__( 'Use Image', 'muhiku-plug' ),
					'i18n_upload_image_remove'     => esc_html__( 'Remove Image', 'muhiku-plug' ),
					'i18n_field_title_empty'       => esc_html__( 'Empty Form Name', 'muhiku-plug' ),
					'i18n_field_title_payload'     => esc_html__( 'Form name can\'t be empty.', 'muhiku-plug' ),
					'email_fields'                 => mhk_get_all_email_fields_by_form_id( isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0 ), // phpcs:ignore WordPress.Security.NonceVerification
					'all_fields'                   => mhk_get_all_form_fields_by_form_id( isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0 ), // phpcs:ignore WordPress.Security.NonceVerification
					'smart_tags_other'             => mhk()->smart_tags->other_smart_tags(),
					'entries_url'                  => ! empty( $_GET['form_id'] ) ? esc_url( admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . absint( $_GET['form_id'] ) ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
					'preview_url'                  => ! empty( $_GET['form_id'] ) ? esc_url( // phpcs:ignore WordPress.Security.NonceVerification
						add_query_arg(
							array(
								'form_id'     => absint( $_GET['form_id'] ), // phpcs:ignore WordPress.Security.NonceVerification
								'mhk_preview' => 'true',
								),
							home_url()
						)
					) : '',
				)
			)
		);

		// Builder upgrade.
		wp_register_script( 'mhk-upgrade', mhk()->plugin_url() . '/assets/js/admin/upgrade.js', array( 'jquery', 'jquery-confirm' ), EVF_VERSION, false );
		wp_localize_script(
			'mhk-upgrade',
			'mhk_upgrade',
			array(
				'upgrade_title'         => esc_html__( 'is a PRO Feature', 'muhiku-plug' ),
				'upgrade_message'       => esc_html__( 'We\'re sorry, the %name% is not available on your plan.<br>Please upgrade to the PRO plan to unlock all these awesome features.', 'muhiku-plug' ),
				'upgrade_button'        => esc_html__( 'Upgrade to PRO', 'muhiku-plug' ),
				'upgrade_url'           => apply_filters( 'everest_forms_upgrade_url', 'https://wpeverest.com/wordpress-plugins/muhiku-plug/pricing/?utm_source=premium-fields&utm_medium=modal-button&utm_campaign=mhk-upgrade-to-pro' ),
				'enable_stripe_title'   => esc_html__( 'Please enable Stripe', 'muhiku-plug' ),
				'enable_stripe_message' => esc_html__( 'Enable Stripe Payment gateway in payments section to use this field.', 'muhiku-plug' ),
			)
		);

		// MuhikuPlug admin pages.
		if ( in_array( $screen_id, mhk_get_screen_ids(), true ) ) {
			wp_enqueue_script( 'muhiku-plug-admin' );
			wp_enqueue_script( 'muhiku-plug-email-admin' );
			wp_enqueue_script( 'mhk-enhanced-select' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );

			wp_localize_script(
				'muhiku-plug-email-admin',
				'mhk_email_params',
				array(
					'i18n_email_connection'  => esc_html__( 'Enter a Email nickname', 'muhiku-plug' ),
					'i18n_email_placeholder' => esc_html__( 'Eg: Support Email', 'muhiku-plug' ),
					'i18n_email_error_name'  => esc_html__( 'You must provide a Email nickname', 'muhiku-plug' ),
					'i18n_email_ok'          => esc_html__( 'OK', 'muhiku-plug' ),
					'ajax_email_nonce'       => wp_create_nonce( 'process-ajax-nonce' ),
					'ajax_url'               => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_email_cancel'      => esc_html__( 'Cancel', 'muhiku-plug' ),
					'i18n_default_address'   => get_option( 'admin_email' ),
					'from_name'              => get_bloginfo( 'name', 'display' ),
					'email_subject'          => esc_html__( 'New Form Entry', 'muhiku-plug' ),
				)
			);

			wp_localize_script(
				'muhiku-plug-admin',
				'everest_forms_admin',
				array(
					'ajax_import_nonce'             => wp_create_nonce( 'process-import-ajax-nonce' ),
					'ajax_url'                      => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_field_meta_key_error'     => esc_html__( 'Please enter in meta key with alphanumeric characters, dashes and underscores.', 'muhiku-plug' ),
					'i18n_field_min_value_greater'  => esc_html__( 'Minimum value is greater than Maximum value.', 'muhiku-plug' ),
					'i18n_field_max_value_smaller'  => esc_html__( 'Maximum value is smaller than Minimum value.', 'muhiku-plug' ),
					'i18n_form_export_action_error' => esc_html__( 'Please select a form which you want to export.', 'muhiku-plug' ),
				)
			);
		}

		// MuhikuPlug builder pages.
		if ( in_array( $screen_id, array( 'muhiku-plug_page_mhk-builder' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'mhk-upgrade' );
			wp_enqueue_script( 'mhk-form-builder' );

			// De-register scripts.
			wp_dequeue_script( 'colorpick' );

			// MuhikuPlug builder setup page.
			if ( isset( $_GET['create-form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				wp_register_script( 'mhk-setup', mhk()->plugin_url() . '/assets/js/admin/mhk-setup' . $suffix . '.js', array( 'jquery', 'muhiku-plug-extensions', 'mhk-template-controller' ), EVF_VERSION, true );
				wp_enqueue_script( 'mhk-setup' );
				wp_localize_script(
					'mhk-setup',
					'mhk_setup_params',
					array(
						'ajax_url'                     => admin_url( 'admin-ajax.php' ),
						'create_form_nonce'            => wp_create_nonce( 'everest_forms_create_form' ),
						'template_licence_check_nonce' => wp_create_nonce( 'everest_forms_template_licence_check' ),
						'i18n_form_name'               => esc_html__( 'Give it a name.', 'muhiku-plug' ),
						'i18n_form_error_name'         => esc_html__( 'You must provide a Form name', 'muhiku-plug' ),
						'upgrade_url'                  => apply_filters( 'everest_forms_upgrade_url', 'https://wpeverest.com/wordpress-plugins/muhiku-plug/pricing/?utm_source=form-template&utm_medium=button&utm_campaign=mhk-upgrade-to-pro' ),
						'upgrade_button'               => esc_html__( 'Upgrade Plan', 'muhiku-plug' ),
						'upgrade_message'              => esc_html__( 'This template requires premium addons. Please upgrade to the Premium plan to unlock all these awesome Templates.', 'muhiku-plug' ),
						'upgrade_title'                => esc_html__( 'is a Premium Template', 'muhiku-plug' ),
						'i18n_form_ok'                 => esc_html__( 'Continue', 'muhiku-plug' ),
						'i18n_form_placeholder'        => esc_html__( 'Untitled Form', 'muhiku-plug' ),
						'i18n_form_title'              => esc_html__( 'Uplift your form experience to the next level.', 'muhiku-plug' ),
					)
				);
			}
		}

		// Tools page.
		if ( 'muhiku-plug_page_mhk-tools' === $screen_id ) {
			wp_register_script( 'mhk-admin-tools', mhk()->plugin_url() . '/assets/js/admin/tools' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
			wp_enqueue_script( 'mhk-admin-tools' );
			wp_localize_script(
				'mhk-admin-tools',
				'everest_forms_admin_tools',
				array(
					'delete_log_confirmation' => esc_js( esc_html__( 'Are you sure you want to delete this log?', 'muhiku-plug' ) ),
				)
			);
		}

		// Plugins page.
		if ( in_array( $screen_id, array( 'plugins' ), true ) ) {
			wp_register_script( 'mhk-plugins', mhk()->plugin_url() . '/assets/js/admin/plugins' . $suffix . '.js', array( 'jquery' ), EVF_VERSION, true );
			wp_enqueue_script( 'mhk-plugins' );
			wp_localize_script(
				'mhk-plugins',
				'mhk_plugins_params',
				array(
					'ajax_url'           => admin_url( 'admin-ajax.php' ),
					'deactivation_nonce' => wp_create_nonce( 'deactivation-notice' ),
				)
			);
		}
	}
}

return new EVF_Admin_Assets();

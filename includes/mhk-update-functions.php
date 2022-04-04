<?php
/**
 * MuhikuPlug Updates
 * @package MuhikuPlug\Functions
 */

defined( 'ABSPATH' ) || exit;


function mhk_update_100_db_version() {
	MHK_Install::update_db_version( '1.0.0' );
}

function mhk_update_101_db_version() {
	MHK_Install::update_db_version( '1.0.1' );
}

function mhk_update_102_db_version() {
	MHK_Install::update_db_version( '1.0.2' );
}

function mhk_update_103_db_version() {
	MHK_Install::update_db_version( '1.0.3' );
}

function mhk_update_110_update_forms() {
	$forms = mhk_get_all_forms();

	foreach ( $forms as $form_id => $form ) {
		$form_obj  = mhk()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as &$field ) {
				if ( ! isset( $field['meta-key'] ) ) {
					$field['meta-key'] = mhk_get_meta_key_field_option( $field );
				}
			}
		}

		mhk()->form->update( $form_id, $form_data );
	}
}

function mhk_update_110_db_version() {
	MHK_Install::update_db_version( '1.1.0' );
}

function mhk_update_116_delete_options() {
	$delete_options = array(
		'mhk_to_email',
		'mhk_from_name',
		'mhk_from_address',
		'mhk_email_subject',
		'mhk_email_message',
		'muhiku_forms_disable_form_entries',
		'muhiku_forms_form_submit_button_label',
		'muhiku_forms_successful_form_submission_message',
	);

	foreach ( $delete_options as $delete_option ) {
		delete_option( $delete_option );
	}
}

function mhk_update_116_db_version() {
	MHK_Install::update_db_version( '1.1.6' );
}

function mhk_update_120_db_rename_options() {
	$rename_options = array(
		'mhk_email_template'        => 'muhiku_forms_email_template',
		'mhk_recaptcha_site_key'    => 'muhiku_forms_recaptcha_site_key',
		'mhk_recaptcha_site_secret' => 'muhiku_forms_recaptcha_site_secret',
		'mhk_required_validation'   => 'muhiku_forms_required_validation',
		'mhk_url_validation'        => 'muhiku_forms_url_validation',
		'mhk_email_validation'      => 'muhiku_forms_email_validation',
		'mhk_number_validation'     => 'muhiku_forms_number_validation',
		'mhk_recaptcha_validation'  => 'muhiku_forms_recaptcha_validation',
		'mhk_default_form_page_id'  => 'muhiku_forms_default_form_page_id',
	);

	foreach ( $rename_options as $old_option => $new_option ) {
		$raw_old_option = get_option( $old_option );

		if ( ! empty( $raw_old_option ) ) {
			update_option( $new_option, $raw_old_option );
			delete_option( $old_option );
		}
	}
}

function mhk_update_140_db_multiple_email() {
	$forms = mhk()->form->get_multiple( array( 'order' => 'DESC' ) );

	foreach ( $forms as $form ) {
		$form_id   = isset( $form->ID ) ? $form->ID : '0';
		$form_data = ! empty( $form->post_content ) ? mhk_decode( $form->post_content ) : '';

		if ( ! empty( $form_data['settings'] ) ) {
			$email = (array) $form_data['settings']['email'];

			$new_email                    = array();
			$new_email['connection_name'] = esc_html__( 'Admin Notification', 'muhiku-plug' );
			$new_email                    = array_merge( $new_email, $email );

			$email_settings = array( 'mhk_send_confirmation_email', 'mhk_user_to_email', 'mhk_user_email_subject', 'mhk_user_email_message', 'attach_pdf_to_user_email' );
			foreach ( $email_settings as $email_setting ) {
				unset( $email_setting );
			}

			if ( ! isset( $form_data['settings']['email']['connection_1'] ) ) {
				$unique_connection_id           = sprintf( 'connection_%s', uniqid() );
				$form_data['settings']['email'] = array( 'connection_1' => $new_email );

				if ( isset( $email['mhk_send_confirmation_email'] ) && '1' === $email['mhk_send_confirmation_email'] ) {
					$form_data['settings']['email'][ $unique_connection_id ] = array(
						'connection_name'   => esc_html__( 'User Notification', 'muhiku-plug' ),
						'mhk_to_email'      => '{field_id="' . $email['mhk_user_to_email'] . '"}',
						'mhk_from_name'     => $email['mhk_from_name'],
						'mhk_from_email'    => $email['mhk_from_email'],
						'mhk_reply_to'      => $email['mhk_reply_to'],
						'mhk_email_subject' => $email['mhk_user_email_subject'],
						'mhk_email_message' => $email['mhk_user_email_message'],
					);
				}

				if ( isset( $email['attach_pdf_to_user_email'] ) && '1' === $email['attach_pdf_to_user_email'] ) {
					$form_data['settings']['email'][ $unique_connection_id ]['attach_pdf_to_admin_email'] = '1';
				}

				if ( isset( $email['conditional_logic_status'] ) ) {
					$form_data['settings']['email'][ $unique_connection_id ]['conditional_logic_status'] = $email['conditional_logic_status'];
					$form_data['settings']['email'][ $unique_connection_id ]['conditional_option']       = $email['conditional_option'];
					$form_data['settings']['email'][ $unique_connection_id ]['conditionals']             = array();
				}
			}

			mhk()->form->update( $form_id, $form_data );
		}
	}
}

function mhk_update_120_db_version() {
	MHK_Install::update_db_version( '1.2.0' );
}

function mhk_update_130_db_version() {
	MHK_Install::update_db_version( '1.3.0' );
}

function mhk_update_140_db_version() {
	MHK_Install::update_db_version( '1.4.0' );
}

function mhk_update_144_delete_options() {
	delete_option( 'muhiku_forms_recaptcha_validation' );
}

function mhk_update_144_db_version() {
	MHK_Install::update_db_version( '1.4.4' );
}

function mhk_update_149_db_rename_options() {
	$rename_options = array(
		'muhiku_forms_recaptcha_site_key'    => 'muhiku_forms_recaptcha_v2_site_key',
		'muhiku_forms_recaptcha_site_secret' => 'muhiku_forms_recaptcha_v2_secret_key',
	);

	foreach ( $rename_options as $old_option => $new_option ) {
		$raw_old_option = get_option( $old_option );

		if ( ! empty( $raw_old_option ) ) {
			update_option( $new_option, $raw_old_option );
			delete_option( $old_option );
		}
	}
}

function mhk_update_149_no_payment_options() {
	$forms = mhk_get_all_forms();

	foreach ( $forms as $form_id => $form ) {
		$form_obj  = mhk()->form->get( $form_id );
		$form_data = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $field_id => &$field ) {
				if ( isset( $field['type'] ) && 'payment-charge-options' === $field['type'] ) {
					unset( $form_data['form_fields'][ $field_id ] );
				}
			}
		}

		mhk()->form->update( $form_id, $form_data );
	}
}

function mhk_update_149_db_version() {
	MHK_Install::update_db_version( '1.4.9' );
}

function mhk_update_150_field_datetime_type() {
	$forms = mhk()->form->get_multiple( array( 'order' => 'DESC' ) );

	foreach ( $forms as $form ) {
		$form_id   = isset( $form->ID ) ? $form->ID : '0';
		$form_data = ! empty( $form->post_content ) ? mhk_decode( $form->post_content ) : '';

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as &$field ) {
				if ( isset( $field['type'] ) && 'date' === $field['type'] ) {
					$field['type'] = 'date-time';
				}
			}
		}

		mhk()->form->update( $form_id, $form_data );
	}
}

function mhk_update_150_db_version() {
	MHK_Install::update_db_version( '1.5.0' );
}

function mhk_update_160_db_version() {
	MHK_Install::update_db_version( '1.6.0' );
}

function mhk_update_175_remove_capabilities() {
	global $wp_roles;

	if ( ! class_exists( 'WP_Roles' ) ) {
		return;
	}

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles(); 
	}

	$capability_types = array( 'muhiku_form' );

	foreach ( $capability_types as $capability_type ) {
		$capabilities[ $capability_type ] = array(
			"edit_{$capability_type}",
			"read_{$capability_type}",
			"delete_{$capability_type}",
			"edit_{$capability_type}s",
			"edit_others_{$capability_type}s",
			"publish_{$capability_type}s",
			"read_private_{$capability_type}s",
			"delete_{$capability_type}s",
			"delete_private_{$capability_type}s",
			"delete_published_{$capability_type}s",
			"delete_others_{$capability_type}s",
			"edit_private_{$capability_type}s",
			"edit_published_{$capability_type}s",

			"manage_{$capability_type}_terms",
			"edit_{$capability_type}_terms",
			"delete_{$capability_type}_terms",
			"assign_{$capability_type}_terms",
		);
	}

	foreach ( $capabilities as $cap_group ) {
		foreach ( $cap_group as $cap ) {
			$wp_roles->remove_cap( 'administrator', $cap );
		}
	}
}

function mhk_update_175_restore_draft_forms() {
	$form_ids = get_posts(
		array(
			'post_type'   => 'muhiku_form',
			'post_status' => 'draft',
			'fields'      => 'ids',
			'numberposts' => - 1,
		)
	);

	foreach ( $form_ids as $form_id ) {
		wp_update_post(
			array(
				'ID'          => $form_id,
				'post_status' => 'publish',
			)
		);
	}
}

function mhk_update_175_db_version() {
	MHK_Install::update_db_version( '1.7.5' );
}

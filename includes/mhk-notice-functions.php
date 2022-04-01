<?php
/**
 * Muhiku Plug Message Functions
 *
 * Functions for error/message handling and display.
 *
 * @package MuhikuPlug/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Get the count of notices added, either for all notices (default) or for one.
 * particular notice type specified by $notice_type.
 *
 * @since  1.0.0
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return int
 */
function mhk_notice_count( $notice_type = '' ) {
	if ( ! did_action( 'muhiku_forms_init' ) ) {
		mhk_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before muhiku_forms_init.', 'muhiku-plug' ), '1.0' );
		return;
	}

	$notice_count = 0;
	$all_notices  = mhk()->session->get( 'mhk_notices', array() );

	if ( isset( $all_notices[ $notice_type ] ) ) {

		$notice_count = count( $all_notices[ $notice_type ] );

	} elseif ( empty( $notice_type ) ) {

		foreach ( $all_notices as $notices ) {
			$notice_count += count( $notices );
		}
	}

	return $notice_count;
}

/**
 * Check if a notice has already been added.
 *
 * @since  1.0.0
 * @param  string $message The text to display in the notice.
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return bool
 */
function mhk_has_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'muhiku_forms_init' ) ) {
		mhk_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before muhiku_forms_init.', 'muhiku-plug' ), '1.0' );
		return false;
	}

	$notices = mhk()->session->get( 'mhk_notices', array() );
	$notices = isset( $notices[ $notice_type ] ) ? $notices[ $notice_type ] : array();

	return array_search( $message, $notices, true ) !== false;
}

/**
 * Add and store a notice.
 *
 * @since 1.0.0
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 */
function mhk_add_notice( $message, $notice_type = 'success' ) {
	if ( ! did_action( 'muhiku_forms_init' ) ) {
		mhk_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before muhiku_forms_init.', 'muhiku-plug' ), '1.0' );
		return;
	}
	if ( mhk_is_amp() ) {
		return;
	}

	$notices = mhk()->session->get( 'mhk_notices', array() );

	// Backward compatibility.
	if ( 'success' === $notice_type ) {
		$message = apply_filters( 'muhiku_forms_add_message', $message );
	}

	$notices[ $notice_type ][] = apply_filters( 'muhiku_forms_add_' . $notice_type, $message );

	mhk()->session->set( 'mhk_notices', $notices );
}

/**
 * Set all notices at once.
 *
 * @since 1.0.0
 * @param mixed $notices Array of notices.
 */
function mhk_set_notices( $notices ) {
	if ( ! did_action( 'muhiku_forms_init' ) ) {
		mhk_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before muhiku_forms_init.', 'muhiku-plug' ), '1.0' );
		return;
	}
	mhk()->session->set( 'mhk_notices', $notices );
}

/**
 * Unset all notices.
 *
 * @since 1.0.0
 */
function mhk_clear_notices() {
	if ( ! did_action( 'muhiku_forms_init' ) ) {
		mhk_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before muhiku_forms_init.', 'muhiku-plug' ), '1.0' );
		return;
	}
	mhk()->session->set( 'mhk_notices', null );
}

/**
 * Prints messages and errors which are stored in the session, then clears them.
 *
 * @since 1.0.0
 *
 * @param array $form_data Prepared form settings.
 */
function mhk_print_notices( $form_data = array() ) {
	if ( ! did_action( 'muhiku_forms_init' ) ) {
		mhk_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before muhiku_forms_init.', 'muhiku-plug' ), '1.0' );
		return;
	}

	$form_id      = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
	$all_notices  = mhk()->session->get( 'mhk_notices', array() );
	$notice_types = apply_filters( 'muhiku_forms_notice_types', array( 'error', 'success', 'notice' ) );

	// Skips notice print if it isn't the right form.
	if ( isset( $_REQUEST['muhiku_forms']['id'] ) && ( (int) $form_id !== (int) $_REQUEST['muhiku_forms']['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}

	foreach ( $notice_types as $notice_type ) {
		if ( mhk_notice_count( $notice_type ) > 0 ) {
			foreach ( $all_notices[ $notice_type ] as $key => $message ) {
				$all_notices[ $notice_type ][ $key ] = mhk_string_translation( $form_id, 'notice_message_' . $notice_type, $message );
			}
			mhk_get_template(
				"notices/{$notice_type}.php",
				array(
					'messages' => array_filter( $all_notices[ $notice_type ] ),
				)
			);
		}
	}

	mhk_clear_notices();
}
add_action( 'muhiku_forms_display_fields_before', 'mhk_print_notices', 10 );

/**
 * Print a single notice immediately.
 *
 * @since 1.0.0
 * @param string $message The text to display in the notice.
 * @param string $notice_type Optional. The name of the notice type - either error, success or notice.
 */
function mhk_print_notice( $message, $notice_type = 'success' ) {
	if ( 'success' === $notice_type ) {
		$message = apply_filters( 'muhiku_forms_add_message', $message );
	}

	mhk_get_template(
		"notices/{$notice_type}.php",
		array(
			'messages' => array( apply_filters( 'muhiku_forms_add_' . $notice_type, $message ) ),
		)
	);
}

/**
 * Returns all queued notices, optionally filtered by a notice type.
 *
 * @since  1.0.0
 * @param  string $notice_type Optional. The name of the notice type - either error, success or notice.
 * @return array|mixed
 */
function mhk_get_notices( $notice_type = '' ) {
	if ( ! did_action( 'muhiku_forms_init' ) ) {
		mhk_doing_it_wrong( __FUNCTION__, __( 'This function should not be called before muhiku_forms_init.', 'muhiku-plug' ), '1.0' );
		return;
	}

	$all_notices = mhk()->session->get( 'mhk_notices', array() );

	if ( empty( $notice_type ) ) {
		$notices = $all_notices;
	} elseif ( isset( $all_notices[ $notice_type ] ) ) {
		$notices = $all_notices[ $notice_type ];
	} else {
		$notices = array();
	}

	return $notices;
}

/**
 * Add notices for WP Errors.
 *
 * @param WP_Error $errors Errors.
 */
function mhk_add_wp_error_notices( $errors ) {
	if ( is_wp_error( $errors ) && $errors->get_error_messages() ) {
		foreach ( $errors->get_error_messages() as $error ) {
			mhk_add_notice( $error, 'error' );
		}
	}
}

<?php
/**
 * @package MuhikuPlug\Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $tag         
 * @param array  $args        
 * @param string $version     
 * @param string $replacement 
 * @param string $message     
 */
function mhk_do_deprecated_action( $tag, $args, $version, $replacement = null, $message = null ) {
	if ( ! has_action( $tag ) ) {
		return;
	}

	mhk_deprecated_hook( $tag, $version, $replacement, $message );
	do_action_ref_array( $tag, $args );
}

/**
 * @param string $function    Function used.
 * @param string $version     Version the message was added in.
 * @param string $replacement Replacement for the called function.
 */
function mhk_deprecated_function( $function, $version, $replacement = null ) {
	// @codingStandardsIgnoreStart
	if ( is_ajax() ) {
		do_action( 'deprecated_function_run', $function, $replacement, $version );
		$log_string  = "The {$function} function is deprecated since version {$version}.";
		$log_string .= $replacement ? " Replace with {$replacement}." : '';
		error_log( $log_string );
	} else {
		_deprecated_function( $function, $version, $replacement );
	}
}

/**
 * @param string $hook        
 * @param string $version     
 * @param string $replacement 
 * @param string $message     
 */
function mhk_deprecated_hook( $hook, $version, $replacement = null, $message = null ) {
	if ( is_ajax() ) {
		do_action( 'deprecated_hook_run', $hook, $replacement, $version, $message );

		$message    = empty( $message ) ? '' : ' ' . $message;
		$log_string = "{$hook} is deprecated since version {$version}";
		$log_string .= $replacement ? "! Use {$replacement} instead." : ' with no alternative available.';

		error_log( $log_string . $message );
	} else {
		_deprecated_hook( $hook, $version, $replacement, $message );
	}
}

/**
 * @param Exception $exception_object 
 * @param string    $function 
 * @param array     $args 
 */
function mhk_caught_exception( $exception_object, $function = '', $args = array() ) {
	$message  = $exception_object->getMessage();
	$message .= '. Args: ' . print_r( $args, true ) . '.';

	do_action( 'muhiku_forms_caught_exception', $exception_object, $function, $args );
	error_log( "Exception caught in {$function}. {$message}." );
}

/**
 * @param string $function 
 * @param string $message  
 * @param string $version  
 */
function mhk_doing_it_wrong( $function, $message, $version ) {
	$message .= ' Backtrace: ' . wp_debug_backtrace_summary();

	if ( is_ajax() ) {
		do_action( 'doing_it_wrong_run', $function, $message, $version );
		error_log( "{$function} was called incorrectly. {$message}. This message was added in version {$version}." );
	} else {
		_doing_it_wrong( $function, $message, $version );
	}
}

/**
 * @param string $argument Argument used.
 * @param string $version  Version the message was added in.
 * @param string $message  A message regarding the change.
 */
function mhk_deprecated_argument( $argument, $version, $message = null ) {
	if ( is_ajax() ) {
		do_action( 'deprecated_argument_run', $argument, $message, $version );
		error_log( "The {$argument} argument is deprecated since version {$version}. {$message}" );
	} else {
		_deprecated_argument( $argument, $version, $message );
	}
}

function mhk_sender_name() {
	mhk_deprecated_function( 'mhk_sender_name', '1.1.6' );
}

function mhk_sender_address() {
	mhk_deprecated_function( 'mhk_sender_address', '1.1.6' );
}

function get_form_data_by_meta_key( $form_id, $meta_key ) {
	mhk_deprecated_function( 'get_form_data_by_meta_key', '1.2.0', 'mhk_get_form_data_by_meta_key' );
	return mhk_get_form_data_by_meta_key( $form_id, $meta_key );
}

function mhk_query_string_form_fields( $values = null, $exclude = array(), $current_key = '', $return = false ) {
	mhk_deprecated_function( 'mhk_sender_address', '1.2.0' );
}

function muhiku_forms_sanitize_textarea_field( $string ) {
	mhk_deprecated_function( 'muhiku_forms_sanitize_textarea_field', '1.2.0', 'mhk_sanitize_textarea_field' );
	return mhk_sanitize_textarea_field( $string );
}

function mhk_get_us_states() {
	mhk_deprecated_function( 'mhk_get_us_states', '1.3.0' );
}

function get_all_email_fields_by_form_id( $form_id ) {
	mhk_deprecated_function( 'get_all_email_fields_by_form_id', '1.3.0', 'mhk_get_all_email_fields_by_form_id' );
	return mhk_get_all_email_fields_by_form_id( $form_id );
}

function get_all_form_fields_by_form_id( $form_id ) {
	mhk_deprecated_function( 'get_all_form_fields_by_form_id', '1.3.0', 'mhk_get_all_form_fields_by_form_id' );
	return mhk_get_all_form_fields_by_form_id( $form_id );
}

function mhk_has_date_field( $form_id ) {
	mhk_deprecated_function( 'mhk_has_date_field', '1.5.7', 'mhk_is_field_exists' );
	return mhk_is_field_exists( $form_id, 'date-time' );
}

<?php
/**
 * @package MuhikuPlug/Functions
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'is_ajax' ) ) {

	/**
	 * @return bool
	 */
	function is_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' );
	}
}

/**
 * @param  string $string String to check.
 * @return bool
 */
function mhk_is_json( $string ) {
	return is_string( $string ) ? is_object( json_decode( $string ) ) : false;
}

/**
 * @param int    $form_id Form ID.
 * @param string $field   Field ID.
 * @return bool  True if the field exists in the form.
 */
function mhk_is_field_exists( $form_id, $field ) {
	$form_obj  = mhk()->form->get( $form_id );
	$form_data = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

	if ( ! empty( $form_data['form_fields'] ) ) {
		foreach ( $form_data['form_fields'] as $form_field ) {
			if ( $field === $form_field['type'] ) {
				return true;
			}
		}
	}

	return false;
}

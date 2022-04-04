<?php
/**
 * @package  MuhikuPlug/Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_Validation {

	/**
	 * @param  string $email Email address to validate.
	 * @return bool
	 */
	public static function is_email( $email ) {
		return is_email( $email );
	}

	/**
	 * @param  string $phone Phone number to validate.
	 * @return bool
	 */
	public static function is_phone( $phone ) {
		if ( 0 < strlen( trim( preg_replace( '/[\s\#0-9_\-\+\/\(\)]/', '', $phone ) ) ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @param  mixed $tel Phone number to format.
	 * @return string
	 */
	public static function format_phone( $tel ) {
		return mhk_format_phone_number( $tel );
	}
}

<?php
/**
 * Shortcodes
 *
 * @package MuhikuPlug\Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * MuhikuPlug Shortcodes class.
 */
class MHK_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		self::init_shortcode_hooks();

		$shortcodes = array(
			'everest_form' => __CLASS__ . '::form',
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Shortcode Wrapper.
	 *
	 * @param string[] $function Callback function.
	 * @param array    $atts     Attributes. Default to empty array.
	 * @param array    $wrapper  Customer wrapper data.
	 *
	 * @return string
	 */
	public static function shortcode_wrapper(
		$function,
		$atts = array(),
		$wrapper = array(
			'class'  => 'muhiku-plug',
			'before' => null,
			'after'  => null,
		)
	) {
		ob_start();

		$wrap_before = empty( $wrapper['before'] ) ? '<div class="' . esc_attr( $wrapper['class'] ) . '">' : $wrapper['before'];
		echo wp_kses_post( $wrap_before );
		call_user_func( $function, $atts );
		$wrap_after = empty( $wrapper['after'] ) ? '</div>' : $wrapper['after'];
		echo wp_kses_post( $wrap_after );

		return ob_get_clean();
	}

	/**
	 * Form shortcode.
	 *
	 * @param  array $atts Attributes.
	 * @return string
	 */
	public static function form( $atts ) {
		return self::shortcode_wrapper( array( 'MHK_Shortcode_Form', 'output' ), $atts );
	}

	/**
	 * Initialize shortcode.
	 */
	public static function init_shortcode_hooks() {
		self::shortcode_wrapper( array( 'MHK_Shortcode_Form', 'hooks' ) );
	}
}

<?php
/**
 * @package WPMuhiku\Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param string $string 
 * @return bool
 */
function mhk_string_to_bool( $string ) {
	return is_bool( $string ) ? $string : ( 'yes' === $string || 1 === $string || 'true' === $string || '1' === $string );
}

/**
 * @param bool $bool 
 * @return string
 */
function mhk_bool_to_string( $bool ) {
	if ( ! is_bool( $bool ) ) {
		$bool = mhk_string_to_bool( $bool );
	}
	return true === $bool ? 'yes' : 'no';
}

/**
 * @param  array  $array  Raw array data.
 * @param  string $suffix Suffix to be added.
 * @return array Modified array with suffix added.
 */
function mhk_suffix_array( $array = array(), $suffix = '' ) {
	return preg_filter( '/$/', $suffix, $array );
}

/**
 * @param  array  $array Array to convert.
 * @param  string $glue  Glue, defaults to ' '.
 * @return string
 */
function mhk_array_to_string( $array = array(), $glue = ' ' ) {
	return is_string( $array ) ? $array : implode( $glue, array_filter( $array ) );
}

/**
 * @param  string $string    String to convert.
 * @param  string $delimiter Delimiter, defaults to ','.
 * @return array
 */
function mhk_string_to_array( $string, $delimiter = ',' ) {
	return is_array( $string ) ? $string : array_filter( explode( $delimiter, $string ) );
}

/**
 * @param  array $dimensions Array of dimensions.
 * @param  array $unit       Unit, defaults to 'px'.
 * @return string
 */
function mhk_sanitize_dimension_unit( $dimensions = array(), $unit = 'px' ) {
	return mhk_array_to_string( mhk_suffix_array( $dimensions, $unit ) );
}

/**
 * @param string $taxonomy Taxonomy name.
 * @return string
 */
function mhk_sanitize_taxonomy_name( $taxonomy ) {
	return apply_filters( 'sanitize_taxonomy_name', urldecode( sanitize_title( urldecode( $taxonomy ) ) ), $taxonomy );
}

/**
 * @param  string $value Permalink.
 * @return string
 */
function mhk_sanitize_permalink( $value ) {
	global $wpdb;

	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

	if ( is_wp_error( $value ) ) {
		$value = '';
	}

	$value = esc_url_raw( trim( $value ) );
	$value = str_replace( 'http://', '', $value );
	return untrailingslashit( $value );
}

/**
 * @param string $file_url File URL.
 * @return string
 */
function mhk_get_filename_from_url( $file_url ) {
	$parts = wp_parse_url( $file_url );
	if ( isset( $parts['path'] ) ) {
		return basename( $parts['path'] );
	}
}

/**
 * @param string|array $var Data to sanitize.
 * @return string|array
 */
function mhk_clean( $var ) {
	if ( is_array( $var ) ) {
		return array_map( 'mhk_clean', $var );
	} else {
		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
	}
}

/**
 * @param  string $var Data to sanitize.
 * @return string
 */
function mhk_sanitize_textarea( $var ) {
	return implode( "\n", array_map( 'mhk_clean', explode( "\n", $var ) ) );
}

/**
 * @param  string $var Data to sanitize.
 * @return string
 */
function mhk_sanitize_tooltip( $var ) {
	return htmlspecialchars(
		wp_kses(
			html_entity_decode( $var ),
			array(
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
				'small'  => array(),
				'span'   => array(),
				'ul'     => array(),
				'li'     => array(),
				'ol'     => array(),
				'p'      => array(),
			)
		)
	);
}

/**
 * @param array $a1 First array to merge.
 * @param array $a2 Second array to merge.
 * @return array
 */
function mhk_array_overlay( $a1, $a2 ) {
	foreach ( $a1 as $k => $v ) {
		if ( ! array_key_exists( $k, $a2 ) ) {
			continue;
		}
		if ( is_array( $v ) && is_array( $a2[ $k ] ) ) {
			$a1[ $k ] = mhk_array_overlay( $v, $a2[ $k ] );
		} else {
			$a1[ $k ] = $a2[ $k ];
		}
	}
	return $a1;
}

/**
 * @param  array $array Array of data.
 * @return array
 */
function mhk_sanitize_array_combine( $array ) {
	if ( empty( $array ) || ! is_array( $array ) ) {
		return $array;
	}

	return array_map( 'sanitize_text_field', $array );
}

/**
 * @param  string $size Size value.
 * @return int
 */
function mhk_let_to_num( $size ) {
	$l    = substr( $size, -1 );
	$ret  = substr( $size, 0, -1 );
	$byte = 1024;

	switch ( strtoupper( $l ) ) {
		case 'P':
			$ret *= 1024;
			// No break.
		case 'T':
			$ret *= 1024;
			// No break.
		case 'G':
			$ret *= 1024;
			// No break.
		case 'M':
			$ret *= 1024;
			// No break.
		case 'K':
			$ret *= 1024;
			// No break.
	}
	return $ret;
}

/**
 * @return string
 */
function mhk_date_format() {
	return apply_filters( 'muhiku_forms_date_format', get_option( 'date_format' ) );
}

/**
 * @return string
 */
function mhk_time_format() {
	return apply_filters( 'muhiku_forms_time_format', get_option( 'time_format' ) );
}

/**
 * @param  array $value Value to flatten.
 * @return mixed
 */
function mhk_flatten_meta_callback( $value ) {
	return is_array( $value ) ? current( $value ) : $value;
}

if ( ! function_exists( 'mhk_rgb_from_hex' ) ) {

	/**
	 * @param mixed $color Color.
	 *
	 * @return array
	 */
	function mhk_rgb_from_hex( $color ) {
		$color = str_replace( '#', '', $color );
		$color = preg_replace( '~^(.)(.)(.)$~', '$1$1$2$2$3$3', $color );

		$rgb      = array();
		$rgb['R'] = hexdec( $color[0] . $color[1] );
		$rgb['G'] = hexdec( $color[2] . $color[3] );
		$rgb['B'] = hexdec( $color[4] . $color[5] );

		return $rgb;
	}
}

if ( ! function_exists( 'mhk_hex_darker' ) ) {

	/**
	 * @param mixed $color  Color.
	 * @param int   $factor Darker factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	function mhk_hex_darker( $color, $factor = 30 ) {
		$base  = mhk_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = $v / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v - $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'mhk_hex_lighter' ) ) {

	/**
	 * @param mixed $color  Color.
	 * @param int   $factor Lighter factor.
	 *                      Defaults to 30.
	 * @return string
	 */
	function mhk_hex_lighter( $color, $factor = 30 ) {
		$base  = mhk_rgb_from_hex( $color );
		$color = '#';

		foreach ( $base as $k => $v ) {
			$amount      = 255 - $v;
			$amount      = $amount / 100;
			$amount      = round( $amount * $factor );
			$new_decimal = $v + $amount;

			$new_hex_component = dechex( $new_decimal );
			if ( strlen( $new_hex_component ) < 2 ) {
				$new_hex_component = '0' . $new_hex_component;
			}
			$color .= $new_hex_component;
		}

		return $color;
	}
}

if ( ! function_exists( 'mhk_is_light' ) ) {

	/**
	 * @param mixed $color Color.
	 * @return bool  True if a light color.
	 */
	function mhk_hex_is_light( $color ) {
		$hex = str_replace( '#', '', $color );

		$c_r = hexdec( substr( $hex, 0, 2 ) );
		$c_g = hexdec( substr( $hex, 2, 2 ) );
		$c_b = hexdec( substr( $hex, 4, 2 ) );

		$brightness = ( ( $c_r * 299 ) + ( $c_g * 587 ) + ( $c_b * 114 ) ) / 1000;

		return $brightness > 155;
	}
}

if ( ! function_exists( 'mhk_light_or_dark' ) ) {

	/**
	 * @param mixed  $color Color.
	 * @param string $dark  Darkest reference.
	 *                      Defaults to '#000000'.
	 * @param string $light Lightest reference.
	 *                      Defaults to '#FFFFFF'.
	 * @return string
	 */
	function mhk_light_or_dark( $color, $dark = '#000000', $light = '#FFFFFF' ) {
		return mhk_hex_is_light( $color ) ? $dark : $light;
	}
}

if ( ! function_exists( 'mhk_format_hex' ) ) {

	/**
	 * @param string $hex HEX color.
	 * @return string|null
	 */
	function mhk_format_hex( $hex ) {
		$hex = trim( str_replace( '#', '', $hex ) );

		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		return $hex ? '#' . $hex : null;
	}
}

/**
 * @param  string $phone Phone number.
 * @return string
 */
function mhk_format_phone_number( $phone ) {
	return str_replace( '.', '-', $phone );
}

/**
 * @param  string $string String to format.
 * @return string
 */
function mhk_strtoupper( $string ) {
	return function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $string, 'UTF-8' ) : strtoupper( $string );
}

/**
 * @param  string $string String to format.
 * @return string
 */
function mhk_strtolower( $string ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $string, 'UTF-8' ) : strtolower( $string );
}

/**
 * @param  string  $string String to trim.
 * @param  integer $chars  Amount of characters.
 *                         Defaults to 200.
 * @param  string  $suffix Suffix.
 *                         Defaults to '...'.
 * @return string
 */
function mhk_trim_string( $string, $chars = 200, $suffix = '...' ) {
	if ( strlen( $string ) > $chars ) {
		if ( function_exists( 'mb_substr' ) ) {
			$string = mb_substr( $string, 0, ( $chars - mb_strlen( $suffix, 'UTF-8' ) ), 'UTF-8' ) . $suffix;
		} else {
			$string = substr( $string, 0, ( $chars - strlen( $suffix ) ) ) . $suffix;
		}
	}
	return $string;
}

/**
 * @param  string $raw_string Raw string.
 * @return string
 */
function mhk_format_content( $raw_string ) {
	return apply_filters( 'muhiku_forms_format_content', apply_filters( 'muhiku_forms_short_description', $raw_string ), $raw_string );
}

/**
 * @param  string $content Content.
 * @return string
 */
function mhk_do_oembeds( $content ) {
	global $wp_embed;

	$content = $wp_embed->autoembed( $content );

	return $content;
}

/**
 * @return array
 */
function mhk_array_merge_recursive_numeric() {
	$arrays = func_get_args();

	if ( 1 === count( $arrays ) ) {
		return $arrays[0];
	}

	foreach ( $arrays as $key => $array ) {
		if ( ! is_array( $array ) ) {
			unset( $arrays[ $key ] );
		}
	}

	$final = array_shift( $arrays );

	foreach ( $arrays as $b ) {
		foreach ( $final as $key => $value ) {
			if ( ! isset( $b[ $key ] ) ) {
				$final[ $key ] = $value;
			} else {
				if ( is_numeric( $value ) && is_numeric( $b[ $key ] ) ) {
					$final[ $key ] = $value + $b[ $key ];
				} elseif ( is_array( $value ) && is_array( $b[ $key ] ) ) {
					$final[ $key ] = mhk_array_merge_recursive_numeric( $value, $b[ $key ] );
				} else {
					$final[ $key ] = $b[ $key ];
				}
			}
		}

		foreach ( $b as $key => $value ) {
			if ( ! isset( $final[ $key ] ) ) {
				$final[ $key ] = $value;
			}
		}
	}

	return $final;
}

/**
 * @param array $raw_attributes Attribute name value pairs.
 * @return string
 */
function mhk_implode_html_attributes( $raw_attributes ) {
	$attributes = array();
	foreach ( $raw_attributes as $name => $value ) {
		$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}
	return implode( ' ', $attributes );
}

/**
 * @param mixed $raw_value Value stored in DB.
 * @return array Nicely formatted array with number and unit values.
 */
function mhk_parse_relative_date_option( $raw_value ) {
	$periods = array(
		'days'   => __( 'Day(s)', 'muhiku-plug' ),
		'weeks'  => __( 'Week(s)', 'muhiku-plug' ),
		'months' => __( 'Month(s)', 'muhiku-plug' ),
		'years'  => __( 'Year(s)', 'muhiku-plug' ),
	);

	$value = wp_parse_args(
		(array) $raw_value,
		array(
			'number' => '',
			'unit'   => 'days',
		)
	);

	$value['number'] = ! empty( $value['number'] ) ? absint( $value['number'] ) : '';

	if ( ! in_array( $value['unit'], array_keys( $periods ), true ) ) {
		$value['unit'] = 'days';
	}

	return $value;
}

/**
 * @param  array $value Value to flatten.
 * @return array
 */
function mhk_flatten_array( $value = array() ) {
	$return = array();
	array_walk_recursive( $value, function( $a ) use ( &$return ) { $return[] = $a; } ); 
	return $return;
}

/**
 *
 * @param  array $input       The input array.
 * @param  int   $offset      The offeset to start.
 * @param  int   $length      Optional length.
 * @param  array $replacement The replacement array.
 *
 * @return array the array consisting of the extracted elements.
 */
function mhk_array_splice_preserve_keys( &$input, $offset, $length = null, $replacement = array() ) {
	if ( empty( $replacement ) ) {
		return array_splice( $input, $offset, $length );
	}

	$part_before  = array_slice( $input, 0, $offset, true );
	$part_removed = array_slice( $input, $offset, $length, true );
	$part_after   = array_slice( $input, $offset + $length, null, true );

	$input = $part_before + $replacement + $part_after;

	return $part_removed;
}

<?php
/**
 * MuhikuPlug Template
 *
 * Functions for the templating system.
 *
 * @package MuhikuPlug/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add body classes for MHK pages.
 *
 * @param  array $classes Body Classes.
 * @return array
 */
function mhk_body_class( $classes ) {
	$classes = (array) $classes;

	$classes[] = 'muhiku-plug-no-js';

	add_action( 'wp_footer', 'mhk_no_js' );

	return array_unique( $classes );
}

/**
 * NO JS handling.
 *
 * @since 1.2.0
 */
function mhk_no_js() {
	if ( mhk_is_amp() ) {
		return;
	}
	?>
	<script type="text/javascript">
		var c = document.body.className;
		c = c.replace( /muhiku-plug-no-js/, 'muhiku-plug-js' );
		document.body.className = c;
	</script>
	<?php
}

/**
 * Output generator tag to aid debugging.
 *
 * @param string $gen Generator.
 * @param string $type Type.
 *
 * @return string
 */
function mhk_generator_tag( $gen, $type ) {
	switch ( $type ) {
		case 'html':
			$gen .= "\n" . '<meta name="generator" content="Muhiku Plug ' . esc_attr( MHK_VERSION ) . '">';
			break;
		case 'xhtml':
			$gen .= "\n" . '<meta name="generator" content="Muhiku Plug ' . esc_attr( MHK_VERSION ) . '" />';
			break;
	}

	return $gen;
}

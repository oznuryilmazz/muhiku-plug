<?php
/**
 * @package MuhikuPlug/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * @param  array $classes Body Classes.
 * @return array
 */
function mhk_body_class( $classes ) {
	$classes = (array) $classes;

	$classes[] = 'muhiku-plug-no-js';

	add_action( 'wp_footer', 'mhk_no_js' );

	return array_unique( $classes );
}

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

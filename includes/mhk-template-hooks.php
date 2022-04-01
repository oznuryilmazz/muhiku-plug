<?php
/**
 * MuhikuPlug Template Hooks
 *
 * Action/filter hooks used for MuhikuPlug functions/templates.
 *
 * @package MuhikuPlug/Templates
 * @version 1.2.0
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'body_class', 'mhk_body_class' );

/**
 * WP Header.
 *
 * @see mhk_generator_tag()
 */
add_filter( 'get_the_generator_html', 'mhk_generator_tag', 10, 2 );
add_filter( 'get_the_generator_xhtml', 'mhk_generator_tag', 10, 2 );

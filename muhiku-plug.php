<?php
/**
 * Plugin Name: Muhiku Plug
 * Plugin URI: https://wpmuhiku.com/wordpress-plugins/muhiku-plug/
 * Description: Drag and Drop contact form builder to easily create simple to complex forms for any purpose. Lightweight, Beautiful design, responsive and more.
 * Version: 1.8.6
 * Author: WPMuhiku
 * Author URI: https://wpmuhiku.com
 * Text Domain: muhiku-plug
 * Domain Path: /languages/
 *
 * @package MuhikuPlug
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define MHK_PLUGIN_FILE.
if ( ! defined( 'MHK_PLUGIN_FILE' ) ) {
	define( 'MHK_PLUGIN_FILE', __FILE__ );
}

// Include the main MuhikuPlug class.
if ( ! class_exists( 'MuhikuPlug' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-muhiku-plug.php';
}

/**
 * Main instance of MuhikuPlug.
 *
 * Returns the main instance of MHK to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return MuhikuPlug
 */
function mhk() {
	return MuhikuPlug::instance();
}

// Global for backwards compatibility.
$GLOBALS['muhiku-plug'] = mhk();

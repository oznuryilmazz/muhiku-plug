<?php
/**
 * Plugin Name: Muhiku Plug
 * Plugin URI: https://muhiku.com/
 * Description: Muhiku Plug-in
 * Author: Öznur Yılmaz
 * Author URI: https://oznuryilmaz.com/
 * Text Domain: muhiku-plug
 *
 * @package MuhikuPlug
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; 
}

if ( ! defined( 'MHK_PLUGIN_FILE' ) ) {
	define( 'MHK_PLUGIN_FILE', __FILE__ );
}

if ( ! class_exists( 'MuhikuPlug' ) ) {
	include_once dirname( __FILE__ ) . '/includes/class-muhiku-plug.php';
}

/**
 * @return MuhikuPlug
 */


function mhk() {
	return MuhikuPlug::instance();
}

$GLOBALS['muhiku-plug'] = mhk();

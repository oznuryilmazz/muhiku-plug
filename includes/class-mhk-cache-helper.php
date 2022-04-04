<?php
/**
 * Cache Helper Class
 * @package MuhikuPlug/Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_Cache_Helper {

	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	/**
	 * @param  string $group Group of cache to get.
	 * @return string
	 */
	public static function get_cache_prefix( $group ) {
		$prefix = wp_cache_get( 'mhk_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'mhk_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'mhk_cache_' . $prefix . '_';
	}

	/**
	 * @param string $group Group of cache to clear.
	 */
	public static function incr_cache_prefix( $group ) {
		wp_cache_incr( 'mhk_' . $group . '_cache_prefix', 1, $group );
	}

	/**
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		mhk_maybe_define_constant( 'DONOTCACHEPAGE', true );
		mhk_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		mhk_maybe_define_constant( 'DONOTCACHEDB', true );
		return $return;
	}

	public static function notices() {
		if ( ! function_exists( 'w3tc_pgcache_flush' ) || ! function_exists( 'w3_instance' ) ) {
			return;
		}

		$config   = w3_instance( 'W3_Config' );
		$enabled  = $config->get_integer( 'dbcache.enabled' );
		$settings = array_map( 'trim', $config->get_array( 'dbcache.reject.sql' ) );

		if ( $enabled && ! in_array( '_mhk_session_', $settings, true ) ) {
			?>
			<div class="error">
				<p>
				<?php
				echo wp_kses_post( sprintf( __( 'In order for <strong>database caching</strong> to work with Muhiku Plug you must add %1$s to the "Ignored Query Strings" option in <a href="%2$s">W3 Total Cache settings</a>.', 'muhiku-plug' ), '<code>_mhk_session_</code>', esc_url( admin_url( 'admin.php?page=w3tc_dbcache' ) ) ) );
				?>
				</p>
			</div>
			<?php
		}
	}
}

MHK_Cache_Helper::init();

<?php
/**
 * @package MuhikuPlug\Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_Session_Handler extends MHK_Session {

	/**
	 * @var string cookie name
	 */
	protected $_cookie;

	/**
	 * @var string session due to expire timestamp
	 */
	protected $_session_expiring;

	/**
	 * @var string session expiration timestamp
	 */
	protected $_session_expiration;

	/**
	 * @var bool Based on whether a cookie exists.
	 */
	protected $_has_cookie = false;

	/**
	 * @var string Custom session table name
	 */
	protected $_table;

	public function __construct() {
		$this->_cookie = apply_filters( 'muhiku_forms_cookie', 'wp_muhiku_forms_session_' . COOKIEHASH );
		$this->_table  = $GLOBALS['wpdb']->prefix . 'mhk_sessions';
	}

	public function init() {
		$cookie = $this->get_session_cookie();

		if ( $cookie ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_session_expiring   = $cookie[2];
			$this->_has_cookie         = true;

			if ( time() > $this->_session_expiring ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}
		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();
		}

		$this->_data = $this->get_session_data();

		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( ! is_user_logged_in() ) {
			add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

	/**
	 * @return bool
	 */
	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in();
	}

	public function set_session_expiration() {
		$this->_session_expiring   = time() + intval( apply_filters( 'mhk_session_expiring', 60 * 60 * 47 ) ); 
		$this->_session_expiration = time() + intval( apply_filters( 'mhk_session_expiration', 60 * 60 * 48 ) ); 
	}

	/**
	 * @return string
	 */
	public function generate_customer_id() {
		$customer_id = '';

		if ( is_user_logged_in() ) {
			$customer_id = get_current_user_id();
		}

		if ( empty( $customer_id ) ) {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher      = new PasswordHash( 8, false );
			$customer_id = md5( $hasher->get_random_bytes( 32 ) );
		}

		return $customer_id;
	}

	/**
	 * @return bool|array
	 */
	public function get_session_cookie() {
		$cookie_value = isset( $_COOKIE[ $this->_cookie ] ) ? wp_unslash( $_COOKIE[ $this->_cookie ] ) : false; 

		if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $cookie_value );

		if ( empty( $customer_id ) ) {
			return false;
		}

		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
	}

	/**
	 * @return array
	 */
	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_customer_id, array() ) : array();
	}

	/**
	 * @return string
	 */
	private function get_cache_prefix() {
		return MHK_Cache_Helper::get_cache_prefix( MHK_SESSION_CACHE_GROUP );
	}
	public function save_data() {
		if ( $this->_dirty && $this->has_session() ) {
			global $wpdb;

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}mhk_sessions (`session_key`, `session_value`, `session_expiry`) VALUES (%s, %s, %d)
					ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `session_expiry` = VALUES(`session_expiry`)",
					$this->_customer_id,
					maybe_serialize( $this->_data ),
					$this->_session_expiration
				)
			);

			wp_cache_set( $this->get_cache_prefix() . $this->_customer_id, $this->_data, MHK_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
			$this->_dirty = false;
		}
	}

	public function destroy_session() {
		mhk_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'mhk_session_use_secure_cookie', false ) );

		$this->delete_session( $this->_customer_id );

		$this->_data        = array();
		$this->_dirty       = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	/**
	 * @param int $uid User ID.
	 * @return string
	 */
	public function nonce_user_logged_out( $uid ) {
		return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
	}

	public function cleanup_sessions() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) ); 

		if ( class_exists( 'MHK_Cache_Helper' ) ) {
			MHK_Cache_Helper::incr_cache_prefix( MHK_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * @param string $customer_id Customer ID.
	 * @param mixed  $default Default session value.
	 * @return string|array
	 */
	public function get_session( $customer_id, $default = false ) {
		global $wpdb;

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		$value = wp_cache_get( $this->get_cache_prefix() . $customer_id, MHK_SESSION_CACHE_GROUP );

		if ( false === $value ) {
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ) );

			if ( is_null( $value ) ) {
				$value = $default;
			}

			wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, MHK_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
		}

		return maybe_unserialize( $value );
	}

	/**
	 * @param int $customer_id Customer ID.
	 */
	public function delete_session( $customer_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $customer_id, MHK_SESSION_CACHE_GROUP );

		$wpdb->delete( 
			$this->_table,
			array(
				'session_key' => $customer_id,
			)
		);
	}

	/**
	 * @param string $customer_id Customer ID.
	 * @param int    $timestamp Timestamp to expire the cookie.
	 */
	public function update_session_timestamp( $customer_id, $timestamp ) {
		global $wpdb;

		$wpdb->update(
			$this->_table,
			array(
				'session_expiry' => $timestamp,
			),
			array(
				'session_key' => $customer_id,
			),
			array(
				'%d'
			)
		);
	}
}

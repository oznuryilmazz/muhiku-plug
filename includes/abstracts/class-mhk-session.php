<?php
/**
 * @package MuhikuPlug\Abstracts
 */

defined( 'ABSPATH' ) || exit;

abstract class MHK_Session {

	/**
	 * @var int $_customer_id 
	 */
	protected $_customer_id;

	/**
	 * @var array $_data
	 */
	protected $_data = array();

	/**
	 * @var bool $_dirty
	 */
	protected $_dirty = false;

	public function init() {}

	public function cleanup_sessions() {}

	/**
	 * @param mixed $key 
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * @param mixed $key 
	 * @param mixed $value 
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	/**
	 * @param mixed $key
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_title( $key ) ] );
	}

	/**
	 * @param mixed $key
	 */
	public function __unset( $key ) {
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	/**
	 * @param string $key
	 * @param mixed  $default 
	 * @return array|string value of session variable
	 */
	public function get( $key, $default = null ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default;
	}

	/**
	 * @param string $key 
	 * @param mixed  $value 
	 */
	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty                        = true;
		}
	}

	/**
	 * @return int
	 */
	public function get_customer_id() {
		return $this->_customer_id;
	}
}

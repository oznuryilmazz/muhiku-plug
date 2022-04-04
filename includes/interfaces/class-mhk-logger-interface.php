<?php
/**
 * @package MuhikuPlug/Interface
 */

defined( 'ABSPATH' ) || exit;

interface MHK_Logger_Interface {

	/**
	 * @param string $handle File handle.
	 * @param string $message Log message.
	 * @param string $level Log level.
	 *
	 * @return bool True if log was added, otherwise false.
	 */
	public function add( $handle, $message, $level = MHK_Log_Levels::NOTICE );

	/**
	 * @param string $level One of the following:
	 *     'emergency': System is unusable.
	 *     'alert': Action must be taken immediately.
	 *     'critical': Critical conditions.
	 *     'error': Error conditions.
	 *     'warning': Warning conditions.
	 *     'notice': Normal but significant condition.
	 *     'info': Informational messages.
	 *     'debug': Debug-level messages.
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function log( $level, $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function emergency( $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function alert( $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function critical( $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function error( $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function warning( $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function notice( $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function info( $message, $context = array() );

	/**
	 * @param string $message Log message.
	 * @param array  $context Optional. Additional information for log handlers.
	 */
	public function debug( $message, $context = array() );
}

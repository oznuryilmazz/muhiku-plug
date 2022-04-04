<?php
/**
 * @package MuhikuPlug\Abstracts
 */

defined( 'ABSPATH' ) || exit;

abstract class MHK_Log_Handler implements MHK_Log_Handler_Interface {

	/**
	 * @param int $timestamp 
	 *
	 * @return string Formatted time for use in log entry.
	 */
	protected static function format_time( $timestamp ) {
		return date( 'c', $timestamp ); 
	}

	/**
	 * @param  int    $timestamp 
	 * @param  string $level 
	 * @param  string $message 
	 * @param  array  $context 
	 *
	 * @return string Formatted log entry.
	 */
	protected static function format_entry( $timestamp, $level, $message, $context ) {
		$time_string  = self::format_time( $timestamp );
		$level_string = strtoupper( $level );
		$entry        = "{$time_string} {$level_string} {$message}";

		return apply_filters(
			'muhiku_forms_format_log_entry',
			$entry,
			array(
				'timestamp' => $timestamp,
				'level'     => $level,
				'message'   => $message,
				'context'   => $context,
			)
		);
	}
}

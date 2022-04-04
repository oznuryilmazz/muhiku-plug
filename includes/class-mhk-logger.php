<?php
/**
 * @package MuhikuPlug/Classes
 */

defined( 'ABSPATH' ) || exit;


class MHK_Logger implements MHK_Logger_Interface {

	/**
	 * @var array
	 */
	protected $handlers;

	/**
	 * @var int Integer representation of minimum log level to handle.
	 */
	protected $threshold;

	/**
	 * @param array  $handlers Optional. Array of log handlers. If $handlers is not provided, the filter 'muhiku_forms_register_log_handlers' will be used to define the handlers. If $handlers is provided, the filter will not be applied and the handlers will be used directly.
	 * @param string $threshold Optional. Define an explicit threshold. May be configured via  MHK_LOG_THRESHOLD. By default, all logs will be processed.
	 */
	public function __construct( $handlers = null, $threshold = null ) {
		if ( null === $handlers ) {
			$handlers = apply_filters( 'muhiku_forms_register_log_handlers', array() );
		}

		$register_handlers = array();

		if ( ! empty( $handlers ) && is_array( $handlers ) ) {
			foreach ( $handlers as $handler ) {
				$implements = class_implements( $handler );
				if ( is_object( $handler ) && is_array( $implements ) && in_array( 'MHK_Log_Handler_Interface', $implements, true ) ) {
					$register_handlers[] = $handler;
				} else {
					mhk_doing_it_wrong(
						__METHOD__,
						sprintf(
							/* translators: 1: class name 2: MHK_Log_Handler_Interface */
							__( 'The provided handler %1$s does not implement %2$s.', 'muhiku-plug' ),
							'<code>' . esc_html( is_object( $handler ) ? get_class( $handler ) : $handler ) . '</code>',
							'<code>MHK_Log_Handler_Interface</code>'
						),
						'1.0'
					);
				}
			}
		}

		if ( null !== $threshold ) {
			$threshold = MHK_Log_Levels::get_level_severity( $threshold );
		} elseif ( defined( 'MHK_LOG_THRESHOLD' ) && MHK_Log_Levels::is_valid_level( MHK_LOG_THRESHOLD ) ) {
			$threshold = MHK_Log_Levels::get_level_severity( MHK_LOG_THRESHOLD );
		} else {
			$threshold = null;
		}

		$this->handlers  = $register_handlers;
		$this->threshold = $threshold;
	}

	/**
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @return bool True if the log should be handled.
	 */
	protected function should_handle( $level ) {
		if ( null === $this->threshold ) {
			return true;
		}
		return $this->threshold <= MHK_Log_Levels::get_level_severity( $level );
	}

	/**
	 * @param string $handle File handle.
	 * @param string $message Message to log.
	 * @param string $level Logging level.
	 *
	 * @return bool
	 */
	public function add( $handle, $message, $level = MHK_Log_Levels::NOTICE ) {
		$message = apply_filters( 'muhiku_forms_logger_add_message', $message, $handle );
		$this->log(
			$level,
			$message,
			array(
				'source'  => $handle,
				'_legacy' => true,
			)
		);
		mhk_do_deprecated_action( 'muhiku_forms_log_add', array( $handle, $message ), '1.2', 'This action has been deprecated with no alternative.' );
		return true;
	}

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
	public function log( $level, $message, $context = array() ) {
		if ( 'no' === get_option( 'muhiku_forms_enable_log', 'no' ) ) {
			return false;
		}

		if ( ! MHK_Log_Levels::is_valid_level( $level ) ) {
			mhk_doing_it_wrong( __METHOD__, sprintf( __( '%1$s was called with an invalid level "%2$s".', 'muhiku-plug' ), '<code>MHK_Logger::log</code>', $level ), '1.2' );
		}

		if ( $this->should_handle( $level ) ) {
			$message = apply_filters( 'muhiku_forms_logger_log_message', $message, $level, $context );

			foreach ( $this->handlers as $handler ) {
				$handler->handle( time(), $level, $message, $context );
			}
		}
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function emergency( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::EMERGENCY, $message, $context );
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function alert( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::ALERT, $message, $context );
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function critical( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::CRITICAL, $message, $context );
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function error( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::ERROR, $message, $context );
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function warning( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::WARNING, $message, $context );
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function notice( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::NOTICE, $message, $context );
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function info( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::INFO, $message, $context );
	}

	/**
	 * @param string $message Message to log.
	 * @param array  $context Log context.
	 */
	public function debug( $message, $context = array() ) {
		$this->log( MHK_Log_Levels::DEBUG, $message, $context );
	}

	/**
	 * @param string $source Source/handle to clear.
	 * @return bool
	 */
	public function clear( $source = '' ) {
		if ( ! $source ) {
			return false;
		}
		foreach ( $this->handlers as $handler ) {
			if ( is_callable( array( $handler, 'clear' ) ) ) {
				$handler->clear( $source );
			}
		}
		return true;
	}
	public function clear_expired_logs() {
		$days      = absint( apply_filters( 'muhiku_forms_logger_days_to_retain_logs', 30 ) );
		$timestamp = strtotime( "-{$days} days" );

		foreach ( $this->handlers as $handler ) {
			if ( is_callable( array( $handler, 'delete_logs_before_timestamp' ) ) ) {
				$handler->delete_logs_before_timestamp( $timestamp );
			}
		}
	}
}

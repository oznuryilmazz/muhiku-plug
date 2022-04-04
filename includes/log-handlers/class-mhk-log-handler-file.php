<?php
/**
 * @package MuhikuPlug/Classes/Log_Handlers
 */

defined( 'ABSPATH' ) || exit;

class MHK_Log_Handler_File extends MHK_Log_Handler {

	/**
	 * @var array
	 */
	protected $handles = array();

	/**
	 * @var int
	 */
	protected $log_size_limit;

	/**
	 * @var array
	 */
	protected $cached_logs = array();

	/**
	 * @param int $log_size_limit Optional. Size limit for log files. Default 5mb.
	 */
	public function __construct( $log_size_limit = null ) {
		if ( null === $log_size_limit ) {
			$log_size_limit = 5 * 1024 * 1024;
		}

		$this->log_size_limit = apply_filters( 'muhiku_forms_log_file_size_limit', $log_size_limit );

		add_action( 'plugins_loaded', array( $this, 'write_cached_logs' ) );
	}

	public function __destruct() {
		foreach ( $this->handles as $handle ) {
			if ( is_resource( $handle ) ) {
				fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}
		}
	}

	/**
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array  $context {
	 *      Additional information for log handlers.
	 *
	 *     @type string $source Optional. Determines log file to write to. Default 'log'.
	 *     @type bool $_legacy Optional. Default false. True to use outdated log format
	 *         originally used in deprecated MHK_Logger::add calls.
	 * }
	 *
	 * @return bool False if value was not handled and true if value was handled.
	 */
	public function handle( $timestamp, $level, $message, $context ) {
		if ( isset( $context['source'] ) && $context['source'] ) {
			$handle = $context['source'];
		} else {
			$handle = 'log';
		}

		$entry = self::format_entry( $timestamp, $level, $message, $context );

		return $this->add( $entry, $handle );
	}

	/**
	 * @param int    $timestamp Log timestamp.
	 * @param string $level emergency|alert|critical|error|warning|notice|info|debug.
	 * @param string $message Log message.
	 * @param array  $context Additional information for log handlers.
	 *
	 * @return string Formatted log entry.
	 */
	protected static function format_entry( $timestamp, $level, $message, $context ) {
		if ( isset( $context['_legacy'] ) && true === $context['_legacy'] ) {
			if ( isset( $context['source'] ) && $context['source'] ) {
				$handle = $context['source'];
			} else {
				$handle = 'log';
			}
			$message = apply_filters( 'muhiku_forms_logger_add_message', $message, $handle );
			$time    = date_i18n( 'm-d-Y @ H:i:s' );
			$entry   = "{$time} - {$message}";
		} else {
			$entry = parent::format_entry( $timestamp, $level, $message, $context );
		}

		return $entry;
	}

	/**
	 * @param string $handle Log handle.
	 * @param string $mode Optional. File mode. Default 'a'.
	 * @return bool Success.
	 */
	protected function open( $handle, $mode = 'a' ) {
		if ( $this->is_open( $handle ) ) {
			return true;
		}

		$file = self::get_log_file_path( $handle );

		if ( $file ) {
			if ( ! file_exists( $file ) ) {
				$temphandle = @fopen( $file, 'w+' );  
				@fclose( $temphandle );  

				if ( defined( 'FS_CHMOD_FILE' ) ) {
					@chmod( $file, FS_CHMOD_FILE );  
				}
			}

			$resource = @fopen( $file, $mode );  

			if ( $resource ) {
				$this->handles[ $handle ] = $resource;
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $handle Log handle.
	 * @return bool True if $handle is open.
	 */
	protected function is_open( $handle ) {
		return array_key_exists( $handle, $this->handles ) && is_resource( $this->handles[ $handle ] );
	}

	/**
	 * @param string $handle Log handle.
	 * @return bool success
	 */
	protected function close( $handle ) {
		$result = false;

		if ( $this->is_open( $handle ) ) {
			$result = fclose( $this->handles[ $handle ] );  
			unset( $this->handles[ $handle ] );
		}

		return $result;
	}

	/**
	 * @param string $entry Log entry text.
	 * @param string $handle Log entry handle.
	 *
	 * @return bool True if write was successful.
	 */
	protected function add( $entry, $handle ) {
		$result = false;

		if ( $this->should_rotate( $handle ) ) {
			$this->log_rotate( $handle );
		}

		if ( $this->open( $handle ) && is_resource( $this->handles[ $handle ] ) ) {
			$result = fwrite( $this->handles[ $handle ], $entry . PHP_EOL ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fwrite
		} else {
			$this->cache_log( $entry, $handle );
		}

		return false !== $result;
	}

	/**
	 * @param string $handle Log handle.
	 *
	 * @return bool
	 */
	public function clear( $handle ) {
		$result = false;

		$this->close( $handle );

		if ( $this->open( $handle, 'w' ) && is_resource( $this->handles[ $handle ] ) ) {
			$result = true;
		}

		do_action( 'muhiku_forms_log_clear', $handle );

		return $result;
	}

	/**
	 * @param string $handle Log handle.
	 *
	 * @return bool
	 */
	public function remove( $handle ) {
		$removed = false;
		$logs    = $this->get_log_files();
		$handle  = sanitize_title( $handle );

		if ( isset( $logs[ $handle ] ) && $logs[ $handle ] ) {
			$file = realpath( trailingslashit( MHK_LOG_DIR ) . $logs[ $handle ] );
			if ( 0 === stripos( $file, realpath( trailingslashit( MHK_LOG_DIR ) ) ) && is_file( $file ) && is_writable( $file ) ) { // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_is_writable
				$this->close( $file ); // Close first to be certain no processes keep it alive after it is unlinked.
				$removed = unlink( $file ); // phpcs:ignore WordPress.VIP.FileSystemWritesDisallow.file_ops_unlink
			}
			do_action( 'muhiku_forms_log_remove', $handle, $removed );
		}
		return $removed;
	}

	/**
	 * @param string $handle Log handle.
	 * @return bool True if if should be rotated.
	 */
	protected function should_rotate( $handle ) {
		$file = self::get_log_file_path( $handle );
		if ( $file ) {
			if ( $this->is_open( $handle ) ) {
				$file_stat = fstat( $this->handles[ $handle ] );
				return $file_stat['size'] > $this->log_size_limit;
			} elseif ( file_exists( $file ) ) {
				return filesize( $file ) > $this->log_size_limit;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param string $handle Log handle.
	 */
	protected function log_rotate( $handle ) {
		for ( $i = 8; $i >= 0; $i-- ) {
			$this->increment_log_infix( $handle, $i );
		}
		$this->increment_log_infix( $handle );
	}

	/**
	 * @param string   $handle Log handle.
	 * @param null|int $number Optional. Default null. Log suffix number to be incremented.
	 * @return bool True if increment was successful, otherwise false.
	 */
	protected function increment_log_infix( $handle, $number = null ) {
		if ( null === $number ) {
			$suffix      = '';
			$next_suffix = '.0';
		} else {
			$suffix      = '.' . $number;
			$next_suffix = '.' . ( $number + 1 );
		}

		$rename_from = self::get_log_file_path( "{$handle}{$suffix}" );
		$rename_to   = self::get_log_file_path( "{$handle}{$next_suffix}" );

		if ( $this->is_open( $rename_from ) ) {
			$this->close( $rename_from );
		}

		if ( is_writable( $rename_from ) ) { 
			return rename( $rename_from, $rename_to ); 
		} else {
			return false;
		}
	}

	/**
	 * @param string $handle Log name.
	 * @return bool|string The log file path or false if path cannot be determined.
	 */
	public static function get_log_file_path( $handle ) {
		if ( function_exists( 'wp_hash' ) ) {
			return trailingslashit( MHK_LOG_DIR ) . self::get_log_file_name( $handle );
		} else {
			mhk_doing_it_wrong( __METHOD__, __( 'This method should not be called before plugins_loaded.', 'muhiku-plug' ), '1.2' );
			return false;
		}
	}

	/**
	 * @param string $handle Log name.
	 * @return bool|string The log file name or false if cannot be determined.
	 */
	public static function get_log_file_name( $handle ) {
		if ( function_exists( 'wp_hash' ) ) {
			$date_suffix = date( 'Y-m-d', time() ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			$hash_suffix = wp_hash( $handle );
			return sanitize_file_name( implode( '-', array( $handle, $date_suffix, $hash_suffix ) ) . '.log' );
		} else {
			mhk_doing_it_wrong( __METHOD__, esc_html__( 'This method should not be called before plugins_loaded.', 'muhiku-plug' ), '1.2' );
			return false;
		}
	}

	/**
	 * @param string $entry Log entry text.
	 * @param string $handle Log entry handle.
	 */
	protected function cache_log( $entry, $handle ) {
		$this->cached_logs[] = array(
			'entry'  => $entry,
			'handle' => $handle,
		);
	}

	public function write_cached_logs() {
		foreach ( $this->cached_logs as $log ) {
			$this->add( $log['entry'], $log['handle'] );
		}
	}

	/**
	 * @param integer $timestamp Timestamp to delete logs before.
	 */
	public static function delete_logs_before_timestamp( $timestamp = 0 ) {
		if ( ! $timestamp ) {
			return;
		}

		$log_files = self::get_log_files();

		foreach ( $log_files as $log_file ) {
			$last_modified = filemtime( trailingslashit( MHK_LOG_DIR ) . $log_file );

			if ( $last_modified < $timestamp ) {
				@unlink( trailingslashit( MHK_LOG_DIR ) . $log_file );
			}
		}
	}

	/**
	 * @return array
	 */
	public static function get_log_files() {
		$files  = @scandir( MHK_LOG_DIR );  .
		$result = array();

		if ( ! empty( $files ) ) {
			foreach ( $files as $key => $value ) {
				if ( ! in_array( $value, array( '.', '..' ), true ) ) {
					if ( ! is_dir( $value ) && strstr( $value, '.log' ) ) {
						$result[ sanitize_title( $value ) ] = $value;
					}
				}
			}
		}

		return $result;
	}
}

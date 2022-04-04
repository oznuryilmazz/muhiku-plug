<?php
/**
 * @package MuhikuPlug/Admin/Tools
 */

defined( 'ABSPATH' ) || exit;

class MHK_Admin_Tools {

	public static function output() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-tools.php';
	}

	public static function import() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-import.php';
	}

	public static function export() {
		include_once dirname( __FILE__ ) . '/views/html-admin-page-export.php';
	}

	public static function status_logs() {
		self::status_logs_file();
	}

	public static function status_logs_file() {
		$logs = self::scan_log_files();

		if ( ! empty( $_REQUEST['log_file'] ) && isset( $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ] ) ) { 
			$viewed_log = $logs[ sanitize_title( wp_unslash( $_REQUEST['log_file'] ) ) ]; 
		} elseif ( ! empty( $logs ) ) {
			$viewed_log = current( $logs );
		}

		if ( ! empty( $_REQUEST['handle'] ) ) { 
			self::remove_log();
		}

		include_once 'views/html-admin-page-tools-logs.php';
	}

	/**
	 * @param  string $file
	 * @return string
	 */
	public static function get_file_version( $file ) {
		if ( ! file_exists( $file ) ) {
			return '';
		}

		$fp = fopen( $file, 'r' ); 

		$file_data = fread( $fp, 8192 ); 

		fclose( $fp ); 

		$file_data = str_replace( "\r", "\n", $file_data );
		$version   = '';

		if ( preg_match( '/^[ \t\/*#@]*' . preg_quote( '@version', '/' ) . '(.*)$/mi', $file_data, $match ) && $match[1] ) {
			$version = _cleanup_header_comment( $match[1] );
		}

		return $version;
	}

	/**
	 * @param string $filename 
	 * @return string
	 */
	public static function get_log_file_handle( $filename ) {
		return substr( $filename, 0, strlen( $filename ) > 48 ? strlen( $filename ) - 48 : strlen( $filename ) - 4 );
	}

	/**
	 * @param  string $template_path 
	 * @return array
	 */
	public static function scan_template_files( $template_path ) {
		$files  = @scandir( $template_path ); 
		$result = array();

		if ( ! empty( $files ) ) {

			foreach ( $files as $key => $value ) {

				if ( ! in_array( $value, array( '.', '..' ), true ) ) {

					if ( is_dir( $template_path . DIRECTORY_SEPARATOR . $value ) ) {
						$sub_files = self::scan_template_files( $template_path . DIRECTORY_SEPARATOR . $value );
						foreach ( $sub_files as $sub_file ) {
							$result[] = $value . DIRECTORY_SEPARATOR . $sub_file;
						}
					} else {
						$result[] = $value;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public static function scan_log_files() {
		$files  = @scandir( MHK_LOG_DIR ); 
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

	public static function remove_log() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'remove_log' ) ) {
			wp_die( esc_html__( 'Eylem başarısız. Lütfen sayfayı yenileyin ve tekrar deneyin.', 'muhiku-plug' ) );
		}

		if ( ! empty( $_REQUEST['handle'] ) ) {
			$log_handler = new MHK_Log_Handler_File();
			$log_handler->remove( sanitize_text_field( wp_unslash( $_REQUEST['handle'] ) ) );
		}

		wp_safe_redirect( esc_url_raw( admin_url( 'admin.php?page=mhk-tools&tab=logs' ) ) );
		exit();
	}
}

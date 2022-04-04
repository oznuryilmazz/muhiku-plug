<?php
/**
 * @package MuhikuPlug/Export
 */

defined( 'ABSPATH' ) || exit;

abstract class MHK_CSV_Exporter {

	/**
	 * @var string
	 */
	protected $export_type = '';

	/**
	 * @var string
	 */
	protected $filename = 'mhk-export.csv';

	/**
	 * @var array
	 */
	protected $row_data = array();

	/**
	 * @var array
	 */
	protected $column_names = array();

	/**
	 * @var array
	 */
	protected $columns_to_export = array();

	/**
	 * @var string
	 */
	protected $delimiter = ',';

	abstract public function prepare_data_to_export();

	abstract public function get_quiz_report();

	/**
	 * @return string
	 */
	public function get_delimiter() {
		return apply_filters( "muhiku_forms_{$this->export_type}_export_delimiter", $this->delimiter );
	}

	/**
	 * @return array
	 */
	public function get_column_names() {
		return apply_filters( "muhiku_forms_{$this->export_type}_export_column_names", $this->column_names, $this );
	}

	/**
	 * @param array $column_names
	 */
	public function set_column_names( $column_names ) {
		$this->column_names = array();

		foreach ( $column_names as $column_id => $column_name ) {
			$this->column_names[ mhk_clean( $column_id ) ] = mhk_clean( $column_name );
		}
	}

	/**
	 * @return array
	 */
	public function get_columns_to_export() {
		return $this->columns_to_export;
	}

	/**
	 * @param array $columns
	 */
	public function set_columns_to_export( $columns ) {
		$this->columns_to_export = array_map( 'mhk_clean', $columns );
	}

	/**
	 * @param  string $column_id 
	 * @return boolean
	 */
	public function is_column_exporting( $column_id ) {
		$column_id         = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
		$columns_to_export = $this->get_columns_to_export();

		if ( empty( $columns_to_export ) ) {
			return true;
		}

		if ( in_array( $column_id, $columns_to_export, true ) || 'meta' === $column_id ) {
			return true;
		}

		return false;
	}

	/**
	 * @return array
	 */
	public function get_default_column_names() {
		return array();
	}

	public function export() {
		$this->prepare_data_to_export();
		$this->send_headers();
		$this->send_content( chr( 239 ) . chr( 187 ) . chr( 191 ) . $this->export_column_headers() . $this->get_csv_data() );
		die();
	}

	public function export_quiz_report() {
		$this->send_headers();
		$this->send_content( $this->get_quiz_report() );
		die();
	}

	public function send_headers() {
		if ( function_exists( 'gc_enable' ) ) {
			gc_enable(); 
		}
		if ( function_exists( 'apache_setenv' ) ) {
			@apache_setenv( 'no-gzip', 1 ); 
		}
		@ini_set( 'zlib.output_compression', 'Off' ); 
		@ini_set( 'output_buffering', 'Off' ); 
		@ini_set( 'output_handler', '' );
		ignore_user_abort( true );
		mhk_set_time_limit( 0 );
		mhk_nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $this->get_filename() );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
	}

	/**
	 * @param  string $filename 
	 */
	public function set_filename( $filename ) {
		$this->filename = sanitize_file_name( str_replace( '.csv', '', $filename ) . '.csv' );
	}

	/**
	 * @return string
	 */
	public function get_filename() {
		return sanitize_file_name( apply_filters( "muhiku_forms_{$this->export_type}_export_get_filename", $this->filename ) );
	}

	/**
	 * @param string $csv_data 
	 */
	public function send_content( $csv_data ) {
		echo $csv_data; 
	}

	/**
	 * @return string
	 */
	protected function get_csv_data() {
		return $this->export_rows();
	}

	/**
	 * @return string
	 */
	protected function export_column_headers() {
		$columns    = $this->get_column_names();
		$export_row = array();
		$buffer     = fopen( 'php://output', 'w' ); 
		ob_start();

		foreach ( $columns as $column_id => $column_name ) {
			if ( ! $this->is_column_exporting( $column_id ) ) {
				continue;
			}
			$export_row[] = $this->format_data( $column_name );
		}

		$this->fputcsv( $buffer, $export_row );

		return ob_get_clean();
	}

	/**
	 * @return array
	 */
	protected function get_data_to_export() {
		return $this->row_data;
	}

	/**
	 * @return string
	 */
	protected function export_rows() {
		$data   = $this->get_data_to_export();
		$buffer = fopen( 'php://output', 'w' ); 
		ob_start();

		array_walk( $data, array( $this, 'export_row' ), $buffer );

		return apply_filters( "muhiku_forms_{$this->export_type}_export_rows", ob_get_clean(), $this );
	}

	/**
	 * @param array    $row_data 
	 * @param string   $key 
	 * @param resource $buffer 
	 */
	protected function export_row( $row_data, $key, $buffer ) {
		$columns    = $this->get_column_names();
		$export_row = array();

		foreach ( $columns as $column_id => $column_name ) {
			if ( ! $this->is_column_exporting( $column_id ) ) {
				continue;
			}
			if ( isset( $row_data[ $column_id ] ) ) {
				$export_row[] = $this->format_data( $row_data[ $column_id ] );
			} else {
				$export_row[] = '';
			}
		}

		$this->fputcsv( $buffer, $export_row );
	}

	/**
	 * @param string $data
	 * @return string
	 */
	public function escape_data( $data ) {
		$active_content_triggers = array( '=', '+', '-', '@' );

		if ( in_array( mb_substr( $data, 0, 1 ), $active_content_triggers, true ) ) {  
			$data = "'" . $data;
		}

		return $data;
	}

	/**
	 * @param  string $data
	 * @return string
	 */
	public function format_data( $data ) {
		if ( ! is_scalar( $data ) ) {
			$data = ''; 
		} elseif ( is_bool( $data ) ) {
			$data = $data ? 1 : 0;
		}

		$use_mb = function_exists( 'mb_convert_encoding' );

		if ( $use_mb ) {
			$encoding = mb_detect_encoding( $data, 'UTF-8, ISO-8859-1', true );
			$data     = 'UTF-8' === $encoding ? $data : utf8_encode( $data );
		}

		return $this->escape_data( $data );
	}

	/**
	 * @param  array $values Values to implode.
	 * @return string
	 */
	protected function implode_values( $values ) {
		$values_to_implode = array();

		if ( ! empty( $values['label'] ) ) {
			$values = $values['label'];
		}

		if ( is_array( $values ) ) {
			foreach ( $values as $value ) {
				$value               = is_scalar( $value ) ? (string) $value : '';
				$values_to_implode[] = str_replace( ',', '\\,', $value );
			}
		} else {
			$values_to_implode[] = str_replace( ',', '\\,', $values );
		}

		return implode( ', ', $values_to_implode );
	}

	/**
	 * @param resource $buffer 
	 * @param array    $export_row 
	 */
	protected function fputcsv( $buffer, $export_row ) {
		if ( version_compare( PHP_VERSION, '5.5.4', '<' ) ) {
			ob_start();
			$temp = fopen( 'php://output', 'w' ); 
    		fputcsv( $temp, $export_row, $this->get_delimiter(), '"' ); 
			fclose( $temp ); 
			$row = ob_get_clean();
			$row = str_replace( '\\"', '\\""', $row );
			fwrite( $buffer, $row );
		} else {
			fputcsv( $buffer, $export_row, $this->get_delimiter(), '"', "\0" ); 
		}
	}
}

<?php
/**
 * @package MuhikuPlug\Export
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MHK_CSV_Exporter', false ) ) {
	require_once MHK_ABSPATH . 'includes/export/abstract-mhk-csv-exporter.php';
}

class MHK_Entry_CSV_Exporter extends MHK_CSV_Exporter {

	/**
	 * @var int|mixed
	 */
	public $form_id;

	/**
	 * @var int|mixed
	 */
	public $entry_id;

	/**
	 * @var string
	 */
	protected $export_type = 'entry';

	/**
	 * @param int $form_id  
	 * @param int $entry_id 
	 */
	public function __construct( $form_id = '', $entry_id = '' ) {
		$this->form_id      = absint( $form_id );
		$this->entry_id     = absint( $entry_id );
		$this->column_names = $this->get_default_column_names();
	}

	/**
	 * @return array
	 */
	public function get_default_column_names() {
		$columns   = array();
		$form_obj  = mhk()->form->get( $this->form_id );
		$form_data = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

		$columns['entry_id'] = esc_html__( 'ID', 'muhiku-plug' );

		if ( ! empty( $form_data['form_fields'] ) ) {
			foreach ( $form_data['form_fields'] as $field ) {
				if ( ! in_array( $field['type'], array( 'html', 'title', 'captcha' ), true ) ) {
					$columns[ $field['meta-key'] ] = mhk_clean( $field['label'] );
				}
			}
		}

		$columns['status']           = esc_html__( 'Status', 'muhiku-plug' );
		$columns['date_created']     = esc_html__( 'Oluşturulma Tarihi', 'muhiku-plug' );
		$columns['date_created_gmt'] = esc_html__( 'Oluşturulma Tarihi GMT', 'muhiku-plug' );

		if ( 'yes' !== get_option( 'muhiku_forms_disable_user_details' ) ) {
			$columns['user_device']     = esc_html__( 'User Device', 'muhiku-plug' );
			$columns['user_ip_address'] = esc_html__( 'User IP Address', 'muhiku-plug' );
		}

		return apply_filters( "muhiku_forms_export_{$this->export_type}_default_columns", $columns );
	}

	public function prepare_data_to_export() {
		$this->row_data = array();

		if ( $this->entry_id ) {
			$entry            = mhk_get_entry( $this->entry_id );
			$this->row_data[] = $this->generate_row_data( $entry );
		} else {
			$entry_ids = mhk_search_entries(
				array(
					'limit'   => -1,
					'order'   => 'ASC',
					'form_id' => $this->form_id,
				)
			);

			$entries = array_map( 'mhk_get_entry', $entry_ids );

			foreach ( $entries as $entry ) {
				$this->row_data[] = $this->generate_row_data( $entry );
			}
		}

		return $this->row_data;
	}

	public function get_quiz_report() {
		$form_data          = MHK()->form->get(
			absint( $this->form_id ),
			array(
				'content_only' => true,
			)
		);
		$form_fields        = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$entry              = mhk_get_entry( $this->entry_id );
		$columns            = array( 'ID' );
		$row                = array( $this->entry_id );
		$total_score        = 0;
		$respondent_score   = 0;
		$obtained_score     = 0;
		$include_all_fields = apply_filters( 'mhk_include_all_fields_in_quiz_report_csv', false );

		foreach ( $form_fields as $field_id => $field ) {
			$quiz_enabled = isset( $field['quiz_status'] ) && '1' === $field['quiz_status'] ? true : false;

			if ( false === $include_all_fields && false === $quiz_enabled ) {
				continue;
			}

			$meta_key       = isset( $field['meta-key'] ) ? $field['meta-key'] : '';
			$given_answer   = isset( $entry->meta[ $meta_key ] ) ? $entry->meta[ $meta_key ] : null;
			$correct_answer = isset( $field['correct_answer'] ) ? $field['correct_answer'] : array();
			$field_score    = empty( $field['score'] ) ? 0 : $field['score'];
			$score          = 0;
			$total_score   += $field_score;
			$is_correct     = false;

			if ( ! is_null( $given_answer ) ) {
				$respondent_score += $field_score;

				if ( ! empty( $correct_answer ) ) {
					if ( 'select' === $field['type'] ) {
						foreach ( $correct_answer as $answer_key => $answer_status ) {
							$choice = $field['choices'][ $answer_key ]['label'];

							if ( $given_answer === $choice ) {
								$is_correct = true;
								break;
							}
						}
					} elseif ( 'radio' === $field['type'] ) {
						$correct_answer_key = array_keys( $correct_answer )[0];
						$correct_answer     = $field['choices'][ $correct_answer_key ]['label'];
						$given_answer       = maybe_unserialize( $given_answer )['label'];
						$is_correct         = ( $given_answer === $correct_answer );
					} elseif ( 'checkbox' === $field['type'] ) {
						$given_answer_data   = maybe_unserialize( $given_answer )['label'];
						$is_correct          = true;
						$choices             = $field['choices'];
						$correct_answer_keys = array_keys( $correct_answer );
						$correct_answers     = array();
						$given_answers       = array();

						foreach ( $correct_answer_keys as $correct_answer_key ) {
							$correct_answers[] = $choices[ $correct_answer_key ]['label'];
						}

						foreach ( $given_answer_data as $given_answer ) {
							$given_answers[] = $given_answer;
						}

						foreach ( $given_answers as $given_answer ) {
							if ( ! in_array( $given_answer, $correct_answers, true ) ) {
								$is_correct = false;
								break;
							}
						}
					}
				}
			}

			if ( true === $is_correct ) {
				$score           = $field_score;
				$obtained_score += $field_score;
			}

			$columns[] = $this->sanitize_csv_cell_data( $field['label'] );
			$row[]     = $this->sanitize_csv_cell_data( $score );
		}

		$extra_data = array(
			'Total Score'      => $total_score,
			'Respondent Score' => $respondent_score,
			'Obtained Score'   => $obtained_score,
		);
		foreach ( $extra_data as $key => $value ) {
			$columns[] = $this->sanitize_csv_cell_data( $key );
			$row[]     = $this->sanitize_csv_cell_data( $value );
		}

		ob_start();
		echo esc_html( implode( ', ', $columns ) );
		echo "\n";
		echo esc_html( implode( ', ', $row ) );

		return ob_get_clean();
	}

	/**
	 * @param string $str 
	 */
	public function sanitize_csv_cell_data( $str ) {
		$str = (string) $str;
		$str = str_replace( '"', '""', $str );
		$str = '"' . $str . '"';
		return $str;
	}

	/**
	 * @param  object $entry 
	 * @return array
	 */
	protected function generate_row_data( $entry ) {
		$columns = $this->get_column_names();
		$row     = array();
		foreach ( $columns as $column_id => $column_name ) {
			$column_id = strstr( $column_id, ':' ) ? current( explode( ':', $column_id ) ) : $column_id;
			$value     = '';
			$raw_value = '';

			if ( isset( $entry->meta[ $column_id ] ) ) {
				$value     = $entry->meta[ $column_id ];
				$raw_value = $entry->meta[ $column_id ];

				if ( is_serialized( $value ) ) {
					$value = $this->implode_values( maybe_unserialize( $value ) );
				}

				$value = apply_filters( 'muhiku_forms_html_field_value', $value, $entry->meta[ $column_id ], $entry, 'export-csv', $column_id );

			} elseif ( is_callable( array( $this, "get_column_value_{$column_id}" ) ) ) {
				$value     = $this->{"get_column_value_{$column_id}"}( $entry );
				$raw_value = $value;
			}
			$column_type       = $this->get_entry_type( $column_id, $entry );
			$row[ $column_id ] = apply_filters( 'muhiku_forms_format_csv_field_data', preg_match( '/textarea/', $column_type ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value ), $raw_value, $column_id, $column_name, $columns, $entry );
		}

		return apply_filters( 'muhiku_forms_entry_export_row_data', $row, $entry );
	}

	/**
	 * @param  string $column_id 
	 * @param  object $entry
	 * @return string
	 */
	protected function get_entry_type( $column_id, $entry ) {
		$fields = json_decode( $entry->fields, 1 );
		if ( is_null( $fields ) || ! is_array( $fields ) ) {
			return false; 
		}
		foreach ( $fields as $field ) {
			if ( $column_id === $field['meta_key'] ) {
				return $field['type'];
			}
		}
		return false;
	}

	/**
	 * @param  object $entry 
	 * @return int
	 */
	protected function get_column_value_entry_id( $entry ) {
		return absint( $entry->entry_id );
	}

	/**
	 * @param  object $entry 
	 * @return string
	 */
	protected function get_column_value_status( $entry ) {
		$statuses = mhk_get_entry_statuses();

		if ( isset( $statuses[ $entry->status ] ) ) {
			return $statuses[ $entry->status ];
		}

		return $entry->status;
	}

	/**
	 * @param  object $entry
	 * @return string
	 */
	protected function get_column_value_date_created( $entry ) {
		$timestamp = false;

		if ( isset( $entry->date_created ) ) {
			$timestamp = strtotime( $entry->date_created );
		}

		return sprintf( esc_html__( '%1$s %2$s', 'muhiku-plug' ), date_i18n( mhk_date_format(), $timestamp ), date_i18n( mhk_time_format(), $timestamp ) );
	}

	/**
	 * @param  object $entry 
	 * @return string
	 */
	protected function get_column_value_date_created_gmt( $entry ) {
		$timestamp = false;

		if ( isset( $entry->date_created ) ) {
			$timestamp = strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 );
		}

		return sprintf( esc_html__( '%1$s %2$s', 'muhiku-plug' ), date_i18n( mhk_date_format(), $timestamp ), date_i18n( mhk_time_format(), $timestamp ) );
	}

	/**
	 * @param  object $entry 
	 * @return string
	 */
	protected function get_column_value_user_device( $entry ) {
		return sanitize_text_field( $entry->user_device );
	}

	/**
	 * @param  object $entry 
	 * @return string
	 */
	protected function get_column_value_user_ip_address( $entry ) {
		return sanitize_text_field( $entry->user_ip_address );
	}
}

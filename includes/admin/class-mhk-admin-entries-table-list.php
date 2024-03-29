<?php
/**
 * MuhikuPlug Entries Table List
 *
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MHK_Admin_Entries_Table_List extends WP_List_Table {

	/**
	 * @var int
	 */
	public $form_id;

	/**
	 * @var MHK_Form_Handler
	 */
	public $form;

	/**
	 * @var MHK_Form_Handler[]
	 */
	public $forms;

	/**
	 * @var array
	 */
	public $form_data;

	public function __construct() {
		$this->forms = mhk_get_all_forms( true );

		if ( ! empty( $this->forms ) ) {
			$this->form_id   = ! empty( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : apply_filters( 'muhiku_forms_entry_list_default_form_id', key( $this->forms ) );  
			$this->form      = mhk()->form->get( $this->form_id );
			$this->form_data = ! empty( $this->form->post_content ) ? mhk_decode( $this->form->post_content ) : '';
		}

		parent::__construct(
			array(
				'singular' => 'entry',
				'plural'   => 'entries',
				'ajax'     => false,
			)
		);
	}

	/**
	 *
	 * @return string|false The action name or False if no action was selected.
	 */
	public function current_action() {
		if ( isset( $_REQUEST['export_action'] ) && ! empty( $_REQUEST['export_action'] ) ) {  
			return false;
		}

		if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {  
			return 'delete_all';
		}

		return parent::current_action();
	}

	public function no_items() {
		esc_html_e( 'Hata! Henüz herhangi bir form girişiniz yok.', 'muhiku-plug' );
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$columns            = array();
		$columns['cb']      = '<input type="checkbox" />';
		$columns            = apply_filters( 'muhiku_forms_entries_table_form_fields_columns', $this->get_columns_form_fields( $columns ), $this->form_id, $this->form_data );
		$columns['date']    = esc_html__( 'Oluşturulma Tarihi', 'muhiku-plug' );
		$columns['actions'] = esc_html__( 'Eylemler', 'muhiku-plug' );
		if ( defined( 'EFP_VERSION' ) ) {
			$columns['more'] = '<a href="#" class="muhiku-plug-entries-setting" title="' . esc_attr__( 'More Options', 'muhiku-plug' ) . '" data-mhk-form_id="' . $this->form_id . '"><i class="dashicons dashicons-admin-generic"></i></a>';
		}
		return apply_filters( 'muhiku_forms_entries_table_columns', $columns, $this->form_data );
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array();

		if ( isset( $_GET['form_id'] ) ) { 
			$sortable_columns = array(
				'date' => array( 'date_created', false ),
			);
		}

		return array_merge(
			array(
				'id' => array( 'title', false ),
			),
			$sortable_columns
		);
	}

	/**
	 * @param object $entry Entry data.
	 */
	public function single_row( $entry ) {
		if ( empty( $_GET['status'] ) || ( isset( $_GET['status'] ) && 'trash' !== $_GET['status'] ) ) {  
			echo '<tr class="' . ( $entry->viewed ? 'read' : 'unread' ) . '">';
			$this->single_row_columns( $entry );
			echo '</tr>';
		} else {
			parent::single_row( $entry );
		}
	}

	/**
	 * @return array
	 */
	public static function get_columns_form_disallowed_fields() {
		return (array) apply_filters( 'muhiku_forms_entries_table_fields_disallow', array( 'html', 'title', 'captcha', 'repeater-fields' ) );
	}

	/**
	 * @param array $columns List of colums.
	 * @param int   $display Numbers of columns to display.
	 *
	 * @return array
	 */
	public function get_columns_form_fields( $columns = array(), $display = 3 ) {
		$entry_columns = mhk()->form->get_meta( $this->form_id, 'entry_columns' );

		if ( ! $entry_columns && ! empty( $this->form_data['form_fields'] ) ) {
			$x = 0;
			foreach ( $this->form_data['form_fields'] as $id => $field ) {
				if ( ! in_array( $field['type'], self::get_columns_form_disallowed_fields(), true ) && $x < $display ) {
					$columns[ 'mhk_field_' . $id ] = ! empty( $field['label'] ) ? wp_strip_all_tags( $field['label'] ) : esc_html__( 'Field', 'muhiku-plug' );
					$x++;
				}
			}
		} elseif ( ! empty( $entry_columns ) ) {
			foreach ( $entry_columns as $id ) {
				if ( empty( $this->form_data['form_fields'][ $id ] ) ) {
					continue;
				}

				$columns[ 'mhk_field_' . $id ] = ! empty( $this->form_data['form_fields'][ $id ]['label'] ) ? wp_strip_all_tags( $this->form_data['form_fields'][ $id ]['label'] ) : esc_html__( 'Field', 'muhiku-plug' );
			}
		}

		return $columns;
	}

	/**
	 * @param  object $entry Entry object.
	 * @return string
	 */
	public function column_cb( $entry ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $entry->entry_id );
	}

	/**
	 * @param  object $entry Entry object.
	 * @param  string $column_name Column Name.
	 * @return string
	 */
	public function column_form_field( $entry, $column_name ) {
		$field_id = str_replace( 'mhk_field_', '', $column_name );
		$meta_key = isset( $this->form_data['form_fields'][ $field_id ]['meta-key'] ) ? strtolower( $this->form_data['form_fields'][ $field_id ]['meta-key'] ) : $field_id;

		if ( ! empty( $entry->meta[ $meta_key ] ) ) {
			$value = $entry->meta[ $meta_key ];
			if ( mhk_is_json( $value ) ) {
				$field_value = json_decode( $value, true );
				$value       = $field_value['value'];
			}

			if ( is_serialized( $value ) ) {
				$field_html  = array();
				$field_value = maybe_unserialize( $value );

				$field_label = ! empty( $field_value['label'] ) ? mhk_clean( $field_value['label'] ) : $field_value;
				if ( is_array( $field_label ) ) {
					foreach ( $field_label as $value ) {
						$field_html[] = esc_html( $value );
					}

					$value = implode( ' | ', $field_html );
				} else {
					$value = esc_html( $field_label );
				}
			}

			if ( false === strpos( $value, 'http' ) ) {
				$lines = explode( "\n", $value );
				$value = array_slice( $lines, 0, 4 );
				$value = implode( "\n", $value );

				if ( count( $lines ) > 5 ) {
					$value .= '&hellip;';
				} elseif ( strlen( $value ) > 75 ) {
					$value = substr( $value, 0, 75 ) . '&hellip;';
				}

				$value = nl2br( wp_strip_all_tags( trim( $value ) ) );
			}
			return apply_filters( 'muhiku_forms_html_field_value', $value, $entry->meta[ $meta_key ], $entry, 'entry-table' );
		} else {
			return '<span class="na">&mdash;</span>';
		}
	}

	/**
	 * @param  object $entry Entry object.
	 * @param  string $column_name Column Name.
	 * @return string
	 */
	public function column_default( $entry, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				$value = absint( $entry->entry_id );
				break;

			case 'date':
				$value = date_i18n( get_option( 'date_format' ), strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 ) );
				break;

			default:
				if ( false !== strpos( $column_name, 'mhk_field_' ) ) {
					$value = $this->column_form_field( $entry, $column_name );
				} else {
					$value = '';
				}
				break;
		}

		return apply_filters( 'muhiku_forms_entry_table_column_value', $value, $entry, $column_name );
	}

	/**
	 * @param  object $entry Entry object.
	 * @return string
	 */
	public function column_actions( $entry ) {
		$actions = array();

		if ( 'trash' !== $entry->status ) {
			if ( current_user_can( 'muhiku_forms_view_entry', $entry->entry_id ) ) {
				$actions['view'] = '<a href="' . esc_url( admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . $entry->form_id . '&amp;view-entry=' . $entry->entry_id ) ) . '">' . esc_html__( 'Görüntüle', 'muhiku-plug' ) . '</a>';
			}

			if ( current_user_can( 'muhiku_forms_delete_entry', $entry->entry_id ) ) {
				$actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Trash form entry', 'muhiku-plug' ) . '" href="' . esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'trash'   => $entry->entry_id,
								'form_id' => $this->form_id,
							),
							admin_url( 'admin.php?page=mhk-entries' )
						),
						'trash-entry'
					)
				) . '">' . esc_html__( 'Çöpe At', 'muhiku-plug' ) . '</a>';
			}
		} else {
			if ( current_user_can( 'muhiku_forms_edit_entry', $entry->entry_id ) ) {
				$actions['untrash'] = '<a aria-label="' . esc_attr__( 'Restore form entry from trash', 'muhiku-plug' ) . '" href="' . esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'untrash' => $entry->entry_id,
								'form_id' => $this->form_id,
							),
							admin_url( 'admin.php?page=mhk-entries' )
						),
						'untrash-entry'
					)
				) . '">' . esc_html__( 'Restore', 'muhiku-plug' ) . '</a>';
			}

			if ( current_user_can( 'muhiku_forms_delete_entry', $entry->entry_id ) ) {
				$actions['delete'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete form entry permanently', 'muhiku-plug' ) . '" href="' . esc_url(
					wp_nonce_url(
						add_query_arg(
							array(
								'delete'  => $entry->entry_id,
								'form_id' => $this->form_id,
							),
							admin_url( 'admin.php?page=mhk-entries' )
						),
						'delete-entry'
					)
				) . '">' . esc_html__( 'Delete Permanently', 'muhiku-plug' ) . '</a>';
			}
		}

		return implode( ' <span class="sep">|</span> ', apply_filters( 'muhiku_forms_entry_table_actions', $actions, $entry ) );
	}

	/**
	 * @param string $status_name Status name.
	 * @param int    $amount      Amount of entries.
	 * @return array
	 */
	private function get_status_label( $status_name, $amount ) {
		$statuses = mhk_get_entry_statuses( $this->form_data );

		if ( isset( $statuses[ $status_name ] ) ) {
			return array(
				'singular' => sprintf( '%s <span class="count">(<span class="%s-count">%s</span>)</span>', esc_html( $statuses[ $status_name ] ), $status_name, $amount ),
				'plural'   => sprintf( '%s <span class="count">(<span class="%s-count">%s</span>)</span>', esc_html( $statuses[ $status_name ] ), $status_name, $amount ),
				'context'  => '',
				'domain'   => 'muhiku-plug',
			);
		}

		return array(
			'singular' => sprintf( '%s <span class="count">(<span class="%s-count">%s</span>)</span>', esc_html( $statuses[ $status_name ] ), $status_name, $amount ),
			'plural'   => sprintf( '%s <span class="count">(%s)</span>', esc_html( $status_name ), $amount ),
			'context'  => '',
			'domain'   => 'muhiku-plug',
		);
	}

	/**
	 * @return array
	 */
	protected function get_views() {
		$status_links  = array();
		$num_entries   = mhk_get_count_entries_by_status( $this->form_id );
		$total_entries = apply_filters( 'muhiku_forms_total_entries_count', (int) $num_entries['publish'], $num_entries, $this->form_id );
		$statuses      = array_keys( mhk_get_entry_statuses( $this->form_data ) );
		$class         = empty( $_REQUEST['status'] ) ? ' class="current"' : '';  

		$status_links['all'] = "<a href='admin.php?page=mhk-entries&amp;form_id=$this->form_id'$class>" . sprintf( _nx( 'All <span class="count">(%s)</span>', 'All <span class="count">(%s)</span>', $total_entries, 'entries', 'muhiku-plug' ), number_format_i18n( $total_entries ) ) . '</a>';

		foreach ( $statuses as $status_name ) {
			$class = '';

			if ( 'publish' === $status_name ) {
				continue;
			}

			if ( isset( $_REQUEST['status'] ) && sanitize_key( wp_unslash( $_REQUEST['status'] ) ) === $status_name ) {  
				$class = ' class="current"';
			}

			$label = $this->get_status_label( $status_name, $num_entries[ $status_name ] );

			$status_links[ $status_name ] = "<a href='admin.php?page=mhk-entries&amp;form_id=$this->form_id&amp;status=$status_name'$class>" . sprintf( translate_nooped_plural( $label, $num_entries[ $status_name ] ), number_format_i18n( $num_entries[ $status_name ] ) ) . '</a>';
		}

		return apply_filters( 'muhiku_forms_entries_table_views', $status_links, $num_entries, $this->form_data );
	}

	/**
	 *
	 * @return array
	 */
	protected function get_bulk_actions() {
		if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] ) {  
			$actions = array(
				'untrash' => __( 'Restore', 'muhiku-plug' ),
				'delete'  => __( 'Delete Permanently', 'muhiku-plug' ),
			);
		} else {
			$actions = array(
				'trash' => __( 'Çöpe At', 'muhiku-plug' ),
			);
		}

		return apply_filters( 'muhiku_forms_entry_bulk_actions', $actions );
	}

	public function process_bulk_action() {
		$pagenum   = $this->get_pagenum();
		$doaction  = $this->current_action();
		$entry_ids = isset( $_REQUEST['entry'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['entry'] ) ) : array();  
		$count     = 0;

		if ( $doaction ) {
			check_admin_referer( 'bulk-entries' );

			$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted' ), wp_get_referer() );
			if ( ! $sendback ) {
				$sendback = admin_url( 'admin.php?page=mhk-entries' );
			}
			$sendback = add_query_arg( 'paged', $pagenum, $sendback );

			if ( ! isset( $entry_ids ) ) {
				wp_safe_redirect( $sendback );
				exit;
			}

			switch ( $doaction ) {
				case 'star':
				case 'unstar':
					foreach ( $entry_ids as $entry_id ) {
						if ( MHK_Admin_Entries::update_status( $entry_id, $doaction ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						sprintf( _n( '%1$d entry successfully %2$s.', '%1$d entries successfully %2$s.', $count, 'muhiku-plug' ), $count, 'star' === $doaction ? 'starred' : 'unstarred' ),
						'updated'
					);
					break;
				case 'read':
				case 'unread':
					foreach ( $entry_ids as $entry_id ) {
						if ( MHK_Admin_Entries::update_status( $entry_id, $doaction ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						sprintf( _n( '%1$d entry successfully marked as %2$s.', '%1$d entries successfully marked as %2$s.', $count, 'muhiku-plug' ), $count, $doaction ),
						'updated'
					);
					break;
				case 'trash':
					foreach ( $entry_ids as $entry_id ) {
						if ( MHK_Admin_Entries::update_status( $entry_id, 'trash' ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						sprintf( _n( '%d entry moved to the Trash.', '%d entries moved to the Trash.', $count, 'muhiku-plug' ), $count ),
						'updated'
					);
					break;
				case 'untrash':
					foreach ( $entry_ids as $entry_id ) {
						if ( MHK_Admin_Entries::update_status( $entry_id, 'publish' ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						sprintf( _n( '%d entry restored from the Trash.', '%d entries restored from the Trash.', $count, 'muhiku-plug' ), $count ),
						'updated'
					);
					break;
				case 'delete':
					foreach ( $entry_ids as $entry_id ) {
						if ( MHK_Admin_Entries::remove_entry( $entry_id ) ) {
							$count ++;
						}
					}

					add_settings_error(
						'bulk_action',
						'bulk_action',
						sprintf( _n( '%d entry permanently deleted.', '%d entries permanently deleted.', $count, 'muhiku-plug' ), $count ),
						'updated'
					);
					break;
			}
			$sendback = remove_query_arg( array( 'action', 'action2' ), $sendback );

			wp_safe_redirect( $sendback );
			exit();
		} elseif ( ! empty( $_REQUEST['_wp_http_referer'] ) && isset( $_SERVER['REQUEST_URI'] ) ) {  
			wp_safe_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) );  
			exit();
		}
	}

	/**
	 * @param string $which The location of the extra table nav markup.
	 */
	protected function extra_tablenav( $which ) {
		$num_entries = mhk_get_count_entries_by_status( $this->form_id );
		$show_export = isset( $_GET['status'] ) && 'trash' === $_GET['status'] ? false : true;  
		?>
		<div class="alignleft actions">
		<?php
		if ( ! empty( $this->forms ) && 'top' === $which ) {

			$this->forms_dropdown();
			submit_button( __( 'Filter', 'muhiku-plug' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );

			if ( apply_filters( 'muhiku_forms_enable_csv_export', $show_export ) && current_user_can( 'export' ) ) {
				submit_button( __( 'Dışarı CSV olarak aktar', 'muhiku-plug' ), '', 'export_action', false, array( 'id' => 'export-csv-submit' ) );
			}
		}

		if ( $num_entries['trash'] && isset( $_GET['status'] ) && 'trash' === $_GET['status'] && current_user_can( 'manage_muhiku_forms' ) ) {  
			submit_button( __( 'Çöpü temizle', 'muhiku-plug' ), 'apply', 'delete_all', false );
		}
		?>
		</div>
		<?php
	}

	public function forms_dropdown() {
		$forms   = mhk_get_all_forms( true );
		$form_id = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : $this->form_id;  
		?>
		<label for="filter-by-form" class="screen-reader-text"><?php esc_html_e( 'Filter by form', 'muhiku-plug' ); ?></label>
		<select name="form_id" id="filter-by-form">
			<?php foreach ( $forms as $id => $form ) : ?>
				<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $form_id, $id ); ?>><?php echo esc_html( $form ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function prepare_items() {
		$per_page     = $this->get_items_per_page( 'mhk_entries_per_page' );
		$current_page = $this->get_pagenum();

		$args = array(
			'status'  => 'publish',
			'form_id' => $this->form_id,
			'limit'   => $per_page,
			'offset'  => $per_page * ( $current_page - 1 ),
		);

		if ( ! empty( $_REQUEST['status'] ) ) { 
			$args['status'] = sanitize_key( wp_unslash( $_REQUEST['status'] ) ); 
		}

		if ( ! empty( $_REQUEST['s'] ) ) {  
			$args['search'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );  
		}

		if ( ! empty( $_REQUEST['orderby'] ) ) {  
			$args['orderby'] = sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );  
		}

		if ( ! empty( $_REQUEST['order'] ) ) {  
			$args['order'] = sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );  
		}

		$entries     = mhk_search_entries( $args );
		$this->items = array_map( 'mhk_get_entry', $entries );

		$args['limit']  = -1;
		$args['offset'] = 0;
		$total_items    = count( mhk_search_entries( $args ) );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}
}

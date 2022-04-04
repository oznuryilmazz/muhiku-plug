<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

class MHK_Admin_Entries {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_filter( 'heartbeat_received', array( $this, 'check_new_entries' ), 10, 3 );
	}

	/**
	 * @return bool
	 */
	private function is_entries_page() {
		return isset( $_GET['page'] ) && 'mhk-entries' === $_GET['page'];  
	}

	public static function page_output() {
		if ( apply_filters( 'muhiku_forms_entries_list_actions', false ) ) {
			do_action( 'muhiku_forms_entries_list_actions_execute' );
		} elseif ( isset( $_GET['view-entry'] ) ) {  
			include 'views/html-admin-page-entries-view.php';
		} else {
			self::table_list_output();
		}
	}

	private static function table_list_output() {
		global $entries_table_list;

		$entry_ids = mhk_get_entries_ids( $entries_table_list->form_id );

		$entries_table_list->process_bulk_action();
		$entries_table_list->prepare_items();
		?>
		<div id="muhiku-plug-entries-list" class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Yanıtlar', 'muhiku-plug' ); ?></h1>
			<hr class="wp-header-end">

			<?php settings_errors(); ?>
			<?php do_action( 'muhiku_forms_before_entry_list', $entries_table_list ); ?>

			<?php if ( 0 < count( $entry_ids ) ) : ?>
				<?php $entries_table_list->views(); ?>
				<form id="entries-list" method="get" data-form-id="<?php echo absint( $entries_table_list->form_id ); ?>" data-last-entry-id="<?php echo absint( end( $entry_ids ) ); ?>">
					<input type="hidden" name="page" value="mhk-entries" />
					<?php if ( ! empty( $_REQUEST['form_id'] ) ) :   ?>
						<input type="hidden" name="form_id" value="<?php echo absint( $_REQUEST['form_id'] );   ?>" />
					<?php endif; ?>
					<?php if ( ! empty( $_REQUEST['status'] ) ) :   ?>
						<input type="hidden" name="status" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) );   ?>" />
					<?php endif; ?>
					<?php
						$entries_table_list->search_box( esc_html__( 'Search Entries', 'muhiku-plug' ), 'muhiku-plug' );
						$entries_table_list->display();
					?>
				</form>
			<?php else : ?>
				<div class="muhiku-plug-BlankState">
					<svg aria-hidden="true" class="octicon octicon-graph muhiku-plug-BlankState-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M16 14v1H0V0h1v14h15zM5 13H3V8h2v5zm4 0H7V3h2v10zm4 0h-2V6h2v7z"/></svg>
					<h2 class="muhiku-plug-BlankState-message"><?php esc_html_e( 'Hata! Henüz herhangi bir form girişiniz yok.', 'muhiku-plug' ); ?></h2>
					<?php if ( ! empty( $entries_table_list->forms ) ) : ?>
						<form id="entries-list" method="get">
							<input type="hidden" name="page" value="mhk-entries" />
							<?php
								$entries_table_list->forms_dropdown();
								submit_button( __( 'Filter', 'muhiku-plug' ), '', '', false, array( 'id' => 'post-query-submit' ) );
							?>
						</form>
					<?php else : ?>
						<a class="muhiku-plug-BlankState-cta button" href="<?php echo esc_url( admin_url( 'admin.php?page=mhk-builder&create-form=1' ) ); ?>"><?php esc_html_e( 'Create your first form!', 'muhiku-plug' ); ?></a>
					<?php endif; ?>
					<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub { display: none; }</style>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function actions() {
		if ( $this->is_entries_page() ) {
			if ( isset( $_GET['trash'] ) ) {  
				$this->trash_entry();
			}

			if ( isset( $_GET['untrash'] ) ) {  
				$this->untrash_entry();
			}

			if ( isset( $_GET['delete'] ) ) {  
				$this->delete_entry();
			}

			if ( isset( $_REQUEST['export_action'] ) ) {  
				$this->export_csv();
			}

			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {  
				$this->empty_trash();
			}
		}
	}

	private function trash_entry() {
		check_admin_referer( 'trash-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['trash'] ) ) {  
			$entry_id = absint( $_GET['trash'] );  

			if ( $entry_id ) {
				self::update_status( $entry_id, 'trash' );
			}
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'form_id' => $form_id,
						'trashed' => 1,
					),
					admin_url( 'admin.php?page=mhk-entries' )
				)
			)
		);
		exit();
	}

	private function untrash_entry() {
		check_admin_referer( 'untrash-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['untrash'] ) ) {  
			$entry_id = absint( $_GET['untrash'] );  

			if ( $entry_id ) {
				self::update_status( $entry_id, 'publish' );
			}
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'form_id'   => $form_id,
						'untrashed' => 1,
					),
					admin_url( 'admin.php?page=mhk-entries' )
				)
			)
		);
		exit();
	}

	private function delete_entry() {
		check_admin_referer( 'delete-entry' );

		$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : '';

		if ( isset( $_GET['delete'] ) ) {  
			$entry_id = absint( $_GET['delete'] );  

			if ( $entry_id ) {
				self::remove_entry( $entry_id );
			}
		}

		wp_safe_redirect(
			esc_url_raw(
				add_query_arg(
					array(
						'form_id' => $form_id,
						'deleted' => 1,
					),
					admin_url( 'admin.php?page=mhk-entries' )
				)
			)
		);
		exit();
	}

	public function empty_trash() {
		global $wpdb;

		check_admin_referer( 'bulk-entries' );

		if ( isset( $_GET['form_id'] ) ) {  
			$form_id = absint( $_GET['form_id'] );  

			if ( $form_id ) {
				$count     = 0;
				$results   = $wpdb->get_results( $wpdb->prepare( "SELECT entry_id FROM {$wpdb->prefix}mhk_entries WHERE `status` = 'trash' AND form_id = %d", $form_id ) ); // WPCS: cache ok, DB call ok.
				$entry_ids = array_map( 'intval', wp_list_pluck( $results, 'entry_id' ) );

				foreach ( $entry_ids as $entry_id ) {
					if ( self::remove_entry( $entry_id ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'empty_trash',
					'empty_trash',
					sprintf( _n( '%d entry permanently deleted.', '%d entries permanently deleted.', $count, 'muhiku-plug' ), $count ),
					'updated'
				);
			}
		}
	}

	public function export_csv() {
		check_admin_referer( 'bulk-entries' );

		if ( isset( $_REQUEST['form_id'] ) && current_user_can( 'export' ) ) {  
			include_once MHK_ABSPATH . 'includes/export/class-mhk-entry-csv-exporter.php';
			$form_id   = absint( $_REQUEST['form_id'] );  
			$form_name = strtolower( get_the_title( $form_id ) );

			if ( $form_name ) {
				$exporter = new MHK_Entry_CSV_Exporter( $form_id );
				$exporter->set_filename( mhk_get_csv_file_name( $form_name ) );
			}

			$exporter->export();
		}
	}

	/**
	 * @param  int $entry_id Entry ID.
	 * @return bool
	 */
	public static function remove_entry( $entry_id ) {
		global $wpdb;

		do_action( 'muhiku_forms_before_delete_entries', $entry_id );

		$delete = $wpdb->delete( $wpdb->prefix . 'mhk_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );

		if ( apply_filters( 'muhiku_forms_delete_entrymeta', true ) ) {
			$wpdb->delete( $wpdb->prefix . 'mhk_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
		}

		return $delete;
	}

	/**
	 * @param int    $entry_id Entry ID.
	 * @param string $status   Entry status.
	 */
	public static function update_status( $entry_id, $status = 'publish' ) {
		global $wpdb;

		if ( in_array( $status, array( 'star', 'unstar' ), true ) ) {
			$update = $wpdb->update(
				$wpdb->prefix . 'mhk_entries',
				array(
					'starred' => 'star' === $status ? 1 : 0,
				),
				array( 'entry_id' => $entry_id ),
				array( '%d' ),
				array( '%d' )
			);
		} elseif ( in_array( $status, array( 'read', 'unread' ), true ) ) {
			$update = $wpdb->update(
				$wpdb->prefix . 'mhk_entries',
				array(
					'viewed' => 'read' === $status ? 1 : 0,
				),
				array( 'entry_id' => $entry_id ),
				array( '%d' ),
				array( '%d' )
			);
		} else {
			$entry = mhk_get_entry( $entry_id );

			if ( 'trash' === $status ) {
				$wpdb->insert(
					$wpdb->prefix . 'mhk_entrymeta',
					array(
						'entry_id'   => $entry_id,
						'meta_key'   => '_mhk_trash_entry_status',
						'meta_value' => sanitize_text_field( $entry->status ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					)
				);
			} elseif ( 'publish' === $status ) {
				$status = $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}mhk_entrymeta WHERE entry_id = %d AND meta_key = '_mhk_trash_entry_status'", $entry_id ) );
				$wpdb->delete(
					$wpdb->prefix . 'mhk_entrymeta',
					array(
						'entry_id' => $entry_id,
						'meta_key' => '_mhk_trash_entry_status',
					)
				);
			}

			$update = $wpdb->update(
				$wpdb->prefix . 'mhk_entries',
				array( 'status' => $status ),
				array( 'entry_id' => $entry_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return $update;
	}

	/**
	 * @param  array  $response  The Heartbeat response.
	 * @param  array  $data      The $_POST data sent.
	 * @param  string $screen_id The screen id.
	 * @return array The Heartbeat response.
	 */
	public function check_new_entries( $response, $data, $screen_id ) {
		if ( 'muhiku-plug_page_mhk-entries' === $screen_id ) {
			$form_id       = ! empty( $data['mhk_new_entries_form_id'] ) ? absint( $data['mhk_new_entries_form_id'] ) : 0;
			$last_entry_id = ! empty( $data['mhk_new_entries_last_entry_id'] ) ? absint( $data['mhk_new_entries_last_entry_id'] ) : 0;

			$entries_count = mhk_get_count_entries_by_last_entry( $form_id, $last_entry_id );

			if ( ! empty( $entries_count ) ) {
				$response['mhk_new_entries_notification'] = esc_html( sprintf( _n( '%d new entry since you last checked.', '%d new entries since you last checked.', $entries_count, 'muhiku-plug' ), $entries_count ) );
			}
		}

		return $response;
	}
}

new MHK_Admin_Entries();

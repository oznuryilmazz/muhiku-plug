<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class MHK_Admin_Forms_Table_List extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'form',
				'plural'   => 'forms',
				'ajax'     => false,
			)
		);
	}

	public function no_items() {
		esc_html_e( 'Form Bulunamadı.', 'muhiku-plug' );
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		$forms_columns = array(
			'cb'        => '<input type="checkbox" />',
			'enabled'   => '',
			'title'     => esc_html__( 'Başlık', 'muhiku-plug' ),
			'shortcode' => esc_html__( 'Kısa Kod', 'muhiku-plug' ),
			'author'    => esc_html__( 'Yaratan', 'muhiku-plug' ),
			'date'      => esc_html__( 'Tarih', 'muhiku-plug' ),
		);

		if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] ) {  
			unset( $forms_columns['enabled'] );
		}

		if ( current_user_can( 'muhiku_forms_view_entries' ) || current_user_can( 'muhiku_forms_view_others_entries' ) ) {
			$forms_columns['entries'] = esc_html__( 'Yanıtlar', 'muhiku-plug' );
		}

		if ( isset( $_GET['status'] ) && 'trash' !== $_GET['status'] && ! current_user_can( 'muhiku_forms_delete_forms' ) ) {  
			unset( $forms_columns['cb'] );
		}

		return $forms_columns;
	}

	/**
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'title'  => array( 'title', false ),
			'author' => array( 'author', false ),
			'date'   => array( 'date', false ),
		);
	}

	/**
	 * @param  object $form Form object.
	 * @return string
	 */
	public function column_cb( $form ) {
		$show   = current_user_can( 'muhiku_forms_edit_form', $form->ID );
		$delete = current_user_can( 'muhiku_forms_delete_form', $form->ID );

		/**
		 * @param bool    $show Whether to show the checkbox.
		 * @param WP_Post $post The current WP_Post object.
		 */
		if ( apply_filters( 'muhiku_forms_list_table_show_form_checkbox', $show, $form ) || apply_filters( 'muhiku_forms_list_table_delete_form_checkbox', $delete, $form ) ) {
			return sprintf( '<input type="checkbox" name="form_id[]" value="%1$s" />', esc_attr( $form->ID ) );
		}
	}

	/**
	 * @param  object $posts Form object.
	 * @return string
	 */
	public function column_enabled( $posts ) {
		$form_data    = mhk()->form->get( absint( $posts->ID ), array( 'content_only' => true ) );
		$form_enabled = isset( $form_data['form_enabled'] ) ? $form_data['form_enabled'] : 1;

		if ( current_user_can( 'muhiku_forms_edit_form', $posts->ID ) ) {
			return '<label class="muhiku-plug-toggle-form form-enabled"><input type="checkbox" data-form_id="' . absint( $posts->ID ) . '" value="1" ' . checked( 1, $form_enabled, false ) . '/><span class="slider round"></span></label>';
		}
	}

	/**
	 * @param  object $posts Form object.
	 * @return string
	 */
	public function column_title( $posts ) {
		$edit_link        = admin_url( 'admin.php?page=mhk-builder&tab=fields&form_id=' . $posts->ID );
		$preview_link     = add_query_arg(
			array(
				'form_id'     => absint( $posts->ID ),
				'mhk_preview' => 'true',
			),
			home_url()
		);
		$title            = _draft_or_post_title( $posts->ID );
		$post_type_object = get_post_type_object( 'muhiku_form' );
		$post_status      = $posts->post_status;

		// Title.
		$output = '<strong>';
		if ( 'trash' === $post_status ) {
			$output .= esc_html( $title );
		} else {
			$name = esc_html( $title );

			if ( current_user_can( 'muhiku_forms_view_form', $posts->ID ) ) {
				$name = '<a href="' . esc_url( $preview_link ) . '" title="' . esc_html__( 'View Preview', 'muhiku-plug' ) . '" class="row-title" target="_blank" rel="noopener noreferrer">' . esc_html( $title ) . '</a>';
			}

			if ( current_user_can( 'muhiku_forms_view_form_entries', $posts->ID ) ) {
				$name = '<a href="' . esc_url( esc_url( admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . $posts->ID ) ) ) . '" title="' . esc_html__( 'View Entries', 'muhiku-plug' ) . '" class="row-title">' . esc_html( $title ) . '</a>';
			}

			if ( current_user_can( 'muhiku_forms_edit_form', $posts->ID ) ) {
				$name = '<a href="' . esc_url( $edit_link ) . '" title="' . esc_html__( 'Bu Formu Düzenle', 'muhiku-plug' ) . '" class="row-title">' . esc_html( $title ) . '</a>';
			}

			$output .= $name;
		}
		$output .= '</strong>';

		$actions = array();

		if ( current_user_can( 'muhiku_forms_edit_form', $posts->ID ) && 'trash' !== $post_status ) {
			$actions['edit'] = '<a href="' . esc_url( $edit_link ) . '" title="' . esc_html__( 'Bu Formu Düzenle', 'muhiku-plug' ) . '">' . __( 'Düzenle', 'muhiku-plug' ) . '</a>';
		}

		if ( current_user_can( 'muhiku_forms_view_form_entries', $posts->ID ) && 'trash' !== $post_status ) {
			$actions['entries'] = '<a href="' . esc_url( admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . $posts->ID ) ) . '" title="' . esc_html__( 'View Entries', 'muhiku-plug' ) . '">' . __( 'Yanıtlar', 'muhiku-plug' ) . '</a>';
		}

		if ( current_user_can( 'muhiku_forms_delete_form', $posts->ID ) ) {
			if ( 'trash' === $post_status ) {
				$actions['untrash'] = '<a aria-label="' . esc_attr__( 'Restore this item from the Trash', 'muhiku-plug' ) . '" href="' . wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $posts->ID ) ), 'untrash-post_' . $posts->ID ) . '">' . esc_html__( 'Restore', 'muhiku-plug' ) . '</a>';
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Move this item to the Trash', 'muhiku-plug' ) . '" href="' . get_delete_post_link( $posts->ID ) . '">' . esc_html__( 'Çöpe At', 'muhiku-plug' ) . '</a>';
			}
			if ( 'trash' === $post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = '<a class="submitdelete" aria-label="' . esc_attr__( 'Delete this item permanently', 'muhiku-plug' ) . '" href="' . get_delete_post_link( $posts->ID, '', true ) . '">' . esc_html__( 'Delete permanently', 'muhiku-plug' ) . '</a>';
			}
		}

		if ( current_user_can( 'muhiku_forms_view_form', $posts->ID ) ) {
			$preview_link   = add_query_arg(
				array(
					'form_id'     => absint( $posts->ID ),
					'mhk_preview' => 'true',
				),
				home_url()
			);
			$duplicate_link = wp_nonce_url( admin_url( 'admin.php?page=mhk-builder&action=duplicate_form&form_id=' . absint( $posts->ID ) ), 'muhiku-plug-duplicate-form_' . $posts->ID );

			if ( 'trash' !== $post_status ) {
				$actions['view'] = '<a href="' . esc_url( $preview_link ) . '" rel="bookmark" target="_blank">' . __( 'Önizleme', 'muhiku-plug' ) . '</a>';
			}

			if ( 'publish' === $post_status && current_user_can( 'muhiku_forms_create_forms' ) ) {
				$actions['duplicate'] = '<a href="' . esc_url( $duplicate_link ) . '">' . __( 'Kopyasını Oluştur', 'muhiku-plug' ) . '</a>';
			}
		}

		$row_actions = array();

		foreach ( $actions as $action => $link ) {
			$row_actions[] = '<span class="' . esc_attr( $action ) . '">' . $link . '</span>';
		}

		$output .= '<div class="row-actions">' . implode( ' | ', $row_actions ) . '</div>';

		return $output;
	}

	/**
	 * @param object $posts Form object.
	 */
	public function column_shortcode( $posts ) {
		?>
		<span class="shortcode mhk-shortcode-field">
			<input type="text" onfocus="this.select();" readonly="readonly" value="<?php echo esc_attr( '[muhiku_form id="' . absint( $posts->ID ) . '"]' ); ?> " class="large-text code">
			<button class="button mhk-copy-shortcode help_tip" type="button" href="#" data-tip="<?php esc_attr_e( 'Copy Shortcode!', 'muhiku-plug' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'muhiku-plug' ); ?>">
				<span class="dashicons dashicons-admin-page"></span>
			</button>
		</span>
		<?php
	}

	/**
	 * @param  object $posts Form object.
	 * @return string
	 */
	public function column_author( $posts ) {
		$user = get_user_by( 'id', $posts->post_author );

		if ( ! $user ) {
			return '<span class="na">&ndash;</span>';
		}

		$user_name = ! empty( $user->data->display_name ) ? $user->data->display_name : $user->data->user_login;

		if ( current_user_can( 'edit_user' ) ) {
			return '<a href="' . esc_url(
				add_query_arg(
					array(
						'user_id' => $user->ID,
					),
					admin_url( 'user-edit.php' )
				)
			) . '">' . esc_html( $user_name ) . '</a>';
		}

		return esc_html( $user_name );
	}

	/**
	 * @param  object $posts Form object.
	 * @return string
	 */
	public function column_date( $posts ) {
		$post = get_post( $posts->ID );

		if ( ! $post ) {
			return;
		}

		$t_time = mysql2date(
			__( 'Y/m/d g:i:s A', 'muhiku-plug' ),
			$post->post_date,
			true
		);
		$m_time = $post->post_date;
		$time   = mysql2date( 'G', $post->post_date ) - get_option( 'gmt_offset' ) * 3600;

		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < 24 * 60 * 60 ) {
			$h_time = sprintf(
				/* translators: %s: Time */
				__( '%s ago', 'muhiku-plug' ),
				human_time_diff( $time )
			);
		} else {
			$h_time = mysql2date( __( 'Y/m/d', 'muhiku-plug' ), $m_time );
		}

		return '<abbr title="' . $t_time . '">' . $h_time . '</abbr>';
	}

	/**
	 * @param  object $posts Form object.
	 * @return string
	 */
	public function column_entries( $posts ) {
		global $wpdb;

		if ( ! current_user_can( 'muhiku_forms_view_form_entries', $posts->ID ) ) {
			return '-';
		}

		$entries = count( $wpdb->get_results( $wpdb->prepare( "SELECT form_id FROM {$wpdb->prefix}mhk_entries WHERE `status` != 'trash' AND form_id = %d", $posts->ID ) ) ); // WPCS: cache ok, DB call ok.

		if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] ) {  
			return '<strong>' . absint( $entries ) . '</strong>';
		} else {
			return '<a href="' . esc_url( admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . $posts->ID ) ) . '">' . absint( $entries ) . '</a>';
		}
	}

	/**
	 * @return array
	 */
	protected function get_views() {
		$class        = '';
		$status_links = array();
		$num_posts    = array();
		$total_posts  = count( $this->items );
		$all_args     = array( 'page' => 'mhk-builder' );

		if ( empty( $class ) && empty( $_REQUEST['status'] ) ) {  
			$class = 'current';
		}

		$all_inner_html = sprintf(
			_nx(
				'All <span class="count">(%s)</span>',
				'All <span class="count">(%s)</span>',
				$total_posts,
				'posts',
				'muhiku-plug'
			),
			number_format_i18n( $total_posts )
		);

		$status_links['all'] = $this->get_edit_link( $all_args, $all_inner_html, $class );

		foreach ( get_post_stati( array( 'show_in_admin_status_list' => true ), 'objects' ) as $status ) {
			$class                     = '';
			$status_name               = $status->name;
			$num_posts[ $status_name ] = count( mhk()->form->get_multiple( array( 'post_status' => $status_name ) ) );

			if ( ! in_array( $status_name, array( 'publish', 'draft', 'pending', 'trash', 'future', 'private', 'auto-draft' ), true ) || empty( $num_posts[ $status_name ] ) ) {
				continue;
			}

			if ( isset( $_REQUEST['status'] ) && $status_name === $_REQUEST['status'] ) {  
				$class = 'current';
			}

			$status_args = array(
				'page'   => 'mhk-builder',
				'status' => $status_name,
			);

			$status_label = sprintf(
				translate_nooped_plural( $status->label_count, $num_posts[ $status_name ] ),
				number_format_i18n( $num_posts[ $status_name ] )
			);

			$status_links[ $status_name ] = $this->get_edit_link( $status_args, $status_label, $class );
		}

		return $status_links;
	}

	/**
	 * @param string[] $args  Associative array of URL parameters for the link.
	 * @param string   $label Link text.
	 * @param string   $class Optional. Class attribute. Default empty string.
	 * @return string  The formatted link string.
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		$url = add_query_arg( $args, 'admin.php' );

		$class_html   = '';
		$aria_current = '';

		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * @return array
	 */
	protected function get_bulk_actions() {
		$actions = array();

		if ( isset( $_GET['status'] ) && 'trash' === $_GET['status'] ) {  
			if ( current_user_can( 'muhiku_forms_edit_forms' ) ) {
				$actions['untrash'] = esc_html__( 'Restore', 'muhiku-plug' );
			}

			if ( current_user_can( 'muhiku_forms_delete_forms' ) ) {
				$actions['delete'] = esc_html__( 'Delete permanently', 'muhiku-plug' );
			}
		} elseif ( current_user_can( 'muhiku_forms_delete_forms' ) ) {
			$actions = array(
				'trash' => esc_html__( 'Çöpe At', 'muhiku-plug' ),
			);
		}

		return $actions;
	}

	public function process_bulk_action() {
		$action   = $this->current_action();
		$form_ids = isset( $_REQUEST['form_id'] ) ? wp_parse_id_list( wp_unslash( $_REQUEST['form_id'] ) ) : array();  
		$count    = 0;

		if ( $form_ids ) {
			check_admin_referer( 'bulk-forms' );
		}

		switch ( $action ) {
			case 'trash':
				foreach ( $form_ids as $form_id ) {
					if ( wp_trash_post( $form_id ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'bulk_action',
					'bulk_action',
					sprintf( _n( '%d form moved to the Trash.', '%d forms moved to the Trash.', $count, 'muhiku-plug' ), $count ),
					'updated'
				);
				break;
			case 'untrash':
				foreach ( $form_ids as $form_id ) {
					if ( wp_untrash_post( $form_id ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'bulk_action',
					'bulk_action',
					sprintf( _n( '%d form restored from the Trash.', '%d forms restored from the Trash.', $count, 'muhiku-plug' ), $count ),
					'updated'
				);
				break;
			case 'delete':
				foreach ( $form_ids as $form_id ) {
					if ( wp_delete_post( $form_id, true ) ) {
						$count ++;
					}
				}

				add_settings_error(
					'bulk_action',
					'bulk_action',
					sprintf( _n( '%d form permanently deleted.', '%d forms permanently deleted.', $count, 'muhiku-plug' ), $count ),
					'updated'
				);
				break;
		}
	}

	/**
	 * @param string $which The location of the extra table nav markup.
	 */
	protected function extra_tablenav( $which ) {
		$num_posts = wp_count_posts( 'muhiku_form', 'readable' );

		if ( $num_posts->trash && isset( $_GET['status'] ) && 'trash' === $_GET['status'] && current_user_can( 'muhiku_forms_delete_forms' ) ) {  
			echo '<div class="alignleft actions">';
				submit_button( __( 'Empty Trash', 'muhiku-plug' ), 'apply', 'delete_all', false );
			echo '</div>';
		}
	}

	public function prepare_items() {
		$user_id      = get_current_user_id();
		$per_page     = $this->get_items_per_page( 'mhk_forms_per_page' );
		$current_page = $this->get_pagenum();

		$args = array(
			'post_type'           => 'muhiku_form',
			'posts_per_page'      => $per_page,
			'paged'               => $current_page,
			'no_found_rows'       => false,
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $_REQUEST['status'] ) ) {  
			$args['post_status'] = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );  
		}

		if ( ! empty( $_REQUEST['s'] ) ) {  
			$args['s'] = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );  
		}

		$args['orderby'] = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'date_created';  
		$args['order']   = isset( $_REQUEST['order'] ) && 'ASC' === strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) ? 'ASC' : 'DESC';  

		if ( current_user_can( 'muhiku_forms_view_forms' ) && ! current_user_can( 'muhiku_forms_view_others_forms' ) ) {
			$args['author'] = $user_id;
		}

		if ( ! current_user_can( 'muhiku_forms_view_forms' ) && current_user_can( 'muhiku_forms_view_others_forms' ) ) {
			$args['author__not_in'] = $user_id;
		}

		if ( ! current_user_can( 'muhiku_forms_view_forms' ) && ! current_user_can( 'muhiku_forms_view_others_forms' ) ) {
			$args['post__in'] = array( 0 );
		}

		$posts       = new WP_Query( $args );
		$this->items = $posts->posts;

		$this->set_pagination_args(
			array(
				'total_items' => $posts->found_posts,
				'per_page'    => $per_page,
				'total_pages' => $posts->max_num_pages,
			)
		);
	}
}

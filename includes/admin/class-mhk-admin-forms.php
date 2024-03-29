<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

class MHK_Admin_Forms {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'actions' ) );
		add_action( 'deleted_post', array( $this, 'delete_entries' ) );
		add_filter( 'wp_untrash_post_status', array( $this, 'untrash_form_status' ), 10, 2 );
	}

	/**
	 * @return bool
	 */
	private function is_forms_page() {
		return isset( $_GET['page'] ) && 'mhk-builder' === $_GET['page']; 
	}

	public static function page_output() {
		global $current_tab;

		if ( isset( $_GET['form_id'] ) && $current_tab ) { 
			$form      = mhk()->form->get( absint( $_GET['form_id'] ) ); 
			$form_id   = is_object( $form ) ? absint( $form->ID ) : absint( $_GET['form_id'] ); 
			$form_data = is_object( $form ) ? mhk_decode( $form->post_content ) : false;

			include 'views/html-admin-page-builder.php';
		} elseif ( isset( $_GET['create-form'] ) ) { 
			$templates       = array();
			$refresh_url     = add_query_arg(
				array(
					'page'               => 'mhk-builder&create-form=1',
					'action'             => 'mhk-template-refresh',
					'mhk-template-nonce' => wp_create_nonce( 'refresh' ),
				),
				admin_url( 'admin.php' )
			);
			$license_plan    = mhk_get_license_plan();
			$current_section = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : '_all'; 

			if ( '_featured' !== $current_section ) {
				$category  = isset( $_GET['section'] ) ? sanitize_text_field( wp_unslash( $_GET['section'] ) ) : 'free'; 
				$templates = self::get_template_data( $category );
			}

			/**
			 * @uses $templates
			 * @uses $refresh_url
			 * @uses $current_section
			 */
			include 'views/html-admin-page-builder-setup.php';
		} else {
			self::table_list_output();
		}
	}

	/**
	 * @return array of objects
	 */
	public static function get_sections() {
		$template_sections = get_transient( 'mhk_template_sections_list' );

		if ( false === $template_sections ) {
			$template_sections = mhk_get_json_file_contents( 'assets/extensions-json/templates/template-sections.json' );

			if ( $template_sections ) {
				set_transient( 'mhk_template_sections_list', $template_sections, WEEK_IN_SECONDS );
			}
		}

		return apply_filters( 'muhiku_forms_template_sections', $template_sections );
	}

	/**
	 * @return array
	 */
	public static function get_template_data() {
		$template_data = get_transient( 'mhk_template_section_list' );

		if ( false === $template_data ) {
			$template_data     = mhk_get_json_file_contents( 'assets/extensions-json/templates/all_templates.json' );

			$folder_path = untrailingslashit( plugin_dir_path( MHK_PLUGIN_FILE ) . '/assets/images/templates' );

			foreach ( $template_data->templates as $template_tuple ) {
			
				$image = wp_remote_get( $template_tuple->image );
				$type  = wp_remote_retrieve_header( $image, 'content-type' );

			
				if ( ! $type ) {
					continue;
				}

				$temp_name     = explode( '/', $template_tuple->image );
				$relative_path = $folder_path . '/' . end( $temp_name );
				$exists        = file_exists( $relative_path );


				if ( $exists ) {
					$template_tuple->image = plugin_dir_url( MHK_PLUGIN_FILE ) . 'assets/images/templates/' . end( $temp_name );
				}
			}

			if ( ! empty( $template_data->templates ) ) {
				set_transient( 'mhk_template_section_list', $template_data, WEEK_IN_SECONDS );
			}
		}

		if ( ! empty( $template_data->templates ) ) {
			return apply_filters( 'muhiku_forms_template_section_data', $template_data->templates );
		}
	}

	public static function table_list_output() {
		global $forms_table_list;

		$forms_table_list->process_bulk_action();
		$forms_table_list->prepare_items();
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Bütün Öneri Talebi Formları', 'muhiku-plug' ); ?></h1>
			<?php if ( current_user_can( 'muhiku_forms_create_forms' ) ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=mhk-builder&create-form=1' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Yeni Form Ekle', 'muhiku-plug' ); ?></a>
			<?php endif; ?>
			<hr class="wp-header-end">

			<?php settings_errors(); ?>

			<form id="form-list" method="post">
				<input type="hidden" name="page" value="muhiku-plug"/>
				<?php
					$forms_table_list->views();
					$forms_table_list->search_box( __( 'Form İçinde Ara', 'muhiku-plug' ), 'muhiku-plug' );
					$forms_table_list->display();

					wp_nonce_field( 'save', 'muhiku-plug_nonce' );
				?>
			</form>
		</div>
		<?php
	}

	public function actions() {
		if ( $this->is_forms_page() ) {
			if ( isset( $_REQUEST['delete_all'] ) || isset( $_REQUEST['delete_all2'] ) ) {  
				$this->empty_trash();
			}

			if ( isset( $_REQUEST['action'] ) && 'duplicate_form' === $_REQUEST['action'] ) {  
				$this->duplicate_form();
			}
		}
	}

	private function empty_trash() {
		check_admin_referer( 'bulk-forms' );

		$count    = 0;
		$form_ids = get_posts(
			array(
				'post_type'           => 'muhiku_form',
				'ignore_sticky_posts' => true,
				'nopaging'            => true,
				'post_status'         => 'trash',
				'fields'              => 'ids',
			)
		);

		foreach ( $form_ids as $form_id ) {
			if ( wp_delete_post( $form_id, true ) ) {
				$count ++;
			}
		}

		add_settings_error(
			'empty_trash',
			'empty_trash',
			sprintf( _n( '%d form permanently deleted.', '%d forms permanently deleted.', $count, 'muhiku-plug' ), $count ),
			'updated'
		);
	}

	private function duplicate_form() {
		if ( empty( $_REQUEST['form_id'] ) ) {
			wp_die( esc_html__( 'No form to duplicate has been supplied!', 'muhiku-plug' ) );
		}

		$form_id = isset( $_REQUEST['form_id'] ) ? absint( $_REQUEST['form_id'] ) : '';

		check_admin_referer( 'muhiku-plug-duplicate-form_' . $form_id );

		$duplicate_id = mhk()->form->duplicate( $form_id );

		wp_safe_redirect( admin_url( 'admin.php?page=mhk-builder&tab=fields&form_id=' . $duplicate_id ) );
		exit;
	}

	/**
	 * @param int $postid Post ID.
	 */
	public function delete_entries( $postid ) {
		global $wpdb;

		$entries = mhk_get_entries_ids( $postid );

		if ( ! empty( $entries ) ) {
			foreach ( $entries as $entry_id ) {
				$wpdb->delete( $wpdb->prefix . 'mhk_entries', array( 'entry_id' => $entry_id ), array( '%d' ) );
				$wpdb->delete( $wpdb->prefix . 'mhk_entrymeta', array( 'entry_id' => $entry_id ), array( '%d' ) );
			}
		}
	}

	/**
	 * @param string $new_status 
	 * @param int    $post_id    
	 * @return string
	 */
	public function untrash_form_status( $new_status, $post_id ) {
		return current_user_can( 'muhiku_forms_edit_forms', $post_id ) ? 'publish' : $new_status;
	}
}

new MHK_Admin_Forms();

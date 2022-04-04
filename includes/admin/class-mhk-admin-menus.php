<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Admin_Menus', false ) ) {
	return new MHK_Admin_Menus();
}

class MHK_Admin_Menus {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'builder_menu' ), 20 );
		add_action( 'admin_menu', array( $this, 'entries_menu' ), 30 );

		add_action( 'admin_head', array( $this, 'menu_highlight' ) );
		add_action( 'admin_head', array( $this, 'custom_menu_count' ) );
		add_action( 'admin_head', array( $this, 'hide_submenu_items' ) );
		add_filter( 'custom_menu_order', array( $this, 'custom_menu_order' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 11, 3 );
	}

	/**
	 * @param  string $fill   
	 * @param  bool   $base64 
	 * @return string
	 */
	public static function get_icon_svg( $fill = '#82878c', $base64 = true ) {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path fill="' . $fill . '" d="M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z"/></g></svg>';

		if ( $base64 ) {
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return $svg;
	}

	public function admin_menu() {
		add_menu_page( esc_html__( 'Muhiku Plug', 'muhiku-plug' ), esc_html__( 'Muhiku Plug', 'muhiku-plug' ), 'manage_muhiku_forms', 'muhiku-plug', null, self::get_icon_svg(), '55.5' );
	}

	public function builder_menu() {
		$builder_page = add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug Builder', 'muhiku-plug' ), esc_html__( 'Bütün Öneri Talebi Formları', 'muhiku-plug' ), current_user_can( 'muhiku_forms_create_forms' ) ? 'muhiku_forms_create_forms' : 'muhiku_forms_view_forms', 'mhk-builder', array( $this, 'builder_page' ) );

		add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug Setup', 'muhiku-plug' ), esc_html__( 'Yeni Form Ekle', 'muhiku-plug' ), current_user_can( 'muhiku_forms_create_forms' ) ? 'muhiku_forms_create_forms' : 'muhiku_forms_edit_forms', 'mhk-builder&create-form=1', array( $this, 'builder_page' ) );

		add_action( 'load-' . $builder_page, array( $this, 'builder_page_init' ) );

		if ( ! current_user_can( 'manage_muhiku_forms' ) ) {
			if ( ! current_user_can( 'muhiku_forms_create_forms' ) ) {
				if ( isset( $_GET['page'], $_GET['create-form'] ) && 'mhk-builder' === $_GET['page'] ) {  
					wp_safe_redirect( admin_url( 'admin.php?page=mhk-builder' ) );
					exit;
				}
			} elseif ( ! current_user_can( 'muhiku_forms_view_forms' ) ) {
				if ( ! isset( $_GET['create-form'] ) && ( ! empty( $_GET['page'] ) && 'mhk-builder' === $_GET['page'] ) ) {  
					wp_safe_redirect( admin_url( 'admin.php?page=mhk-builder&create-form=1' ) );
					exit;
				}
			}
		}
	}

	public function builder_page_init() {
		global $current_tab, $forms_table_list;

		mhk()->form_fields();

		MHK_Admin_Builder::get_builder_pages();

		$current_tab = empty( $_GET['tab'] ) ? 'fields' : sanitize_title( wp_unslash( $_GET['tab'] ) ); 

		if ( ! isset( $_GET['tab'], $_GET['form_id'] ) ) { 
			$forms_table_list = new MHK_Admin_Forms_Table_List();

			add_screen_option(
				'per_page',
				array(
					'default' => 20,
					'option'  => 'mhk_forms_per_page',
				)
			);
		}

		do_action( 'muhiku_forms_builder_page_init' );
	}

	public function entries_menu() {
		$entries_page = add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug Entries', 'muhiku-plug' ), esc_html__( 'Yanıtlar', 'muhiku-plug' ), current_user_can( 'muhiku_forms_view_entries' ) ? 'muhiku_forms_view_entries' : 'muhiku_forms_view_others_entries', 'mhk-entries', array( $this, 'entries_page' ) );
		add_action( 'load-' . $entries_page, array( $this, 'entries_page_init' ) );
	}

	public function entries_page_init() {
		global $entries_table_list;

		if ( ! isset( $_GET['view-entry'] ) ) { 
			$entries_table_list = new MHK_Admin_Entries_Table_List();

			add_screen_option(
				'per_page',
				array(
					'default' => 20,
					'option'  => 'mhk_entries_per_page',
				)
			);
		}

		do_action( 'muhiku_forms_entries_page_init' );
	}

	public function settings_menu() {
		$settings_page = add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug settings', 'muhiku-plug' ), esc_html__( 'Settings', 'muhiku-plug' ), 'manage_muhiku_forms', 'mhk-settings', array( $this, 'settings_page' ) );

		add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
	}

	public function settings_page_init() {
		global $current_tab, $current_section;

		MHK_Admin_Settings::get_settings_pages();

		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); 
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); 

		if ( apply_filters( '' !== $current_section ? "muhiku_forms_save_settings_{$current_tab}_{$current_section}" : "muhiku_forms_save_settings_{$current_tab}", ! empty( $_POST ) ) ) { 
			MHK_Admin_Settings::save();
		}

		if ( ! empty( $_GET['mhk_error'] ) ) { 
			MHK_Admin_Settings::add_error( wp_kses_post( wp_unslash( $_GET['mhk_error'] ) ) ); 
		}

		if ( ! empty( $_GET['mhk_message'] ) ) { 
			MHK_Admin_Settings::add_message( wp_kses_post( wp_unslash( $_GET['mhk_message'] ) ) ); 
		}

		do_action( 'muhiku_forms_settings_page_init' );
	}

	public function tools_menu() {
		add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug tools', 'muhiku-plug' ), esc_html__( 'Tools', 'muhiku-plug' ), 'manage_muhiku_forms', 'mhk-tools', array( $this, 'tools_page' ) );
	}


	public function menu_highlight() {
		global $parent_file, $submenu_file;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( isset( $_GET['create-form'] ) && in_array( $screen_id, array( 'muhiku-plug_page_mhk-builder' ), true ) ) { 
			$parent_file  = 'muhiku-plug'; 
			$submenu_file = 'mhk-builder&create-form=1'; 
		}
	}

	public function custom_menu_count() {
		global $submenu;

		if ( isset( $submenu['muhiku-plug'] ) ) {
			if ( apply_filters( 'muhiku_forms_include_count_in_menu', true ) && current_user_can( 'manage_muhiku_forms' ) ) {
				do_action( 'muhiku_forms_custom_menu_count' );
			}
		}
	}
	public function hide_submenu_items() {
		global $submenu;

		if ( ! isset( $submenu['muhiku-plug'] ) ) {
			return;
		}

		foreach ( $submenu['muhiku-plug'] as $key => $item ) {
			if ( isset( $item[2] ) && 'muhiku-plug' === $item[2] ) {
				unset( $submenu['muhiku-plug'][ $key ] );
				break;
			}
		}

		if ( ! current_user_can( 'muhiku_forms_view_forms' ) ) {
			foreach ( $submenu['muhiku-plug'] as $key => $item ) {
				if ( isset( $item[2] ) && 'mhk-builder' === $item[2] ) {
					unset( $submenu['muhiku-plug'][ $key ] );
					break;
				}
			}
		}

		if ( ! current_user_can( 'muhiku_forms_create_forms' ) ) {
			foreach ( $submenu['muhiku-plug'] as $key => $item ) {
				if ( isset( $item[2] ) && 'mhk-builder&create-form=1' === $item[2] ) {
					unset( $submenu['muhiku-plug'][ $key ] );
					break;
				}
			}
		}
	}

	/**
	 * @param  bool $enabled Whether custom menu ordering is already enabled.
	 * @return bool
	 */
	public function custom_menu_order( $enabled ) {
		return $enabled || current_user_can( 'manage_muhiku_forms' );
	}

	/**
	 * @param bool|int $status 
	 * @param string   $option 
	 * @param int      $value  
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'mhk_forms_per_page', 'mhk_entries_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

	public function builder_page() {
		MHK_Admin_Forms::page_output();
	}
	public function entries_page() {
		MHK_Admin_Entries::page_output();
	}
	public function settings_page() {
		MHK_Admin_Settings::output();
	}
	public function tools_page() {
		MHK_Admin_Tools::output();
	}
	public function addons_page() {
		MHK_Admin_Addons::output();
	}
}

return new MHK_Admin_Menus();

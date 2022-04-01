<?php
/**
 * Setup menus in WP admin.
 *
 * @package MuhikuPlug\Admin
 * @version 1.2.0
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Admin_Menus', false ) ) {
	return new MHK_Admin_Menus();
}

/**
 * MHK_Admin_Menus Class.
 */
class MHK_Admin_Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus.
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
	 * Returns a base64 URL for the SVG for use in the menu.
	 *
	 * @param  string $fill   SVG Fill color code. Default: '#82878c'.
	 * @param  bool   $base64 Whether or not to return base64-encoded SVG.
	 * @return string
	 */
	public static function get_icon_svg( $fill = '#82878c', $base64 = true ) {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path fill="' . $fill . '" d="M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z"/></g></svg>';

		if ( $base64 ) {
			return 'data:image/svg+xml;base64,' . base64_encode( $svg );
		}

		return $svg;
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		add_menu_page( esc_html__( 'Muhiku Plug', 'muhiku-plug' ), esc_html__( 'Muhiku Plug', 'muhiku-plug' ), 'manage_muhiku_forms', 'muhiku-plug', null, self::get_icon_svg(), '55.5' );
	}

	/**
	 * Add menu items.
	 */
	public function builder_menu() {
		$builder_page = add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug Builder', 'muhiku-plug' ), esc_html__( 'Bütün Öneri Talebi Formları', 'muhiku-plug' ), current_user_can( 'muhiku_forms_create_forms' ) ? 'muhiku_forms_create_forms' : 'muhiku_forms_view_forms', 'mhk-builder', array( $this, 'builder_page' ) );

		add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug Setup', 'muhiku-plug' ), esc_html__( 'Yeni Form Ekle', 'muhiku-plug' ), current_user_can( 'muhiku_forms_create_forms' ) ? 'muhiku_forms_create_forms' : 'muhiku_forms_edit_forms', 'mhk-builder&create-form=1', array( $this, 'builder_page' ) );

		add_action( 'load-' . $builder_page, array( $this, 'builder_page_init' ) );

		/*
		 * Page redirects based on user's capability as 'All Forms' and 'Yeni Form Ekle' both have same handle.
		 *
		 * - If only `muhiku_forms_create_forms` roles - dont show view all forms list table.
		 * - If only `muhiku_forms_view_forms` roles - dont show create new template selection.
		 */
		if ( ! current_user_can( 'manage_muhiku_forms' ) ) {
			if ( ! current_user_can( 'muhiku_forms_create_forms' ) ) {
				if ( isset( $_GET['page'], $_GET['create-form'] ) && 'mhk-builder' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
					wp_safe_redirect( admin_url( 'admin.php?page=mhk-builder' ) );
					exit;
				}
			} elseif ( ! current_user_can( 'muhiku_forms_view_forms' ) ) {
				if ( ! isset( $_GET['create-form'] ) && ( ! empty( $_GET['page'] ) && 'mhk-builder' === $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					wp_safe_redirect( admin_url( 'admin.php?page=mhk-builder&create-form=1' ) );
					exit;
				}
			}
		}
	}

	/**
	 * Loads builder page.
	 */
	public function builder_page_init() {
		global $current_tab, $forms_table_list;

		mhk()->form_fields();

		// Include builder pages.
		MHK_Admin_Builder::get_builder_pages();

		// Get current tab/section.
		$current_tab = empty( $_GET['tab'] ) ? 'fields' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! isset( $_GET['tab'], $_GET['form_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$forms_table_list = new MHK_Admin_Forms_Table_List();

			// Add screen option.
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

	/**
	 * Add menu item.
	 */
	public function entries_menu() {
		$entries_page = add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug Entries', 'muhiku-plug' ), esc_html__( 'Yanıtlar', 'muhiku-plug' ), current_user_can( 'muhiku_forms_view_entries' ) ? 'muhiku_forms_view_entries' : 'muhiku_forms_view_others_entries', 'mhk-entries', array( $this, 'entries_page' ) );
		add_action( 'load-' . $entries_page, array( $this, 'entries_page_init' ) );
	}

	/**
	 * Loads entries into memory.
	 */
	public function entries_page_init() {
		global $entries_table_list;

		if ( ! isset( $_GET['view-entry'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$entries_table_list = new MHK_Admin_Entries_Table_List();

			// Add screen option.
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

	/**
	 * Add menu item.
	 */
	public function settings_menu() {
		$settings_page = add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug settings', 'muhiku-plug' ), esc_html__( 'Settings', 'muhiku-plug' ), 'manage_muhiku_forms', 'mhk-settings', array( $this, 'settings_page' ) );

		add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
	}

	/**
	 * Loads settings page.
	 */
	public function settings_page_init() {
		global $current_tab, $current_section;

		// Include settings pages.
		MHK_Admin_Settings::get_settings_pages();

		// Get current tab/section.
		$current_tab     = empty( $_GET['tab'] ) ? 'general' : sanitize_title( wp_unslash( $_GET['tab'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( wp_unslash( $_REQUEST['section'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		// Save settings if data has been posted.
		if ( apply_filters( '' !== $current_section ? "muhiku_forms_save_settings_{$current_tab}_{$current_section}" : "muhiku_forms_save_settings_{$current_tab}", ! empty( $_POST ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			MHK_Admin_Settings::save();
		}

		// Add any posted messages.
		if ( ! empty( $_GET['mhk_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			MHK_Admin_Settings::add_error( wp_kses_post( wp_unslash( $_GET['mhk_error'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if ( ! empty( $_GET['mhk_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			MHK_Admin_Settings::add_message( wp_kses_post( wp_unslash( $_GET['mhk_message'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		do_action( 'muhiku_forms_settings_page_init' );
	}

	/**
	 * Add menu item.
	 */
	public function tools_menu() {
		add_submenu_page( 'muhiku-plug', esc_html__( 'Muhiku Plug tools', 'muhiku-plug' ), esc_html__( 'Tools', 'muhiku-plug' ), 'manage_muhiku_forms', 'mhk-tools', array( $this, 'tools_page' ) );
	}


	/**
	 * Highlights the correct top level admin menu item.
	 */
	public function menu_highlight() {
		global $parent_file, $submenu_file;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Check to make sure we're on a MuhikuPlug builder setup page.
		if ( isset( $_GET['create-form'] ) && in_array( $screen_id, array( 'muhiku-plug_page_mhk-builder' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$parent_file  = 'muhiku-plug'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$submenu_file = 'mhk-builder&create-form=1'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}

	/**
	 * Adds the custom count to the menu.
	 */
	public function custom_menu_count() {
		global $submenu;

		// Add count if user has access.
		if ( isset( $submenu['muhiku-plug'] ) ) {
			if ( apply_filters( 'muhiku_forms_include_count_in_menu', true ) && current_user_can( 'manage_muhiku_forms' ) ) {
				do_action( 'muhiku_forms_custom_menu_count' );
			}
		}
	}

	/**
	 * Hide submenu menu item if a user can't access.
	 *
	 * @since 1.7.5
	 */
	public function hide_submenu_items() {
		global $submenu;

		if ( ! isset( $submenu['muhiku-plug'] ) ) {
			return;
		}

		// Remove 'Muhiku Plug' sub menu item.
		foreach ( $submenu['muhiku-plug'] as $key => $item ) {
			if ( isset( $item[2] ) && 'muhiku-plug' === $item[2] ) {
				unset( $submenu['muhiku-plug'][ $key ] );
				break;
			}
		}

		// Remove 'All Forms' sub menu item if a user can't read forms.
		if ( ! current_user_can( 'muhiku_forms_view_forms' ) ) {
			foreach ( $submenu['muhiku-plug'] as $key => $item ) {
				if ( isset( $item[2] ) && 'mhk-builder' === $item[2] ) {
					unset( $submenu['muhiku-plug'][ $key ] );
					break;
				}
			}
		}

		// Remove 'Yeni Form Ekle' sub menu item if a user can't create forms.
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
	 * Custom menu order.
	 *
	 * @param  bool $enabled Whether custom menu ordering is already enabled.
	 * @return bool
	 */
	public function custom_menu_order( $enabled ) {
		return $enabled || current_user_can( 'manage_muhiku_forms' );
	}

	/**
	 * Validate screen options on update.
	 *
	 * @param bool|int $status Screen option value. Default false to skip.
	 * @param string   $option The option name.
	 * @param int      $value  The number of rows to use.
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( in_array( $option, array( 'mhk_forms_per_page', 'mhk_entries_per_page' ), true ) ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Init the settings page.
	 */
	public function builder_page() {
		MHK_Admin_Forms::page_output();
	}

	/**
	 * Init the entries page.
	 */
	public function entries_page() {
		MHK_Admin_Entries::page_output();
	}

	/**
	 * Init the settings page.
	 */
	public function settings_page() {
		MHK_Admin_Settings::output();
	}

	/**
	 * Init the status page.
	 */
	public function tools_page() {
		MHK_Admin_Tools::output();
	}

	/**
	 * Init the addons page.
	 */
	public function addons_page() {
		MHK_Admin_Addons::output();
	}
}

return new MHK_Admin_Menus();

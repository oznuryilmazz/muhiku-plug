<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

class MHK_Admin {

	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'template_actions' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'mhk_print_js', 25 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
	}

	public function buffer() {
		ob_start();
	}

	public function includes() {
		include_once dirname( __FILE__ ) . '/mhk-admin-functions.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-menus.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-notices.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-assets.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-editor.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-forms.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-entries.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-import-export.php';

		if ( ! empty( $_GET['page'] ) ) { 
			switch ( $_GET['page'] ) { 
				case 'mhk-welcome':
					include_once dirname( __FILE__ ) . '/class-mhk-admin-welcome.php';
					break;
			}
		}
	}

	public function template_actions() {
		if ( isset( $_GET['page'], $_REQUEST['action'] ) && 'mhk-builder' === $_GET['page'] ) {
			$action        = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
			$templatres = mhk_get_json_file_contents( 'assets/extensions-json/templates/all_templates.json' );

			if ( 'mhk-template-refresh' === $action && ! empty( $templatres ) ) {
				if ( empty( $_GET['mhk-template-nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['mhk-template-nonce'] ) ), 'refresh' ) ) {
					wp_die( esc_html_e( 'Could not verify nonce', 'muhiku-plug' ) );
				}

				foreach ( array( 'mhk_pro_license_plan', 'mhk_template_sections', 'mhk_template_section' ) as $transient ) {
					delete_transient( $transient );
				}

				wp_safe_redirect( admin_url( 'admin.php?page=mhk-builder&create-form=1' ) );
				exit;
			}
		}
	}

	public function admin_redirects() {
		if ( ! empty( $_GET['mhk-install-plugin-redirect'] ) ) {
			$plugin_slug = mhk_clean( esc_url_raw( wp_unslash( $_GET['mhk-install-plugin-redirect'] ) ) ); 

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );
			wp_safe_redirect( $url );
			exit;
		}

		if ( get_transient( '_mhk_activation_redirect' ) && apply_filters( 'muhiku_forms_show_welcome_page', true ) ) {
			$do_redirect  = true;
			$current_page = isset( $_GET['page'] ) ? mhk_clean( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : false; 

			if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_muhiku_forms' ) ) {
				$do_redirect = false;
			}

			if ( 'mhk-welcome' === $current_page || MHK_Admin_Notices::has_notice( 'install' ) || apply_filters( 'muhiku_forms_prevent_automatic_wizard_redirect', false ) || isset( $_GET['activate-multi'] ) ) {  
				delete_transient( '_mhk_activation_redirect' );
				$do_redirect = false;
			}

			if ( $do_redirect ) {
				delete_transient( '_mhk_activation_redirect' );
				wp_safe_redirect( admin_url( 'index.php?page=mhk-welcome' ) );
				exit;
			}
		}
	}

	/**
	 * @param  array $classes Admin body classes.
	 * @return array
	 */
	public function admin_body_class( $classes ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		if ( ( isset( $_GET['form_id'] ) || isset( $_GET['create-form'] ) ) && in_array( $screen_id, array( 'muhiku-plug_page_mhk-builder' ), true ) ) { 
			$classes = isset( $_GET['form_id'] ) ? 'muhiku-plug-builder' : 'muhiku-plug-builder-setup';
		}

		return $classes;
	}
}

return new MHK_Admin();

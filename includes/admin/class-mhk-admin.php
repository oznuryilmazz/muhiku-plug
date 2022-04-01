<?php
/**
 * MuhikuPlug Admin
 *
 * @package MuhikuPlug\Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * EVF_Admin class.
 */
class EVF_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'includes' ) );
		add_action( 'admin_init', array( $this, 'buffer' ), 1 );
		add_action( 'admin_init', array( $this, 'template_actions' ) );
		add_action( 'admin_init', array( $this, 'admin_redirects' ) );
		add_action( 'admin_footer', 'mhk_print_js', 25 );
		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
	}

	/**
	 * Output buffering allows admin screens to make redirects later on.
	 */
	public function buffer() {
		ob_start();
	}

	/**
	 * Include any classes we need within admin.
	 */
	public function includes() {
		include_once dirname( __FILE__ ) . '/mhk-admin-functions.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-menus.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-notices.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-assets.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-editor.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-forms.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-entries.php';
		include_once dirname( __FILE__ ) . '/class-mhk-admin-import-export.php';

		// Setup/welcome.
		if ( ! empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			switch ( $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				case 'mhk-welcome':
					include_once dirname( __FILE__ ) . '/class-mhk-admin-welcome.php';
					break;
			}
		}
	}

	/**
	 * Handle redirects after template refresh.
	 */
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

				// Redirect to the builder page normally.
				wp_safe_redirect( admin_url( 'admin.php?page=mhk-builder&create-form=1' ) );
				exit;
			}
		}
	}

	/**
	 * Handle redirects to setup/welcome page after install and updates.
	 *
	 * For setup wizard, transient must be present, the user must have access rights, and we must ignore the network/bulk plugin updaters.
	 */
	public function admin_redirects() {
		// Nonced plugin install redirects (whitelisted).
		if ( ! empty( $_GET['mhk-install-plugin-redirect'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$plugin_slug = mhk_clean( esc_url_raw( wp_unslash( $_GET['mhk-install-plugin-redirect'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.

			$url = admin_url( 'plugin-install.php?tab=search&type=term&s=' . $plugin_slug );
			wp_safe_redirect( $url );
			exit;
		}

		// Setup wizard redirect.
		if ( get_transient( '_mhk_activation_redirect' ) && apply_filters( 'everest_forms_show_welcome_page', true ) ) {
			$do_redirect  = true;
			$current_page = isset( $_GET['page'] ) ? mhk_clean( sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) : false; // phpcs:ignore WordPress.Security.NonceVerification

			// On these pages, or during these events, postpone the redirect.
			if ( wp_doing_ajax() || is_network_admin() || ! current_user_can( 'manage_everest_forms' ) ) {
				$do_redirect = false;
			}

			// On these pages, or during these events, disable the redirect.
			if ( 'mhk-welcome' === $current_page || EVF_Admin_Notices::has_notice( 'install' ) || apply_filters( 'everest_forms_prevent_automatic_wizard_redirect', false ) || isset( $_GET['activate-multi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
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
	 * Change the admin footer text on MuhikuPlug admin pages.
	 *
	 * @since  1.0.0
	 * @param  string $footer_text Footer text.
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {
		if ( ! current_user_can( 'manage_everest_forms' ) || ! function_exists( 'mhk_get_screen_ids' ) ) {
			return $footer_text;
		}
		$current_screen = get_current_screen();
		$mhk_pages      = mhk_get_screen_ids();

		// Check to make sure we're on a MuhikuPlug admin page.
		if ( isset( $current_screen->id ) && apply_filters( 'everest_forms_display_admin_footer_text', in_array( $current_screen->id, $mhk_pages, true ) ) ) {
			// Change the footer text.
			if ( ! get_option( 'everest_forms_admin_footer_text_rated' ) ) {
				$footer_text = sprintf(
					/* translators: 1: MuhikuPlug 2:: five stars */
					esc_html__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'muhiku-plug' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'Muhiku Plug', 'muhiku-plug' ) ),
					'<a href="https://wordpress.org/support/plugin/muhiku-plug/reviews?rate=5#new-post" target="_blank" class="mhk-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'muhiku-plug' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);
				mhk_enqueue_js(
					"
					jQuery( 'a.mhk-rating-link' ).on( 'click', function() {
						jQuery.post( '" . mhk()->ajax_url() . "', { action: 'everest_forms_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
					"
				);
			} else {
				$footer_text = esc_html__( 'Thank you for creating with Muhiku Plug.', 'muhiku-plug' );
			}
		}

		return $footer_text;
	}

	/**
	 * Add body classes for Everest builder.
	 *
	 * @param  array $classes Admin body classes.
	 * @return array
	 */
	public function admin_body_class( $classes ) {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Check to make sure we're on a MuhikuPlug builder page.
		if ( ( isset( $_GET['form_id'] ) || isset( $_GET['create-form'] ) ) && in_array( $screen_id, array( 'muhiku-plug_page_mhk-builder' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$classes = isset( $_GET['form_id'] ) ? 'muhiku-plug-builder' : 'muhiku-plug-builder-setup'; // phpcs:ignore WordPress.Security.NonceVerification
		}

		return $classes;
	}
}

return new EVF_Admin();

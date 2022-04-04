<?php
/**
 * @package MuhikuPlug/Admin
 */

defined( 'ABSPATH' ) || exit;

class MHK_Admin_Notices {

	/**
	 * @var array
	 */
	private static $notices = array();

	/**
	 * @var array
	 */
	private static $core_notices = array(
		'update' => 'update_notice',
		'review' => 'review_notice',
		'survey' => 'survey_notice',
	);

	public static function init() {
		self::$notices = get_option( 'muhiku_forms_admin_notices', array() );

		add_action( 'switch_theme', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'muhiku_forms_installed', array( __CLASS__, 'reset_admin_notices' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'hide_notices' ) );
		add_action( 'shutdown', array( __CLASS__, 'store_notices' ) );

		if ( current_user_can( 'manage_muhiku_forms' ) ) {
			add_action( 'admin_print_styles', array( __CLASS__, 'add_notices' ) );
			add_action( 'in_admin_header', array( __CLASS__, 'hide_unrelated_notices' ) );
		}
	}

	public static function store_notices() {
		update_option( 'muhiku_forms_admin_notices', self::get_notices() );
	}

	/**
	 * @return array
	 */
	public static function get_notices() {
		return self::$notices;
	}

	public static function remove_all_notices() {
		self::$notices = array();
	}

	public static function reset_admin_notices() {
		if ( self::is_plugin_active( 'muhiku-plug-stripe/muhiku-plug-stripe.php' ) ) {
			self::add_notice( 'deprecated_payment_charge' );
		}
		self::add_notice( 'review' );
		self::add_notice( 'survey' );
	}

	/**
	 * @param string $name Notice name.
	 */
	public static function add_notice( $name ) {
		self::$notices = array_unique( array_merge( self::get_notices(), array( $name ) ) );
	}

	/**
	 * @param string $name
	 */
	public static function remove_notice( $name ) {
		self::$notices = array_diff( self::get_notices(), array( $name ) );
		delete_option( 'muhiku_forms_admin_notice_' . $name );
	}

	/**
	 * @param  string $name 
	 * @return boolean
	 */
	public static function has_notice( $name ) {
		return in_array( $name, self::get_notices(), true );
	}

	public static function hide_notices() {
		if ( isset( $_GET['mhk-hide-notice'] ) && isset( $_GET['_mhk_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_mhk_notice_nonce'] ) ), 'muhiku_forms_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'muhiku-plug' ) );
			}

			if ( ! current_user_can( 'manage_muhiku_forms' ) ) {
				wp_die( esc_html__( 'You don&#8217;t have permission to do this.', 'muhiku-plug' ) );
			}

			$hide_notice = sanitize_text_field( wp_unslash( $_GET['mhk-hide-notice'] ) );

			self::remove_notice( $hide_notice );

			update_user_meta( get_current_user_id(), 'dismissed_' . $hide_notice . '_notice', true );

			do_action( 'muhiku_forms_hide_' . $hide_notice . '_notice' );
		}
	}

	public static function add_notices() {
		$notices = self::get_notices();

		if ( empty( $notices ) ) {
			return;
		}

		$screen          = get_current_screen();
		$screen_id       = $screen ? $screen->id : '';
		$show_on_screens = array(
			'dashboard',
			'plugins',
		);

		if ( ! in_array( $screen_id, mhk_get_screen_ids(), true ) && ! in_array( $screen_id, $show_on_screens, true ) ) {
			return;
		}

		wp_enqueue_style( 'muhiku-plug-activation', plugins_url( '/assets/css/activation.css', MHK_PLUGIN_FILE ), array(), MHK_VERSION );

		wp_style_add_data( 'muhiku-plug-activation', 'rtl', 'replace' );

		foreach ( $notices as $notice ) {
			if ( ! empty( self::$core_notices[ $notice ] ) && apply_filters( 'muhiku_forms_show_admin_notice', true, $notice ) ) {
				add_action( 'admin_notices', array( __CLASS__, self::$core_notices[ $notice ] ) );
			} else {
				add_action( 'admin_notices', array( __CLASS__, 'output_custom_notices' ) );
			}
		}
	}

	/**
	 * @param string $name        
	 * @param string $notice_html
	 */
	public static function add_custom_notice( $name, $notice_html ) {
		self::add_notice( $name );
		update_option( 'muhiku_forms_admin_notice_' . $name, wp_kses_post( $notice_html ) );
	}

	public static function output_custom_notices() {
		$notices = self::get_notices();

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $notice ) {
				if ( empty( self::$core_notices[ $notice ] ) ) {
					$notice_html = get_option( 'muhiku_forms_admin_notice_' . $notice );

					if ( $notice_html ) {
						include 'views/html-notice-custom.php';
					}
				}
			}
		}
	}

	public static function update_notice() {
		if ( MHK_Install::needs_db_update() ) {
			$updater = new MHK_Background_Updater();

			if ( $updater->is_updating() || ! empty( $_GET['do_update_muhiku_forms'] ) ) {  
				include 'views/html-notice-updating.php';
			} else {
				include 'views/html-notice-update.php';
			}
		} else {
			MHK_Install::update_db_version();
			include 'views/html-notice-updated.php';
		}
	}

	public static function review_notice() {
		global $wpdb;

		if ( self::survey_notice( true ) ) {
			return;
		}

		$load      = false;
		$time      = time();
		$review    = get_option( 'muhiku_forms_review' );
		$activated = get_option( 'muhiku_forms_activated' );

		if ( ! $review ) {
			$review = array(
				'time'      => $time,
				'dismissed' => false,
			);
			update_option( 'muhiku_forms_review', $review );
		} else {
			if ( ( isset( $review['dismissed'] ) && ! $review['dismissed'] ) && ( isset( $review['time'] ) && ( ( $review['time'] + DAY_IN_SECONDS ) <= $time ) ) ) {
				$load = true;
			}
		}

		if ( $load && class_exists( 'MuhikuPlug_Pro', false ) ) {
			$entries_count = $wpdb->get_var( "SELECT COUNT(entry_id) FROM {$wpdb->prefix}mhk_entries WHERE `status` = 'publish'" );

			if ( empty( $entries_count ) || $entries_count < 50 ) {
				return;
			}
		} else {
			if ( ( $activated + ( WEEK_IN_SECONDS * 2 ) ) > $time ) {
				return;
			}
		}

		if ( $load && ( is_super_admin() || current_user_can( 'manage_muhiku_forms' ) ) ) {
			include 'views/html-notice-review.php';
		}
	}

	/**
	 * @param boolean $status Twice notice to check.
	 * @return boolean
	 */
	public static function survey_notice( $status = false ) {

		$time        = time();
		$survey      = get_option( 'muhiku_forms_survey' );
		$activated   = get_option( 'muhiku_forms_activated' );
		$license_key = trim( get_option( 'muhiku-plug-pro_license_key' ) );

		if ( ! empty( $survey['dismissed'] ) ) {
			return;
		}

		if ( ( $activated + ( DAY_IN_SECONDS * 10 ) ) > $time ) {
			return;
		}

		if ( ! $status && $license_key && ( is_super_admin() || current_user_can( 'manage_muhiku_forms' ) ) ) {
				include 'views/html-notice-survey.php';
		}

		return $status;

	}

	public static function hide_unrelated_notices() {
		global $wp_filter;

		if ( empty( $_REQUEST['page'] ) || false === strpos( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ), 'mhk-' ) ) {  
			return;
		}

		foreach ( array( 'user_admin_notices', 'admin_notices', 'all_admin_notices' ) as $wp_notice ) {
			if ( ! empty( $wp_filter[ $wp_notice ]->callbacks ) && is_array( $wp_filter[ $wp_notice ]->callbacks ) ) {
				foreach ( $wp_filter[ $wp_notice ]->callbacks as $priority => $hooks ) {
					foreach ( $hooks as $name => $arr ) {
						if ( is_object( $arr['function'] ) && $arr['function'] instanceof Closure ) {
							unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
							continue;
						}
						if ( ( isset( $_GET['tab'], $_GET['form_id'] ) || isset( $_GET['create-form'] ) ) && 'mhk-builder' === $_REQUEST['page'] ) {  
							unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
							continue;
						}
						if ( ! empty( $arr['function'][0] ) && is_object( $arr['function'][0] ) && false !== strpos( strtolower( get_class( $arr['function'][0] ) ), 'mhk_' ) ) {
							continue;
						}
						if ( ! empty( $name ) && false === strpos( strtolower( $name ), 'mhk_' ) ) {
							unset( $wp_filter[ $wp_notice ]->callbacks[ $priority ][ $name ] );
						}
					}
				}
			}
		}
	}

	/**
	 * @param string $plugin
	 * @return boolean
	 */
	protected static function is_plugin_active( $plugin ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $plugin );
	}
}

MHK_Admin_Notices::init();

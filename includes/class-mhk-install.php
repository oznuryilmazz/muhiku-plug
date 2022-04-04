<?php
/**
 * @package MuhikuPlug\Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_Install {

	/**
	 * @var array
	 */
	private static $db_updates = array(
		'1.0.0' => array(
			'mhk_update_100_db_version',
		),
		'1.0.1' => array(
			'mhk_update_101_db_version',
		),
		'1.0.2' => array(
			'mhk_update_102_db_version',
		),
		'1.0.3' => array(
			'mhk_update_103_db_version',
		),
		'1.1.0' => array(
			'mhk_update_110_update_forms',
			'mhk_update_110_db_version',
		),
		'1.1.6' => array(
			'mhk_update_116_delete_options',
			'mhk_update_116_db_version',
		),
		'1.2.0' => array(
			'mhk_update_120_db_rename_options',
			'mhk_update_120_db_version',
		),
		'1.3.0' => array(
			'mhk_update_130_db_version',
		),
		'1.4.0' => array(
			'mhk_update_140_db_multiple_email',
			'mhk_update_140_db_version',
		),
		'1.4.4' => array(
			'mhk_update_144_delete_options',
			'mhk_update_144_db_version',
		),
		'1.4.9' => array(
			'mhk_update_149_db_rename_options',
			'mhk_update_149_no_payment_options',
			'mhk_update_149_db_version',
		),
		'1.5.0' => array(
			'mhk_update_150_field_datetime_type',
			'mhk_update_150_db_version',
		),
		'1.6.0' => array(
			'mhk_update_160_db_version',
		),
		'1.7.5' => array(
			'mhk_update_175_remove_capabilities',
			'mhk_update_175_restore_draft_forms',
			'mhk_update_175_db_version',
		),
	);

	/**
	 * @var object
	 */
	private static $background_updater;

	public static function init() {
		add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'init', array( __CLASS__, 'init_background_updater' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'install_actions' ) );
		add_filter( 'map_meta_cap', array( __CLASS__, 'filter_map_meta_cap' ), 10, 4 );
		add_filter( 'plugin_action_links_' . MHK_PLUGIN_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
	}
	public static function init_background_updater() {
		include_once dirname( __FILE__ ) . '/class-mhk-background-updater.php';
		self::$background_updater = new MHK_Background_Updater();
	}

	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'muhiku_forms_version' ), mhk()->version, '<' ) ) {
			self::install();
			do_action( 'muhiku_forms_updated' );
		}
	}

	public static function install_actions() {
		if ( ! empty( $_GET['do_update_muhiku_forms'] ) ) {
			check_admin_referer( 'mhk_db_update', 'mhk_db_update_nonce' );
			self::update();
			MHK_Admin_Notices::add_notice( 'update' );
		}
		if ( ! empty( $_GET['force_update_muhiku_forms'] ) ) {
			do_action( 'wp_' . get_current_blog_id() . '_mhk_updater_cron' );
			wp_safe_redirect( admin_url( 'admin.php?page=mhk-settings' ) );
			exit;
		}
	}
	public static function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		if ( 'yes' === get_transient( 'mhk_installing' ) ) {
			return;
		}

		set_transient( 'mhk_installing', 'yes', MINUTE_IN_SECONDS * 10 );
		mhk_maybe_define_constant( 'MHK_INSTALLING', true );

		self::remove_admin_notices();
		self::create_options();
		self::create_tables();
		self::create_roles();
		self::setup_environment();
		self::create_cron_jobs();
		self::create_files();
		self::create_forms();
		self::maybe_set_activation_transients();
		self::update_mhk_version();
		self::maybe_update_db_version();
		self::maybe_add_activated_date();

		delete_transient( 'mhk_installing' );

		do_action( 'muhiku_forms_flush_rewrite_rules' );
		do_action( 'muhiku_forms_installed' );
	}

	private static function remove_admin_notices() {
		include_once dirname( __FILE__ ) . '/admin/class-mhk-admin-notices.php';
		MHK_Admin_Notices::remove_all_notices();
	}
	private static function setup_environment() {
		MHK_Post_Types::register_post_types();
	}

	/**
	 * @return boolean
	 */
	private static function is_new_install() {
		return is_null( get_option( 'muhiku_forms_version', null ) ) && is_null( get_option( 'muhiku_forms_db_version', null ) );
	}

	/**
	 * @return boolean
	 */
	public static function needs_db_update() {
		$current_db_version = get_option( 'muhiku_forms_db_version', null );
		$updates            = self::get_db_update_callbacks();
		$update_versions    = array_keys( $updates );
		usort( $update_versions, 'version_compare' );

		return ! is_null( $current_db_version ) && version_compare( $current_db_version, end( $update_versions ), '<' );
	}

	private static function maybe_set_activation_transients() {
		if ( self::is_new_install() ) {
			set_transient( '_mhk_activation_redirect', 1, 30 );
		}
	}

	private static function maybe_update_db_version() {
		if ( self::needs_db_update() ) {
			if ( apply_filters( 'muhiku_forms_enable_auto_update_db', false ) ) {
				self::init_background_updater();
				self::update();
			} else {
				MHK_Admin_Notices::add_notice( 'update' );
			}
		} else {
			self::update_db_version();
		}
	}

	private static function maybe_add_activated_date() {
		$activated_date = get_option( 'muhiku_forms_activated', '' );

		if ( empty( $activated_date ) ) {
			update_option( 'muhiku_forms_activated', time() );
		}
	}

	private static function update_mhk_version() {
		delete_option( 'muhiku_forms_version' );
		add_option( 'muhiku_forms_version', mhk()->version );
	}

	/**
	 * @return array
	 */
	public static function get_db_update_callbacks() {
		return self::$db_updates;
	}

	private static function update() {
		$current_db_version = get_option( 'muhiku_forms_db_version' );
		$logger             = mhk_get_logger();
		$update_queued      = false;

		foreach ( self::get_db_update_callbacks() as $version => $update_callbacks ) {
			if ( version_compare( $current_db_version, $version, '<' ) ) {
				foreach ( $update_callbacks as $update_callback ) {
					$logger->info(
						sprintf( 'Queuing %s - %s', $version, $update_callback ),
						array( 'source' => 'mhk_db_updates' )
					);
					self::$background_updater->push_to_queue( $update_callback );
					$update_queued = true;
				}
			}
		}

		if ( $update_queued ) {
			self::$background_updater->save()->dispatch();
		}
	}

	/**
	 * @param string|null $version New MuhikuPlug DB version or null.
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'muhiku_forms_db_version' );
		add_option( 'muhiku_forms_db_version', is_null( $version ) ? mhk()->version : $version );
	}

	/**
	 * @param  array $schedules List of WP scheduled cron jobs.
	 * @return array
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['monthly'] = array(
			'interval' => 2635200,
			'display'  => __( 'Monthly', 'muhiku-plug' ),
		);
		return $schedules;
	}

	private static function create_cron_jobs() {
		wp_clear_scheduled_hook( 'muhiku_forms_cleanup_logs' );
		wp_clear_scheduled_hook( 'muhiku_forms_cleanup_sessions' );
		wp_schedule_event( time() + ( 3 * HOUR_IN_SECONDS ), 'daily', 'muhiku_forms_cleanup_logs' );
		wp_schedule_event( time() + ( 6 * HOUR_IN_SECONDS ), 'twicedaily', 'muhiku_forms_cleanup_sessions' );
	}

	private static function create_options() {
		// Include settings so that we can run through defaults.
		include_once dirname( __FILE__ ) . '/admin/class-mhk-admin-settings.php';

		$settings = MHK_Admin_Settings::get_settings_pages();

		foreach ( $settings as $section ) {
			if ( ! method_exists( $section, 'get_settings' ) ) {
				continue;
			}
			$subsections = array_unique( array_merge( array( '' ), array_keys( $section->get_sections() ) ) );

			foreach ( $subsections as $subsection ) {
				foreach ( $section->get_settings( $subsection ) as $value ) {
					if ( isset( $value['default'] ) && isset( $value['id'] ) ) {
						$autoload = isset( $value['autoload'] ) ? (bool) $value['autoload'] : true;
						add_option( $value['id'], $value['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}
	}

	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}mhk_entries';" ) ) {
			if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}mhk_entries` LIKE 'fields';" ) ) {
				$wpdb->query( "ALTER TABLE {$wpdb->prefix}mhk_entries ADD `fields` longtext NULL AFTER `referer`;" );
			}
		}

		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}mhk_sessions'" ) ) {
			if ( ! $wpdb->get_var( "SHOW KEYS FROM {$wpdb->prefix}mhk_sessions WHERE Key_name = 'PRIMARY' AND Column_name = 'session_id'" ) ) {
				$wpdb->query(
					"ALTER TABLE `{$wpdb->prefix}mhk_sessions` DROP PRIMARY KEY, DROP KEY `session_id`, ADD PRIMARY KEY(`session_id`), ADD UNIQUE KEY(`session_key`)"
				);
			}
		}

		dbDelta( self::get_schema() );
	}

	/**
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$charset_collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$charset_collate = $wpdb->get_charset_collate();
		}

		$tables = "
			CREATE TABLE {$wpdb->prefix}mhk_entries (
				entry_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				form_id BIGINT UNSIGNED NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				user_device varchar(100) NOT NULL,
				user_ip_address VARCHAR(100) NULL DEFAULT '',
				referer text NOT NULL,
				fields longtext NULL,
				status varchar(20) NOT NULL,
				viewed tinyint(1) NOT NULL DEFAULT '0',
				starred tinyint(1) NOT NULL DEFAULT '0',
				date_created datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
				PRIMARY KEY  (entry_id),
				KEY form_id (form_id)
			) $charset_collate;
			CREATE TABLE {$wpdb->prefix}mhk_entrymeta (
				meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				entry_id BIGINT UNSIGNED NOT NULL,
				meta_key varchar(255) default NULL,
				meta_value longtext NULL,
				PRIMARY KEY  (meta_id),
				KEY entry_id (entry_id),
				KEY meta_key (meta_key(32))
			) $charset_collate;
			CREATE TABLE {$wpdb->prefix}mhk_sessions (
				session_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				session_key char(32) NOT NULL,
				session_value longtext NOT NULL,
				session_expiry BIGINT UNSIGNED NOT NULL,
				PRIMARY KEY  (session_id),
				UNIQUE KEY session_key (session_key)
			) $charset_collate;
		";

		return $tables;
	}

	/**
	 * @return array UM tables.
	 */
	public static function get_tables() {
		global $wpdb;

		$tables = array(
			"{$wpdb->prefix}mhk_entries",
			"{$wpdb->prefix}mhk_entrymeta",
			"{$wpdb->prefix}mhk_sessions",
		);

		return $tables;
	}

	public static function drop_tables() {
		global $wpdb;

		$tables = self::get_tables();

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}

	/**
	 * @param  array $tables List of tables that will be deleted by WP.
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		return array_merge( $tables, self::get_tables() );
	}

	public static function create_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles(); 
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->add_cap( 'administrator', $cap );
			}
		}
	}

	/**
	 * @return array $capabilities Core capabilities.
	 */
	private static function get_core_capabilities() {
		$capabilities = array();

		$capabilities['core'] = array(
			'manage_muhiku_forms',
		);

		$capability_types = array( 'forms', 'entries' );

		foreach ( $capability_types as $capability_type ) {
			if ( 'forms' === $capability_type ) {
				$capabilities[ $capability_type ][] = "muhiku_forms_create_{$capability_type}";
			}

			foreach ( array( 'view', 'edit', 'delete' ) as $context ) {
				$capabilities[ $capability_type ][] = "muhiku_forms_{$context}_{$capability_type}";
				$capabilities[ $capability_type ][] = "muhiku_forms_{$context}_others_{$capability_type}";
			}
		}

		return $capabilities;
	}

	/**
	 * @param string $cap Capability name to get.
	 * @return array $meta_caps Meta capabilities.
	 */
	private static function get_meta_caps( $cap = '' ) {
		$meta_caps      = array();
		$meta_cap_types = array( 'form', 'form_entries', 'entry' );

		foreach ( $meta_cap_types as $meta_cap_type ) {
			if ( $cap && $cap !== $meta_cap_type ) {
				continue;
			}

			foreach ( array( 'view', 'edit', 'delete' ) as $context ) {
				$meta_caps[ "muhiku_forms_{$context}_{$meta_cap_type}" ] = array(
					'own'    => 'form' === $meta_cap_type ? "muhiku_forms_{$context}_forms" : "muhiku_forms_{$context}_entries",
					'others' => 'form' === $meta_cap_type ? "muhiku_forms_{$context}_others_forms" : "muhiku_forms_{$context}_others_entries",
				);
			}
		}

		return $meta_caps;
	}

	public static function remove_roles() {
		global $wp_roles;

		if ( ! class_exists( 'WP_Roles' ) ) {
			return;
		}

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new WP_Roles();
		}

		$capabilities = self::get_core_capabilities();

		foreach ( $capabilities as $cap_group ) {
			foreach ( $cap_group as $cap ) {
				$wp_roles->remove_cap( 'administrator', $cap );
			}
		}
	}

	public static function create_forms() {
		$forms_count = wp_count_posts( 'muhiku_form' );

		if ( empty( $forms_count->publish ) ) {
			include_once dirname( __FILE__ ) . '/templates/contact.php';

			$form_id = wp_insert_post(
				array(
					'post_title'   => esc_html__( 'Contact Form', 'muhiku-plug' ),
					'post_status'  => 'publish',
					'post_type'    => 'muhiku_form',
					'post_content' => '{}',
				)
			);

			if ( $form_id ) {
				wp_update_post(
					array(
						'ID'           => $form_id,
						'post_content' => mhk_encode( array_merge( array( 'id' => $form_id ), $form_template['contact'] ) ),
					)
				);
			}

			update_option( 'muhiku_forms_default_form_page_id', $form_id );
		}
	}

	private static function create_files() {
		if ( apply_filters( 'muhiku_forms_install_skip_create_files', false ) ) {
			return;
		}
		$files = array(
			array(
				'base'    => MHK_LOG_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
			array(
				'base'    => MHK_LOG_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				$file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ); 
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] ); 
					fclose( $file_handle ); 
				}
			}
		}
	}

	/**
	 * @param  string[] $caps    Array of the user's capabilities.
	 * @param  string   $cap     Capability being checked.
	 * @param  int      $user_id The user ID.
	 * @param  array    $args    Adds context to the capability check, typically the object ID.
	 *
	 * @return string[] Array of required capabilities for the requested action.
	 */
	public static function filter_map_meta_cap( $caps, $cap, $user_id, $args ) {
		$meta_caps  = self::get_meta_caps();
		$entry_caps = self::get_meta_caps( 'entry' );

		if ( in_array( $cap, array_keys( $meta_caps ), true ) ) {
			$id = isset( $args[0] ) ? (int) $args[0] : 0;

			if ( in_array( $cap, array_keys( $entry_caps ), true ) ) {
				$entry = mhk_get_entry( $id, false, array( 'cap' => false ) );
				if ( ! $entry ) {
					return $caps;
				}

				$id = isset( $entry->form_id ) ? (int) $entry->form_id : 0;
			}

			$form = mhk()->form->get( $id, array( 'cap' => false ) );
			if ( ! $form ) {
				return $caps;
			}

			if ( ! is_a( $form, 'WP_Post' ) ) {
				return $caps;
			}

			if ( 'muhiku_form' !== $form->post_type ) {
				return $caps;
			}

			if ( $form->post_author && $user_id === (int) $form->post_author ) {
				$caps = isset( $meta_caps[ $cap ]['own'] ) ? array( $meta_caps[ $cap ]['own'] ) : array( 'do_not_allow' );
			} else {
				$caps = isset( $meta_caps[ $cap ]['others'] ) ? array( $meta_caps[ $cap ]['others'] ) : array( 'do_not_allow' );
			}
		}

		return $caps;
	}

	/**
	 * @param  array $actions Plugin Action links.
	 * @return array
	 */
	public static function plugin_action_links( $actions ) {
		$new_actions = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=mhk-settings' ) . '" aria-label="' . esc_attr__( 'View Muhiku Plug Settings', 'muhiku-plug' ) . '">' . esc_html__( 'Settings', 'muhiku-plug' ) . '</a>',
		);

		return array_merge( $new_actions, $actions );
	}

}

MHK_Install::init();

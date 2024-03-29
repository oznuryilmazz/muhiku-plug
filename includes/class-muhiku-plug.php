<?php

defined( 'ABSPATH' ) || exit;

final class MuhikuPlug {

	/**
	 * @var string
	 */
	public $version = '1.8.6';

	/**
	 * @var   MuhikuPlug
	 */
	protected static $instance = null;

	/**
	 * @var MHK_Session|MHK_Session_Handler
	 */
	public $session = null;

	/**
	 * @var MHK_Form_Handler
	 */
	public $form;

	/**
	 * @var MHK_Entry_Handler
	 */
	public $entry;

	/**
	 * @var MHK_Entry_Meta_Handler
	 */
	public $entry_meta;

	/**
	 * @var MHK_Integrations
	 */
	public $integrations = null;

	/**
	 * @var array of MHK_Deprecated_Hooks
	 */
	public $deprecated_hook_handlers = array();

	/**
	 * @static
	 * @see    MHK()
	 * @return MuhikuPlug - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __clone() {
		mhk_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'muhiku-plug' ), '1.0.0' );
	}

	public function __wakeup() {
		mhk_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'muhiku-plug' ), '1.0.0' );
	}

	/**
	 * @param mixed $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'form_fields' ), true ) ) {
			return $this->$key();
		}
	}

	public function __construct() {
		$this->define_constants();
		$this->define_tables();
		$this->includes();
		$this->init_hooks();
		add_action( 'plugins_loaded', array( $this, 'objects' ), 1 );

		do_action( 'muhiku_forms_loaded' );
	}

	private function init_hooks() {
		register_activation_hook( MHK_PLUGIN_FILE, array( 'MHK_Install', 'install' ) );
		register_shutdown_function( array( $this, 'log_errors' ) );
		add_action( 'after_setup_theme', array( $this, 'include_template_functions' ), 11 );
		add_action( 'init', array( $this, 'init' ), 0 );
		add_action( 'init', array( $this, 'form_fields' ), 0 );
		add_action( 'init', array( 'MHK_Shortcodes', 'init' ), 0 );
		add_action( 'switch_blog', array( $this, 'wpdb_table_fix' ), 0 );
	}

	public function log_errors() {
		$error = error_get_last();

		if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR ), true ) ) {
			$logger = mhk_get_logger();
			$logger->critical(
				$error['message'] . PHP_EOL,
				array(
					'source' => 'fatal-errors',
				)
			);
		}
	}

	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'MHK_ABSPATH', dirname( MHK_PLUGIN_FILE ) . '/' );
		$this->define( 'MHK_PLUGIN_BASENAME', plugin_basename( MHK_PLUGIN_FILE ) );
		$this->define( 'MHK_VERSION', $this->version );
		$this->define( 'MHK_LOG_DIR', $upload_dir['basedir'] . '/mhk-logs/' );
		$this->define( 'MHK_SESSION_CACHE_GROUP', 'mhk_session_id' );
		$this->define( 'MHK_TEMPLATE_DEBUG_MODE', false );
	}

	private function define_tables() {
		global $wpdb;

		$tables = array(
			'form_entrymeta' => 'mhk_entrymeta',
		);
		foreach ( $tables as $name => $table ) {
			$wpdb->$name    = $wpdb->prefix . $table;
			$wpdb->tables[] = $table;
		}
	}

	/**
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * @param  string $type admin, ajax, cron or frontend.
	 * @return bool
	 */
	private function is_request( $type ) {
		switch ( $type ) {
			case 'admin':
				return is_admin();
			case 'ajax':
				return defined( 'DOING_AJAX' );
			case 'cron':
				return defined( 'DOING_CRON' );
			case 'frontend':
				return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' ) && ! defined( 'REST_REQUEST' );
		}
	}

	public function includes() {

		include_once MHK_ABSPATH . 'includes/class-mhk-autoloader.php';

	
		include_once MHK_ABSPATH . 'includes/interfaces/class-mhk-logger-interface.php';
		include_once MHK_ABSPATH . 'includes/interfaces/class-mhk-log-handler-interface.php';


		include_once MHK_ABSPATH . 'includes/abstracts/class-mhk-settings-api.php';
		include_once MHK_ABSPATH . 'includes/abstracts/class-mhk-integration.php';
		include_once MHK_ABSPATH . 'includes/abstracts/class-mhk-log-handler.php';
		include_once MHK_ABSPATH . 'includes/abstracts/class-mhk-deprecated-hooks.php';
		include_once MHK_ABSPATH . 'includes/abstracts/class-mhk-session.php';
		include_once MHK_ABSPATH . 'includes/abstracts/class-mhk-form-fields.php';

	
		include_once MHK_ABSPATH . 'includes/mhk-core-functions.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-post-types.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-install.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-ajax.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-form-block.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-integrations.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-cache-helper.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-deprecated-action-hooks.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-deprecated-filter-hooks.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-forms-features.php';

		if ( class_exists( '\Elementor\Plugin' ) ) {
			include_once MHK_ABSPATH . 'includes/elementor/class-mhk-elementor.php';
		}

		if ( $this->is_request( 'admin' ) ) {
			include_once MHK_ABSPATH . 'includes/admin/class-mhk-admin.php';
		}

		if ( $this->is_request( 'frontend' ) ) {
			$this->frontend_includes();
		}
	}

	public function frontend_includes() {
		include_once MHK_ABSPATH . 'includes/mhk-notice-functions.php';
		include_once MHK_ABSPATH . 'includes/mhk-template-hooks.php';
		include_once MHK_ABSPATH . 'includes/class-mhk-template-loader.php';  // Template Loader.
		include_once MHK_ABSPATH . 'includes/class-mhk-frontend-scripts.php'; // Frontend Scripts.
		include_once MHK_ABSPATH . 'includes/class-mhk-shortcodes.php';       // Shortcodes class.
		include_once MHK_ABSPATH . 'includes/class-mhk-session-handler.php';  // Session handler class.
	}

	public function include_template_functions() {
		include_once MHK_ABSPATH . 'includes/mhk-template-functions.php';
	}

	public function init() {
		do_action( 'before_muhiku_forms_init' );

		$this->load_plugin_textdomain();

		$this->integrations                        = new MHK_Integrations();
		$this->deprecated_hook_handlers['actions'] = new MHK_Deprecated_Action_Hooks();
		$this->deprecated_hook_handlers['filters'] = new MHK_Deprecated_Filter_Hooks();

		if ( $this->is_request( 'frontend' ) ) {
			$session_class = apply_filters( 'muhiku_forms_session_handler', 'MHK_Session_Handler' );
			$this->session = new $session_class();
			$this->session->init();
		}

		do_action( 'muhiku_forms_init' );
	}

	public function objects() {
		$this->form       = new MHK_Form_Handler();
		$this->task       = new MHK_Form_Task();
		$this->smart_tags = new MHK_Smart_Tags();
	}

	public function load_plugin_textdomain() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
		} else {
			$locale = is_admin() ? get_user_locale() : get_locale();
		}

		$locale = apply_filters( 'plugin_locale', $locale, 'muhiku_forms' );

		unload_textdomain( 'muhiku-plug' );
		load_textdomain( 'muhiku-plug', WP_LANG_DIR . '/muhiku-plug/muhiku-plug-' . $locale . '.mo' );
		load_plugin_textdomain( 'muhiku-plug', false, plugin_basename( dirname( MHK_PLUGIN_FILE ) ) . '/languages' );
	}

	/**
	 * @param String $path Path.
	 *
	 * @return string
	 */
	public function plugin_url( $path = '/' ) {
		return untrailingslashit( plugins_url( $path, MHK_PLUGIN_FILE ) );
	}

	/**
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( MHK_PLUGIN_FILE ) );
	}

	/**
	 * @return string
	 */
	public function template_path() {
		return apply_filters( 'muhiku_forms_template_path', 'muhiku-plug/' );
	}

	/**
	 * @return string
	 */
	public function ajax_url() {
		return admin_url( 'admin-ajax.php', 'relative' );
	}

	public function wpdb_table_fix() {
		$this->define_tables();
	}

	/**
	 * @return MHK_Form_Fields
	 */
	public function form_fields() {
		return MHK_Fields::instance();
	}
}

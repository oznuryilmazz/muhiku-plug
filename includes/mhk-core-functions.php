<?php
/**
 * MuhikuPlug Core Functions
 *
 * General core functions available on both the front-end and admin.
 *
 * @package MuhikuPlug/Functions
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Include core functions (available in both admin and frontend).
require EVF_ABSPATH . 'includes/mhk-conditional-functions.php';
require EVF_ABSPATH . 'includes/mhk-deprecated-functions.php';
require EVF_ABSPATH . 'includes/mhk-formatting-functions.php';
require EVF_ABSPATH . 'includes/mhk-entry-functions.php';

/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.0
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function mhk_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * Get template part.
 *
 * EVF_TEMPLATE_DEBUG_MODE will prevent overrides in themes from taking priority.
 *
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function mhk_get_template_part( $slug, $name = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template-part', $slug, $name, EVF_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'muhiku-plug' );

	if ( ! $template ) {
		if ( $name ) {
			$template = EVF_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}-{$name}.php",
					mhk()->template_path() . "{$slug}-{$name}.php",
				)
			);

			if ( ! $template ) {
				$fallback = mhk()->plugin_path() . "/templates/{$slug}-{$name}.php";
				$template = file_exists( $fallback ) ? $fallback : '';
			}
		}

		if ( ! $template ) {
			// If template file doesn't exist, look in yourtheme/slug.php and yourtheme/muhiku-plug/slug.php.
			$template = EVF_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}.php",
					mhk()->template_path() . "{$slug}.php",
				)
			);
		}

		wp_cache_set( $cache_key, $template, 'muhiku-plug' );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'mhk_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Get other templates passing attributes and including the file.
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function mhk_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path, EVF_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'muhiku-plug' );

	if ( ! $template ) {
		$template = mhk_locate_template( $template_name, $template_path, $default_path );
		wp_cache_set( $cache_key, $template, 'muhiku-plug' );
	}

	// Allow 3rd party plugin filter template file from their plugin.
	$filter_template = apply_filters( 'mhk_get_template', $template, $template_name, $args, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
			/* translators: %s template */
			mhk_doing_it_wrong( __FUNCTION__, sprintf( __( '%s does not exist.', 'muhiku-plug' ), '<code>' . $filter_template . '</code>' ), '1.0.0' );
			return;
		}
		$template = $filter_template;
	}

	$action_args = array(
		'template_name' => $template_name,
		'template_path' => $template_path,
		'located'       => $template,
		'args'          => $args,
	);

	if ( ! empty( $args ) && is_array( $args ) ) {
		if ( isset( $args['action_args'] ) ) {
			mhk_doing_it_wrong(
				__FUNCTION__,
				__( 'action_args should not be overwritten when calling mhk_get_template.', 'muhiku-plug' ),
				'1.4.9'
			);
			unset( $args['action_args'] );
		}
		extract( $args ); // @codingStandardsIgnoreLine
	}

	do_action( 'everest_forms_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'everest_forms_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}

/**
 * Like mhk_get_template, but returns the HTML instead of outputting.
 *
 * @see    mhk_get_template
 * @since  1.0.0
 * @param  string $template_name Template name.
 * @param  array  $args          Arguments. (default: array).
 * @param  string $template_path Template path. (default: '').
 * @param  string $default_path  Default path. (default: '').
 * @return string
 */
function mhk_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	ob_start();
	mhk_get_template( $template_name, $args, $template_path, $default_path );
	return ob_get_clean();
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/$template_path/$template_name
 * yourtheme/$template_name
 * $default_path/$template_name
 *
 * @param  string $template_name Template name.
 * @param  string $template_path Template path. (default: '').
 * @param  string $default_path  Default path. (default: '').
 * @return string
 */
function mhk_locate_template( $template_name, $template_path = '', $default_path = '' ) {
	if ( ! $template_path ) {
		$template_path = mhk()->template_path();
	}

	if ( ! $default_path ) {
		$default_path = mhk()->plugin_path() . '/templates/';
	}

	// Look within passed path within the theme - this is priority.
	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	// Get default template/.
	if ( ! $template || EVF_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	// Return what we found.
	return apply_filters( 'everest_forms_locate_template', $template, $template_name, $template_path );
}

/**
 * Send HTML emails from MuhikuPlug.
 *
 * @param mixed  $to          Receiver.
 * @param mixed  $subject     Subject.
 * @param mixed  $message     Message.
 * @param string $headers     Headers. (default: "Content-Type: text/html\r\n").
 * @param string $attachments Attachments. (default: "").
 */
function mhk_mail( $to, $subject, $message, $headers = "Content-Type: text/html\r\n", $attachments = '' ) {
	$mailer = mhk()->mailer();

	$mailer->send( $to, $subject, $message, $headers, $attachments );
}

/**
 * Queue some JavaScript code to be output in the footer.
 *
 * @param string $code Code.
 */
function mhk_enqueue_js( $code ) {
	global $mhk_queued_js;

	if ( empty( $mhk_queued_js ) ) {
		$mhk_queued_js = '';
	}

	$mhk_queued_js .= "\n" . $code . "\n";
}

/**
 * Output any queued javascript code in the footer.
 */
function mhk_print_js() {
	global $mhk_queued_js;

	if ( ! empty( $mhk_queued_js ) ) {
		// Sanitize.
		$mhk_queued_js = wp_check_invalid_utf8( $mhk_queued_js );
		$mhk_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $mhk_queued_js );
		$mhk_queued_js = str_replace( "\r", '', $mhk_queued_js );

		$js = "<!-- Muhiku Plug JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $mhk_queued_js });\n</script>\n";

		/**
		 * Queued jsfilter.
		 *
		 * @since 1.0.0
		 * @param string $js JavaScript code.
		 */
		echo wp_kses( apply_filters( 'everest_forms_queued_js', $js ), array( 'script' => array( 'type' => true ) ) );
		unset( $mhk_queued_js );
	}
}

/**
 * Set a cookie - wrapper for setcookie using WP constants.
 *
 * @param  string  $name   Name of the cookie being set.
 * @param  string  $value  Value of the cookie.
 * @param  integer $expire Expiry of the cookie.
 * @param  bool    $secure Whether the cookie should be served only over https.
 * @param  bool    $httponly Whether the cookie is only accessible over HTTP, not scripting languages like JavaScript. @since 1.4.9.
 */
function mhk_setcookie( $name, $value, $expire = 0, $secure = false, $httponly = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters( 'everest_forms_cookie_httponly', $httponly, $name, $value, $expire, $secure ) );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE ); // @codingStandardsIgnoreLine
	}
}

/**
 * Get a log file path.
 *
 * @since 1.0.0
 *
 * @param  string $handle name.
 * @return string the log file path.
 */
function mhk_get_log_file_path( $handle ) {
	return EVF_Log_Handler_File::get_log_file_path( $handle );
}

/**
 * Get a csv file name.
 *
 * File names consist of the handle, followed by the date, followed by a hash, .csv.
 *
 * @since 1.3.0
 *
 * @param  string $handle Name.
 * @return bool|string The csv file name or false if cannot be determined.
 */
function mhk_get_csv_file_name( $handle ) {
	if ( function_exists( 'wp_hash' ) ) {
		$date_suffix = date_i18n( 'Y-m-d', time() );
		$hash_suffix = wp_hash( $handle );
		return sanitize_file_name( implode( '-', array( 'mhk-entry-export', $handle, $date_suffix, $hash_suffix ) ) . '.csv' );
	} else {
		mhk_doing_it_wrong( __METHOD__, __( 'This method should not be called before plugins_loaded.', 'muhiku-plug' ), '1.3.0' );
		return false;
	}
}

/**
 * Recursively get page children.
 *
 * @param  int $page_id Page ID.
 * @return int[]
 */
function mhk_get_page_children( $page_id ) {
	$page_ids = get_posts(
		array(
			'post_parent' => $page_id,
			'post_type'   => 'page',
			'numberposts' => - 1,
			'post_status' => 'any',
			'fields'      => 'ids',
		)
	);

	if ( ! empty( $page_ids ) ) {
		foreach ( $page_ids as $page_id ) {
			$page_ids = array_merge( $page_ids, mhk_get_page_children( $page_id ) );
		}
	}

	return $page_ids;
}

/**
 * Get user agent string.
 *
 * @since  1.0.0
 * @return string
 */
function mhk_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? mhk_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : ''; // @codingStandardsIgnoreLine
}

// This function can be removed when WP 3.9.2 or greater is required.
if ( ! function_exists( 'hash_equals' ) ) :
	/**
	 * Compare two strings in constant time.
	 *
	 * This function was added in PHP 5.6.
	 * It can leak the length of a string.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $a Expected string.
	 * @param  string $b Actual string.
	 * @return bool Whether strings are equal.
	 */
	function hash_equals( $a, $b ) {
		$a_length = strlen( $a );
		if ( strlen( $b ) !== $a_length ) {
			return false;
		}
		$result = 0;

		// Do not attempt to "optimize" this.
		for ( $i = 0; $i < $a_length; $i ++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		return 0 === $result;
	}
endif;

/**
 * Generate a rand hash.
 *
 * @since  1.0.0
 * @return string
 */
function mhk_rand_hash() {
	if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return sha1( wp_rand() );
	}

	return bin2hex( openssl_random_pseudo_bytes( 20 ) ); // @codingStandardsIgnoreLine
}

/**
 * Find all possible combinations of values from the input array and return in a logical order.
 *
 * @since  1.0.0
 * @param  array $input Input.
 * @return array
 */
function mhk_array_cartesian( $input ) {
	$input   = array_filter( $input );
	$results = array();
	$indexes = array();
	$index   = 0;

	// Generate indexes from keys and values so we have a logical sort order.
	foreach ( $input as $key => $values ) {
		foreach ( $values as $value ) {
			$indexes[ $key ][ $value ] = $index++;
		}
	}

	// Loop over the 2D array of indexes and generate all combinations.
	foreach ( $indexes as $key => $values ) {
		// When result is empty, fill with the values of the first looped array.
		if ( empty( $results ) ) {
			foreach ( $values as $value ) {
				$results[] = array( $key => $value );
			}
		} else {
			// Second and subsequent input sub-array merging.
			foreach ( $results as $result_key => $result ) {
				foreach ( $values as $value ) {
					// If the key is not set, we can set it.
					if ( ! isset( $results[ $result_key ][ $key ] ) ) {
						$results[ $result_key ][ $key ] = $value;
					} else {
						// If the key is set, we can add a new combination to the results array.
						$new_combination         = $results[ $result_key ];
						$new_combination[ $key ] = $value;
						$results[]               = $new_combination;
					}
				}
			}
		}
	}

	// Sort the indexes.
	arsort( $results );

	// Convert indexes back to values.
	foreach ( $results as $result_key => $result ) {
		$converted_values = array();

		// Sort the values.
		arsort( $results[ $result_key ] );

		// Convert the values.
		foreach ( $results[ $result_key ] as $key => $value ) {
			$converted_values[ $key ] = array_search( $value, $indexes[ $key ], true );
		}

		$results[ $result_key ] = $converted_values;
	}

	return $results;
}

/**
 * Run a MySQL transaction query, if supported.
 *
 * @since 1.0.0
 * @param string $type Types: start (default), commit, rollback.
 * @param bool   $force use of transactions.
 */
function mhk_transaction_query( $type = 'start', $force = false ) {
	global $wpdb;

	$wpdb->hide_errors();

	mhk_maybe_define_constant( 'EVF_USE_TRANSACTIONS', true );

	if ( EVF_USE_TRANSACTIONS || $force ) {
		switch ( $type ) {
			case 'commit':
				$wpdb->query( 'COMMIT' );
				break;
			case 'rollback':
				$wpdb->query( 'ROLLBACK' );
				break;
			default:
				$wpdb->query( 'START TRANSACTION' );
				break;
		}
	}
}

/**
 * Outputs a "back" link so admin screens can easily jump back a page.
 *
 * @param string $label Title of the page to return to.
 * @param string $url   URL of the page to return to.
 */
function mhk_back_link( $label, $url ) {
	echo '<small class="mhk-admin-breadcrumb"><a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '">&#x2934;</a></small>';
}

/**
 * Display a MuhikuPlug help tip.
 *
 * @since  1.0.0
 *
 * @param  string $tip        Help tip text.
 * @param  bool   $allow_html Allow sanitized HTML if true or escape.
 * @return string
 */
function mhk_help_tip( $tip, $allow_html = false ) {
	if ( $allow_html ) {
		$tip = mhk_sanitize_tooltip( $tip );
	} else {
		$tip = esc_attr( $tip );
	}

	return '<span class="muhiku-plug-help-tip" data-tip="' . $tip . '"></span>';
}

/**
 * Wrapper for set_time_limit to see if it is enabled.
 *
 * @since 1.0.0
 * @param int $limit Time limit.
 */
function mhk_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit ); // @codingStandardsIgnoreLine
	}
}

/**
 * Wrapper for nocache_headers which also disables page caching.
 *
 * @since 1.2.0
 */
function mhk_nocache_headers() {
	EVF_Cache_Helper::set_nocache_constants();
	nocache_headers();
}

/**
 * Get a shared logger instance.
 *
 * Use the everest_forms_logging_class filter to change the logging class. You may provide one of the following:
 *     - a class name which will be instantiated as `new $class` with no arguments
 *     - an instance which will be used directly as the logger
 * In either case, the class or instance *must* implement EVF_Logger_Interface.
 *
 * @see EVF_Logger_Interface
 *
 * @return EVF_Logger
 */
function mhk_get_logger() {
	static $logger = null;

	$class = apply_filters( 'everest_forms_logging_class', 'EVF_Logger' );

	if ( null !== $logger && is_string( $class ) && is_a( $logger, $class ) ) {
		return $logger;
	}

	$implements = class_implements( $class );

	if ( is_array( $implements ) && in_array( 'EVF_Logger_Interface', $implements, true ) ) {
		$logger = is_object( $class ) ? $class : new $class();
	} else {
		mhk_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				/* translators: 1: class name 2: everest_forms_logging_class 3: EVF_Logger_Interface */
				__( 'The class %1$s provided by %2$s filter must implement %3$s.', 'muhiku-plug' ),
				'<code>' . esc_html( is_object( $class ) ? get_class( $class ) : $class ) . '</code>',
				'<code>everest_forms_logging_class</code>',
				'<code>EVF_Logger_Interface</code>'
			),
			'1.2'
		);
		$logger = is_a( $logger, 'EVF_Logger' ) ? $logger : new EVF_Logger();
	}

	return $logger;
}

/**
 * Prints human-readable information about a variable.
 *
 * Some server environments blacklist some debugging functions. This function provides a safe way to
 * turn an expression into a printable, readable form without calling blacklisted functions.
 *
 * @since 1.0.0
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return     Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string|bool False if expression could not be printed. True if the expression was printed.
 *     If $return is true, a string representation will be returned.
 */
function mhk_print_r( $expression, $return = false ) {
	$alternatives = array(
		array(
			'func' => 'print_r',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'var_export',
			'args' => array( $expression, true ),
		),
		array(
			'func' => 'json_encode',
			'args' => array( $expression ),
		),
		array(
			'func' => 'serialize',
			'args' => array( $expression ),
		),
	);

	$alternatives = apply_filters( 'everest_forms_print_r_alternatives', $alternatives, $expression );

	foreach ( $alternatives as $alternative ) {
		if ( function_exists( $alternative['func'] ) ) {
			$res = call_user_func_array( $alternative['func'], $alternative['args'] );
			if ( $return ) {
				return $res;
			}

			echo wp_kses_post( $res );
			return true;
		}
	}

	return false;
}

/**
 * Registers the default log handler.
 *
 * @since  1.0.0
 * @param  array $handlers Handlers.
 * @return array
 */
function mhk_register_default_log_handler( $handlers ) {
	if ( defined( 'EVF_LOG_HANDLER' ) && class_exists( EVF_LOG_HANDLER ) ) {
		$handler_class   = EVF_LOG_HANDLER;
		$default_handler = new $handler_class();
	} else {
		$default_handler = new EVF_Log_Handler_File();
	}

	array_push( $handlers, $default_handler );

	return $handlers;
}

add_filter( 'everest_forms_register_log_handlers', 'mhk_register_default_log_handler' );

/**
 * Based on wp_list_pluck, this calls a method instead of returning a property.
 *
 * @since 1.0.0
 * @param array      $list              List of objects or arrays.
 * @param int|string $callback_or_field Callback method from the object to place instead of the entire object.
 * @param int|string $index_key         Optional. Field from the object to use as keys for the new array.
 *                                      Default null.
 * @return array Array of values.
 */
function mhk_list_pluck( $list, $callback_or_field, $index_key = null ) {
	// Use wp_list_pluck if this isn't a callback.
	$first_el = current( $list );
	if ( ! is_object( $first_el ) || ! is_callable( array( $first_el, $callback_or_field ) ) ) {
		return wp_list_pluck( $list, $callback_or_field, $index_key );
	}
	if ( ! $index_key ) {
		/*
		 * This is simple. Could at some point wrap array_column()
		 * if we knew we had an array of arrays.
		 */
		foreach ( $list as $key => $value ) {
			$list[ $key ] = $value->{$callback_or_field}();
		}
		return $list;
	}

	/*
	 * When index_key is not set for a particular item, push the value
	 * to the end of the stack. This is how array_column() behaves.
	 */
	$newlist = array();
	foreach ( $list as $value ) {
		// Get index.
		if ( is_callable( array( $value, $index_key ) ) ) {
			$newlist[ $value->{$index_key}() ] = $value->{$callback_or_field}();
		} elseif ( isset( $value->$index_key ) ) {
			$newlist[ $value->$index_key ] = $value->{$callback_or_field}();
		} else {
			$newlist[] = $value->{$callback_or_field}();
		}
	}
	return $newlist;
}

/**
 * Switch MuhikuPlug to site language.
 *
 * @since 1.0.0
 */
function mhk_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		// Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
		add_filter( 'plugin_locale', 'get_locale' );

		// Init EVF locale.
		mhk()->load_plugin_textdomain();
	}
}

/**
 * Switch MuhikuPlug language to original.
 *
 * @since 1.0.0
 */
function mhk_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		// Remove filter.
		remove_filter( 'plugin_locale', 'get_locale' );

		// Init EVF locale.
		mhk()->load_plugin_textdomain();
	}
}

/**
 * Get an item of post data if set, otherwise return a default value.
 *
 * @since  1.0.0
 * @param  string $key     Key.
 * @param  string $default Default.
 * @return mixed value sanitized by mhk_clean
 */
function mhk_get_post_data_by_key( $key, $default = '' ) {
	return mhk_clean( mhk_get_var( $_POST[ $key ], $default ) ); // @codingStandardsIgnoreLine
}

/**
 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
 *
 * @since  1.0.0
 * @param  mixed  $var     Variable.
 * @param  string $default Default value.
 * @return mixed
 */
function mhk_get_var( &$var, $default = null ) {
	return isset( $var ) ? $var : $default;
}

/**
 * Read in MuhikuPlug headers when reading plugin headers.
 *
 * @since  1.2.0
 * @param  array $headers Headers.
 * @return array
 */
function mhk_enable_mhk_plugin_headers( $headers ) {
	if ( ! class_exists( 'EVF_Plugin_Updates' ) ) {
		include_once dirname( __FILE__ ) . '/admin/plugin-updates/class-mhk-plugin-updates.php';
	}

	// EVF requires at least - allows developers to define which version of Muhiku Plug the plugin requires to run.
	$headers[] = EVF_Plugin_Updates::VERSION_REQUIRED_HEADER;

	// EVF tested up to - allows developers to define which version of Muhiku Plug they have tested up to.
	$headers[] = EVF_Plugin_Updates::VERSION_TESTED_HEADER;

	return $headers;
}
add_filter( 'extra_theme_headers', 'mhk_enable_mhk_plugin_headers' );
add_filter( 'extra_plugin_headers', 'mhk_enable_mhk_plugin_headers' );

/**
 * Delete expired transients.
 *
 * Deletes all expired transients. The multi-table delete syntax is used.
 * to delete the transient record from table a, and the corresponding.
 * transient_timeout record from table b.
 *
 * Based on code inside core's upgrade_network() function.
 *
 * @since  1.0.0
 * @return int Number of transients that were cleared.
 */
function mhk_delete_expired_transients() {
	global $wpdb;

	$sql  = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
		AND b.option_value < %d";
	$rows = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	$sql   = "DELETE a, b FROM $wpdb->options a, $wpdb->options b
		WHERE a.option_name LIKE %s
		AND a.option_name NOT LIKE %s
		AND b.option_name = CONCAT( '_site_transient_timeout_', SUBSTRING( a.option_name, 17 ) )
		AND b.option_value < %d";
	$rows2 = $wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_site_transient_' ) . '%', $wpdb->esc_like( '_site_transient_timeout_' ) . '%', time() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	return absint( $rows + $rows2 );
}
add_action( 'everest_forms_installed', 'mhk_delete_expired_transients' );

/**
 * Make a URL relative, if possible.
 *
 * @since  1.0.0
 * @param  string $url URL to make relative.
 * @return string
 */
function mhk_get_relative_url( $url ) {
	return mhk_is_external_resource( $url ) ? $url : str_replace( array( 'http://', 'https://' ), '//', $url );
}

/**
 * See if a resource is remote.
 *
 * @since  1.0.0
 * @param  string $url URL to check.
 * @return bool
 */
function mhk_is_external_resource( $url ) {
	$wp_base = str_replace( array( 'http://', 'https://' ), '//', get_home_url( null, '/', 'http' ) );
	return strstr( $url, '://' ) && strstr( $wp_base, $url );
}

/**
 * See if theme/s is activate or not.
 *
 * @since  1.0.0
 * @param  string|array $theme Theme name or array of theme names to check.
 * @return boolean
 */
function mhk_is_active_theme( $theme ) {
	return is_array( $theme ) ? in_array( get_template(), $theme, true ) : get_template() === $theme;
}

/**
 * Cleans up session data - cron callback.
 *
 * @since 1.0.0
 */
function mhk_cleanup_session_data() {
	$session_class = apply_filters( 'everest_forms_session_handler', 'EVF_Session_Handler' );
	$session       = new $session_class();

	if ( is_callable( array( $session, 'cleanup_sessions' ) ) ) {
		$session->cleanup_sessions();
	}
}
add_action( 'everest_forms_cleanup_sessions', 'mhk_cleanup_session_data' );

/**
 * Return the html selected attribute if stringified $value is found in array of stringified $options
 * or if stringified $value is the same as scalar stringified $options.
 *
 * @param string|int       $value   Value to find within options.
 * @param string|int|array $options Options to go through when looking for value.
 * @return string
 */
function mhk_selected( $value, $options ) {
	if ( is_array( $options ) ) {
		$options = array_map( 'strval', $options );
		return selected( in_array( (string) $value, $options, true ), true, false );
	}

	return selected( $value, $options, false );
}

/**
 * Retrieve actual fields from a form.
 *
 * Non-posting elements such as section divider, page break, and HTML are
 * automatically excluded. Optionally a white list can be provided.
 *
 * @since 1.0.0
 *
 * @param mixed $form Form data.
 * @param array $whitelist Whitelist args.
 *
 * @return mixed boolean or array
 */
function mhk_get_form_fields( $form = false, $whitelist = array() ) {
	// Accept form (post) object or form ID.
	if ( is_object( $form ) ) {
		$form = json_decode( $form->post_content );
	} elseif ( is_numeric( $form ) ) {
		$form = mhk()->form->get(
			$form,
			array(
				'content_only' => true,
			)
		);
	}

	if ( ! is_array( $form ) || empty( $form['form_fields'] ) ) {
		return false;
	}

	// White list of field types to allow.
	$allowed_form_fields = array(
		'first-name',
		'last-name',
		'text',
		'textarea',
		'select',
		'radio',
		'checkbox',
		'email',
		'address',
		'country',
		'url',
		'name',
		'hidden',
		'date',
		'phone',
		'number',
		'file-upload',
		'image-upload',
		'payment-single',
		'payment-multiple',
		'payment-checkbox',
		'payment-total',
	);
	$allowed_form_fields = apply_filters( 'everest_forms_allowed_form_fields', $allowed_form_fields );

	$whitelist = ! empty( $whitelist ) ? $whitelist : $allowed_form_fields;

	$form_fields = $form['form_fields'];

	foreach ( $form_fields as $id => $form_field ) {
		if ( ! in_array( $form_field['type'], $whitelist, true ) ) {
			unset( $form_fields[ $id ] );
		}
	}

	return $form_fields;
}

/**
 * Sanitize a string, that can be a multiline.
 * If WP core `sanitize_textarea_field()` exists (after 4.7.0) - use it.
 * Otherwise - split onto separate lines, sanitize each one, merge again.
 *
 * @since 1.4.1
 *
 * @param string $string Raw string to sanitize.
 *
 * @return string If empty var is passed, or not a string - return unmodified. Otherwise - sanitize.
 */
function mhk_sanitize_textarea_field( $string ) {
	if ( empty( $string ) || ! is_string( $string ) ) {
		return $string;
	}

	if ( function_exists( 'sanitize_textarea_field' ) ) {
		$string = sanitize_textarea_field( $string );
	} else {
		$string = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $string ) ) );
	}

	return $string;
}

/**
 * Formats, sanitizes, and returns/echos HTML element ID, classes, attributes,
 * and data attributes.
 *
 * @param string $id    Element ID.
 * @param array  $class Class args.
 * @param array  $datas Data args.
 * @param array  $atts  Attributes.
 * @param bool   $echo  True to echo else return.
 *
 * @return string
 */
function mhk_html_attributes( $id = '', $class = array(), $datas = array(), $atts = array(), $echo = false ) {
	$id    = trim( $id );
	$parts = array();

	if ( ! empty( $id ) ) {
		$id = sanitize_html_class( $id );
		if ( ! empty( $id ) ) {
			$parts[] = 'id="' . $id . '"';
		}
	}

	if ( ! empty( $class ) ) {
		$class = mhk_sanitize_classes( $class, true );
		if ( ! empty( $class ) ) {
			$parts[] = 'class="' . $class . '"';
		}
	}

	if ( ! empty( $datas ) ) {
		foreach ( $datas as $data => $val ) {
			$parts[] = 'data-' . sanitize_html_class( $data ) . '="' . esc_attr( $val ) . '"';
		}
	}

	if ( ! empty( $atts ) ) {
		foreach ( $atts as $att => $val ) {
			if ( '0' === $val || ! empty( $val ) ) {
				if ( $att[0] === '[' ) { //phpcs:ignore
					// Handle special case for bound attributes in AMP.
					$escaped_att = '[' . sanitize_html_class( trim( $att, '[]' ) ) . ']';
				} else {
					$escaped_att = sanitize_html_class( $att );
				}
				$parts[] = $escaped_att . '="' . esc_attr( $val ) . '"';
			}
		}
	}

	$output = implode( ' ', $parts );

	if ( $echo ) {
		echo esc_html( trim( $output ) );
	} else {
		return trim( $output );
	}
}

/**
 * Sanitize string of CSS classes.
 *
 * @param array|string $classes Class names.
 * @param bool         $convert True will convert strings to array and vice versa.
 *
 * @return string|array
 */
function mhk_sanitize_classes( $classes, $convert = false ) {
	$css   = array();
	$array = is_array( $classes );

	if ( ! empty( $classes ) ) {
		if ( ! $array ) {
			$classes = explode( ' ', trim( $classes ) );
		}
		foreach ( $classes as $class ) {
			$css[] = sanitize_html_class( $class );
		}
	}

	if ( $array ) {
		return $convert ? implode( ' ', $css ) : $css;
	} else {
		return $convert ? $css : implode( ' ', $css );
	}
}

/**
 * Performs json_decode and unslash.
 *
 * @since 1.0.0
 *
 * @param string $data Data to decode.
 *
 * @return array|bool
 */
function mhk_decode( $data ) {
	if ( ! $data || empty( $data ) ) {
		return false;
	}

	return json_decode( $data, true );
}

/**
 * Performs json_encode and wp_slash.
 *
 * @since 1.0.0
 *
 * @param mixed $data Data to encode.
 *
 * @return string
 */
function mhk_encode( $data = false ) {
	if ( empty( $data ) ) {
		return false;
	}

	return wp_slash( wp_json_encode( $data ) );
}

/**
 * Crypto rand secure.
 *
 * @param int $min Min value.
 * @param int $max Max value.
 *
 * @return mixed
 */
function mhk_crypto_rand_secure( $min, $max ) {
	$range = $max - $min;
	if ( $range < 1 ) {
		return $min;
	} // not so random...
	$log    = ceil( log( $range, 2 ) );
	$bytes  = (int) ( $log / 8 ) + 1; // Length in bytes.
	$bits   = (int) $log + 1; // Length in bits.
	$filter = (int) ( 1 << $bits ) - 1; // Set all lower bits to 1.
	do {
		$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
		$rnd = $rnd & $filter; // Discard irrelevant bits.
	} while ( $rnd > $range );

	return $min + $rnd;
}

/**
 * Generate random string.
 *
 * @param int $length Length of string.
 *
 * @return string
 */
function mhk_get_random_string( $length = 10 ) {
	$string         = '';
	$code_alphabet  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$code_alphabet .= 'abcdefghijklmnopqrstuvwxyz';
	$code_alphabet .= '0123456789';
	$max            = strlen( $code_alphabet );
	for ( $i = 0; $i < $length; $i ++ ) {
		$string .= $code_alphabet[ mhk_crypto_rand_secure( 0, $max - 1 ) ];
	}

	return $string;
}

/**
 * Get all forms.
 *
 * @param  bool $skip_disabled_entries True to skip disabled entries.
 * @return array of form data.
 */
function mhk_get_all_forms( $skip_disabled_entries = false ) {
	$forms    = array();
	$form_ids = wp_parse_id_list(
		mhk()->form->get_multiple(
			array(
				'fields'      => 'ids',
				'status'      => 'publish',
				'order'       => 'DESC',
				'numberposts' => -1, // @codingStandardsIgnoreLine
			)
		)
	);

	if ( ! empty( $form_ids ) ) {
		foreach ( $form_ids as $form_id ) {
			$form      = mhk()->form->get( $form_id );
			$entries   = mhk_get_entries_ids( $form_id );
			$form_data = ! empty( $form->post_content ) ? mhk_decode( $form->post_content ) : '';

			if ( ! $form || ( $skip_disabled_entries && count( $entries ) < 1 ) && ( isset( $form_data['settings']['disabled_entries'] ) && '1' === $form_data['settings']['disabled_entries'] ) ) {
				continue;
			}

			// Check permissions for forms with viewable.
			if ( current_user_can( 'everest_forms_view_form_entries', $form_id ) ) {
				$forms[ $form_id ] = $form->post_title;
			}
		}
	}

	return $forms;
}

/**
 * Get random meta-key for field option.
 *
 * @param  array $field Field data array.
 * @return string
 */
function mhk_get_meta_key_field_option( $field ) {
	$random_number = rand( pow( 10, 3 ), pow( 10, 4 ) - 1 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.rand_rand
	return strtolower( str_replace( array( ' ', '/_' ), array( '_', '' ), $field['label'] ) ) . '_' . $random_number;
}

/**
 * Get current user IP Address.
 *
 * @return string
 */
function mhk_get_ip_address() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { // WPCS: input var ok, CSRF ok.
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );  // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { // WPCS: input var ok, CSRF ok.
		// Proxy servers can send through this header like this: X-Forwarded-For: client1, proxy1, proxy2
		// Make sure we always only send through the first IP in the list which should always be the client IP.
		return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); // WPCS: input var ok, CSRF ok.
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { // @codingStandardsIgnoreLine
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); // @codingStandardsIgnoreLine
	}
	return '';
}

/**
 * Get User Agent browser and OS type
 *
 * @since  1.1.0
 * @return array
 */
function mhk_get_browser() {
	$u_agent  = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
	$bname    = 'Unknown';
	$platform = 'Unknown';
	$version  = '';

	// First get the platform.
	if ( preg_match( '/linux/i', $u_agent ) ) {
		$platform = 'Linux';
	} elseif ( preg_match( '/macintosh|mac os x/i', $u_agent ) ) {
		$platform = 'MAC OS';
	} elseif ( preg_match( '/windows|win32/i', $u_agent ) ) {
		$platform = 'Windows';
	}

	// Next get the name of the useragent yes seperately and for good reason.
	if ( preg_match( '/MSIE/i', $u_agent ) && ! preg_match( '/Opera/i', $u_agent ) ) {
		$bname = 'Internet Explorer';
		$ub    = 'MSIE';
	} elseif ( preg_match( '/Trident/i', $u_agent ) ) {
		// this condition is for IE11.
		$bname = 'Internet Explorer';
		$ub    = 'rv';
	} elseif ( preg_match( '/Firefox/i', $u_agent ) ) {
		$bname = 'Mozilla Firefox';
		$ub    = 'Firefox';
	} elseif ( preg_match( '/Chrome/i', $u_agent ) ) {
		$bname = 'Google Chrome';
		$ub    = 'Chrome';
	} elseif ( preg_match( '/Safari/i', $u_agent ) ) {
		$bname = 'Apple Safari';
		$ub    = 'Safari';
	} elseif ( preg_match( '/Opera/i', $u_agent ) ) {
		$bname = 'Opera';
		$ub    = 'Opera';
	} elseif ( preg_match( '/Netscape/i', $u_agent ) ) {
		$bname = 'Netscape';
		$ub    = 'Netscape';
	}

	// Finally get the correct version number.
	// Added "|:".
	$known   = array( 'Version', $ub, 'other' );
	$pattern = '#(?<browser>' . join( '|', $known ) . ')[/|: ]+(?<version>[0-9.|a-zA-Z.]*)#';
	if ( ! preg_match_all( $pattern, $u_agent, $matches ) ) { // @codingStandardsIgnoreLine
		// We have no matching number just continue.
	}

	// See how many we have.
	$i = count( $matches['browser'] );

	if ( 1 !== $i ) {
		// we will have two since we are not using 'other' argument yet.
		// see if version is before or after the name.
		if ( strripos( $u_agent, 'Version' ) < strripos( $u_agent, $ub ) ) {
			$version = $matches['version'][0];
		} else {
			$version = $matches['version'][1];
		}
	} else {
		$version = $matches['version'][0];
	}

	// Check if we have a number.
	if ( null === $version || '' === $version ) {
		$version = '';
	}

	return array(
		'userAgent' => $u_agent,
		'name'      => $bname,
		'version'   => $version,
		'platform'  => $platform,
		'pattern'   => $pattern,
	);
}

/**
 * Get the certain date of a specified day in a specified format.
 *
 * @since 1.1.0
 *
 * @param string $period Supported values: start, end.
 * @param string $timestamp Default is the current timestamp, if left empty.
 * @param string $format Default is a MySQL format.
 *
 * @return string
 */
function mhk_get_day_period_date( $period, $timestamp = '', $format = 'Y-m-d H:i:s' ) {
	$date = '';

	if ( empty( $timestamp ) ) {
		$timestamp = time();
	}

	switch ( $period ) {
		case 'start_of_day':
			$date = date( $format, strtotime( 'today', $timestamp ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			break;

		case 'end_of_day':
			$date = date( $format, strtotime( 'tomorrow', $timestamp ) - 1 ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
			break;

	}

	return $date;
}

/**
 * Get field label by meta key
 *
 * @param int    $form_id  Form ID.
 * @param string $meta_key Field's meta key.
 * @param array  $fields Entry Field Data.
 *
 * @return string|false True if field label exists in form.
 */
function mhk_get_form_data_by_meta_key( $form_id, $meta_key, $fields = array() ) {
	$get_post     = get_post( $form_id );
	$post_content = json_decode( $get_post->post_content, true );
	$form_fields  = isset( $post_content['form_fields'] ) ? $post_content['form_fields'] : array();

	if ( ! empty( $form_fields ) ) {
		foreach ( $form_fields as $field ) {
			if ( isset( $field['meta-key'] ) && $meta_key === $field['meta-key'] ) {
				return $field['label'];
			}
		}
	}

	if ( ! empty( $fields ) ) {
		foreach ( $fields as $field ) {
			if ( isset( $field->meta_key ) && $meta_key === $field->meta_key ) {
				return isset( $field->name ) ? $field->name : $field->value->name;
			}
		}
	}

	return false;
}

/**
 * Get field type by meta key
 *
 * @param int    $form_id  Form ID.
 * @param string $meta_key Field's meta key.
 *
 * @return string|false True if field type exists in form.
 */
function mhk_get_field_type_by_meta_key( $form_id, $meta_key ) {
	$get_post     = get_post( $form_id );
	$post_content = json_decode( $get_post->post_content, true );
	$form_fields  = isset( $post_content['form_fields'] ) ? $post_content['form_fields'] : array();

	if ( ! empty( $form_fields ) ) {
		foreach ( $form_fields as $field ) {
			if ( isset( $field['meta-key'] ) && $meta_key === $field['meta-key'] ) {
				return $field['type'];
			}
		}
	}

	return false;
}

/**
 * Get all the email fields of a Form.
 *
 * @param int $form_id  Form ID.
 */
function mhk_get_all_email_fields_by_form_id( $form_id ) {
	$user_emails = array();
	$form_obj    = mhk()->form->get( $form_id );
	$form_data   = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

	if ( ! empty( $form_data['form_fields'] ) ) {
		foreach ( $form_data['form_fields'] as $form_fields ) {
			if ( 'email' === $form_fields['type'] ) {
				$user_emails[ $form_fields['meta-key'] ] = $form_fields['label'];
			}
		}
	}

	return $user_emails;
}

/**
 * Get all the field's meta-key label pair.
 *
 * @param int $form_id  Form ID.
 * @return array
 */
function mhk_get_all_form_fields_by_form_id( $form_id ) {
	$data      = array();
	$form_obj  = mhk()->form->get( $form_id );
	$form_data = ! empty( $form_obj->post_content ) ? mhk_decode( $form_obj->post_content ) : '';

	if ( ! empty( $form_data['form_fields'] ) ) {
		foreach ( $form_data['form_fields'] as $form_fields ) {
			if ( isset( $form_fields['meta-key'], $form_fields['label'] ) ) {
				$data[ $form_fields['meta-key'] ] = $form_fields['label'];
			}
		}
	}

	return $data;
}

/**
 * Check if the string JSON.
 *
 * @param string $string String to check.
 * @return bool
 */
function mhk_isJson( $string ) {
	json_decode( $string );
	return ( json_last_error() == JSON_ERROR_NONE ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
}

/**
 * Checks whether the content passed contains a specific short code.
 *
 * @since  1.1.4
 * @param  string $tag Shortcode tag to check.
 * @return bool
 */
function mhk_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}

/**
 * Convert a file size provided, such as "2M", to bytes.
 *
 * @since 1.2.0
 * @link http://stackoverflow.com/a/22500394
 *
 * @param string $size Size to convert to bytes.
 *
 * @return int
 */
function mhk_size_to_bytes( $size ) {
	if ( is_numeric( $size ) ) {
		return $size;
	}

	$suffix = substr( $size, - 1 );
	$value  = substr( $size, 0, - 1 );

	// @codingStandardsIgnoreStart
	switch ( strtoupper( $suffix ) ) {
		case 'P':
			$value *= 1024;
		case 'T':
			$value *= 1024;
		case 'G':
			$value *= 1024;
		case 'M':
			$value *= 1024;
		case 'K':
			$value *= 1024;
			break;
	}
	// @codingStandardsIgnoreEnd

	return $value;
}

/**
 * Convert bytes to megabytes (or in some cases KB).
 *
 * @since 1.2.0
 *
 * @param int $bytes Bytes to convert to a readable format.
 *
 * @return string
 */
function mhk_size_to_megabytes( $bytes ) {
	if ( $bytes < 1048676 ) {
		return number_format( $bytes / 1024, 1 ) . ' KB';
	} else {
		return round( (float) number_format( $bytes / 1048576, 1 ) ) . ' MB';
	}
}

/**
 * Convert a file size provided, such as "2M", to bytes.
 *
 * @since 1.2.0
 * @link http://stackoverflow.com/a/22500394
 *
 * @param  bool $bytes Whether to convert Bytes to a readable format.
 * @return mixed
 */
function mhk_max_upload( $bytes = false ) {
	$max = wp_max_upload_size();

	if ( $bytes ) {
		return $max;
	} else {
		return mhk_size_to_megabytes( $max );
	}
}

/**
 * Get the required label text, with a filter.
 *
 * @since  1.2.0
 * @return string
 */
function mhk_get_required_label() {
	return apply_filters( 'everest_forms_required_label', esc_html__( 'This field is required.', 'muhiku-plug' ) );
}

/**
 * Get a PRO license plan.
 *
 * @since  1.2.0
 * @return bool|string Plan on success, false on failure.
 */
function mhk_get_license_plan() {
	$license_key = get_option( 'muhiku-plug-pro_license_key' );

	if ( ! function_exists( 'is_plugin_active' ) ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( $license_key && is_plugin_active( 'muhiku-plug-pro/muhiku-plug-pro.php' ) ) {
		$license_data = get_transient( 'mhk_pro_license_plan' );

		if ( false === $license_data ) {
			$license_data = json_decode(
				EVF_Updater_Key_API::check(
					array(
						'license' => $license_key,
					)
				)
			);

			if ( ! empty( $license_data->item_plan ) ) {
				set_transient( 'mhk_pro_license_plan', $license_data, WEEK_IN_SECONDS );
			}
		}

		return isset( $license_data->item_plan ) ? $license_data->item_plan : false;
	}

	return false;
}

/**
 * Decode special characters, both alpha- (<) and numeric-based (').
 *
 * @since 1.2.0
 *
 * @param string $string Raw string to decode.
 *
 * @return string
 */
function mhk_decode_string( $string ) {
	if ( ! is_string( $string ) ) {
		return $string;
	}

	return wp_kses_decode_entities( html_entity_decode( $string, ENT_QUOTES ) );
}
add_filter( 'everest_forms_email_message', 'mhk_decode_string' );

/**
 * Get Countries.
 *
 * @since  1.2.0
 * @return array
 */
function mhk_get_countries() {
	$countries = array(
		'AF' => esc_html__( 'Afghanistan', 'muhiku-plug' ),
		'AX' => esc_html__( 'Åland Islands', 'muhiku-plug' ),
		'AL' => esc_html__( 'Albania', 'muhiku-plug' ),
		'DZ' => esc_html__( 'Algeria', 'muhiku-plug' ),
		'AS' => esc_html__( 'American Samoa', 'muhiku-plug' ),
		'AD' => esc_html__( 'Andorra', 'muhiku-plug' ),
		'AO' => esc_html__( 'Angola', 'muhiku-plug' ),
		'AI' => esc_html__( 'Anguilla', 'muhiku-plug' ),
		'AQ' => esc_html__( 'Antarctica', 'muhiku-plug' ),
		'AG' => esc_html__( 'Antigua and Barbuda', 'muhiku-plug' ),
		'AR' => esc_html__( 'Argentina', 'muhiku-plug' ),
		'AM' => esc_html__( 'Armenia', 'muhiku-plug' ),
		'AW' => esc_html__( 'Aruba', 'muhiku-plug' ),
		'AU' => esc_html__( 'Australia', 'muhiku-plug' ),
		'AT' => esc_html__( 'Austria', 'muhiku-plug' ),
		'AZ' => esc_html__( 'Azerbaijan', 'muhiku-plug' ),
		'BS' => esc_html__( 'Bahamas', 'muhiku-plug' ),
		'BH' => esc_html__( 'Bahrain', 'muhiku-plug' ),
		'BD' => esc_html__( 'Bangladesh', 'muhiku-plug' ),
		'BB' => esc_html__( 'Barbados', 'muhiku-plug' ),
		'BY' => esc_html__( 'Belarus', 'muhiku-plug' ),
		'BE' => esc_html__( 'Belgium', 'muhiku-plug' ),
		'PW' => esc_html__( 'Belau', 'muhiku-plug' ),
		'BZ' => esc_html__( 'Belize', 'muhiku-plug' ),
		'BJ' => esc_html__( 'Benin', 'muhiku-plug' ),
		'BM' => esc_html__( 'Bermuda', 'muhiku-plug' ),
		'BT' => esc_html__( 'Bhutan', 'muhiku-plug' ),
		'BO' => esc_html__( 'Bolivia', 'muhiku-plug' ),
		'BQ' => esc_html__( 'Bonaire, Saint Eustatius and Saba', 'muhiku-plug' ),
		'BA' => esc_html__( 'Bosnia and Herzegovina', 'muhiku-plug' ),
		'BW' => esc_html__( 'Botswana', 'muhiku-plug' ),
		'BV' => esc_html__( 'Bouvet Island', 'muhiku-plug' ),
		'BR' => esc_html__( 'Brazil', 'muhiku-plug' ),
		'IO' => esc_html__( 'British Indian Ocean Territory', 'muhiku-plug' ),
		'BN' => esc_html__( 'Brunei', 'muhiku-plug' ),
		'BG' => esc_html__( 'Bulgaria', 'muhiku-plug' ),
		'BF' => esc_html__( 'Burkina Faso', 'muhiku-plug' ),
		'BI' => esc_html__( 'Burundi', 'muhiku-plug' ),
		'KH' => esc_html__( 'Cambodia', 'muhiku-plug' ),
		'CM' => esc_html__( 'Cameroon', 'muhiku-plug' ),
		'CA' => esc_html__( 'Canada', 'muhiku-plug' ),
		'CV' => esc_html__( 'Cape Verde', 'muhiku-plug' ),
		'KY' => esc_html__( 'Cayman Islands', 'muhiku-plug' ),
		'CF' => esc_html__( 'Central African Republic', 'muhiku-plug' ),
		'TD' => esc_html__( 'Chad', 'muhiku-plug' ),
		'CL' => esc_html__( 'Chile', 'muhiku-plug' ),
		'CN' => esc_html__( 'China', 'muhiku-plug' ),
		'CX' => esc_html__( 'Christmas Island', 'muhiku-plug' ),
		'CC' => esc_html__( 'Cocos (Keeling) Islands', 'muhiku-plug' ),
		'CO' => esc_html__( 'Colombia', 'muhiku-plug' ),
		'KM' => esc_html__( 'Comoros', 'muhiku-plug' ),
		'CG' => esc_html__( 'Congo (Brazzaville)', 'muhiku-plug' ),
		'CD' => esc_html__( 'Congo (Kinshasa)', 'muhiku-plug' ),
		'CK' => esc_html__( 'Cook Islands', 'muhiku-plug' ),
		'CR' => esc_html__( 'Costa Rica', 'muhiku-plug' ),
		'HR' => esc_html__( 'Croatia', 'muhiku-plug' ),
		'CU' => esc_html__( 'Cuba', 'muhiku-plug' ),
		'CW' => esc_html__( 'Cura&ccedil;ao', 'muhiku-plug' ),
		'CY' => esc_html__( 'Cyprus', 'muhiku-plug' ),
		'CZ' => esc_html__( 'Czech Republic', 'muhiku-plug' ),
		'DK' => esc_html__( 'Denmark', 'muhiku-plug' ),
		'DJ' => esc_html__( 'Djibouti', 'muhiku-plug' ),
		'DM' => esc_html__( 'Dominica', 'muhiku-plug' ),
		'DO' => esc_html__( 'Dominican Republic', 'muhiku-plug' ),
		'EC' => esc_html__( 'Ecuador', 'muhiku-plug' ),
		'EG' => esc_html__( 'Egypt', 'muhiku-plug' ),
		'SV' => esc_html__( 'El Salvador', 'muhiku-plug' ),
		'GQ' => esc_html__( 'Equatorial Guinea', 'muhiku-plug' ),
		'ER' => esc_html__( 'Eritrea', 'muhiku-plug' ),
		'EE' => esc_html__( 'Estonia', 'muhiku-plug' ),
		'ET' => esc_html__( 'Ethiopia', 'muhiku-plug' ),
		'FK' => esc_html__( 'Falkland Islands', 'muhiku-plug' ),
		'FO' => esc_html__( 'Faroe Islands', 'muhiku-plug' ),
		'FJ' => esc_html__( 'Fiji', 'muhiku-plug' ),
		'FI' => esc_html__( 'Finland', 'muhiku-plug' ),
		'FR' => esc_html__( 'France', 'muhiku-plug' ),
		'GF' => esc_html__( 'French Guiana', 'muhiku-plug' ),
		'PF' => esc_html__( 'French Polynesia', 'muhiku-plug' ),
		'TF' => esc_html__( 'French Southern Territories', 'muhiku-plug' ),
		'GA' => esc_html__( 'Gabon', 'muhiku-plug' ),
		'GM' => esc_html__( 'Gambia', 'muhiku-plug' ),
		'GE' => esc_html__( 'Georgia', 'muhiku-plug' ),
		'DE' => esc_html__( 'Germany', 'muhiku-plug' ),
		'GH' => esc_html__( 'Ghana', 'muhiku-plug' ),
		'GI' => esc_html__( 'Gibraltar', 'muhiku-plug' ),
		'GR' => esc_html__( 'Greece', 'muhiku-plug' ),
		'GL' => esc_html__( 'Greenland', 'muhiku-plug' ),
		'GD' => esc_html__( 'Grenada', 'muhiku-plug' ),
		'GP' => esc_html__( 'Guadeloupe', 'muhiku-plug' ),
		'GU' => esc_html__( 'Guam', 'muhiku-plug' ),
		'GT' => esc_html__( 'Guatemala', 'muhiku-plug' ),
		'GG' => esc_html__( 'Guernsey', 'muhiku-plug' ),
		'GN' => esc_html__( 'Guinea', 'muhiku-plug' ),
		'GW' => esc_html__( 'Guinea-Bissau', 'muhiku-plug' ),
		'GY' => esc_html__( 'Guyana', 'muhiku-plug' ),
		'HT' => esc_html__( 'Haiti', 'muhiku-plug' ),
		'HM' => esc_html__( 'Heard Island and McDonald Islands', 'muhiku-plug' ),
		'HN' => esc_html__( 'Honduras', 'muhiku-plug' ),
		'HK' => esc_html__( 'Hong Kong', 'muhiku-plug' ),
		'HU' => esc_html__( 'Hungary', 'muhiku-plug' ),
		'IS' => esc_html__( 'Iceland', 'muhiku-plug' ),
		'IN' => esc_html__( 'India', 'muhiku-plug' ),
		'ID' => esc_html__( 'Indonesia', 'muhiku-plug' ),
		'IR' => esc_html__( 'Iran', 'muhiku-plug' ),
		'IQ' => esc_html__( 'Iraq', 'muhiku-plug' ),
		'IE' => esc_html__( 'Ireland', 'muhiku-plug' ),
		'IM' => esc_html__( 'Isle of Man', 'muhiku-plug' ),
		'IL' => esc_html__( 'Israel', 'muhiku-plug' ),
		'IT' => esc_html__( 'Italy', 'muhiku-plug' ),
		'CI' => esc_html__( 'Ivory Coast', 'muhiku-plug' ),
		'JM' => esc_html__( 'Jamaica', 'muhiku-plug' ),
		'JP' => esc_html__( 'Japan', 'muhiku-plug' ),
		'JE' => esc_html__( 'Jersey', 'muhiku-plug' ),
		'JO' => esc_html__( 'Jordan', 'muhiku-plug' ),
		'KZ' => esc_html__( 'Kazakhstan', 'muhiku-plug' ),
		'KE' => esc_html__( 'Kenya', 'muhiku-plug' ),
		'KI' => esc_html__( 'Kiribati', 'muhiku-plug' ),
		'KW' => esc_html__( 'Kuwait', 'muhiku-plug' ),
		'XK' => esc_html__( 'Kosovo', 'muhiku-plug' ),
		'KG' => esc_html__( 'Kyrgyzstan', 'muhiku-plug' ),
		'LA' => esc_html__( 'Laos', 'muhiku-plug' ),
		'LV' => esc_html__( 'Latvia', 'muhiku-plug' ),
		'LB' => esc_html__( 'Lebanon', 'muhiku-plug' ),
		'LS' => esc_html__( 'Lesotho', 'muhiku-plug' ),
		'LR' => esc_html__( 'Liberia', 'muhiku-plug' ),
		'LY' => esc_html__( 'Libya', 'muhiku-plug' ),
		'LI' => esc_html__( 'Liechtenstein', 'muhiku-plug' ),
		'LT' => esc_html__( 'Lithuania', 'muhiku-plug' ),
		'LU' => esc_html__( 'Luxembourg', 'muhiku-plug' ),
		'MO' => esc_html__( 'Macao', 'muhiku-plug' ),
		'MK' => esc_html__( 'North Macedonia', 'muhiku-plug' ),
		'MG' => esc_html__( 'Madagascar', 'muhiku-plug' ),
		'MW' => esc_html__( 'Malawi', 'muhiku-plug' ),
		'MY' => esc_html__( 'Malaysia', 'muhiku-plug' ),
		'MV' => esc_html__( 'Maldives', 'muhiku-plug' ),
		'ML' => esc_html__( 'Mali', 'muhiku-plug' ),
		'MT' => esc_html__( 'Malta', 'muhiku-plug' ),
		'MH' => esc_html__( 'Marshall Islands', 'muhiku-plug' ),
		'MQ' => esc_html__( 'Martinique', 'muhiku-plug' ),
		'MR' => esc_html__( 'Mauritania', 'muhiku-plug' ),
		'MU' => esc_html__( 'Mauritius', 'muhiku-plug' ),
		'YT' => esc_html__( 'Mayotte', 'muhiku-plug' ),
		'MX' => esc_html__( 'Mexico', 'muhiku-plug' ),
		'FM' => esc_html__( 'Micronesia', 'muhiku-plug' ),
		'MD' => esc_html__( 'Moldova', 'muhiku-plug' ),
		'MC' => esc_html__( 'Monaco', 'muhiku-plug' ),
		'MN' => esc_html__( 'Mongolia', 'muhiku-plug' ),
		'ME' => esc_html__( 'Montenegro', 'muhiku-plug' ),
		'MS' => esc_html__( 'Montserrat', 'muhiku-plug' ),
		'MA' => esc_html__( 'Morocco', 'muhiku-plug' ),
		'MZ' => esc_html__( 'Mozambique', 'muhiku-plug' ),
		'MM' => esc_html__( 'Myanmar', 'muhiku-plug' ),
		'NA' => esc_html__( 'Namibia', 'muhiku-plug' ),
		'NR' => esc_html__( 'Nauru', 'muhiku-plug' ),
		'NP' => esc_html__( 'Nepal', 'muhiku-plug' ),
		'NL' => esc_html__( 'Netherlands', 'muhiku-plug' ),
		'NC' => esc_html__( 'New Caledonia', 'muhiku-plug' ),
		'NZ' => esc_html__( 'New Zealand', 'muhiku-plug' ),
		'NI' => esc_html__( 'Nicaragua', 'muhiku-plug' ),
		'NE' => esc_html__( 'Niger', 'muhiku-plug' ),
		'NG' => esc_html__( 'Nigeria', 'muhiku-plug' ),
		'NU' => esc_html__( 'Niue', 'muhiku-plug' ),
		'NF' => esc_html__( 'Norfolk Island', 'muhiku-plug' ),
		'MP' => esc_html__( 'Northern Mariana Islands', 'muhiku-plug' ),
		'KP' => esc_html__( 'North Korea', 'muhiku-plug' ),
		'NO' => esc_html__( 'Norway', 'muhiku-plug' ),
		'OM' => esc_html__( 'Oman', 'muhiku-plug' ),
		'PK' => esc_html__( 'Pakistan', 'muhiku-plug' ),
		'PS' => esc_html__( 'Palestinian Territory', 'muhiku-plug' ),
		'PA' => esc_html__( 'Panama', 'muhiku-plug' ),
		'PG' => esc_html__( 'Papua New Guinea', 'muhiku-plug' ),
		'PY' => esc_html__( 'Paraguay', 'muhiku-plug' ),
		'PE' => esc_html__( 'Peru', 'muhiku-plug' ),
		'PH' => esc_html__( 'Philippines', 'muhiku-plug' ),
		'PN' => esc_html__( 'Pitcairn', 'muhiku-plug' ),
		'PL' => esc_html__( 'Poland', 'muhiku-plug' ),
		'PT' => esc_html__( 'Portugal', 'muhiku-plug' ),
		'PR' => esc_html__( 'Puerto Rico', 'muhiku-plug' ),
		'QA' => esc_html__( 'Qatar', 'muhiku-plug' ),
		'RE' => esc_html__( 'Reunion', 'muhiku-plug' ),
		'RO' => esc_html__( 'Romania', 'muhiku-plug' ),
		'RU' => esc_html__( 'Russia', 'muhiku-plug' ),
		'RW' => esc_html__( 'Rwanda', 'muhiku-plug' ),
		'BL' => esc_html__( 'Saint Barth&eacute;lemy', 'muhiku-plug' ),
		'SH' => esc_html__( 'Saint Helena', 'muhiku-plug' ),
		'KN' => esc_html__( 'Saint Kitts and Nevis', 'muhiku-plug' ),
		'LC' => esc_html__( 'Saint Lucia', 'muhiku-plug' ),
		'MF' => esc_html__( 'Saint Martin (French part)', 'muhiku-plug' ),
		'SX' => esc_html__( 'Saint Martin (Dutch part)', 'muhiku-plug' ),
		'PM' => esc_html__( 'Saint Pierre and Miquelon', 'muhiku-plug' ),
		'VC' => esc_html__( 'Saint Vincent and the Grenadines', 'muhiku-plug' ),
		'SM' => esc_html__( 'San Marino', 'muhiku-plug' ),
		'ST' => esc_html__( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'muhiku-plug' ),
		'SA' => esc_html__( 'Saudi Arabia', 'muhiku-plug' ),
		'SN' => esc_html__( 'Senegal', 'muhiku-plug' ),
		'RS' => esc_html__( 'Serbia', 'muhiku-plug' ),
		'SC' => esc_html__( 'Seychelles', 'muhiku-plug' ),
		'SL' => esc_html__( 'Sierra Leone', 'muhiku-plug' ),
		'SG' => esc_html__( 'Singapore', 'muhiku-plug' ),
		'SK' => esc_html__( 'Slovakia', 'muhiku-plug' ),
		'SI' => esc_html__( 'Slovenia', 'muhiku-plug' ),
		'SB' => esc_html__( 'Solomon Islands', 'muhiku-plug' ),
		'SO' => esc_html__( 'Somalia', 'muhiku-plug' ),
		'ZA' => esc_html__( 'South Africa', 'muhiku-plug' ),
		'GS' => esc_html__( 'South Georgia/Sandwich Islands', 'muhiku-plug' ),
		'KR' => esc_html__( 'South Korea', 'muhiku-plug' ),
		'SS' => esc_html__( 'South Sudan', 'muhiku-plug' ),
		'ES' => esc_html__( 'Spain', 'muhiku-plug' ),
		'LK' => esc_html__( 'Sri Lanka', 'muhiku-plug' ),
		'SD' => esc_html__( 'Sudan', 'muhiku-plug' ),
		'SR' => esc_html__( 'Suriname', 'muhiku-plug' ),
		'SJ' => esc_html__( 'Svalbard and Jan Mayen', 'muhiku-plug' ),
		'SZ' => esc_html__( 'Swaziland', 'muhiku-plug' ),
		'SE' => esc_html__( 'Sweden', 'muhiku-plug' ),
		'CH' => esc_html__( 'Switzerland', 'muhiku-plug' ),
		'SY' => esc_html__( 'Syria', 'muhiku-plug' ),
		'TW' => esc_html__( 'Taiwan', 'muhiku-plug' ),
		'TJ' => esc_html__( 'Tajikistan', 'muhiku-plug' ),
		'TZ' => esc_html__( 'Tanzania', 'muhiku-plug' ),
		'TH' => esc_html__( 'Thailand', 'muhiku-plug' ),
		'TL' => esc_html__( 'Timor-Leste', 'muhiku-plug' ),
		'TG' => esc_html__( 'Togo', 'muhiku-plug' ),
		'TK' => esc_html__( 'Tokelau', 'muhiku-plug' ),
		'TO' => esc_html__( 'Tonga', 'muhiku-plug' ),
		'TT' => esc_html__( 'Trinidad and Tobago', 'muhiku-plug' ),
		'TN' => esc_html__( 'Tunisia', 'muhiku-plug' ),
		'TR' => esc_html__( 'Turkey', 'muhiku-plug' ),
		'TM' => esc_html__( 'Turkmenistan', 'muhiku-plug' ),
		'TC' => esc_html__( 'Turks and Caicos Islands', 'muhiku-plug' ),
		'TV' => esc_html__( 'Tuvalu', 'muhiku-plug' ),
		'UG' => esc_html__( 'Uganda', 'muhiku-plug' ),
		'UA' => esc_html__( 'Ukraine', 'muhiku-plug' ),
		'AE' => esc_html__( 'United Arab Emirates', 'muhiku-plug' ),
		'GB' => esc_html__( 'United Kingdom (UK)', 'muhiku-plug' ),
		'US' => esc_html__( 'United States (US)', 'muhiku-plug' ),
		'UM' => esc_html__( 'United States (US) Minor Outlying Islands', 'muhiku-plug' ),
		'UY' => esc_html__( 'Uruguay', 'muhiku-plug' ),
		'UZ' => esc_html__( 'Uzbekistan', 'muhiku-plug' ),
		'VU' => esc_html__( 'Vanuatu', 'muhiku-plug' ),
		'VA' => esc_html__( 'Vatican', 'muhiku-plug' ),
		'VE' => esc_html__( 'Venezuela', 'muhiku-plug' ),
		'VN' => esc_html__( 'Vietnam', 'muhiku-plug' ),
		'VG' => esc_html__( 'Virgin Islands (British)', 'muhiku-plug' ),
		'VI' => esc_html__( 'Virgin Islands (US)', 'muhiku-plug' ),
		'WF' => esc_html__( 'Wallis and Futuna', 'muhiku-plug' ),
		'EH' => esc_html__( 'Western Sahara', 'muhiku-plug' ),
		'WS' => esc_html__( 'Samoa', 'muhiku-plug' ),
		'YE' => esc_html__( 'Yemen', 'muhiku-plug' ),
		'ZM' => esc_html__( 'Zambia', 'muhiku-plug' ),
		'ZW' => esc_html__( 'Zimbabwe', 'muhiku-plug' ),
	);

	return (array) apply_filters( 'everest_forms_countries', $countries );
}

/**
 * Get U.S. States.
 *
 * @since  1.7.0
 * @return array
 */
function mhk_get_states() {
	$states = array(
		'AL' => esc_html__( 'Alabama', 'muhiku-plug' ),
		'AK' => esc_html__( 'Alaska', 'muhiku-plug' ),
		'AZ' => esc_html__( 'Arizona', 'muhiku-plug' ),
		'AR' => esc_html__( 'Arkansas', 'muhiku-plug' ),
		'CA' => esc_html__( 'California', 'muhiku-plug' ),
		'CO' => esc_html__( 'Colorado', 'muhiku-plug' ),
		'CT' => esc_html__( 'Connecticut', 'muhiku-plug' ),
		'DE' => esc_html__( 'Delaware', 'muhiku-plug' ),
		'DC' => esc_html__( 'District of Columbia', 'muhiku-plug' ),
		'FL' => esc_html__( 'Florida', 'muhiku-plug' ),
		'GA' => esc_html__( 'Georgia', 'muhiku-plug' ),
		'HI' => esc_html__( 'Hawaii', 'muhiku-plug' ),
		'ID' => esc_html__( 'Idaho', 'muhiku-plug' ),
		'IL' => esc_html__( 'Illinois', 'muhiku-plug' ),
		'IN' => esc_html__( 'Indiana', 'muhiku-plug' ),
		'IA' => esc_html__( 'Iowa', 'muhiku-plug' ),
		'KS' => esc_html__( 'Kansas', 'muhiku-plug' ),
		'KY' => esc_html__( 'Kentucky', 'muhiku-plug' ),
		'LA' => esc_html__( 'Louisiana', 'muhiku-plug' ),
		'ME' => esc_html__( 'Maine', 'muhiku-plug' ),
		'MD' => esc_html__( 'Maryland', 'muhiku-plug' ),
		'MA' => esc_html__( 'Massachusetts', 'muhiku-plug' ),
		'MI' => esc_html__( 'Michigan', 'muhiku-plug' ),
		'MN' => esc_html__( 'Minnesota', 'muhiku-plug' ),
		'MS' => esc_html__( 'Mississippi', 'muhiku-plug' ),
		'MO' => esc_html__( 'Missouri', 'muhiku-plug' ),
		'MT' => esc_html__( 'Montana', 'muhiku-plug' ),
		'NE' => esc_html__( 'Nebraska', 'muhiku-plug' ),
		'NV' => esc_html__( 'Nevada', 'muhiku-plug' ),
		'NH' => esc_html__( 'New Hampshire', 'muhiku-plug' ),
		'NJ' => esc_html__( 'New Jersey', 'muhiku-plug' ),
		'NM' => esc_html__( 'New Mexico', 'muhiku-plug' ),
		'NY' => esc_html__( 'New York', 'muhiku-plug' ),
		'NC' => esc_html__( 'North Carolina', 'muhiku-plug' ),
		'ND' => esc_html__( 'North Dakota', 'muhiku-plug' ),
		'OH' => esc_html__( 'Ohio', 'muhiku-plug' ),
		'OK' => esc_html__( 'Oklahoma', 'muhiku-plug' ),
		'OR' => esc_html__( 'Oregon', 'muhiku-plug' ),
		'PA' => esc_html__( 'Pennsylvania', 'muhiku-plug' ),
		'RI' => esc_html__( 'Rhode Island', 'muhiku-plug' ),
		'SC' => esc_html__( 'South Carolina', 'muhiku-plug' ),
		'SD' => esc_html__( 'South Dakota', 'muhiku-plug' ),
		'TN' => esc_html__( 'Tennessee', 'muhiku-plug' ),
		'TX' => esc_html__( 'Texas', 'muhiku-plug' ),
		'UT' => esc_html__( 'Utah', 'muhiku-plug' ),
		'VT' => esc_html__( 'Vermont', 'muhiku-plug' ),
		'VA' => esc_html__( 'Virginia', 'muhiku-plug' ),
		'WA' => esc_html__( 'Washington', 'muhiku-plug' ),
		'WV' => esc_html__( 'West Virginia', 'muhiku-plug' ),
		'WI' => esc_html__( 'Wisconsin', 'muhiku-plug' ),
		'WY' => esc_html__( 'Wyoming', 'muhiku-plug' ),
	);

	return (array) apply_filters( 'everest_forms_states', $states );
}

/**
 * Get builder fields groups.
 *
 * @return array
 */
function mhk_get_fields_groups() {
	return (array) apply_filters(
		'everest_forms_builder_fields_groups',
		array(
			'general'  => __( 'General Fields', 'muhiku-plug' ),
			'advanced' => __( 'Advanced Fields', 'muhiku-plug' ),
			'payment'  => __( 'Payment Fields', 'muhiku-plug' ),
			'survey'   => __( 'Survey Fields', 'muhiku-plug' ),
		)
	);
}

/**
 * Get a builder fields type's name.
 *
 * @param string $type Coupon type.
 * @return string
 */
function mhk_get_fields_group( $type = '' ) {
	$types = mhk_get_fields_groups();
	return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

/**
 * Get all fields settings.
 *
 * @return array Settings data.
 */
function mhk_get_all_fields_settings() {
	$settings = array(
		'label'         => array(
			'id'       => 'label',
			'title'    => __( 'Label', 'muhiku-plug' ),
			'desc'     => __( 'Enter text for the form field label. This is recommended and can be hidden in the Advanced Settings.', 'muhiku-plug' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'meta'          => array(
			'id'       => 'meta-key',
			'title'    => __( 'Meta Key', 'muhiku-plug' ),
			'desc'     => __( 'Enter meta key to be stored in database.', 'muhiku-plug' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'description'   => array(
			'id'       => 'description',
			'title'    => __( 'Description', 'muhiku-plug' ),
			'type'     => 'textarea',
			'desc'     => __( 'Enter text for the form field description.', 'muhiku-plug' ),
			'default'  => '',
			'desc_tip' => true,
		),
		'required'      => array(
			'id'       => 'require',
			'title'    => __( 'Required', 'muhiku-plug' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Check this option to mark the field required.', 'muhiku-plug' ),
			'default'  => 'no',
			'desc_tip' => true,
		),
		'choices'       => array(
			'id'       => 'choices',
			'title'    => __( 'Choices', 'muhiku-plug' ),
			'desc'     => __( 'Add choices for the form field.', 'muhiku-plug' ),
			'type'     => 'choices',
			'desc_tip' => true,
			'defaults' => array(
				1 => __( 'First Choice', 'muhiku-plug' ),
				2 => __( 'Second Choice', 'muhiku-plug' ),
				3 => __( 'Third Choice', 'muhiku-plug' ),
			),
		),
		'placeholder'   => array(
			'id'       => 'placeholder',
			'title'    => __( 'Placeholder Text', 'muhiku-plug' ),
			'desc'     => __( 'Enter text for the form field placeholder.', 'muhiku-plug' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'css'           => array(
			'id'       => 'css',
			'title'    => __( 'CSS Classes', 'muhiku-plug' ),
			'desc'     => __( 'Enter CSS class for this field container. Class names should be separated with spaces.', 'muhiku-plug' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'label_hide'    => array(
			'id'       => 'label_hide',
			'title'    => __( 'Hide Label', 'muhiku-plug' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Check this option to hide the form field label.', 'muhiku-plug' ),
			'default'  => 'no',
			'desc_tip' => true,
		),
		'sublabel_hide' => array(
			'id'       => 'sublabel_hide',
			'title'    => __( 'Hide Sub-Labels', 'muhiku-plug' ),
			'type'     => 'checkbox',
			'desc'     => __( 'Check this option to hide the form field sub-label.', 'muhiku-plug' ),
			'default'  => 'no',
			'desc_tip' => true,
		),
	);

	return apply_filters( 'everest_form_all_fields_settings', $settings );
}

/**
 * Helper function to display debug data.
 *
 * @since 1.3.2
 *
 * @param mixed $expression The expression to be printed.
 * @param bool  $return     Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string
 */
function mhk_debug_data( $expression, $return = false ) {
	if ( defined( 'EVF_DEBUG' ) && true === EVF_DEBUG ) {

		if ( ! $return ) {
			echo '<textarea style="color:#666;background:#fff;margin: 20px 0;width:100%;height:500px;font-size:12px;font-family: Consolas,Monaco,Lucida Console,monospace;direction: ltr;unicode-bidi: embed;line-height: 1.4;padding: 4px 6px 1px;" readonly>';

			echo "==================== Muhiku Plug Debugging ====================\n\n";

			if ( is_array( $expression ) || is_object( $expression ) ) {
				echo esc_html( mhk_print_r( $expression, true ) );
			} else {
				echo esc_html( $expression );
			}
			echo '</textarea>';

		} else {
			$output = '<textarea style="color:#666;background:#fff;margin: 20px 0;width:100%;height:500px;font-size:12px;font-family: Consolas,Monaco,Lucida Console,monospace;direction: ltr;unicode-bidi: embed;line-height: 1.4;padding: 4px 6px 1px;" readonly>';

			$output .= "==================== Muhiku Plug Debugging ====================\n\n";

			if ( is_array( $expression ) || is_object( $expression ) ) {
				$output .= mhk_print_r( $expression, true );
			} else {
				$output .= $expression;
			}

			$output .= '</textarea>';

			return $output;
		}
	}
}

/**
 * String translation function.
 *
 * @since 1.4.9
 *
 * @param int    $form_id Form ID.
 * @param string $field_id Field ID.
 * @param mixed  $value The string that needs to be translated.
 * @param string $suffix The suffix to make the field have unique naem.
 *
 * @return mixed The translated string.
 */
function mhk_string_translation( $form_id, $field_id, $value, $suffix = '' ) {
	$context = isset( $form_id ) ? 'everest_forms_' . absint( $form_id ) : 0;
	$name    = isset( $field_id ) ? mhk_clean( $field_id . $suffix ) : '';

	if ( function_exists( 'icl_register_string' ) ) {
		icl_register_string( $context, $name, $value );
	}

	if ( function_exists( 'icl_t' ) ) {
		$value = icl_t( $context, $name, $value );
	}

	return $value;
}

/**
 * Trigger logging cleanup using the logging class.
 *
 * @since 1.6.2
 */
function mhk_cleanup_logs() {
	$logger = mhk_get_logger();

	if ( is_callable( array( $logger, 'clear_expired_logs' ) ) ) {
		$logger->clear_expired_logs();
	}
}
add_action( 'everest_forms_cleanup_logs', 'mhk_cleanup_logs' );


/**
 * Check whether it device is table or not from HTTP user agent
 *
 * @since 1.7.0
 *
 * @return bool
 */
function mhk_is_tablet() {
	return false !== stripos( mhk_get_user_agent(), 'tablet' ) || false !== stripos( mhk_get_user_agent(), 'tab' );
}

/**
 * Get user device from user agent from HTTP user agent.
 *
 * @since 1.7.0
 *
 * @return string
 */
function mhk_get_user_device() {
	if ( mhk_is_tablet() ) {
		return esc_html__( 'Tablet', 'muhiku-plug' );
	} elseif ( wp_is_mobile() ) {
		return esc_html__( 'Mobile', 'muhiku-plug' );
	} else {
		return esc_html__( 'Desktop', 'muhiku-plug' );
	}
}


/**
 * A wp_parse_args() for multi-dimensional array.
 *
 * @see https://developer.wordpress.org/reference/functions/wp_parse_args/
 *
 * @since 1.7.0
 *
 * @param array $args       Value to merge with $defaults.
 * @param array $defaults   Array that serves as the defaults.
 *
 * @return array    Merged user defined values with defaults.
 */
function mhk_parse_args( &$args, $defaults ) {
	$args     = (array) $args;
	$defaults = (array) $defaults;
	$result   = $defaults;
	foreach ( $args as $k => &$v ) {
		if ( is_array( $v ) && isset( $result[ $k ] ) ) {
			$result[ $k ] = mhk_parse_args( $v, $result[ $k ] );
		} else {
			$result[ $k ] = $v;
		}
	}
	return $result;
}

/**
 * Get date of ranges.
 *
 * @since 1.7.0
 *
 * @param string $first Starting date.
 * @param string $last  End date.
 * @param string $step Date step.
 * @param string $format Date format.
 *
 * @return array Range dates.
 */
function mhk_date_range( $first, $last = '', $step = '+1 day', $format = 'Y/m/d' ) {
	$dates   = array();
	$current = strtotime( $first );
	$last    = strtotime( $last );

	while ( $current <= $last ) {
		$dates[] = date_i18n( $format, $current );
		$current = strtotime( $step, $current );
	}

	return $dates;
}

/**
 * Process syntaxes in a text.
 *
 * @since 1.7.0
 *
 * @param string $text Text to be processed.
 * @param bool   $escape_html Whether to escape all the htmls before processing or not.
 * @param bool   $trim_trailing_spaces Whether to trim trailing spaces or not.
 *
 * @return string Processed text.
 */
function mhk_process_syntaxes( $text, $escape_html = true, $trim_trailing_spaces = true ) {

	if ( true === $trim_trailing_spaces ) {
		$text = trim( $text );
	}
	if ( true === $escape_html ) {
		$text = esc_html( $text );
	}
	$text = mhk_process_hyperlink_syntax( $text );
	$text = mhk_process_italic_syntax( $text );
	$text = mhk_process_bold_syntax( $text );
	$text = mhk_process_underline_syntax( $text );
	$text = mhk_process_line_breaks( $text );
	return $text;
}

/**
 * Extract page ids from a text.
 *
 * @since 1.7.0
 *
 * @param string $text Text to extract page ids from.
 *
 * @return mixed
 */
function mhk_extract_page_ids( $text ) {
	$page_id_syntax_matches = array();
	$page_ids               = array();

	while ( preg_match( '/page_id=([0-9]+)/', $text, $page_id_syntax_matches ) ) {
		$page_id    = $page_id_syntax_matches[1];
		$page_ids[] = $page_id;
		$text       = str_replace( 'page_id=' . $page_id, '', $text );
	}

	if ( count( $page_ids ) > 0 ) {
		return $page_ids;
	}
	return false;
}

/**
 * Process hyperlink syntaxes in a text.
 * The syntax used for hyperlink is: [Link Label](Link URL)
 * Example: [Google Search Page](https://google.com)
 *
 * @since 1.7.0
 *
 * @param string $text         Text to process.
 * @param string $use_no_a_tag If set to `true` only the link will be used and no `a` tag. Particularly useful for exporting CSV,
 *                             as the html tags are escaped in a CSV file.
 *
 * @return string Processed text.
 */
function mhk_process_hyperlink_syntax( $text, $use_no_a_tag = false ) {
	$matches = array();
	$regex   = '/(\[[^\[\]]*\])(\([^\(\)]*\))/';

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = $matches[1];
		$link           = $matches[2];
		$class          = '';
		$page_id        = '';

		// Trim brackets.
		$label = trim( substr( $label, 1, -1 ) );
		$link  = trim( substr( $link, 1, -1 ) );

		// Proceed only if label or link is not empty.
		if ( ! empty( $label ) || ! empty( $link ) ) {

			// Use hash(#) if the link is empty.
			if ( empty( $link ) ) {
				$link = '#';
			}

			// Use link as label if it's empty.
			if ( empty( $label ) ) {
				$label = $link;
			}

			// See if it's a link to a local page.
			if ( strpos( $link, '?' ) === 0 ) {
				$class .= ' mhk-privacy-policy-local-page-link';

				// Extract page id.
				$page_ids = mhk_extract_page_ids( $link );

				if ( false !== $page_ids ) {
					$page_id = $page_ids[0];
					$link    = get_page_link( $page_id );

					if ( empty( $link ) ) {
						$link = '#';
					}
				}
			}

			// Insert hyperlink html.
			if ( true === $use_no_a_tag ) {
				$html = $link;
			} else {
				$html = sprintf( '<a data-page-id="%s" target="_blank" rel="noopener noreferrer nofollow" href="%s" class="%s">%s</a>', $page_id, $link, $class, $label );
			}
			$text = str_replace( $matched_string, $html, $text );
		} else {
			// If both label and link are empty then replace it with empty string.
			$text = str_replace( $matched_string, '', $text );
		}
	}

	return $text;
}

/**
 * Process italic syntaxes in a text.
 * The syntax used for italic text is: `text`
 * Just wrap the text with back tick characters. To escape a backtick insert a backslash(\) before the character like "\`".
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function mhk_process_italic_syntax( $text ) {
	$matches = array();
	$regex   = '/`[^`]+`/';
	$text    = str_replace( '\`', '<&&&&&>', $text ); // To preserve an escaped special character '`'.

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = substr( trim( $matched_string ), 1, -1 );
		$html           = sprintf( '<i>%s</i>', $label );
		$text           = str_replace( $matched_string, $html, $text );
	}

	return str_replace( '<&&&&&>', '`', $text );
}

/**
 * Process bold syntaxes in a text.
 * The syntax used for bold text is: *text*
 * Just wrap the text with asterisk characters. To escape an asterisk insert a backslash(\) before the character like "\*".
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function mhk_process_bold_syntax( $text ) {
	$matches = array();
	$regex   = '/\*[^*]+\*/';
	$text    = str_replace( '\*', '<&&&&&>', $text ); // To preserve an escaped special character '*'.

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = substr( trim( $matched_string ), 1, -1 );
		$html           = sprintf( '<b>%s</b>', $label );
		$text           = str_replace( $matched_string, $html, $text );
	}

	return str_replace( '<&&&&&>', '*', $text );
}

/**
 * Process underline syntaxes in a text.
 * The syntax used for bold text is: __text__
 * Wrap the text with double underscore characters. To escape an underscore insert a backslash(\) before the character like "\_".
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function mhk_process_underline_syntax( $text ) {
	$matches = array();
	$regex   = '/__[^_]+__/';
	$text    = str_replace( '\_', '<&&&&&>', $text ); // To preserve an escaped special character '_'.

	while ( preg_match( $regex, $text, $matches ) ) {
		$matched_string = $matches[0];
		$label          = substr( trim( $matched_string ), 2, -2 );
		$html           = sprintf( '<u>%s</u>', $label );
		$text           = str_replace( $matched_string, $html, $text );
	}

	$text = str_replace( '<&&&&&>', '_', $text );
	return $text;
}

/**
 * It replaces `\n` characters with `<br/>` tag because new line `\n` character is not supported in html.
 *
 * @since 1.7.0
 *
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function mhk_process_line_breaks( $text ) {
	return str_replace( "\n", '<br/>', $text );
}

/**
 * Check whether the current page is in AMP mode or not.
 * We need to check for specific functions, as there is no special AMP header.
 *
 * @since 1.8.4
 *
 * @param bool $check_theme_support Whether theme support should be checked. Defaults to true.
 *
 * @return bool
 */
function mhk_is_amp( $check_theme_support = true ) {

	$is_amp = false;

	if (
	   // AMP by Automattic.
	   ( function_exists( 'amp_is_request' ) && amp_is_request() ) ||
	   // Better AMP.
	   ( function_exists( 'is_better_amp' ) && is_better_amp() )
	) {
		$is_amp = true;
	}

	if ( $is_amp && $check_theme_support ) {
		$is_amp = current_theme_supports( 'amp' );
	}

	return apply_filters( 'mhk_is_amp', $is_amp );
}

/**
 * EVF KSES.
 *
 * @since 1.8.2.1
 *
 * @param string $context Context.
 */
function mhk_get_allowed_html_tags( $context = '' ) {

	$post_tags = wp_kses_allowed_html( 'post' );
	if ( 'builder' === $context ) {
		$builder_tags = get_transient( 'mhk-builder-tags-list' );
		if ( ! empty( $builder_tags ) ) {
			return $builder_tags;
		}
		$allowed_tags = mhk_get_json_file_contents( 'assets/allowed_tags/allowed_tags.json', true );
		if ( ! empty( $allowed_tags ) ) {
			foreach ( $allowed_tags as $tag => $args ) {
				if ( array_key_exists( $tag, $post_tags ) ) {
					foreach ( $args as $arg => $value ) {
						if ( ! array_key_exists( $arg, $post_tags[ $tag ] ) ) {
							$post_tags[ $tag ][ $arg ] = true;
						}
					}
				} else {
					$post_tags[ $tag ] = $args;
				}
			}
			set_transient( 'mhk-builder-tags-list', $post_tags, DAY_IN_SECONDS );
		}
		return $post_tags;
	}

	return wp_parse_args(
		$post_tags,
		array(
			'input'    => array(
				'type'  => true,
				'name'  => true,
				'value' => true,
			),
			'select'   => array(
				'name' => true,
				'id'   => true,
			),
			'option'   => array(
				'value'    => true,
				'selected' => true,
			),
			'textarea' => array(
				'style' => true,
			),
		)
	);
}

/**
 * Parse Builder Post Data.
 *
 * @param mixed $post_data Post Data.
 *
 * @since 1.8.2.2
 */
function mhk_sanitize_builder( $post_data = array() ) {

	if ( empty( $post_data ) || ! is_array( $post_data ) ) {
		return array();
	}

	$form_data = array();
	foreach ( $post_data as $data_key => $data ) {
		$name = sanitize_text_field( $data->name );
		if ( preg_match( '/\<.*\>/', $data->value ) ) {
			$value = wp_kses_post( $data->value );
		} else {
			$value = sanitize_text_field( $data->value );
		}

		$form_data[ sanitize_text_field( $data_key ) ] = (object) array(
			'name'  => $name,
			'value' => $value,
		);
	}
	return $form_data;
}

/**
 * Entry Post Data.
 *
 * @param mixed $entry Post Data.
 *
 * @since 1.8.2.2
 */
function mhk_sanitize_entry( $entry = array() ) {
	if ( empty( $entry ) || ! is_array( $entry ) || empty( $entry['form_fields'] ) ) {
		return $entry;
	}

	$form_id   = absint( $entry['id'] );
	$form_data = mhk()->form->get( $form_id, array( 'contents_only' => true ) );

	if ( ! $form_data ) {
		return array();
	}

	$form_data = mhk_decode( $form_data->post_content );

	$form_fields = $form_data['form_fields'];

	if ( empty( $form_fields ) ) {
		return array();
	}

	foreach ( $form_fields as $key => $field ) {
		$key = sanitize_text_field( $key );
		if ( array_key_exists( $key, $entry['form_fields'] ) ) {
			switch ( $field['type'] ) {
				case 'email':
					if ( isset( $entry['form_fields'][ $key ]['primary'] ) ) {
						$entry['form_fields'][ $key ]['primary']   = sanitize_email( $entry['form_fields'][ $key ]['primary'] );
						$entry['form_fields'][ $key ]['secondary'] = sanitize_email( $entry['form_fields'][ $key ]['secondary'] );
					} else {
						$entry['form_fields'][ $key ] = sanitize_email( $entry['form_fields'][ $key ] );
					}
					break;
				case 'file-upload':
				case 'signature':
				case 'image-upload':
					$entry['form_fields'][ $key ] = is_array( $entry['form_fields'][ $key ] ) ? $entry['form_fields'][ $key ] : esc_url_raw( $entry['form_fields'][ $key ] );
					break;
				case 'textarea':
				case 'html':
				case 'privacy-policy':
				case 'wysiwug':
					$entry['form_fields'][ $key ] = wp_kses_post( $entry['form_fields'][ $key ] );
					break;
				case 'repeater-fields':
					$entry['form_fields'][ $key ] = $entry['form_fields'][ $key ];
					break;
				default:
					if ( is_array( $entry['form_fields'][ $key ] ) ) {
						foreach ( $entry['form_fields'][ $key ] as $field_key => $value ) {
							$field_key                                  = sanitize_text_field( $field_key );
							$entry['form_fields'][ $key ][ $field_key ] = sanitize_text_field( $value );
						}
					} else {
						$entry['form_fields'][ $key ] = sanitize_text_field( $entry['form_fields'][ $key ] );
					}
			}
		}
		return $entry;
	}
}

/**
 * EVF Get json file contents.
 *
 * @param mixed $file File path.
 * @param mixed $to_array Returned data in array.
 */
function mhk_get_json_file_contents( $file, $to_array = false ) {
	if ( $to_array ) {
		return json_decode( mhk_file_get_contents( $file ), true );
	}
	return json_decode( mhk_file_get_contents( $file ) );
}

/**
 * EVF file get contents.
 *
 * @param mixed $file File path.
 */
function mhk_file_get_contents( $file ) {
	if ( $file ) {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		$local_file = preg_replace( '/\\\\|\/\//', '/', plugin_dir_path( EVF_PLUGIN_FILE ) . $file );
		if ( $wp_filesystem->exists( $local_file ) ) {
			$response = $wp_filesystem->get_contents( $local_file );
			return $response;
		}
	}
	return;
}

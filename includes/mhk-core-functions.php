<?php
/**
 * @package MuhikuPlug/Functions
 */

defined( 'ABSPATH' ) || exit;

require MHK_ABSPATH . 'includes/mhk-conditional-functions.php';
require MHK_ABSPATH . 'includes/mhk-deprecated-functions.php';
require MHK_ABSPATH . 'includes/mhk-formatting-functions.php';
require MHK_ABSPATH . 'includes/mhk-entry-functions.php';

/**
 * @param string $name  Constant name.
 * @param mixed  $value Value.
 */
function mhk_maybe_define_constant( $name, $value ) {
	if ( ! defined( $name ) ) {
		define( $name, $value );
	}
}

/**
 * @param mixed  $slug Template slug.
 * @param string $name Template name (default: '').
 */
function mhk_get_template_part( $slug, $name = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template-part', $slug, $name, MHK_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'muhiku-plug' );

	if ( ! $template ) {
		if ( $name ) {
			$template = MHK_TEMPLATE_DEBUG_MODE ? '' : locate_template(
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
			$template = MHK_TEMPLATE_DEBUG_MODE ? '' : locate_template(
				array(
					"{$slug}.php",
					mhk()->template_path() . "{$slug}.php",
				)
			);
		}

		wp_cache_set( $cache_key, $template, 'muhiku-plug' );
	}

	$template = apply_filters( 'mhk_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * @param string $template_name 
 * @param array  $args          
 * @param string $template_path 
 * @param string $default_path  
 */
function mhk_get_template( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	$cache_key = sanitize_key( implode( '-', array( 'template', $template_name, $template_path, $default_path, MHK_VERSION ) ) );
	$template  = (string) wp_cache_get( $cache_key, 'muhiku-plug' );

	if ( ! $template ) {
		$template = mhk_locate_template( $template_name, $template_path, $default_path );
		wp_cache_set( $cache_key, $template, 'muhiku-plug' );
	}

	$filter_template = apply_filters( 'mhk_get_template', $template, $template_name, $args, $template_path, $default_path );

	if ( $filter_template !== $template ) {
		if ( ! file_exists( $filter_template ) ) {
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
		extract( $args ); 
	}

	do_action( 'muhiku_forms_before_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );

	include $action_args['located'];

	do_action( 'muhiku_forms_after_template_part', $action_args['template_name'], $action_args['template_path'], $action_args['located'], $action_args['args'] );
}

/**
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

	$template = locate_template(
		array(
			trailingslashit( $template_path ) . $template_name,
			$template_name,
		)
	);

	if ( ! $template || MHK_TEMPLATE_DEBUG_MODE ) {
		$template = $default_path . $template_name;
	}

	return apply_filters( 'muhiku_forms_locate_template', $template, $template_name, $template_path );
}

/**
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
 * @param string $code
 */
function mhk_enqueue_js( $code ) {
	global $mhk_queued_js;

	if ( empty( $mhk_queued_js ) ) {
		$mhk_queued_js = '';
	}

	$mhk_queued_js .= "\n" . $code . "\n";
}

function mhk_print_js() {
	global $mhk_queued_js;

	if ( ! empty( $mhk_queued_js ) ) {
		$mhk_queued_js = wp_check_invalid_utf8( $mhk_queued_js );
		$mhk_queued_js = preg_replace( '/&#(x)?0*(?(1)27|39);?/i', "'", $mhk_queued_js );
		$mhk_queued_js = str_replace( "\r", '', $mhk_queued_js );

		$js = "<!-- Muhiku Plug JavaScript -->\n<script type=\"text/javascript\">\njQuery(function($) { $mhk_queued_js });\n</script>\n";

		/**
		 * @param string $js 
		 */
		echo wp_kses( apply_filters( 'muhiku_forms_queued_js', $js ), array( 'script' => array( 'type' => true ) ) );
		unset( $mhk_queued_js );
	}
}

/**
 * @param  string  $name  
 * @param  string  $value  
 * @param  integer $expire 
 * @param  bool    $secure 
 * @param  bool    $httponly
 */
function mhk_setcookie( $name, $value, $expire = 0, $secure = false, $httponly = false ) {
	if ( ! headers_sent() ) {
		setcookie( $name, $value, $expire, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, $secure, apply_filters( 'muhiku_forms_cookie_httponly', $httponly, $name, $value, $expire, $secure ) );
	} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		headers_sent( $file, $line );
		trigger_error( "{$name} cookie cannot be set - headers already sent by {$file} on line {$line}", E_USER_NOTICE );  
	}
}

/**
 * @param  string $handle name.
 * @return string the log file path.
 */
function mhk_get_log_file_path( $handle ) {
	return MHK_Log_Handler_File::get_log_file_path( $handle );
}

/**
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
 * @return string
 */
function mhk_get_user_agent() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) ? mhk_clean( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';  
}

if ( ! function_exists( 'hash_equals' ) ) :
	/**
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

		for ( $i = 0; $i < $a_length; $i ++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		return 0 === $result;
	}
endif;

/**
 * @return string
 */
function mhk_rand_hash() {
	if ( ! function_exists( 'openssl_random_pseudo_bytes' ) ) {
		return sha1( wp_rand() );
	}

	return bin2hex( openssl_random_pseudo_bytes( 20 ) );  
}

/**
 * @param  array $input Input.
 * @return array
 */
function mhk_array_cartesian( $input ) {
	$input   = array_filter( $input );
	$results = array();
	$indexes = array();
	$index   = 0;

	foreach ( $input as $key => $values ) {
		foreach ( $values as $value ) {
			$indexes[ $key ][ $value ] = $index++;
		}
	}

	foreach ( $indexes as $key => $values ) {
		if ( empty( $results ) ) {
			foreach ( $values as $value ) {
				$results[] = array( $key => $value );
			}
		} else {
			foreach ( $results as $result_key => $result ) {
				foreach ( $values as $value ) {
					if ( ! isset( $results[ $result_key ][ $key ] ) ) {
						$results[ $result_key ][ $key ] = $value;
					} else {
						$new_combination         = $results[ $result_key ];
						$new_combination[ $key ] = $value;
						$results[]               = $new_combination;
					}
				}
			}
		}
	}
	arsort( $results );

	foreach ( $results as $result_key => $result ) {
		$converted_values = array();

		arsort( $results[ $result_key ] );

		foreach ( $results[ $result_key ] as $key => $value ) {
			$converted_values[ $key ] = array_search( $value, $indexes[ $key ], true );
		}

		$results[ $result_key ] = $converted_values;
	}

	return $results;
}

/**
 * @param string $type Types: start (default), commit, rollback.
 * @param bool   $force use of transactions.
 */
function mhk_transaction_query( $type = 'start', $force = false ) {
	global $wpdb;

	$wpdb->hide_errors();

	mhk_maybe_define_constant( 'MHK_USE_TRANSACTIONS', true );

	if ( MHK_USE_TRANSACTIONS || $force ) {
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
 * @param string $label Title of the page to return to.
 * @param string $url   URL of the page to return to.
 */
function mhk_back_link( $label, $url ) {
	echo '<small class="mhk-admin-breadcrumb"><a href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $label ) . '">&#x2934;</a></small>';
}

/**
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
 * @param int $limit Time limit.
 */
function mhk_set_time_limit( $limit = 0 ) {
	if ( function_exists( 'set_time_limit' ) && false === strpos( ini_get( 'disable_functions' ), 'set_time_limit' ) && ! ini_get( 'safe_mode' ) ) { // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.safe_modeDeprecatedRemoved
		@set_time_limit( $limit );  
	}
}

function mhk_nocache_headers() {
	MHK_Cache_Helper::set_nocache_constants();
	nocache_headers();
}

/**
 * @see MHK_Logger_Interface
 *
 * @return MHK_Logger
 */
function mhk_get_logger() {
	static $logger = null;

	$class = apply_filters( 'muhiku_forms_logging_class', 'MHK_Logger' );

	if ( null !== $logger && is_string( $class ) && is_a( $logger, $class ) ) {
		return $logger;
	}

	$implements = class_implements( $class );

	if ( is_array( $implements ) && in_array( 'MHK_Logger_Interface', $implements, true ) ) {
		$logger = is_object( $class ) ? $class : new $class();
	} else {
		mhk_doing_it_wrong(
			__FUNCTION__,
			sprintf(
				__( 'The class %1$s provided by %2$s filter must implement %3$s.', 'muhiku-plug' ),
				'<code>' . esc_html( is_object( $class ) ? get_class( $class ) : $class ) . '</code>',
				'<code>muhiku_forms_logging_class</code>',
				'<code>MHK_Logger_Interface</code>'
			),
			'1.2'
		);
		$logger = is_a( $logger, 'MHK_Logger' ) ? $logger : new MHK_Logger();
	}

	return $logger;
}

/**
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

	$alternatives = apply_filters( 'muhiku_forms_print_r_alternatives', $alternatives, $expression );

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
 * @param  array $handlers Handlers.
 * @return array
 */
function mhk_register_default_log_handler( $handlers ) {
	if ( defined( 'MHK_LOG_HANDLER' ) && class_exists( MHK_LOG_HANDLER ) ) {
		$handler_class   = MHK_LOG_HANDLER;
		$default_handler = new $handler_class();
	} else {
		$default_handler = new MHK_Log_Handler_File();
	}

	array_push( $handlers, $default_handler );

	return $handlers;
}

add_filter( 'muhiku_forms_register_log_handlers', 'mhk_register_default_log_handler' );

/**
 * @param array      $list              List of objects or arrays.
 * @param int|string $callback_or_field Callback method from the object to place instead of the entire object.
 * @param int|string $index_key         Optional. Field from the object to use as keys for the new array.
 *                                      Default null.
 * @return array Array of values.
 */
function mhk_list_pluck( $list, $callback_or_field, $index_key = null ) {
	$first_el = current( $list );
	if ( ! is_object( $first_el ) || ! is_callable( array( $first_el, $callback_or_field ) ) ) {
		return wp_list_pluck( $list, $callback_or_field, $index_key );
	}
	if ( ! $index_key ) {
		foreach ( $list as $key => $value ) {
			$list[ $key ] = $value->{$callback_or_field}();
		}
		return $list;
	}

	$newlist = array();
	foreach ( $list as $value ) {
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

function mhk_switch_to_site_locale() {
	if ( function_exists( 'switch_to_locale' ) ) {
		switch_to_locale( get_locale() );

		add_filter( 'plugin_locale', 'get_locale' );

		mhk()->load_plugin_textdomain();
	}
}

function mhk_restore_locale() {
	if ( function_exists( 'restore_previous_locale' ) ) {
		restore_previous_locale();

		remove_filter( 'plugin_locale', 'get_locale' );

		mhk()->load_plugin_textdomain();
	}
}

/**
 * @param  string $key     Key.
 * @param  string $default Default.
 * @return mixed value sanitized by mhk_clean
 */
function mhk_get_post_data_by_key( $key, $default = '' ) {
	return mhk_clean( mhk_get_var( $_POST[ $key ], $default ) ); 
}

/**
 * @param  mixed  $var     Variable.
 * @param  string $default Default value.
 * @return mixed
 */
function mhk_get_var( &$var, $default = null ) {
	return isset( $var ) ? $var : $default;
}

/**
 * @param  array $headers Headers.
 * @return array
 */
function mhk_enable_mhk_plugin_headers( $headers ) {
	if ( ! class_exists( 'MHK_Plugin_Updates' ) ) {
		include_once dirname( __FILE__ ) . '/admin/plugin-updates/class-mhk-plugin-updates.php';
	}

	$headers[] = MHK_Plugin_Updates::VERSION_REQUIRED_HEADER;

	$headers[] = MHK_Plugin_Updates::VERSION_TESTED_HEADER;

	return $headers;
}
add_filter( 'extra_theme_headers', 'mhk_enable_mhk_plugin_headers' );
add_filter( 'extra_plugin_headers', 'mhk_enable_mhk_plugin_headers' );

/**
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
add_action( 'muhiku_forms_installed', 'mhk_delete_expired_transients' );

/**
 * @param  string $url URL to make relative.
 * @return string
 */
function mhk_get_relative_url( $url ) {
	return mhk_is_external_resource( $url ) ? $url : str_replace( array( 'http://', 'https://' ), '//', $url );
}

/**
 * @param  string $url URL to check.
 * @return bool
 */
function mhk_is_external_resource( $url ) {
	$wp_base = str_replace( array( 'http://', 'https://' ), '//', get_home_url( null, '/', 'http' ) );
	return strstr( $url, '://' ) && strstr( $wp_base, $url );
}

/**
 * @param  string|array $theme Theme name or array of theme names to check.
 * @return boolean
 */
function mhk_is_active_theme( $theme ) {
	return is_array( $theme ) ? in_array( get_template(), $theme, true ) : get_template() === $theme;
}

function mhk_cleanup_session_data() {
	$session_class = apply_filters( 'muhiku_forms_session_handler', 'MHK_Session_Handler' );
	$session       = new $session_class();

	if ( is_callable( array( $session, 'cleanup_sessions' ) ) ) {
		$session->cleanup_sessions();
	}
}
add_action( 'muhiku_forms_cleanup_sessions', 'mhk_cleanup_session_data' );

/**
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
 * @param mixed $form Form data.
 * @param array $whitelist Whitelist args.
 *
 * @return mixed boolean or array
 */
function mhk_get_form_fields( $form = false, $whitelist = array() ) {
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
	$allowed_form_fields = apply_filters( 'muhiku_forms_allowed_form_fields', $allowed_form_fields );

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
				if ( $att[0] === '[' ) { 
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
 * @param int $min Min value.
 * @param int $max Max value.
 *
 * @return mixed
 */
function mhk_crypto_rand_secure( $min, $max ) {
	$range = $max - $min;
	if ( $range < 1 ) {
		return $min;
	} 
	$log    = ceil( log( $range, 2 ) );
	$bytes  = (int) ( $log / 8 ) + 1; 
	$bits   = (int) $log + 1; 
	$filter = (int) ( 1 << $bits ) - 1; 
	do {
		$rnd = hexdec( bin2hex( openssl_random_pseudo_bytes( $bytes ) ) );
		$rnd = $rnd & $filter; 
	} while ( $rnd > $range );

	return $min + $rnd;
}

/**
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
				'numberposts' => -1, 
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

			if ( current_user_can( 'muhiku_forms_view_form_entries', $form_id ) ) {
				$forms[ $form_id ] = $form->post_title;
			}
		}
	}

	return $forms;
}

/**
 * @param  array $field Field data array.
 * @return string
 */
function mhk_get_meta_key_field_option( $field ) {
	$random_number = rand( pow( 10, 3 ), pow( 10, 4 ) - 1 );
	return strtolower( str_replace( array( ' ', '/_' ), array( '_', '' ), $field['label'] ) ) . '_' . $random_number;
}

/**
 * @return string
 */
function mhk_get_ip_address() {
	if ( isset( $_SERVER['HTTP_X_REAL_IP'] ) ) { 
		return sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) ); 
	} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) { 
		return (string) rest_is_ip_address( trim( current( preg_split( '/[,:]/', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) ) ) ) ); 
	} elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) { 
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ); 
	}
	return '';
}

/**
 * @return array
 */
function mhk_get_browser() {
	$u_agent  = ! empty( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) : '';
	$bname    = 'Unknown';
	$platform = 'Unknown';
	$version  = '';

	if ( preg_match( '/linux/i', $u_agent ) ) {
		$platform = 'Linux';
	} elseif ( preg_match( '/macintosh|mac os x/i', $u_agent ) ) {
		$platform = 'MAC OS';
	} elseif ( preg_match( '/windows|win32/i', $u_agent ) ) {
		$platform = 'Windows';
	}

	if ( preg_match( '/MSIE/i', $u_agent ) && ! preg_match( '/Opera/i', $u_agent ) ) {
		$bname = 'Internet Explorer';
		$ub    = 'MSIE';
	} elseif ( preg_match( '/Trident/i', $u_agent ) ) {
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

	$known   = array( 'Version', $ub, 'other' );
	$pattern = '#(?<browser>' . join( '|', $known ) . ')[/|: ]+(?<version>[0-9.|a-zA-Z.]*)#';
	if ( ! preg_match_all( $pattern, $u_agent, $matches ) ) { 
	}

	$i = count( $matches['browser'] );

	if ( 1 !== $i ) {
		if ( strripos( $u_agent, 'Version' ) < strripos( $u_agent, $ub ) ) {
			$version = $matches['version'][0];
		} else {
			$version = $matches['version'][1];
		}
	} else {
		$version = $matches['version'][0];
	}

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
 * @param string $string String to check.
 * @return bool
 */
function mhk_isJson( $string ) {
	json_decode( $string );
	return ( json_last_error() == JSON_ERROR_NONE ); // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
}

/**
 * @param  string $tag Shortcode tag to check.
 * @return bool
 */
function mhk_post_content_has_shortcode( $tag = '' ) {
	global $post;

	return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
}

/**
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

	return $value;
}

/**
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
 * @return string
 */
function mhk_get_required_label() {
	return apply_filters( 'muhiku_forms_required_label', esc_html__( 'This field is required.', 'muhiku-plug' ) );
}


/**
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
add_filter( 'muhiku_forms_email_message', 'mhk_decode_string' );


/**
 * @return array
 */
function mhk_get_fields_groups() {
	return (array) apply_filters(
		'muhiku_forms_builder_fields_groups',
		array(
			'general'  => __( 'Genel Alanlar', 'muhiku-plug' ),
			'advanced' => __( 'Geliştirilmiş Alanlar', 'muhiku-plug' ),
		)
	);
}

/**
 * @param string $type Coupon type.
 * @return string
 */
function mhk_get_fields_group( $type = '' ) {
	$types = mhk_get_fields_groups();
	return isset( $types[ $type ] ) ? $types[ $type ] : '';
}

/**
 * @return array Settings data.
 */
function mhk_get_all_fields_settings() {
	$settings = array(
		'label'         => array(
			'id'       => 'label',
			'title'    => __( 'Kısa isim', 'muhiku-plug' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'description'   => array(
			'id'       => 'description',
			'title'    => __( 'Açıklama', 'muhiku-plug' ),
			'type'     => 'textarea',
			'default'  => '',
			'desc_tip' => true,
		),
		'required'      => array(
			'id'       => 'require',
			'title'    => __( 'Gerekli', 'muhiku-plug' ),
			'type'     => 'checkbox',
			'default'  => 'no',
			'desc_tip' => true,
		),
		'choices'       => array(
			'id'       => 'choices',
			'title'    => __( 'Seçenekler', 'muhiku-plug' ),
			'type'     => 'choices',
			'desc_tip' => true,
			'defaults' => array(
				1 => __( '1. Seçenek', 'muhiku-plug' ),
				2 => __( '2. Seçenek', 'muhiku-plug' ),
				3 => __( '3. Seçenek', 'muhiku-plug' ),
			),
		),
		'placeholder'   => array(
			'id'       => 'placeholder',
			'title'    => __( 'Yer Tutucu Metin', 'muhiku-plug' ),
			'default'  => '',
			'type'     => 'text',
			'desc_tip' => true,
		),
		'label_hide'    => array(
			'id'       => 'label_hide',
			'title'    => __( 'Alanı Gizle', 'muhiku-plug' ),
			'type'     => 'checkbox',
			'default'  => 'no',
			'desc_tip' => true,
		),
		'sublabel_hide' => array(
			'id'       => 'sublabel_hide',
			'title'    => __( 'Alt Alanları Gizle', 'muhiku-plug' ),
			'type'     => 'checkbox',
			'default'  => 'no',
			'desc_tip' => true,
		),
	);

	return apply_filters( 'muhiku_form_all_fields_settings', $settings );
}

/**
 * @param mixed $expression The expression to be printed.
 * @param bool  $return     Optional. Default false. Set to true to return the human-readable string.
 *
 * @return string
 */
function mhk_debug_data( $expression, $return = false ) {
	if ( defined( 'MHK_DEBUG' ) && true === MHK_DEBUG ) {

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
 * @param int    $form_id Form ID.
 * @param string $field_id Field ID.
 * @param mixed  $value The string that needs to be translated.
 * @param string $suffix The suffix to make the field have unique naem.
 *
 * @return mixed The translated string.
 */
function mhk_string_translation( $form_id, $field_id, $value, $suffix = '' ) {
	$context = isset( $form_id ) ? 'muhiku_forms_' . absint( $form_id ) : 0;
	$name    = isset( $field_id ) ? mhk_clean( $field_id . $suffix ) : '';

	if ( function_exists( 'icl_register_string' ) ) {
		icl_register_string( $context, $name, $value );
	}

	if ( function_exists( 'icl_t' ) ) {
		$value = icl_t( $context, $name, $value );
	}

	return $value;
}

function mhk_cleanup_logs() {
	$logger = mhk_get_logger();

	if ( is_callable( array( $logger, 'clear_expired_logs' ) ) ) {
		$logger->clear_expired_logs();
	}
}
add_action( 'muhiku_forms_cleanup_logs', 'mhk_cleanup_logs' );


/**
 *
 * @return bool
 */
function mhk_is_tablet() {
	return false !== stripos( mhk_get_user_agent(), 'tablet' ) || false !== stripos( mhk_get_user_agent(), 'tab' );
}

/**
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

		$label = trim( substr( $label, 1, -1 ) );
		$link  = trim( substr( $link, 1, -1 ) );

		if ( ! empty( $label ) || ! empty( $link ) ) {

			if ( empty( $link ) ) {
				$link = '#';
			}

			if ( empty( $label ) ) {
				$label = $link;
			}

			if ( strpos( $link, '?' ) === 0 ) {
				$class .= ' mhk-privacy-policy-local-page-link';

				$page_ids = mhk_extract_page_ids( $link );

				if ( false !== $page_ids ) {
					$page_id = $page_ids[0];
					$link    = get_page_link( $page_id );

					if ( empty( $link ) ) {
						$link = '#';
					}
				}
			}
			if ( true === $use_no_a_tag ) {
				$html = $link;
			} else {
				$html = sprintf( '<a data-page-id="%s" target="_blank" rel="noopener noreferrer nofollow" href="%s" class="%s">%s</a>', $page_id, $link, $class, $label );
			}
			$text = str_replace( $matched_string, $html, $text );
		} else {
			$text = str_replace( $matched_string, '', $text );
		}
	}

	return $text;
}

/**
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
 * @param string $text Text to process.
 *
 * @return string Processed text.
 */
function mhk_process_line_breaks( $text ) {
	return str_replace( "\n", '<br/>', $text );
}

/**
 *
 * @param bool $check_theme_support Whether theme support should be checked. Defaults to true.
 *
 * @return bool
 */
function mhk_is_amp( $check_theme_support = true ) {

	$is_amp = false;

	if (
	   ( function_exists( 'amp_is_request' ) && amp_is_request() ) ||
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
 * MHK KSES.
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
 * @param mixed $post_data Post Data.
 *
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
 * @param mixed $entry Post Data.
 *
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
 * @param mixed $file File path.
 */
function mhk_file_get_contents( $file ) {
	if ( $file ) {
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		$local_file = preg_replace( '/\\\\|\/\//', '/', plugin_dir_path( MHK_PLUGIN_FILE ) . $file );
		if ( $wp_filesystem->exists( $local_file ) ) {
			$response = $wp_filesystem->get_contents( $local_file );
			return $response;
		}
	}
	return;
}

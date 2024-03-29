<?php
/**
 * @class   MHK_AJAX
 * @package MuhikuPlug/Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_AJAX {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'define_ajax' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'do_mhk_ajax' ), 0 );
		self::add_ajax_events();
	}

	public static function define_ajax() {
		if ( ! empty( $_GET['mhk-ajax'] ) ) {
			mhk_maybe_define_constant( 'DOING_AJAX', true );
			mhk_maybe_define_constant( 'MHK_DOING_AJAX', true );
			if ( ! WP_DEBUG || ( WP_DEBUG && ! WP_DEBUG_DISPLAY ) ) {
				@ini_set( 'display_errors', 0 ); 
			}
			$GLOBALS['wpdb']->hide_errors();
		}
	}

	private static function mhk_ajax_headers() {
		if ( ! headers_sent() ) {
			send_origin_headers();
			send_nosniff_header();
			mhk_nocache_headers();
			header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
			header( 'X-Robots-Tag: noindex' );
			status_header( 200 );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			headers_sent( $file, $line );
			trigger_error( "mhk_ajax_headers cannot set headers - headers already sent by {$file} on line {$line}", E_USER_NOTICE );  
		}
	}

	public static function do_mhk_ajax() {
		global $wp_query;

		if ( ! empty( $_GET['mhk-ajax'] ) ) {  
			$wp_query->set( 'mhk-ajax', sanitize_text_field( wp_unslash( $_GET['mhk-ajax'] ) ) );  
		}

		$action = $wp_query->get( 'mhk-ajax' );

		if ( $action ) {
			self::mhk_ajax_headers();
			$action = sanitize_text_field( $action );
			do_action( 'mhk_ajax_' . $action );
			wp_die();
		}
	}

	public static function add_ajax_events() {
		$ajax_events = array(
			'save_form'               => false,
			'create_form'             => false,
			'get_next_id'             => false,
			'install_extension'       => false,
			'integration_connect'     => false,
			'new_email_add'           => false,
			'integration_disconnect'  => false,
			'deactivation_notice'     => false,
			'rated'                   => false,
			'review_dismiss'          => false,
			'survey_dismiss'          => false,
			'enabled_form'            => false,
			'import_form_action'      => false,
			'template_licence_check'  => false,
			'template_activate_addon' => false,
			'ajax_form_submission'    => true,
			'send_test_email'         => false,
		);

		foreach ( $ajax_events as $ajax_event => $nopriv ) {
			add_action( 'wp_ajax_muhiku_forms_' . $ajax_event, array( __CLASS__, $ajax_event ) );

			if ( $nopriv ) {
				add_action( 'wp_ajax_nopriv_muhiku_forms_' . $ajax_event, array( __CLASS__, $ajax_event ) );

				add_action( 'mhk_ajax_' . $ajax_event, array( __CLASS__, $ajax_event ) );
			}
		}
	}

	public static function get_next_id() {
		check_ajax_referer( 'muhiku_forms_get_next_id', 'security' );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		if ( $form_id < 1 ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Invalid form', 'muhiku-plug' ),
				)
			);
		}

		if ( ! current_user_can( 'muhiku_forms_edit_form', $form_id ) ) {
			wp_send_json_error();
		}

		if ( isset( $_POST['fields'] ) ) {
			$fields_data = array();
			for ( $i = 0; $i < $_POST['fields']; $i++ ) {
				$field_key      = mhk()->form->field_unique_key( $form_id );
				$field_id_array = explode( '-', $field_key );
				$new_field_id   = ( $field_id_array[ count( $field_id_array ) - 1 ] + 1 );
				$fields_data [] = array(
					'field_id'  => $new_field_id,
					'field_key' => $field_key,
				);
			}
			wp_send_json_success(
				$fields_data
			);
		} else {
			$field_key      = mhk()->form->field_unique_key( $form_id );
			$field_id_array = explode( '-', $field_key );
			$new_field_id   = ( $field_id_array[ count( $field_id_array ) - 1 ] + 1 );
			wp_send_json_success(
				array(
					'field_id'  => $new_field_id,
					'field_key' => $field_key,
				)
			);
		}
	}

	public static function create_form() {
		ob_start();

		check_ajax_referer( 'muhiku_forms_create_form', 'security' );

		if ( ! current_user_can( 'muhiku_forms_create_forms' ) ) {
			wp_die( -1 );
		}

		$title    = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : esc_html__( 'Blank Form', 'muhiku-plug' );
		$template = isset( $_POST['template'] ) ? sanitize_text_field( wp_unslash( $_POST['template'] ) ) : 'blank';

		$form_id = mhk()->form->create( $title, $template );

		if ( $form_id ) {
			$data = array(
				'id'       => $form_id,
				'redirect' => add_query_arg(
					array(
						'tab'     => 'fields',
						'form_id' => $form_id,
					),
					admin_url( 'admin.php?page=mhk-builder' )
				),
			);

			wp_send_json_success( $data );
		}

		wp_send_json_error(
			array(
				'error' => esc_html__( 'Something went wrong, please try again later', 'muhiku-plug' ),
			)
		);
	}

	public static function save_form() {
		check_ajax_referer( 'muhiku_forms_save_form', 'security' );

		$logger = mhk_get_logger();

		$logger->info(
			__( 'Checking permissions.', 'muhiku-plug' ),
			array( 'source' => 'form-save' )
		);
		if ( ! current_user_can( 'muhiku_forms_edit_forms' ) ) {
			$logger->critical(
				__( 'You do not have permission.', 'muhiku-plug' ),
				array( 'source' => 'form-save' )
			);
			die( esc_html__( 'You do not have permission.', 'muhiku-plug' ) );
		}

		$logger->info(
			__( 'Checking for form data.', 'muhiku-plug' ),
			array( 'source' => 'form-save' )
		);
		if ( empty( $_POST['form_data'] ) ) {
			$logger->critical(
				__( 'No data provided.', 'muhiku-plug' ),
				array( 'source' => 'form-save' )
			);
			die( esc_html__( 'No data provided', 'muhiku-plug' ) );
		}

		$form_post = mhk_sanitize_builder( json_decode( wp_unslash( $_POST['form_data'] ) ) ); 

		$data         = array();
		$choose_field = array();

		if ( ! is_null( $form_post ) && $form_post ) {
			foreach ( $form_post as $post_input_data ) {
				preg_match( '#([^\[]*)(\[(.+)\])?#', $post_input_data->name, $matches );

				$array_bits = array( $matches[1] );

				if ( isset( $matches[3] ) ) {
					$array_bits = array_merge( $array_bits, explode( '][', $matches[3] ) );
				}

				$new_post_data = array();

				for ( $i = count( $array_bits ) - 1; $i >= 0; $i -- ) {
					if ( count( $array_bits ) - 1 === $i ) {
						$new_post_data[ $array_bits[ $i ] ] = wp_slash( $post_input_data->value );
					} else {
						$new_post_data = array(
							$array_bits[ $i ] => $new_post_data,
						);
					}
				}
				$choose_field_data = isset( $new_post_data['settings']['choose_pdf_fields'] ) ? $new_post_data['settings']['choose_pdf_fields'] : array();
				if ( ! empty( $choose_field_data ) ) {
					 array_push( $choose_field, $choose_field_data );
				}
				$data = array_replace_recursive( $data, $new_post_data );
			}
		}
		$data['settings']['choose_pdf_fields'] = $choose_field;
		$logger->info(
			__( 'Check for empty meta key.', 'muhiku-plug' ),
			array( 'source' => 'form-save' )
		);
		$empty_meta_data = array();
		if ( ! empty( $data['form_fields'] ) ) {
			foreach ( $data['form_fields'] as $field_key => $field ) {
				if ( ! empty( $field['label'] ) ) {
					$data['form_fields'][ $field_key ]['label'] = wp_kses(
						$field['label'],
						array(
							'a'      => array(
								'href'  => array(),
								'class' => array(),
							),
							'span'   => array(
								'class' => array(),
							),
							'em'     => array(),
							'small'  => array(),
							'strong' => array(),
						)
					);

					mhk_string_translation( $data['id'], $field['id'], $field['label'] );
				}

				if ( empty( $field['meta-key'] ) && ! in_array( $field['type'], array( 'html', 'title', 'captcha', 'divider' ), true ) ) {
					$empty_meta_data[] = $field['label'];
				}
			}

			if ( ! empty( $empty_meta_data ) ) {
				$logger->error(
					__( 'Meta Key missing.', 'muhiku-plug' ),
					array( 'source' => 'form-save' )
				);
				wp_send_json_error(
					array(
						'errorTitle'   => esc_html__( 'Meta Key missing', 'muhiku-plug' ),
						'errorMessage' => sprintf( esc_html__( 'Please add Meta key for fields: %s', 'muhiku-plug' ), '<strong>' . implode( ', ', $empty_meta_data ) . '</strong>' ),
					)
				);
			}
		}

		$logger->info(
			__( 'Fix for sorting field ordering.', 'muhiku-plug' ),
			array( 'source' => 'form-save' )
		);
		if ( isset( $data['structure'], $data['form_fields'] ) ) {
			$structure           = mhk_flatten_array( $data['structure'] );
			$data['form_fields'] = array_merge( array_intersect_key( array_flip( $structure ), $data['form_fields'] ), $data['form_fields'] );
		}

		$form_id     = mhk()->form->update( $data['id'], $data );
		$form_styles = get_option( 'muhiku_forms_styles', array() );
		$logger->info(
			__( 'Saving form.', 'muhiku-plug' ),
			array( 'source' => 'form-save' )
		);
		do_action( 'muhiku_forms_save_form', $form_id, $data, array(), ! empty( $form_styles[ $form_id ] ) );

		if ( ! $form_id ) {
			$logger->error(
				__( 'An error occurred while saving the form.', 'muhiku-plug' ),
				array( 'source' => 'form-save' )
			);
			wp_send_json_error(
				array(
					'errorTitle'   => esc_html__( 'Form not found', 'muhiku-plug' ),
					'errorMessage' => esc_html__( 'An error occurred while saving the form.', 'muhiku-plug' ),
				)
			);
		} else {
			$logger->info(
				__( 'Form Saved successfully.', 'muhiku-plug' ),
				array( 'source' => 'form-save' )
			);
			wp_send_json_success(
				array(
					'form_name'    => esc_html( $data['settings']['form_title'] ),
					'redirect_url' => admin_url( 'admin.php?page=mhk-builder' ),
				)
			);
		}
	}

	public static function ajax_form_submission() {
		check_ajax_referer( 'muhiku_forms_ajax_form_submission', 'security' );

		if ( ! empty( $_POST['muhiku_forms']['id'] ) ) {
			$process = mhk()->task->ajax_form_submission( mhk_sanitize_entry( wp_unslash( $_POST['muhiku_forms'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( 'success' === $process['response'] ) {
				wp_send_json_success( $process );
			}

			wp_send_json_error( $process );
		}
	}

	public static function template_activate_addon() {
		check_ajax_referer( 'muhiku_forms_template_licence_check', 'security' );

		if ( empty( $_POST['addon'] ) ) {
			wp_send_json_error(
				array(
					'errorCode'    => 'no_addon_specified',
					'errorMessage' => esc_html__( 'No Addon specified.', 'muhiku-plug' ),
				)
			);
		}

		$activate = activate_plugin( sanitize_text_field( wp_unslash( $_POST['addon'] ) ) . '/' . sanitize_text_field( wp_unslash( $_POST['addon'] ) ) . '.php' );

		if ( is_wp_error( $activate ) ) {
			wp_send_json_error(
				array(
					'errorCode'    => 'addon_not_active',
					'errorMessage' => esc_html__( 'Addon can not be activate. Please try again.', 'muhiku-plug' ),
				)
			);
		} else {
			wp_send_json_success( 'Addon sucessfully activated.' );
		}
	}

	/**
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 */
	public static function template_licence_check() {
		check_ajax_referer( 'muhiku_forms_template_licence_check', 'security' );

		if ( empty( $_POST['plan'] ) ) {
			wp_send_json_error(
				array(
					'plan'         => '',
					'errorCode'    => 'no_plan_specified',
					'errorMessage' => esc_html__( 'No Plan specified.', 'muhiku-plug' ),
				)
			);
		}

		$addons        = array();
		$template_data = mhk_get_json_file_contents( 'assets/extensions-json/templates/all_templates.json' );

		if ( ! empty( $template_data->templates ) ) {
			foreach ( $template_data->templates as $template ) {
				if ( isset( $_POST['slug'] ) && $template->slug === $_POST['slug'] && in_array( $_POST['plan'], $template->plan, true ) ) {
					$addons = $template->addons;
				}
			}
		}

		$output  = '<div class="muhiku-plug-recommend-addons">';
		$output .= '<p class="desc plugins-info">' . esc_html__( 'This form template requires the following addons.', 'muhiku-plug' ) . '</p>';
		$output .= '<table class="plugins-list-table widefat striped">';
		$output .= '<thead><tr><th scope="col" class="manage-column required-plugins" colspan="2">Required Addons</th></tr></thead><tbody id="the-list">';
		$output .= '</div>';

		$activated = true;
		foreach ( $addons as $slug => $addon ) {
			if ( is_plugin_active( $slug . '/' . $slug . '.php' ) ) {
				$class        = 'active';
				$parent_class = '';
			} elseif ( file_exists( WP_PLUGIN_DIR . '/' . $slug . '/' . $slug . '.php' ) ) {
				$class        = 'activate-now';
				$parent_class = 'inactive';
				$activated    = false;
			} else {
				$class        = 'install-now';
				$parent_class = 'inactive';
				$activated    = false;
			}
			$output .= '<tr class="plugin-card-' . $slug . ' plugin ' . $parent_class . '" data-slug="' . $slug . '" data-plugin="' . $slug . '/' . $slug . '.php" data-name="' . $addon . '">';
			$output .= '<td class="plugin-name">' . $addon . '</td>';
			$output .= '<td class="plugin-status"><span class="' . esc_attr( $class ) . '"></span></td>';
			$output .= '</tr>';
		}
		$output .= '</tbody></table></div>';

		wp_send_json_success(
			array(
				'html'     => $output,
				'activate' => $activated,
			)
		);
	}

	/**
	 * @global WP_Filesystem_Base $wp_filesystem Subclass
	 */
	public static function install_extension() {
		check_ajax_referer( 'updates' );

		if ( empty( $_POST['slug'] ) ) {
			wp_send_json_error(
				array(
					'slug'         => '',
					'errorCode'    => 'no_plugin_specified',
					'errorMessage' => esc_html__( 'No plugin specified.', 'muhiku-plug' ),
				)
			);
		}

		$slug   = sanitize_key( wp_unslash( $_POST['slug'] ) );
		$plugin = plugin_basename( sanitize_text_field( wp_unslash( $_POST['slug'] . '/' . $_POST['slug'] . '.php' ) ) );
		$status = array(
			'install' => 'plugin',
			'slug'    => sanitize_key( wp_unslash( $_POST['slug'] ) ),
		);

		if ( ! current_user_can( 'install_plugins' ) ) {
			$status['errorMessage'] = esc_html__( 'Sorry, you are not allowed to install plugins on this site.', 'muhiku-plug' );
			wp_send_json_error( $status );
		}

		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		if ( file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {
			$plugin_data          = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			$status['plugin']     = $plugin;
			$status['pluginName'] = $plugin_data['Name'];

			if ( current_user_can( 'activate_plugin', $plugin ) && is_plugin_inactive( $plugin ) ) {
				$result = activate_plugin( $plugin );

				if ( is_wp_error( $result ) ) {
					$status['errorCode']    = $result->get_error_code();
					$status['errorMessage'] = $result->get_error_message();
					wp_send_json_error( $status );
				}

				wp_send_json_success( $status );
			}
		}

		$api = json_decode(
			MHK_Updater_Key_API::version(
				array(
					'license'   => get_option( 'muhiku-plug-pro_license_key' ),
					'item_name' => ! empty( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
				)
			)
		);

		if ( is_wp_error( $api ) ) {
			$status['errorMessage'] = $api->get_error_message();
			wp_send_json_error( $status );
		}

		$status['pluginName'] = $api->name;

		$skin     = new WP_Ajax_Upgrader_Skin();
		$upgrader = new Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$status['debug'] = $skin->get_upgrade_messages();
		}

		if ( is_wp_error( $result ) ) {
			$status['errorCode']    = $result->get_error_code();
			$status['errorMessage'] = $result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( is_wp_error( $skin->result ) ) {
			$status['errorCode']    = $skin->result->get_error_code();
			$status['errorMessage'] = $skin->result->get_error_message();
			wp_send_json_error( $status );
		} elseif ( $skin->get_errors()->get_error_code() ) {
			$status['errorMessage'] = $skin->get_error_messages();
			wp_send_json_error( $status );
		} elseif ( is_null( $result ) ) {
			global $wp_filesystem;

			$status['errorCode']    = 'unable_to_connect_to_filesystem';
			$status['errorMessage'] = esc_html__( 'Unable to connect to the filesystem. Please confirm your credentials.', 'muhiku-plug' );

			if ( $wp_filesystem instanceof WP_Filesystem_Base && is_wp_error( $wp_filesystem->errors ) && $wp_filesystem->errors->get_error_code() ) {
				$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
			}

			wp_send_json_error( $status );
		}

		$install_status = install_plugin_install_status( $api );

		if ( current_user_can( 'activate_plugin', $install_status['file'] ) && is_plugin_inactive( $install_status['file'] ) ) {
			if ( isset( $_POST['page'] ) && 'muhiku-plug_page_mhk-builder' === $_POST['page'] ) {
				activate_plugin( $install_status['file'] );
			} else {
				$status['activateUrl'] =
				esc_url_raw(
					add_query_arg(
						array(
							'action'   => 'activate',
							'plugin'   => $install_status['file'],
							'_wpnonce' => wp_create_nonce( 'activate-plugin_' . $install_status['file'] ),
						),
						admin_url( 'admin.php?page=mhk-addons' )
					)
				);
			}
		}

		wp_send_json_success( $status );
	}

	public static function integration_connect() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'muhiku_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		if ( empty( $_POST ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Missing data', 'muhiku-plug' ),
				)
			);
		}

		do_action( 'muhiku_forms_integration_account_connect_' . ( isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '' ), $_POST );
	}

	public static function new_email_add() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'muhiku_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		$connection_id = 'connection_' . uniqid();

		wp_send_json_success(
			array(
				'connection_id' => $connection_id,
			)
		);
	}

	public static function integration_disconnect() {
		check_ajax_referer( 'process-ajax-nonce', 'security' );

		if ( ! current_user_can( 'muhiku_forms_edit_forms' ) ) {
			wp_die( -1 );
		}

		if ( empty( $_POST ) ) {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Missing data', 'muhiku-plug' ),
				)
			);
		}

		do_action( 'muhiku_forms_integration_account_disconnect_' . ( isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '' ), $_POST );

		$connected_accounts = get_option( 'muhiku_forms_integrations', false );

		if ( ! empty( $connected_accounts[ $_POST['source'] ][ $_POST['key'] ] ) ) {
			unset( $connected_accounts[ $_POST['source'] ][ $_POST['key'] ] );
			update_option( 'muhiku_forms_integrations', $connected_accounts );
			wp_send_json_success( array( 'remove' => true ) );
		} else {
			wp_send_json_error(
				array(
					'error' => esc_html__( 'Connection missing', 'muhiku-plug' ),
				)
			);
		}
	}

	public static function deactivation_notice() {
		global $status, $page, $s;

		check_ajax_referer( 'deactivation-notice', 'security' );

		$deactivate_url = esc_url(
			wp_nonce_url(
				add_query_arg(
					array(
						'action'        => 'deactivate',
						'plugin'        => MHK_PLUGIN_BASENAME,
						'plugin_status' => $status,
						'paged'         => $page,
						's'             => $s,
					),
					admin_url( 'plugins.php' )
				),
				'deactivate-plugin_' . MHK_PLUGIN_BASENAME
			)
		);
		wp_send_json(
			array(
				'fragments' => apply_filters(
					'muhiku_forms_deactivation_notice_fragments',
					array(
						'deactivation_notice' => '<tr class="plugin-update-tr active updated" data-slug="muhiku-plug" data-plugin="muhiku-plug/muhiku-plug.php"><td colspan ="3" class="plugin-update colspanchange"><div class="notice inline notice-warning notice-alt"><p>' . $deactivation_notice . '</p></div></td></tr>',
					)
				),
			)
		);
	}

	public static function rated() {
		if ( ! current_user_can( 'manage_muhiku_forms' ) ) {
			wp_die( -1 );
		}
		update_option( 'muhiku_forms_admin_footer_text_rated', 1 );
		wp_die();
	}

	public static function review_dismiss() {
		if ( ! current_user_can( 'manage_muhiku_forms' ) ) {
			wp_die( -1 );
		}
		$review              = get_option( 'muhiku_forms_review', array() );
		$review['time']      = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$review['dismissed'] = true;
		update_option( 'muhiku_forms_review', $review );
		wp_die();
	}

	public static function survey_dismiss() {

		if ( ! current_user_can( 'manage_muhiku_forms' ) ) {
			wp_die( -1 );
		}
		$survey              = get_option( 'muhiku_forms_survey', array() );
		$survey['dismissed'] = true;
		update_option( 'muhiku_forms_survey', $survey );
		wp_die();
	}

	public static function enabled_form() {
		// Run a security check.
		check_ajax_referer( 'muhiku_forms_enabled_form', 'security' );

		$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : 0;
		$enabled = isset( $_POST['enabled'] ) ? absint( $_POST['enabled'] ) : 0;

		if ( ! current_user_can( 'muhiku_forms_edit_form', $form_id ) ) {
			wp_die( -1 );
		}

		$form_data = mhk()->form->get( absint( $form_id ), array( 'content_only' => true ) );

		$form_data['form_enabled'] = $enabled;

		mhk()->form->update( $form_id, $form_data );
	}

	public static function import_form_action() {
		try {
			check_ajax_referer( 'process-import-ajax-nonce', 'security' );
			MHK_Admin_Import_Export::import_form();
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}

	public static function send_test_email() {
		try {
			check_ajax_referer( 'process-ajax-nonce', 'security' );
			$from  = esc_attr( get_bloginfo( 'name', 'display' ) );
			$email = sanitize_email( isset( $_POST['email'] ) ? wp_unslash( $_POST['email'] ) : '' );

			/* translators: %s: from address */
			$subject = 'Muhiku Form: ' . sprintf( esc_html__( 'Test email from %s', 'muhiku-plug' ), $from );
			$header  = "Reply-To: {{from}} \r\n";
			$header .= 'Content-Type: text/html; charset=UTF-8';
			$message = sprintf(
				'%s <br /> %s <br /> %s <br /> %s <br /> %s',
				__( 'Congratulations,', 'muhiku-plug' ),
				__( 'Your test email has been received successfully.', 'muhiku-plug' ),
				__( 'We thank you for trying out Muhiku Plug and joining our mission to make sure you get your emails delivered.', 'muhiku-plug' ),
				__( 'Regards,', 'muhiku-plug' ),
				__( 'Muhiku Plug Team', 'muhiku-plug' )
			);
			$status  = wp_mail( $email, $subject, $message, $header );
			if ( $status ) {
				wp_send_json_success( array( 'message' => __( 'Test email was sent successfully! Please check your inbox to make sure it is delivered.', 'muhiku-plug' ) ) );
			} else {
				wp_send_json_error( array( 'message' => __( 'Test email was unsuccessful! Something went wrong.', 'muhiku-plug' ) ) );
			}
		} catch ( Exception $e ) {
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
				)
			);
		}
	}
}

MHK_AJAX::init();

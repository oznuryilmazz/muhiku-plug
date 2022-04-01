<?php
/**
 * Form Shortcode
 *
 * Used on the show frontend form.
 *
 * @package MuhikuPlug\Shortcodes\Form
 * @version 1.0.0
 * @since   1.3.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Form Shortcode class.
 */
class EVF_Shortcode_Form {

	/**
	 * Contains information for multi-part forms.
	 *
	 * Forms that do not contain parts return false, otherwise returns an array
	 * that contains the number of total parts and part counter used when
	 * displaying part rows.
	 *
	 * @since 1.3.2
	 *
	 * @var array
	 */
	public static $parts = array();

	/**
	 * Hooks in tab.
	 */
	public static function hooks() {
		add_filter( 'amp_skip_post', array( 'EVF_Shortcode_Form', 'amp_skip_post' ) );
		add_action( 'everest_forms_frontend_output_success', 'mhk_print_notices', 10, 2 );
		add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'header' ), 5, 4 );
		add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'fields' ), 10, 3 );
		add_action( 'everest_forms_display_field_before', array( 'EVF_Shortcode_Form', 'wrapper_start' ), 5, 2 );
		add_action( 'everest_forms_display_field_before', array( 'EVF_Shortcode_Form', 'label' ), 15, 2 );
		add_action( 'everest_forms_display_field_before', array( 'EVF_Shortcode_Form', 'description' ), 20, 2 );
		add_action( 'everest_forms_display_field_after', array( 'EVF_Shortcode_Form', 'messages' ), 3, 2 );
		add_action( 'everest_forms_display_field_after', array( 'EVF_Shortcode_Form', 'description' ), 5, 2 );
		add_action( 'everest_forms_display_field_after', array( 'EVF_Shortcode_Form', 'wrapper_end' ), 15, 2 );
		add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'honeypot' ), 15, 3 );
		if ( ! apply_filters( 'everest_forms_recaptcha_disabled', false ) ) {
			add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'recaptcha' ), 20, 3 );
		}
		add_action( 'everest_forms_frontend_output', array( 'EVF_Shortcode_Form', 'footer' ), 25, 3 );

		// reCaptcha Language.
		add_filter( 'everest_forms_frontend_recaptcha_url', array( __CLASS__, 'mhk_recaptcha_language' ), 10, 1 );
	}

	/**
	 * Get the amp-state ID for a given form.
	 *
	 * @param int $form_id Form ID.
	 * @return string State ID.
	 */
	protected static function get_form_amp_state_id( $form_id ) {
		return sprintf( 'mhk_form_state_%d', $form_id );
	}

	/**
	 * Disable AMP if query param is detected.
	 *
	 * This allows the full form to be accessible for Pro users or sites
	 * that do not have SSL.
	 *
	 * @since 1.5.3
	 *
	 * @param bool $skip Skip AMP mode, display full post.
	 *
	 * @return bool
	 */
	public static function amp_skip_post( $skip ) {

		return isset( $_GET['nonamp'] ) ? true : $skip; // phpcs:ignore WordPress.Security.NonceVerification
	}


	/**
	 * Form footer area.
	 *
	 * @param array $form_data   Form data and settings.
	 * @param bool  $title       Whether to display form title.
	 * @param bool  $description Whether to display form description.
	 */
	public static function footer( $form_data, $title, $description ) {
		$form_id         = absint( $form_data['id'] );
		$settings        = isset( $form_data['settings'] ) ? $form_data['settings'] : array();
		$submit          = apply_filters( 'everest_forms_field_submit', isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Submit', 'muhiku-plug' ), $form_data );
		$submit_btn      = mhk_string_translation( $form_data['id'], 'submit_button', $submit );
		$process         = '';
		$classes         = isset( $form_data['settings']['submit_button_class'] ) ? mhk_sanitize_classes( $form_data['settings']['submit_button_class'] ) : '';
		$parts           = ! empty( self::$parts[ $form_id ] ) ? self::$parts[ $form_id ] : array();
		$visible         = ! empty( $parts ) ? 'style="display:none"' : '';
		$attrs           = array( 'aria-live' => 'assertive' );
		$data_attrs      = array();
		$mhk_amp_classes = array();

		// Visibility class.
		$visibility_class = apply_filters( 'everest_forms_field_submit_visibility_class', array(), $parts, $form_data );

		// Check for submit button processing-text.
		if ( ! isset( $settings['submit_button_processing_text'] ) ) {
			$process = 'data-process-text="' . esc_attr__( 'Processing&hellip;', 'muhiku-plug' ) . '"';
		} elseif ( ! empty( $settings['submit_button_processing_text'] ) ) {
			if ( mhk_is_amp() ) {
				$attrs['[text]'] = sprintf(
					'%s.submitting ? %s : %s',
					self::get_form_amp_state_id( $form_id ),
					wp_json_encode( $settings['submit_button_processing_text'], JSON_UNESCAPED_UNICODE ),
					wp_json_encode( $submit, JSON_UNESCAPED_UNICODE )
				);
			} else {
				$process = 'data-process-text="' . esc_attr( mhk_string_translation( $form_data['id'], 'processing_text', $settings['submit_button_processing_text'] ) ) . '"';
			}
		}

		// Submit button area.
		$conditional_id = 'mhk-submit-' . $form_id;
		if ( isset( $form_data['settings']['submit']['connection_1']['conditional_logic_status'] ) && '1' === $form_data['settings']['submit']['connection_1']['conditional_logic_status'] ) {
			$con_rules = array(
				'conditional_option' => isset( $form_data['settings']['submit']['connection_1']['conditional_option'] ) ? $form_data['settings']['submit']['connection_1']['conditional_option'] : '',
				'conditionals'       => isset( $form_data['settings']['submit']['connection_1']['conditionals'] ) ? $form_data['settings']['submit']['connection_1']['conditionals'] : '',
			);
		} else {
			$con_rules = '';
		}

		$conditional_rules = wp_json_encode( $con_rules );

		echo '<div class="mhk-submit-container ' . esc_attr( implode( ' ', $visibility_class ) ) . '" >';

		echo '<input type="hidden" name="everest_forms[id]" value="' . absint( $form_id ) . '">';

		echo '<input type="hidden" name="everest_forms[author]" value="' . absint( get_the_author_meta( 'ID' ) ) . '">';

		if ( is_singular() ) {
			echo '<input type="hidden" name="everest_forms[post_id]" value="' . absint( get_the_ID() ) . '">';
		}

		do_action( 'everest_forms_display_submit_before', $form_data );

		printf(
			"<button type='submit' name='everest_forms[submit]' class='muhiku-plug-submit-button button mhk-submit %s' id='mhk-submit-%d' value='mhk-submit' %s conditional_rules='%s' conditional_id='%s' %s %s>%s</button>",
			esc_attr( $classes ),
			esc_attr( $form_id ),
			! isset( $settings['submit_button_processing_text'] ) ? 'data-process-text="' . esc_attr__( 'Processing&hellip;', 'muhiku-plug' ) . '"' : ( ! empty( $settings['submit_button_processing_text'] ) ? 'data-process-text="' . esc_attr( mhk_string_translation( $form_data['id'], 'processing_text', $settings['submit_button_processing_text'] ) ) . '"' : '' ),
			esc_attr( $conditional_rules ),
			esc_attr( $conditional_id ),
			( ! empty( self::$parts[ $form_id ] ) ? 'style="display:none"' : '' ),
			mhk_html_attributes(
				sprintf( 'mhk-submit-%d', absint( $form_id ) ),
				$mhk_amp_classes,
				$data_attrs,
				$attrs
			),
			esc_html( $submit_btn )
		);

		do_action( 'everest_forms_display_submit_after', $form_data );

		echo '</div>';

		if ( mhk_is_amp() ) {
			printf( '<div submit-success><template type="amp-mustache"><div class=" muhiku-plug-notice muhiku-plug-notice--success {{#redirecting}}mhk-redirection-message{{/redirecting}}">{{{message}}}</div></template></div>' );
			return;
		}
	}

	/**
	 * Message.
	 *
	 * @param array $field Field.
	 * @param array $form_data Form data.
	 */
	public static function messages( $field, $form_data ) {
		$error = $field['properties']['error'];

		if ( empty( $error['value'] ) || is_array( $error['value'] ) ) {
			return;
		}

		printf(
			'<label %s>%s</label>',
			mhk_html_attributes( $error['id'], $error['class'], $error['data'], $error['attr'] ),
			esc_html( $error['value'] )
		);
	}

	/**
	 * Description.
	 *
	 * @param array $field Field.
	 * @param array $form_data Form data.
	 */
	public static function description( $field, $form_data ) {
		$action = current_action();

		$description = $field['properties']['description'];

		// If the description is empty don't proceed.
		if ( empty( $description['value'] ) ) {
			return;
		}

		// Determine positioning.
		if ( 'everest_forms_display_field_before' === $action && 'before' !== $description['position'] ) {
			return;
		}
		if ( 'everest_forms_display_field_after' === $action && 'after' !== $description['position'] ) {
			return;
		}

		if ( 'before' === $description['position'] ) {
			$description['class'][] = 'mhk-field-description-before';
		}

		printf(
			'<div %s>%s</div>',
			mhk_html_attributes( $description['id'], $description['class'], $description['data'], $description['attr'] ),
			wp_kses_post( mhk_string_translation( $form_data['id'], $field['id'], $description['value'], '-description' ) )
		);
	}

	/**
	 * Label.
	 *
	 * @param array $field Field.
	 * @param array $form_data Form data.
	 */
	public static function label( $field, $form_data ) {

		$label = $field['properties']['label'];
		// If the label is empty or disabled don't proceed.
		if ( empty( $label['value'] ) || $label['disabled'] ) {
			return;
		}

		$required    = $label['required'] ? apply_filters( 'everest_forms_field_required_label', '<abbr class="required" title="' . esc_attr__( 'Required', 'muhiku-plug' ) . '">' . apply_filters( 'everest_form_get_required_type', '*', $field, $form_data ) . '</abbr>' ) : '';
		$custom_tags = apply_filters( 'everest_forms_field_custom_tags', false, $field, $form_data );

		printf(
			'<label %s><span class="mhk-label">%s</span> %s</label>',
			mhk_html_attributes( $label['id'], $label['class'], $label['data'], $label['attr'] ),
			wp_kses(
				mhk_string_translation(
					$form_data['id'],
					$field['id'],
					wp_kses(
						$label['value'],
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
					)
				),
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
			),
			wp_kses_post( $required ),
			wp_kses_post( $custom_tags )
		);
	}

	/**
	 * Wrapper end.
	 *
	 * @param array $field Field.
	 * @param array $form_data Form data.
	 */
	public static function wrapper_end( $field, $form_data ) {
		echo '</div>';
	}

	/**
	 * Wrapper start.
	 *
	 * @param array $field Field.
	 * @param array $form_data Form data.
	 */
	public static function wrapper_start( $field, $form_data ) {
		$container                     = $field['properties']['container'];
		$container['data']['field-id'] = esc_attr( $field['id'] );
		printf(
			'<div %s>',
			mhk_html_attributes( $container['id'], $container['class'], $container['data'], $container['attr'] )
		);
	}

	/**
	 * Form header for displaying form title and description if enabled.
	 *
	 * @param array $form_data   Form data and settings.
	 * @param bool  $title       Whether to display form title.
	 * @param bool  $description Whether to display form description.
	 * @param array $errors      List of all errors during form submission.
	 */
	public static function header( $form_data, $title, $description, $errors ) {
		$settings = isset( $form_data['settings'] ) ? $form_data['settings'] : array();

		// Check if title and/or description is enabled.
		if ( true === $title || true === $description ) {
			echo '<div class="mhk-title-container">';

			if ( true === $title && ! empty( $settings['form_title'] ) ) {
				echo '<div class="muhiku-plug--title">' . esc_html( mhk_string_translation( $form_data['id'], 'form_title', $settings['form_title'] ) ) . '</div>';
			}

			if ( true === $description && ! empty( $settings['form_description'] ) ) {
				echo '<div class="muhiku-plug--description">' . esc_textarea( mhk_string_translation( $form_data['id'], 'form_description', $settings['form_description'] ) ) . '</div>';
			}

			echo '</div>';
		}

		// Output header errors if they exist.
		if ( ! empty( $errors['header'] ) ) {
			mhk_add_notice( $errors['header'], 'error' );
		}
	}

	/**
	 * Form field area.
	 *
	 * @param array $form_data   Form data and settings.
	 * @param bool  $title       Whether to display form title.
	 * @param bool  $description Whether to display form description.
	 */
	public static function fields( $form_data, $title, $description ) {
		$structure = isset( $form_data['structure'] ) ? $form_data['structure'] : array();

		// Bail if empty form fields.
		if ( empty( $form_data['form_fields'] ) ) {
			return;
		}

		// Form fields area.
		echo '<div class="mhk-field-container">';

		wp_nonce_field( 'muhiku-plug_process_submit', '_wpnonce' . $form_data['id'] );

		/**
		 * Hook: everest_forms_display_fields_before.
		 *
		 * @hooked MuhikuPlug_MultiPart::display_fields_before() Multi-Part markup open.
		 */
		do_action( 'everest_forms_display_fields_before', $form_data );

		foreach ( $structure as $row_key => $row ) {
			/**
			 * Hook: everest_forms_display_repeater_fields.
			 *
			 * @hooked EVF_Repeater_Fields->display_repeater_fields() Display Repeater Fields.
			 */
			$is_repeater = apply_filters( 'everest_forms_display_repeater_fields', false, $row, $form_data, true );

			/**
			 * Hook: everest_forms_display_row_before.
			 */
			do_action( 'everest_forms_display_row_before', $row_key, $form_data );

			echo '<div class="mhk-frontend-row" data-row="' . esc_attr( $row_key ) . '"' . esc_attr( $is_repeater ) . '>'; // @codingStandardsIgnoreLine

			foreach ( $row as $grid_key => $grid ) {
				$number_of_grid = count( $row );

				echo '<div class="mhk-frontend-grid mhk-grid-' . absint( $number_of_grid ) . '" data-grid="' . esc_attr( $grid_key ) . '">';

				if ( ! is_array( $grid ) ) {
					$grid = array();
				}

				foreach ( $grid as $field_key ) {
					$field = isset( $form_data['form_fields'][ $field_key ] ) ? $form_data['form_fields'][ $field_key ] : array();
					$field = apply_filters( 'everest_forms_field_data', $field, $form_data );

					if ( empty( $field ) || in_array( $field['type'], mhk()->form_fields->get_pro_form_field_types(), true ) ) {
						continue;
					}

					$should_display_field = apply_filters( "everest_forms_should_display_field_{$field['type']}", true, $field, $form_data );

					if ( true !== $should_display_field ) {
						continue;
					}

					// Get field attributes.
					$attributes = self::get_field_attributes( $field, $form_data );

					// Get field properties.
					$properties = self::get_field_properties( $field, $form_data, $attributes );

					// Add properties to the field so it's available everywhere.
					$field['properties'] = $properties;

					do_action( 'everest_forms_display_field_before', $field, $form_data );

					do_action( "everest_forms_display_field_{$field['type']}", $field, $attributes, $form_data );

					do_action( 'everest_forms_display_field_after', $field, $form_data );
				}

				echo '</div>';
			}

			/**
			 * Hook: everest_forms_add_remove_buttons.
			 *
			 * @hooked EVF_Repeater_Fields->add_remove_buttons() Show Add and Remove buttons.
			 */
			do_action( 'everest_forms_add_remove_buttons', $row, $form_data, $is_repeater );

			echo '</div>';

			/**
			 * Hook: everest_forms_display_row_after.
			 *
			 * @hooked MuhikuPlug_MultiPart::display_row_after() Multi-Part markup (close previous part, open next).
			 */
			do_action( 'everest_forms_display_row_after', $row_key, $form_data );
		}

		/**
		 * Hook: everest_forms_display_fields_after.
		 *
		 * @hooked MuhikuPlug_MultiPart::display_fields_after() Multi-Part markup open.
		 */
		do_action( 'everest_forms_display_fields_after', $form_data );

		echo '</div>';
	}

	/**
	 * Anti-spam honeypot output if configured.
	 *
	 * @since 1.4.9
	 * @param array $form_data   Form data and settings.
	 */
	public static function honeypot( $form_data ) {
		$names = array( 'Name', 'Phone', 'Comment', 'Message', 'Email', 'Website' );

		// Output the honeypot container.
		if ( isset( $form_data['settings']['honeypot'] ) && '1' === $form_data['settings']['honeypot'] ) {
			echo '<div class="mhk-honeypot-container mhk-field-hp">';

			echo '<label for="mhk-' . esc_attr( $form_data['id'] ) . '-field-hp" class="mhk-field-label">' . esc_attr( $names[ array_rand( $names ) ] ) . '</label>';

			echo '<input type="text" name="everest_forms[hp]" id="mhk-' . esc_attr( $form_data['id'] ) . '-field-hp" class="input-text">';

			echo '</div>';
		}
	}

	/**
	 * Google reCAPTCHA output if configured.
	 *
	 * @param array $form_data Form data and settings.
	 */
	public static function recaptcha( $form_data ) {
		$recaptcha_type      = get_option( 'everest_forms_recaptcha_type', 'v2' );
		$invisible_recaptcha = get_option( 'everest_forms_recaptcha_v2_invisible', 'no' );

		if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_secret_key' );
		} elseif ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v2_invisible_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v2_invisible_secret_key' );
		} elseif ( 'v3' === $recaptcha_type ) {
			$site_key   = get_option( 'everest_forms_recaptcha_v3_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_v3_secret_key' );
		} elseif ( 'hcaptcha' === $recaptcha_type ) {
			$site_key   = get_option( 'everest_forms_recaptcha_hcaptcha_site_key' );
			$secret_key = get_option( 'everest_forms_recaptcha_hcaptcha_secret_key' );
		}

		if ( ! $site_key || ! $secret_key ) {
			return;
		}
		// Check that the CAPTCHA is configured for the specific form.
		if (
		! isset( $form_data['settings']['recaptcha_support'] ) ||
		'1' !== $form_data['settings']['recaptcha_support']
		) {
			return;
		}
		if ( mhk_is_amp() ) {
			if ( 'v3' === $recaptcha_type ) {
				printf(
					'<amp-recaptcha-input name="everest_forms[recaptcha]" data-sitekey="%s" data-action="%s" layout="nodisplay"></amp-recaptcha-input>',
					esc_attr( $site_key ),
					esc_attr( 'mhk_' . $form_data['id'] )
				);
			}
			return; // Only v3 is supported in AMP.
		}

		if ( isset( $form_data['settings']['recaptcha_support'] ) && '1' === $form_data['settings']['recaptcha_support'] ) {
			$form_id = isset( $form_data['id'] ) ? absint( $form_data['id'] ) : 0;
			$visible = ! empty( self::$parts[ $form_id ] ) ? 'style="display:none;"' : '';
			$data    = apply_filters(
				'everest_forms_frontend_recaptcha',
				array(
					'sitekey' => trim( sanitize_text_field( $site_key ) ),
				),
				$form_data
			);

			// Load reCAPTCHA support if form supports it.
			if ( $site_key && $secret_key ) {
				if ( 'v2' === $recaptcha_type ) {
					$recaptcha_api = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://www.google.com/recaptcha/api.js?onload=EVFRecaptchaLoad&render=explicit', $recaptcha_type, $form_id );

					if ( 'yes' === $invisible_recaptcha ) {
						$data['size']     = 'invisible';
						$recaptcha_inline = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID = grecaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);   el.closest("form").querySelector("button[type=submit]").recaptchaID = recaptchaID;});};
						var EVFRecaptchaCallback = function (el) {
							var $form = el.closest("form");
							if( typeof jQuery !==  "undefined" ){
								if( "1" === jQuery( $form ).attr( "data-ajax_submission" ) ) {
									el.closest( "form" ).querySelector( "button[type=submit]" ).recaptchaID = "verified";
									jQuery( $form ).find( ".mhk-submit" ).trigger( "click" );
								} else {
									$form.submit();
								}
								grecaptcha.reset();
							}
						};
						';
					} else {
						$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID =  grecaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);jQuery(el).attr( "data-recaptcha-id", recaptchaID);});};';
						$recaptcha_inline .= 'var EVFRecaptchaCallback = function(el){jQuery(el).parent().find(".mhk-recaptcha-hidden").val("1").trigger("change").valid();};';
					}
				} elseif ( 'v3' === $recaptcha_type ) {
					$recaptcha_api     = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, $recaptcha_type, $form_id );
					$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){grecaptcha.execute("' . esc_html( $site_key ) . '",{action:"everest_form"}).then(function(token){var f=document.getElementsByName("everest_forms[recaptcha]");for(var i=0;i<f.length;i++){f[i].value = token;}});};grecaptcha.ready(EVFRecaptchaLoad);setInterval(EVFRecaptchaLoad, 110000);';
					$recaptcha_inline .= 'grecaptcha.ready(function(){grecaptcha.execute("' . esc_html( $site_key ) . '",{action:"everest_form"}).then(function(token){var f=document.getElementsByName("everest_forms[recaptcha]");for(var i=0;i<f.length;i++){f[i].value = token;}});});';
				} elseif ( 'hcaptcha' === $recaptcha_type ) {
					$recaptcha_api     = apply_filters( 'everest_forms_frontend_recaptcha_url', 'https://hcaptcha.com/1/api.js??onload=EVFRecaptchaLoad&render=explicit', $recaptcha_type, $form_id );
					$recaptcha_inline  = 'var EVFRecaptchaLoad = function(){jQuery(".g-recaptcha").each(function(index, el){var recaptchaID =  hcaptcha.render(el,{callback:function(){EVFRecaptchaCallback(el);}},true);jQuery(el).attr( "data-recaptcha-id", recaptchaID);});};';
					$recaptcha_inline .= 'var EVFRecaptchaCallback = function(el){jQuery(el).parent().find(".mhk-recaptcha-hidden").val("1").trigger("change").valid();};';
				}

				// Enqueue reCaptcha scripts.
				wp_enqueue_script(
					'mhk-recaptcha',
					$recaptcha_api,
					'v3' === $recaptcha_type ? array() : array( 'jquery' ),
					'v3' === $recaptcha_type ? '3.0.0' : '2.0.0',
					true
				);

				// Load reCaptcha callback once.
				static $count = 1;
				if ( 1 === $count ) {
						wp_add_inline_script( 'mhk-recaptcha', $recaptcha_inline );
						$count++;
				}

				// Output the reCAPTCHA container.
				$class = ( 'v3' === $recaptcha_type || ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) ) ? 'recaptcha-hidden' : '';
				echo '<div class="mhk-recaptcha-container ' . esc_attr( $class ) . '" style="display:' . ( ! empty( self::$parts[ $form_id ] ) ? 'none' : 'block' ) . '">';

				if ( 'v2' === $recaptcha_type || 'hcaptcha' === $recaptcha_type ) {
					echo '<div ' . mhk_html_attributes( '', array( 'g-recaptcha' ), $data ) . '></div>';

					if ( 'hcaptcha' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
						echo '<input type="text" name="g-recaptcha-hidden" class="mhk-recaptcha-hidden" style="position:absolute!important;clip:rect(0,0,0,0)!important;height:1px!important;width:1px!important;border:0!important;overflow:hidden!important;padding:0!important;margin:0!important;" required>';
					}
				} else {
					echo '<input type="hidden" name="everest_forms[recaptcha]" value="">';
				}

				echo '</div>';
			}
		}
	}

	/**
	 * Get field attributes.
	 *
	 * @param array $field Field.
	 * @param array $form_data Form data.
	 *
	 * @return array
	 */
	private static function get_field_attributes( $field, $form_data ) {
		$form_id    = absint( $form_data['id'] );
		$field_id   = esc_attr( $field['id'] );
		$attributes = array(
			'field_class'       => array( 'mhk-field', 'mhk-field-' . sanitize_html_class( $field['type'] ), 'form-row' ),
			'field_id'          => array( sprintf( 'mhk-%d-field_%s-container', $form_id, $field_id ) ),
			'field_style'       => '',
			'label_class'       => array( 'mhk-field-label' ),
			'label_id'          => '',
			'description_class' => array( 'mhk-field-description' ),
			'description_id'    => array(),
			'input_id'          => array( sprintf( 'mhk-%d-field_%s', $form_id, $field_id ) ),
			'input_class'       => array(),
			'input_data'        => array(),
		);

		// Check user field defined classes.
		if ( ! empty( $field['css'] ) ) {
			$attributes['field_class'] = array_merge( $attributes['field_class'], mhk_sanitize_classes( $field['css'], true ) );
		}

		// Check for input column layouts.
		if ( ! empty( $field['input_columns'] ) ) {
			if ( 'inline' === $field['input_columns'] ) {
				$attributes['field_class'][] = 'muhiku-plug-list-inline';
			} elseif ( '' !== $field['input_columns'] ) {
				$attributes['field_class'][] = 'muhiku-plug-list-' . $field['input_columns'] . '-columns';
			}
		}

		// Input class.
		if ( ! in_array( $field['type'], array( 'checkbox', 'radio', 'payment-checkbox', 'payment-multiple' ), true ) ) {
			$attributes['input_class'][] = 'input-text';
		}

		// Check label visibility.
		if ( ! empty( $field['label_hide'] ) ) {
			$attributes['label_class'][] = 'mhk-label-hide';
		}

		// Check size.
		if ( ! empty( $field['size'] ) ) {
			$attributes['input_class'][] = 'mhk-field-' . sanitize_html_class( $field['size'] );
		}

		// Check if required.
		if ( ! empty( $field['required'] ) ) {
			$attributes['field_class'][] = 'validate-required';
		}

		// Check if extra validation required.
		if ( in_array( $field['type'], array( 'email', 'phone' ), true ) ) {
			$attributes['field_class'][] = 'validate-' . esc_attr( $field['type'] );
		}

		// Check if there are errors.
		if ( isset( mhk()->task->errors[ $form_id ][ $field_id ] ) ) {
			$attributes['input_class'][] = 'mhk-error';
			$attributes['field_class'][] = 'muhiku-plug-invalid';
		}

		// This filter is deprecated, filter the properties (below) instead.
		$attributes = apply_filters( 'mhk_field_atts', $attributes, $field, $form_data );

		return $attributes;
	}

	/**
	 * Return base properties for a specific field.
	 *
	 * @param array $field      Field data and settings.
	 * @param array $form_data  Form data and settings.
	 * @param array $attributes List of field attributes.
	 *
	 * @return array
	 */
	public static function get_field_properties( $field, $form_data, $attributes = array() ) {
		if ( empty( $attributes ) ) {
			$attributes = self::get_field_attributes( $field, $form_data );
		}

		// This filter is for backwards compatibility purposes.
		$types = array( 'text', 'textarea', 'number', 'email', 'hidden', 'url', 'html', 'title', 'password', 'phone', 'address', 'checkbox', 'radio', 'select' );
		if ( in_array( $field['type'], $types, true ) ) {
			$field = apply_filters( "everest_forms_{$field['type']}_field_display", $field, $attributes, $form_data );
		}

		$form_id  = absint( $form_data['id'] );
		$field_id = sanitize_text_field( $field['id'] );

		// Field container data.
		$container_data = array();

		// Embed required-field-message to the container if the field is required.
		if ( isset( $field['required'] ) && ( '1' === $field['required'] || true === $field['required'] ) ) {
			$has_sub_fields     = false;
			$sub_field_messages = array();

			$required_validation = get_option( 'everest_forms_required_validation' );
			if ( in_array( $field['type'], array( 'number', 'email', 'url', 'phone' ), true ) ) {
				$required_validation = get_option( 'everest_forms_' . $field['type'] . '_validation' );
			}

			if ( 'likert' === $field['type'] ) {
				$has_sub_fields = true;
				$likert_rows    = isset( $field['likert_rows'] ) ? $field['likert_rows'] : array();
				$row_keys       = array();
				foreach ( $likert_rows as $row_key => $row_label ) {
					$row_keys[]                     = $row_key;
					$row_slug                       = 'required-field-message-' . $row_key;
					$sub_field_messages[ $row_key ] = isset( $field[ $row_slug ] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field[ $row_slug ], '-' . $row_slug ) : $required_validation;
				}
				$container_data['row-keys'] = wp_json_encode( $row_keys );
			} elseif ( 'address' === $field['type'] ) {
				$has_sub_fields     = true;
				$sub_field_messages = array(
					'address1' => isset( $field['required-field-message-address1'] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field['required-field-message-address1'], '-required-field-message-address1' ) : '',
					'city'     => isset( $field['required-field-message-city'] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field['required-field-message-city'], '-required-field-message-city' ) : '',
					'state'    => isset( $field['required-field-message-state'] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field['required-field-message-state'], '-required-field-message-state' ) : '',
					'postal'   => isset( $field['required-field-message-postal'] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field['required-field-message-postal'], '-required-field-message-postal' ) : '',
					'country'  => isset( $field['required-field-message-country'] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field['required-field-message-country'], '-required-field-message-country' ) : '',
				);
			}

			if ( true === $has_sub_fields ) {
				foreach ( $sub_field_messages as $sub_field_type => $error_message ) {
					$container_data[ 'required-field-message-' . $sub_field_type ] = $error_message;
				}
			} else {
				$container_data['required-field-message'] = isset( $field['required-field-message'] ) && '' !== $field['required-field-message'] ? mhk_string_translation( $form_data['id'], $field['id'], $field['required-field-message'], '-required-field-message' ) : $required_validation;
			}
		}
		$errors     = isset( mhk()->task->errors[ $form_id ][ $field_id ] ) ? mhk()->task->errors[ $form_id ][ $field_id ] : '';
		$defaults   = isset( $_POST['everest_forms']['form_fields'][ $field_id ] ) && ( ! is_array( $_POST['everest_forms']['form_fields'][ $field_id ] ) && ! empty( $_POST['everest_forms']['form_fields'][ $field_id ] ) ) ? $_POST['everest_forms']['form_fields'][ $field_id ] : ''; // @codingStandardsIgnoreLine
		$properties = apply_filters(
			'everest_forms_field_properties_' . $field['type'],
			array(
				'container'   => array(
					'attr'  => array(
						'style' => $attributes['field_style'],
					),
					'class' => $attributes['field_class'],
					'data'  => $container_data,
					'id'    => implode( '', array_slice( $attributes['field_id'], 0 ) ),
				),
				'label'       => array(
					'attr'     => array(
						'for' => sprintf( 'mhk-%d-field_%s', $form_id, $field_id ),
					),
					'class'    => $attributes['label_class'],
					'data'     => array(),
					'disabled' => ! empty( $field['label_disable'] ) ? true : false,
					'hidden'   => ! empty( $field['label_hide'] ) ? true : false,
					'id'       => $attributes['label_id'],
					'required' => ! empty( $field['required'] ) ? true : false,
					'value'    => ! empty( $field['label'] ) ? $field['label'] : '',
				),
				'inputs'      => array(
					'primary' => array(
						'attr'     => array(
							'name'        => "everest_forms[form_fields][{$field_id}]",
							'value'       => isset( $field['default_value'] ) ? apply_filters( 'everest_forms_process_smart_tags', $field['default_value'], $form_data ) : $defaults,
							'placeholder' => isset( $field['placeholder'] ) ? mhk_string_translation( $form_data['id'], $field['id'], $field['placeholder'], '-placeholder' ) : '',
						),
						'class'    => $attributes['input_class'],
						'data'     => $attributes['input_data'],
						'id'       => implode( array_slice( $attributes['input_id'], 0 ) ),
						'required' => ! empty( $field['required'] ) ? 'required' : '',
					),
				),
				'error'       => array(
					'attr'  => array(
						'for' => sprintf( 'mhk-%d-field_%s', $form_id, $field_id ),
					),
					'class' => array( 'mhk-error' ),
					'data'  => array(),
					'id'    => '',
					'value' => ! empty( $errors ) ? $errors : '',
				),
				'description' => array(
					'attr'     => array(),
					'class'    => $attributes['description_class'],
					'data'     => array(),
					'id'       => implode( '', array_slice( $attributes['description_id'], 0 ) ),
					'position' => 'after',
					'value'    => ! empty( $field['description'] ) ? $field['description'] : '',
				),
			),
			$field,
			$form_data
		);

		return apply_filters( 'everest_forms_field_properties', $properties, $field, $form_data );
	}

	/**
	 * Output the shortcode.
	 *
	 * @param array $atts Attributes.
	 */
	public static function output( $atts ) {
		wp_enqueue_script( 'muhiku-plug' );

		// Load jQuery flatpickr libraries. https://github.com/flatpickr/flatpickr.
		if ( mhk_is_field_exists( $atts['id'], 'date-time' ) ) {
			wp_enqueue_style( 'flatpickr' );
			wp_enqueue_script( 'flatpickr' );
		}

		// Load jQuery mailcheck library - https://github.com/mailcheck/mailcheck.
		if ( mhk_is_field_exists( $atts['id'], 'email' ) && (bool) apply_filters( 'everest_forms_mailcheck_enabled', true ) ) {
			wp_enqueue_script( 'mailcheck' );
		}

		$atts = shortcode_atts(
			array(
				'id'          => false,
				'type'        => false,
				'size'        => false,
				'text'        => false,
				'title'       => false,
				'description' => false,
			),
			$atts,
			'output'
		);

		// Scripts load action.
		do_action( 'everest_forms_shortcode_scripts', $atts );

		ob_start();
		self::view( $atts );
		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Form view.
	 *
	 * @param array $atts Attributes.
	 */
	private static function view( $atts ) {
		$id          = isset( $atts['id'] ) ? $atts['id'] : false;
		$title       = isset( $atts['title'] ) ? $atts['title'] : false;
		$description = isset( $atts['description'] ) ? $atts['description'] : false;
		$popup_type  = isset( $atts['type'] ) ? $atts['type'] : false;
		$popup_text  = isset( $atts['text'] ) ? $atts['text'] : false;
		if ( empty( $id ) ) {
			return;
		}

		// Grab the form data, if not found then we bail.
		$form = mhk()->form->get( (int) $id );

		if ( empty( $form ) || 'publish' !== $form->post_status ) {
			return;
		}

		// Basic form information.
		$form_data            = apply_filters( 'everest_forms_frontend_form_data', mhk_decode( $form->post_content ) );
		$form_id              = absint( $form->ID );
		$settings             = $form_data['settings'];
		$action               = esc_url_raw( remove_query_arg( 'mhk-forms' ) );
		$title                = filter_var( $title, FILTER_VALIDATE_BOOLEAN );
		$description          = filter_var( $description, FILTER_VALIDATE_BOOLEAN );
		$errors               = isset( mhk()->task->errors[ $form_id ] ) ? mhk()->task->errors[ $form_id ] : array();
		$form_enabled         = isset( $form_data['form_enabled'] ) ? absint( $form_data['form_enabled'] ) : 1;
		$disable_message      = isset( $form_data['settings']['form_disable_message'] ) ? mhk_string_translation( $form_data['id'], 'form_disable_message', $form_data['settings']['form_disable_message'] ) : __( 'This form is disabled.', 'muhiku-plug' );
		$ajax_form_submission = isset( $settings['ajax_form_submission'] ) ? $settings['ajax_form_submission'] : 0;

		if ( 0 !== $ajax_form_submission ) {
			wp_enqueue_script( 'muhiku-plug-ajax-submission' );
		}

		// If the form is disabled or does not contain any fields do not proceed.
		if ( empty( $form_data['form_fields'] ) ) {
			echo '<!-- Muhiku Plug: no fields, form hidden -->';
			return;
		} elseif ( 1 !== $form_enabled ) {
			if ( ! empty( $disable_message ) ) {
				printf( '<p class="everst-forms-form-disable-notice muhiku-plug-notice muhiku-plug-notice--info">%s</p>', esc_textarea( $disable_message ) );
			}
			return;
		}

		// We need to stop output processing in case we are on AMP page.
		if ( mhk_is_amp( false ) && ( ! current_theme_supports( 'amp' ) || apply_filters( 'mhkforms_amp_pro', class_exists( 'MuhikuPlug_Pro' ) ) || ! is_ssl() || ! defined( 'AMP__VERSION' ) || version_compare( AMP__VERSION, '1.2', '<' ) ) ) {

			$full_page_url = home_url( add_query_arg( 'nonamp', '1' ) . '#mhkforms-' . absint( $form->ID ) );

			/**
			 * Allow modifying the text or url for the full page on the AMP pages.
			 *
			 * @since 1.4.1.1
			 * @since 1.7.1 Added $form_id, $full_page_url, and $form_data arguments.
			 *
			 * @param int   $form_id   Form id.
			 * @param array $form_data Form data and settings.
			 *
			 * @return string
			 */
			$text = (string) apply_filters(
				'mhk_frontend_shortcode_amp_text',
				sprintf( /* translators: %s - URL to a non-amp version of a page with the form. */
					__( '<a href="%s">Go to the full page</a> to view and submit the form.', 'muhiku-plug' ),
					esc_url( $full_page_url )
				),
				$form_id,
				$full_page_url,
				$form_data
			);

			printf(
				'<p class="mhk-shortcode-amp-text">%s</p>',
				wp_kses_post( $text )
			);

			return;
		}

		// Before output hook.
		do_action( 'everest_forms_frontend_output_before', $form_data, $form );

		// Check for return hash.
		if (
		! empty( $_GET['everest_forms_return'] ) // phpcs:ignore WordPress.Security.NonceVerification
		&& mhk()->task->is_valid_hash
		&& absint( mhk()->task->form_data['id'] ) === $form_id
		) {
			// Output success message if no redirection happened.
			if ( 'same' === $form_data['settings']['redirect_to'] ) {
				mhk_add_notice( isset( $form_data['settings']['successful_form_submission_message'] ) ? $form_data['settings']['successful_form_submission_message'] : esc_html__( 'Thanks for contacting us! We will be in touch with you shortly.', 'muhiku-plug' ), 'success' );
			}

			do_action( 'everest_forms_frontend_output_success', mhk()->task->form_data, mhk()->task->form_fields, mhk()->task->entry_id );
			return;
		}

		$success = apply_filters( 'everest_forms_success', false, $form_id );
		if ( $success && ! empty( $form_data ) ) {
			do_action( 'everest_forms_frontend_output_success', $form_data );
			return;
		}

		// Allow filter to return early if some condition is not meet.
		if ( ! apply_filters( 'everest_forms_frontend_load', true, $form_data ) ) {
			do_action( 'everest_forms_frontend_not_loaded', $form_data, $form );
			return;
		}

		/**
		 * BW compatiable for multi-parts form.
		 *
		 * @todo Remove in Major EVF version 1.6.0
		 */
		if ( defined( 'EVF_MULTI_PART_PLUGIN_FILE' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugin_data = get_plugin_data( EVF_MULTI_PART_PLUGIN_FILE, false, false );

			if ( version_compare( $plugin_data['Version'], '1.3.0', '<' ) ) {
				$settings_defaults = array(
					'indicator'       => 'progress',
					'indicator_color' => '#7e3bd0',
					'nav_align'       => 'center',
				);

				if ( isset( $form_data['settings']['enable_multi_part'] ) && mhk_string_to_bool( $form_data['settings']['enable_multi_part'] ) ) {
					$settings = isset( $form_data['settings']['multi_part'] ) ? $form_data['settings']['multi_part'] : array();

					if ( ! empty( $form_data['multi_part'] ) ) {
						self::$parts = array(
							'total'    => count( $form_data['multi_part'] ),
							'current'  => 1,
							'parts'    => array_values( $form_data['multi_part'] ),
							'settings' => wp_parse_args( $settings, $settings_defaults ),
						);
					}
				} else {
					self::$parts = array(
						'total'    => '',
						'current'  => '',
						'parts'    => array(),
						'settings' => $settings_defaults,
					);
				}
			}
		}

		// Allow Multi-Part to be customized.
		$parts                   = ! empty( self::$parts[ $form_id ] ) ? self::$parts[ $form_id ] : array();
		self::$parts[ $form_id ] = apply_filters( 'everest_forms_parts_data', $parts, $form_data, $form_id );

		// Allow final action to be customized.
		$action = apply_filters( 'everest_forms_frontend_form_action', $action, $form_data );

		// Allow form container classes to be filtered and user defined classes.
		$classes = apply_filters( 'everest_forms_frontend_container_class', array(), $form_data );
		if ( ! empty( $settings['form_class'] ) ) {
			$classes = array_merge( $classes, explode( ' ', $settings['form_class'] ) );
		}
		if ( ! empty( $settings['layout_class'] ) ) {
			$classes = array_merge( $classes, explode( ' ', $settings['layout_class'] ) );
		}
		$classes = mhk_sanitize_classes( $classes, true );

		$form_atts = array(
			'id'    => sprintf( 'mhk-form-%d', absint( $form_id ) ),
			'class' => array( 'everest-form' ),
			'data'  => array(
				'formid'          => absint( $form_id ),
				'ajax_submission' => $ajax_form_submission,
			),
			'atts'  => array(
				'method'  => 'post',
				'enctype' => 'multipart/form-data',
				'action'  => esc_url( $action ),
			),
		);

		if ( mhk_is_amp() ) {

			// Set submitting state.
			if ( ! isset( $form_atts['atts']['on'] ) ) {
				$form_atts['atts']['on'] = '';
			} else {
				$form_atts['atts']['on'] .= ';';
			}
			$form_atts['atts']['on'] .= sprintf(
				'submit:AMP.setState( %1$s ); submit-success:AMP.setState( %2$s ); submit-error:AMP.setState( %2$s );',
				wp_json_encode(
					array(
						self::get_form_amp_state_id( $form_id ) => array(
							'submitting' => true,
						),
					)
				),
				wp_json_encode(
					array(
						self::get_form_amp_state_id( $form_id ) => array(
							'submitting' => false,
						),
					)
				)
			);

			// Upgrade the form to be an amp-form to avoid sanitizer conversion.
			if ( isset( $form_atts['atts']['action'] ) ) {
				$form_atts['atts']['action-xhr'] = $form_atts['atts']['action'];
				unset( $form_atts['atts']['action'] );

				$form_atts['atts']['verify-xhr'] = $form_atts['atts']['action-xhr'];
			}
		}

		$form_atts = apply_filters( 'everest_forms_frontend_form_atts', $form_atts, $form_data );
		// Begin to build the output.
		do_action( 'everest_forms_frontend_output_container_before', $form_data, $form );

		printf( '<div class="mhk-container %s" id="mhk-%d">', esc_attr( $classes ), absint( $form_id ) );

		do_action( 'everest_forms_frontend_output_form_before', $form_data, $form, $errors );
		if ( isset( $atts['type'] ) && 'popup-button' === $popup_type ) {
			printf( "<button class='muhiku-plug-modal-link muhiku-plug-modal-link-%s'>%s</button>", esc_attr( $atts['id'] ), esc_html( $popup_text ) );
			do_action( 'everest_form_popup', $atts );
		} elseif ( isset( $atts['type'] ) && 'popup-link' === $popup_type ) {
			printf( "<a href='javascript:void(0);' class='muhiku-plug-modal-link muhiku-plug-modal-link-%s'>%s</a>", esc_attr( $atts['id'] ), esc_html( $popup_text ) );
			do_action( 'everest_form_popup', $atts );
		} elseif ( isset( $atts['type'] ) && 'popup' === $popup_type ) {
			do_action( 'everest_form_popup', $atts );
		} else {
			echo '<form ' . mhk_html_attributes( $form_atts['id'], $form_atts['class'], $form_atts['data'], $form_atts['atts'] ) . '>';
			if ( mhk_is_amp() ) {
				$state = array(
					'submitting' => false,
				);
				printf(
					'<amp-state id="%s"><script type="application/json">%s</script></amp-state>',
					self::get_form_amp_state_id( $form_id ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					wp_json_encode( $state )
				);
			}
			do_action( 'everest_forms_frontend_output', $form_data, $title, $description, $errors );

			echo '</form>';
		}

		do_action( 'everest_forms_frontend_output_form_after', $form_data, $form );

		echo '</div><!-- .mhk-container -->';

		// After output hook.
		do_action( 'everest_forms_frontend_output_after', $form_data, $form );

		// Debug information.
		if ( is_super_admin() ) {
			mhk_debug_data( $form_data );
		}
	}

	/**
	 * ReCaptcha Langauge.
	 *
	 * @param url $url  Recaptcha URL.
	 *
	 *  @return $url
	 */
	public static function mhk_recaptcha_language( $url ) {

		return esc_url_raw( add_query_arg( array( 'hl' => get_option( 'everest_forms_recaptcha_recaptcha_language', 'en-GB' ) ), $url ) );

	}
}

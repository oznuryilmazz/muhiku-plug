<?php
/**
 * @package MuhikuPlug
 */

defined( 'ABSPATH' ) || exit;

class MHK_Form_Task {

	/**
	 * @var array
	 */
	public $errors;

	/**
	 * @var array
	 */
	public $form_fields;

	/**
	 * @var int
	 */
	public $entry_id = 0;

	/**
	 *
	 * @var array
	 */
	public $form_data = array();

	/**
	 * Is hash validation?
	 *
	 * @var 1.7.4
	 */
	public $is_valid_hash = false;

	public function __construct() {
		add_action( 'wp', array( $this, 'listen_task' ) );
		add_filter( 'muhiku_forms_field_properties', array( $this, 'load_previous_field_value' ), 99, 3 );
	}

	public function listen_task() {
		if ( ! empty( $_GET['muhiku_forms_return'] ) ) { 
			$this->entry_confirmation_redirect( '', sanitize_text_field( wp_unslash( $_GET['muhiku_forms_return'] ) ) ); 
		}

		$form_id = ! empty( $_POST['muhiku_forms']['id'] ) ? absint( $_POST['muhiku_forms']['id'] ) : 0; 

		if ( ! $form_id ) {
			return;
		}

		if ( ! empty( $_POST['muhiku_forms']['id'] ) ) { 
			$this->do_task( mhk_sanitize_entry( wp_unslash( $_POST['muhiku_forms'] ) ) ); 
		}

		if ( ! mhk_is_amp() ) {
			return;
		}

		$settings        = $this->form_data['settings'];
		$success_message = isset( $settings['successful_form_submission_message'] ) ? $settings['successful_form_submission_message'] : __( 'Bizimle önerini paylaştığın için teşekkür ederiz! En kısa zamanda sizinle iletişime geçeceğiz..', 'muhiku-plug' );
		if ( empty( $this->errors[ $form_id ] ) ) {
			wp_send_json(
				array(
					'message' => $success_message,
				),
				200
			);

			return;
		}
		$message = $this->errors[ $form_id ]['header'];

		if ( ! empty( $this->errors[ $form_id ]['footer'] ) ) {
			$message .= ' ' . $this->errors[ $form_id ]['footer'];
		}

		wp_send_json(
			array(
				'message' => $message,
			),
			400
		);

	}

	/**
	 * @param array $entry $_POST object.
	 */
	public function do_task( $entry ) {
		$logger = mhk_get_logger();
		try {
			$this->errors           = array();
			$this->form_fields      = array();
			$form_id                = absint( $entry['id'] );
			$form                   = mhk()->form->get( $form_id );
			$honeypot               = false;
			$response_data          = array();
			$this->ajax_err         = array();
			$this->mhk_notice_print = false;
			$logger                 = mhk_get_logger();

			if ( empty( $_POST[ '_wpnonce' . $form_id ] ) || ! wp_verify_nonce( wp_unslash( sanitize_key( $_POST[ '_wpnonce' . $form_id ] ) ), 'muhiku-plug_process_submit' ) ) {  
				$this->errors[ $form_id ]['header'] = esc_html__( 'We were unable to process your form, please try again.', 'muhiku-plug' );
				$logger->error(
					$this->errors[ $form_id ]['header'],
					array( 'source' => 'form-submission' )
				);
				return $this->errors;
			}

			if ( ! $form || 'publish' !== $form->post_status ) {
				$this->errors[ $form_id ]['header'] = esc_html__( 'Invalid form. Please check again.', 'muhiku-plug' );
				$logger->error(
					$this->errors[ $form_id ]['header'],
					array( 'source' => 'form-submission' )
				);
				return $this->errors;
			}

			$this->form_data = apply_filters( 'muhiku_forms_process_before_form_data', mhk_decode( $form->post_content ), $entry );

			$entry                      = apply_filters( 'muhiku_forms_process_before_filter', $entry, $this->form_data );
			$this->form_data['page_id'] = array_key_exists( 'post_id', $entry ) ? $entry['post_id'] : $form_id;

			$logger->info(
				__( 'Muhiku Plug Process Before.', 'muhiku-plug' ),
				array( 'source' => 'form-submission' )
			);
			do_action( 'muhiku_forms_process_before', $entry, $this->form_data );
			$logger->info(
				__( 'Muhiku Plug Process Before Form ID.', 'muhiku-plug' ),
				array( 'source' => 'form-submission' )
			);
			do_action( "muhiku_forms_process_before_{$form_id}", $entry, $this->form_data );

			$ajax_form_submission = isset( $this->form_data['settings']['ajax_form_submission'] ) ? $this->form_data['settings']['ajax_form_submission'] : 0;
			if ( '1' === $ajax_form_submission ) {

				update_option( 'mhk_validation_error', '' );

				foreach ( $this->form_data['form_fields'] as $field ) {
					if ( '' === isset( $this->form_data['form_fields']['meta-key'] ) ) {
						continue;
					}

					$field_id     = $field['id'];
					$field_type   = $field['type'];
					$field_submit = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';

					if ( 'signature' === $field_type ) {
						$field_submit = isset( $field_submit['signature_image'] ) ? $field_submit['signature_image'] : '';
					}

					$exclude = array( 'title', 'html', 'captcha', 'image-upload', 'file-upload', 'divider' );

					if ( ! in_array( $field_type, $exclude, true ) ) {

						$this->form_fields[ $field_id ] = array(
							'id'       => $field_id,
							'name'     => sanitize_text_field( $field['label'] ),
							'meta_key' => $this->form_data['form_fields'][ $field_id ]['meta-key'],
							'type'     => $field_type,
							'value'    => mhk_sanitize_textarea_field( $field_submit ),
						);
					}
				}
			}

			foreach ( $this->form_data['form_fields'] as $field ) {
				$field_id        = $field['id'];
				$field_type      = $field['type'];
				$repeater_fields = array_key_exists( 'repeater-fields', $field ) ? $field['repeater-fields'] : 'no';

				$field_submit = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';

				if ( 'no' === $repeater_fields || 'repeater-fields' === $field_type ) {
					$logger->info(
						"Muhiku Plug Process Before validate {$field_type}.",
						array( 'source' => 'form-submission' )
					);
					do_action( "muhiku_forms_process_validate_{$field_type}", $field_id, $field_submit, $this->form_data, $field_type );
				}

				if ( 'credit-card' === $field_type && isset( $_POST['muhiku_form_stripe_payment_intent_id'] ) ) {
					$this->mhk_notice_print = true;
				}

				if ( 'yes' === get_option( 'mhk_validation_error' ) && $ajax_form_submission ) {
					if ( count( $this->errors ) ) {
						foreach ( $this->errors as $_error ) {
							$this->ajax_err [] = $_error;
						}
					}
					update_option( 'mhk_validation_error', '' );
				}
			}

			if ( $ajax_form_submission && count( $this->ajax_err ) ) {
				$response_data['error']    = $this->ajax_err;
				$response_data['message']  = __( 'Form has not been submitted, please see the errors below.', 'muhiku-plug' );
				$response_data['response'] = 'error';
				$logger->error(
					__( 'Form has not been submitted.', 'muhiku-plug' ),
					array( 'source' => 'form-submission' )
				);
				return $response_data;
			}

			if ( ! apply_filters( 'muhiku_forms_recaptcha_disabled', false ) ) {
				$recaptcha_type      = get_option( 'muhiku_forms_recaptcha_type', 'v2' );
				$invisible_recaptcha = get_option( 'muhiku_forms_recaptcha_v2_invisible', 'no' );

				if ( 'v2' === $recaptcha_type && 'no' === $invisible_recaptcha ) {
					$site_key   = get_option( 'muhiku_forms_recaptcha_v2_site_key' );
					$secret_key = get_option( 'muhiku_forms_recaptcha_v2_secret_key' );
				} elseif ( 'v2' === $recaptcha_type && 'yes' === $invisible_recaptcha ) {
					$site_key   = get_option( 'muhiku_forms_recaptcha_v2_invisible_site_key' );
					$secret_key = get_option( 'muhiku_forms_recaptcha_v2_invisible_secret_key' );
				} elseif ( 'v3' === $recaptcha_type ) {
					$site_key   = get_option( 'muhiku_forms_recaptcha_v3_site_key' );
					$secret_key = get_option( 'muhiku_forms_recaptcha_v3_secret_key' );
				} elseif ( 'hcaptcha' === $recaptcha_type ) {
					$site_key   = get_option( 'muhiku_forms_recaptcha_hcaptcha_site_key' );
					$secret_key = get_option( 'muhiku_forms_recaptcha_hcaptcha_secret_key' );
				}

				if ( ! empty( $site_key ) && ! empty( $secret_key ) && isset( $this->form_data['settings']['recaptcha_support'] ) && '1' === $this->form_data['settings']['recaptcha_support'] &&
				! isset( $_POST['__amp_form_verify'] ) && ( 'v3' === $recaptcha_type || ! mhk_is_amp() ) ) {
					if ( 'hcaptcha' === $recaptcha_type ) {
						$error = esc_html__( 'hCaptcha verification failed, please try again later.', 'muhiku-plug' );
					} else {
						$error = esc_html__( 'Google reCAPTCHA verification failed, please try again later.', 'muhiku-plug' );
					}

					$logger->error(
						$error,
						array( 'source' => 'Google reCAPTCHA' )
					);

					$token = ! empty( $_POST['g-recaptcha-response'] ) ? mhk_clean( wp_unslash( $_POST['g-recaptcha-response'] ) ) : false;

					if ( 'v3' === $recaptcha_type ) {
						$token = ! empty( $_POST['muhiku_forms']['recaptcha'] ) ? mhk_clean( wp_unslash( $_POST['muhiku_forms']['recaptcha'] ) ) : false;
					}
					if ( 'hcaptcha' === $recaptcha_type ) {
						$token        = ! empty( $_POST['h-captcha-response'] ) ? mhk_clean( wp_unslash( $_POST['h-captcha-response'] ) ) : false;
						$raw_response = wp_safe_remote_get( 'https://hcaptcha.com/siteverify?secret=' . $secret_key . '&response=' . $token );
					} else {
						$raw_response = wp_safe_remote_get( 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $token );
					}

					if ( ! is_wp_error( $raw_response ) ) {
						$response = json_decode( wp_remote_retrieve_body( $raw_response ) );
						if ( empty( $response->success ) || ( 'v3' === $recaptcha_type && $response->score <= get_option( 'muhiku_forms_recaptcha_v3_threshold_score', apply_filters( 'muhiku_forms_recaptcha_v3_threshold', '0.5' ) ) ) ) {
							if ( 'v3' === $recaptcha_type ) {
								if ( isset( $response->score ) ) {
									$error .= ' (' . esc_html( $response->score ) . ')';
								}
							}
							$this->errors[ $form_id ]['header'] = $error;
							$logger->error(
								$error,
								array( 'source' => 'Google reCAPTCHA' )
							);
							return $this->errors;
						}
					}
				}
			}
			$errors = apply_filters( 'muhiku_forms_process_initial_errors', $this->errors, $this->form_data );
			if ( isset( $_POST['__amp_form_verify'] ) ) {
				if ( empty( $errors[ $form_id ] ) ) {
					wp_send_json( array(), 200 );
				} else {
					$verify_errors = array();

					foreach ( $errors[ $form_id ] as $field_id => $error_fields ) {
						$field            = $this->form_data['fields'][ $field_id ];
						$field_properties = MHK_Shortcode_Form::get_field_properties( $field, $this->form_data );

						if ( is_string( $error_fields ) ) {

							if ( 'checkbox' === $field['type'] || 'radio' === $field['type'] || 'select' === $field['type'] ) {
								$first = current( $field_properties['inputs'] );
								$name  = $first['attr']['name'];
							} elseif ( isset( $field_properties['inputs']['primary']['attr']['name'] ) ) {
								$name = $field_properties['inputs']['primary']['attr']['name'];
							}

							$verify_errors[] = array(
								'name'    => $name,
								'message' => $error_fields,
							);
						} else {
							foreach ( $error_fields as $error_field => $error_message ) {

								if ( isset( $field_properties['inputs'][ $error_field ]['attr']['name'] ) ) {
									$name = $field_properties['inputs'][ $error_field ]['attr']['name'];
								}

								$verify_errors[] = array(
									'name'    => $name,
									'message' => $error_message,
								);
							}
						}
					}

					wp_send_json(
						array(
							'verifyErrors' => $verify_errors,
						),
						400
					);
				}
				return;
			}
			if ( ! empty( $errors[ $form_id ] ) ) {
				if ( empty( $errors[ $form_id ]['header'] ) ) {
					$errors[ $form_id ]['header'] = __( 'Form has not been submitted, please see the errors below.', 'muhiku-plug' );
					$logger->error(
						$errors[ $form_id ]['header'],
						array( 'source' => 'form-submission' )
					);
				}
				$this->errors = $errors;
				return $this->errors;
			}
			if ( isset( $this->form_data['settings']['honeypot'] ) && '1' === $this->form_data['settings']['honeypot'] && ! empty( $entry['hp'] ) ) {
				$honeypot = esc_html__( 'Muhiku Plug honeypot field triggered.', 'muhiku-plug' );
			}

			$honeypot = apply_filters( 'muhiku_forms_process_honeypot', $honeypot, $this->form_fields, $entry, $this->form_data );

			if ( $honeypot ) {
				$logger = mhk_get_logger();
				$logger->notice( sprintf( 'Spam entry for Form ID %d Response: %s', absint( $this->form_data['id'] ), mhk_print_r( $entry, true ) ), array( 'source' => 'honeypot' ) );
				return $this->errors;
			}

			$this->form_data['created'] = $form->post_date;

			foreach ( (array) $this->form_data['form_fields'] as $field ) {
				$field_id        = $field['id'];
				$field_key       = isset( $field['meta-key'] ) ? $field['meta-key'] : '';
				$field_type      = $field['type'];
				$field_submit    = isset( $entry['form_fields'][ $field_id ] ) ? $entry['form_fields'][ $field_id ] : '';
				$repeater_fields = array_key_exists( 'repeater-fields', $field ) ? $field['repeater-fields'] : 'no';

				if ( 'no' === $repeater_fields || 'repeater-fields' === $field_type ) {
					$logger->info(
						sprintf( 'Muhiku Plug Process Format %s.', $field_type ),
						array( 'source' => 'form-submission' )
					);
					do_action( "muhiku_forms_process_format_{$field_type}", $field_id, $field_submit, $this->form_data, $field_key );
				}
			}

			$logger->info(
				'Muhiku Plug Process Format After.',
				array( 'source' => 'form-submission' )
			);
			do_action( 'muhiku_forms_process_format_after', $this->form_data );

			$this->form_fields = apply_filters( 'muhiku_forms_process_filter', $this->form_fields, $entry, $this->form_data );
			$logger->notice( sprintf( 'Muhiku Form Process: %s', mhk_print_r( $this->form_fields, true ) ) );

			$logger->info(
				'Muhiku Plug Process.',
				array( 'source' => 'form-submission' )
			);
			do_action( 'muhiku_forms_process', $this->form_fields, $entry, $this->form_data );
			$logger->info(
				"Muhiku Plug Process {$form_id}.",
				array( 'source' => 'form-submission' )
			);
			do_action( "muhiku_forms_process_{$form_id}", $this->form_fields, $entry, $this->form_data );

			$this->form_fields = apply_filters( 'muhiku_forms_process_after_filter', $this->form_fields, $entry, $this->form_data );
			$logger->notice( sprintf( 'Muhiku Form Process After: %s', mhk_print_r( $this->form_fields, true ) ) );

			if ( ! empty( $this->errors[ $form_id ] ) ) {
				if ( empty( $this->errors[ $form_id ]['header'] ) ) {
					$this->errors[ $form_id ]['header'] = esc_html__( 'Form has not been submitted, please see the errors below.', 'muhiku-plug' );
				}
				$logger->error(
					__( 'Form has not been submitted', 'muhiku-plug' ),
					array( 'source' => 'form-submission' )
				);
				return $this->errors;
			}

			$logger->notice( sprintf( 'Entry is Saving to DataBase' ) );
			$logger->info(
				__( 'Entry Added to Database.', 'muhiku-plug' ),
				array( 'source' => 'form-submission' )
			);
			$entry_id = $this->entry_save( $this->form_fields, $entry, $this->form_data['id'], $this->form_data );
			$logger->notice( sprintf( 'Entry is Saved to DataBase' ) );

			$logger->notice( sprintf( 'Sending Email' ) );
			$logger->info(
				__( 'Sent Email Notification.', 'muhiku-plug' ),
				array( 'source' => 'form-submission' )
			);

			add_filter( 'muhiku_forms_success', array( $this, 'check_success_message' ), 10, 2 );

			$_POST['muhiku-plug']['complete'] = $this->form_fields;

			$_POST['muhiku-plug']['entry_id'] = $entry_id;

			$logger->info(
				__( 'Muhiku Plug Process Completed.', 'muhiku-plug' ),
				array( 'source' => 'form-submission' )
			);
			do_action( 'muhiku_forms_process_complete', $this->form_fields, $entry, $this->form_data, $entry_id );
			$logger->info(
				"Muhiku Plug Process Completed {$form_id}.",
				array( 'source' => 'form-submission' )
			);
			do_action( "muhiku_forms_process_complete_{$form_id}", $this->form_fields, $entry, $this->form_data, $entry_id );
		} catch ( Exception $e ) {
			mhk_add_notice( $e->getMessage(), 'error' );
			$logger->error(
				$e->getMessage(),
				array( 'source' => 'form-submission' )
			);
			if ( '1' === $ajax_form_submission ) {
				$this->errors[]            = $e->getMessage();
				$response_data['message']  = $this->errors;
				$response_data['response'] = 'error';
				return $response_data;
			}
		}

		$settings = $this->form_data['settings'];
		$message  = isset( $settings['successful_form_submission_message'] ) ? $settings['successful_form_submission_message'] : __( 'Bizimle önerini paylaştığın için teşekkür ederiz! En kısa zamanda sizinle iletişime geçeceğiz..', 'muhiku-plug' );

		if ( defined( 'MHK_PDF_SUBMISSION_VERSION' ) && 'yes' === get_option( 'muhiku_forms_pdf_download_after_submit', 'no' ) ) {
			global $__muhiku_form_id;
			global $__muhiku_form_entry_id;
			$__muhiku_form_id       = $form_id;
			$__muhiku_form_entry_id = $entry_id;
		}

		$submission_redirection_process = apply_filters( 'muhiku_forms_submission_redirection_process', array(), $this->form_fields, $this->form_data );

		$this->form_data['settings']['redirect_to'] = '0' === $this->form_data['settings']['redirect_to'] ? 'same' : $this->form_data['settings']['redirect_to'];

		if ( '1' === $ajax_form_submission ) {
			$response_data['message']  = $message;
			$response_data['response'] = 'success';
			$response_data['form_id']  = $form_id;
			$response_data['entry_id'] = $entry_id;

			if ( defined( 'MHK_PDF_SUBMISSION_VERSION' ) && 'yes' === get_option( 'muhiku_forms_pdf_download_after_submit', 'no' ) ) {
				$response_data['pdf_download'] = true;
				$pdf_download_message          = get_option( 'muhiku_forms_pdf_custom_download_text', '' );
				if ( empty( $pdf_download_message ) ) {
					$pdf_download_message = __( 'Download your form submission in PDF format', 'muhiku-plug' );
				}
				$response_data['pdf_download_message'] = $pdf_download_message;
			}

			switch ( $settings['redirect_to'] ) {
				case '0':
					$settings['redirect_to'] = 'same';
					break;

				case '1':
					$settings['redirect_to'] = 'custom_page';
					break;

				case '2':
					$settings['redirect_to'] = 'external_url';
					break;
			}

			if ( empty( $submission_redirection_process ) ) {
				if ( isset( $settings['redirect_to'] ) && 'external_url' === $settings['redirect_to'] ) {
					$response_data['redirect_url'] = isset( $settings['external_url'] ) ? esc_url( $settings['external_url'] ) : 'undefined';
				} elseif ( isset( $settings['redirect_to'] ) && 'custom_page' === $settings['redirect_to'] ) {
					$response_data['redirect_url'] = isset( $settings['custom_page'] ) ? get_page_link( absint( $settings['custom_page'] ) ) : 'undefined';
				}
			} else {
				$response_data['redirect_url'] = $submission_redirection_process['external_url'];
			}

			if ( isset( $this->mhk_notice_print ) && $this->mhk_notice_print ) {
				mhk_add_notice( $message, 'success' );
			}
			$response_data = apply_filters( 'muhiku_forms_after_success_ajax_message', $response_data, $this->form_data, $entry );
			return $response_data;
		} elseif ( ( 'same' === $this->form_data['settings']['redirect_to'] && empty( $submission_redirection_process ) ) || ( ! empty( $submission_redirection_process ) && 'same_page' == $submission_redirection_process['redirect_to'] ) ) {
				mhk_add_notice( $message, 'success' );
		}
		$logger->info(
			'Muhiku Plug After success Message.',
			array( 'source' => 'form-submission' )
		);

		do_action( 'muhiku_forms_after_success_message', $this->form_data, $entry );
		$this->entry_confirmation_redirect( $this->form_data );

	}

	/**
	 * @param mixed $posted_data Posted data.
	 */
	public function ajax_form_submission( $posted_data ) {
		add_filter( 'wp_redirect', array( $this, 'ajax_process_redirect' ), 999 );
		$process = $this->do_task( $posted_data );
		return $process;
	}

	/**
	 * @param string $url Redirect URL.
	 */
	public function ajax_process_redirect( $url ) {
		$form_id = isset( $_POST['muhiku_forms']['id'] ) ? absint( $_POST['muhiku_forms']['id'] ) : 0;  

		if ( empty( $form_id ) ) {
			wp_send_json_error();
		}

		$response = array(
			'form_id'      => $form_id,
			'redirect_url' => $url,
		);

		$response = apply_filters( 'muhiku_forms_ajax_submit_redirect', $response, $form_id, $url );

		do_action( 'muhiku_forms_ajax_submit_completed', $form_id, $response );
		wp_send_json_success( $response );
	}

	/**
	 * @param bool $status Message status.
	 * @param int  $form_id Form ID.
	 */
	public function check_success_message( $status, $form_id ) {
		if ( isset( $this->form_data['id'] ) && absint( $this->form_data['id'] ) === $form_id ) {
			return true;
		}
		return false;
	}

	/**
	 * @param string $hash Base64-encoded hash of form and entry IDs.
	 * @return array|false False for invalid or form id.
	 */
	public function validate_return_hash( $hash = '' ) {
		$query_args = base64_decode( $hash );

		parse_str( $query_args, $output );

		if ( wp_hash( $output['form_id'] . ',' . $output['entry_id'] ) !== $output['hash'] ) {
			return false;
		}

		$entry = mhk_get_entry( $output['entry_id'] );

		if ( empty( $entry->form_id ) ) {
			return false;
		}

		if ( $output['form_id'] !== $entry->form_id ) {
			return false;
		}

		return array(
			'form_id'  => absint( $output['form_id'] ),
			'entry_id' => absint( $output['form_id'] ),
			'fields'   => null !== $entry && isset( $entry->fields ) ? $entry->fields : array(),
		);
	}

	/**
	 * @param array  $form_data Form data and settings.
	 * @param string $hash      Base64-encoded hash of form and entry IDs.
	 */
	public function entry_confirmation_redirect( $form_data = '', $hash = '' ) {
		$_POST = array(); 

		if ( ! empty( $hash ) ) {
			$hash_data = $this->validate_return_hash( $hash );

			if ( ! $hash_data || ! is_array( $hash_data ) ) {
				return;
			}

			$this->is_valid_hash = true;
			$this->entry_id      = absint( $hash_data['entry_id'] );
			$this->form_fields   = json_decode( $hash_data['fields'], true );
			$this->form_data     = mhk()->form->get(
				absint( $hash_data['form_id'] ),
				array(
					'content_only' => true,
				)
			);
		} else {
			$this->form_data = $form_data;
		}

		$settings = $this->form_data['settings'];

		switch ( $settings['redirect_to'] ) {
			case '0':
				$settings['redirect_to'] = 'same';
				break;

			case '1':
				$settings['redirect_to'] = 'custom_page';
				break;

			case '2':
				$settings['redirect_to'] = 'external_url';
				break;
		}

		$submission_redirect_process = apply_filters( 'muhiku_forms_submission_redirection_process', array(), $this->form_fields, $this->form_data );

		if ( ! empty( $submission_redirect_process ) ) {
			$settings['redirect_to']  = $submission_redirect_process['redirect_to'];
			$settings['external_url'] = $submission_redirect_process['external_url'];
			$settings['custom_page']  = $submission_redirect_process['custom_page'];
		}

		if ( isset( $settings['redirect_to'] ) && 'custom_page' === $settings['redirect_to'] ) {
			?>
				<script>
				var redirect = '<?php echo esc_url( get_page_link( $settings['custom_page'] ) ); ?>';
				window.setTimeout( function () {
					window.location.href = redirect;
				})
				</script>
			<?php
		} elseif ( isset( $settings['redirect_to'] ) && 'external_url' === $settings['redirect_to'] ) {
			?>
			<script>
				window.setTimeout( function () {
					window.location.href = '<?php echo esc_url( $settings['external_url'] ); ?>';
				})
				</script>
			<?php
		}

		if ( ! empty( $this->form_data['settings']['confirmation_type'] ) && 'message' !== $this->form_data['settings']['confirmation_type'] ) {
			if ( 'redirect' === $this->form_data['settings']['confirmation_type'] ) {
				$url = apply_filters( 'muhiku_forms_process_smart_tags', $this->form_data['settings']['confirmation_redirect'], $this->form_data, $this->form_fields, $this->entry_id );
			}

			if ( 'page' === $this->form_data['settings']['confirmation_type'] ) {
				$url = get_permalink( (int) $this->form_data['settings']['confirmation_page'] );
			}
		}

		if ( ! empty( $this->form_data['id'] ) ) {
			$form_id = $this->form_data['id'];
		} else {
			return;
		}
		if ( isset( $settings['submission_message_scroll'] ) && $settings['submission_message_scroll'] ) {
			add_filter( 'muhiku_forms_success_notice_class', array( $this, 'add_scroll_notice_class' ) );
		}

		if ( ! empty( $url ) ) {
			$url = apply_filters( 'muhiku_forms_process_redirect_url', $url, $form_id, $this->form_fields );
			wp_safe_redirect( esc_url_raw( $url ) );
			do_action( 'muhiku_forms_process_redirect', $form_id );
			do_action( "muhiku_forms_process_redirect_{$form_id}", $form_id );
			exit;
		}
	}

	/**
	 * @param  array $classes Notice Classes.
	 * @return array of notice classes.
	 */
	public function add_scroll_notice_class( $classes ) {
		$classes[] = 'muhiku-plug-submission-scroll';

		return $classes;
	}

	/**
	 * @param array  $fields    List of fields.
	 * @param array  $entry     Submitted form entry.
	 * @param array  $form_data Form data and settings.
	 * @param int    $entry_id  Saved entry id.
	 * @param string $context   In which context this email is sent.
	 */

	/**
	 * @param array $fields    List of form fields.
	 * @param array $entry     User submitted data.
	 * @param int   $form_id   Form ID.
	 * @param array $form_data Prepared form settings.
	 * @return int
	 */
	public function entry_save( $fields, $entry, $form_id, $form_data = array() ) {
		global $wpdb;

		if ( isset( $form_data['settings']['disabled_entries'] ) && '1' === $form_data['settings']['disabled_entries'] ) {
			return;
		}

		if ( ! apply_filters( 'muhiku_forms_entry_save', true, $fields, $entry, $form_data ) ) {
			return;
		}

		do_action( 'muhiku_forms_process_entry_save', $fields, $entry, $form_id, $form_data );

		$fields      = apply_filters( 'muhiku_forms_entry_save_data', $fields, $entry, $form_data );
		$browser     = mhk_get_browser();
		$user_ip     = mhk_get_ip_address();
		$user_device = mhk_get_user_device();
		$user_agent  = $browser['name'] . '/' . $browser['platform'] . '/' . $user_device;
		$referer     = ! empty( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$entry_id    = false;

		if ( 'yes' === get_option( 'muhiku_forms_disable_user_details' ) ) {
			$user_agent = '';
			$user_ip    = '';
		}

		$entry_data = apply_filters(
			'muhiku_forms_entry_data',
			array(
				'form_id'         => $form_id,
				'user_id'         => get_current_user_id(),
				'user_device'     => sanitize_text_field( $user_agent ),
				'user_ip_address' => sanitize_text_field( $user_ip ),
				'status'          => 'publish',
				'referer'         => $referer,
				'fields'          => wp_json_encode( $fields ),
				'date_created'    => current_time( 'mysql', true ),
			),
			$entry
		);

		if ( ! $entry_data['form_id'] ) {
			return new WP_Error( 'no-form-id', __( 'No form ID was found.', 'muhiku-plug' ) );
		}

		$success = $wpdb->insert( $wpdb->prefix . 'mhk_entries', $entry_data );

		if ( is_wp_error( $success ) || ! $success ) {
			return new WP_Error( 'could-not-create', __( 'Could not create an entry', 'muhiku-plug' ) );
		}

		$entry_id = $wpdb->insert_id;

		if ( $entry_id ) {
			foreach ( $fields as $field ) {
				$field = apply_filters( 'muhiku_forms_entry_save_fields', $field, $form_data, $entry_id );
				if ( in_array( $field['type'], array( 'html', 'title' ), true ) ) {
					continue;
				}

				if ( in_array( $field['type'], array( 'image-upload', 'file-upload' ), true ) ) {

					if ( isset( $field['value']['file_url'] ) && '' === $field['value']['file_url'] ) {
						continue;
					}
				}

				if ( in_array( $field['type'], array( 'radio', 'payment-multiple' ), true ) ) {
					if ( isset( $field['value']['label'] ) && '' === $field['value']['label'] ) {
						continue;
					}
				} elseif ( in_array( $field['type'], array( 'checkbox', 'payment-checkbox' ), true ) ) {
					if ( isset( $field['value']['label'] ) && ( empty( $field['value']['label'] ) || '' === current( $field['value']['label'] ) ) ) {
						continue;
					}
				}

				if ( isset( $field['meta_key'], $field['value'] ) && '' !== $field['value'] ) {
					$entry_metadata = array(
						'entry_id'   => $entry_id,
						'meta_key'   => sanitize_key( $field['meta_key'] ),
						'meta_value' => maybe_serialize( $field['value'] ),
					);

					$wpdb->insert( $wpdb->prefix . 'mhk_entrymeta', $entry_metadata );
				}
			}
		}

		$this->entry_id = $entry_id;

		wp_cache_delete( $entry_id, 'mhk-entry' );
		wp_cache_delete( $entry_id, 'mhk-entrymeta' );
		wp_cache_delete( $form_id, 'mhk-entries-ids' );
		wp_cache_delete( $form_id, 'mhk-last-entries-count' );
		wp_cache_delete( $form_id, 'mhk-search-entries' );
		wp_cache_delete( MHK_Cache_Helper::get_cache_prefix( 'entries' ) . '_unread_count', 'entries' );

		do_action( 'muhiku_forms_complete_entry_save', $entry_id, $fields, $entry, $form_id, $form_data );

		return $this->entry_id;
	}

	/**
	 *
	 * @param string $properties Value.
	 * @param mixed  $field Field.
	 * @param mixed  $form_data Form Data.
	 * @return $properties Properties.
	 */
	public function load_previous_field_value( $properties, $field, $form_data ) {

		if ( ! isset( $_POST['muhiku_forms'] ) ) { 
			return $properties;
		}
		$data = ! empty( $_POST['muhiku_forms']['form_fields'][ $field['id'] ] ) ? wp_unslash( $_POST['muhiku_forms']['form_fields'][ $field['id'] ] ) : array(); 

		if ( 'checkbox' === $field['type'] ) {
			foreach ( $field['choices'] as $key => $option_value ) {
				$selected = ! empty( $option_value['default'] ) ? $option_value['default'] : '';
				foreach ( $data  as $value ) {
					if ( $value === $option_value['label'] ) {
						$selected                                = 1;
						$properties['inputs'][ $key ]['default'] = $selected;
					}
				}
			}
		} elseif ( 'radio' === $field['type'] || 'select' === $field['type'] ) {
			foreach ( $field['choices'] as $key => $option_value ) {
				if ( $data === $option_value['label'] ) { 
					$selected                                = 1;
					$properties['inputs'][ $key ]['default'] = $selected;
				}
			}
		} else {
			if ( ! is_array( $data ) ) {
				$properties['inputs']['primary']['attr']['value'] = esc_attr( $data );
			}
		}
		return $properties;
	}
}

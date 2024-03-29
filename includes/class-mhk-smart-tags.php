<?php
/**
 * @package MuhikuPlug\Classes
 */

defined( 'ABSPATH' ) || exit;

class MHK_Smart_Tags {

	public function __construct() {
		add_filter( 'muhiku_forms_process_smart_tags', array( $this, 'process' ), 10, 4 );
	}

	/**
	 * @return string|array
	 */
	public function other_smart_tags() {
		$smart_tags = apply_filters(
			'muhiku_forms_smart_tags',
			array(
				'admin_email'     => esc_html__( 'Site Admin Email', 'muhiku-plug' ),
				'site_name'       => esc_html__( 'Site Name', 'muhiku-plug' ),
				'site_url'        => esc_html__( 'Site URL', 'muhiku-plug' ),
				'page_title'      => esc_html__( 'Page Title', 'muhiku-plug' ),
				'page_url'        => esc_html__( 'Page URL', 'muhiku-plug' ),
				'page_id'         => esc_html__( 'Page ID', 'muhiku-plug' ),
				'form_name'       => esc_html__( 'Form Name', 'muhiku-plug' ),
				'user_ip_address' => esc_html__( 'User IP Address', 'muhiku-plug' ),
				'user_id'         => esc_html__( 'User ID', 'muhiku-plug' ),
				'user_name'       => esc_html__( 'User Name', 'muhiku-plug' ),
				'display_name'    => esc_html__( 'User Display Name', 'muhiku-plug' ),
				'user_email'      => esc_html__( 'User Email', 'muhiku-plug' ),
				'referrer_url'    => esc_html__( 'Referrer URL', 'muhiku-plug' ),
			)
		);

		return $smart_tags;
	}

	/**
	 *
	 * @param string       $content 
	 * @param array        $form_data Array of the form data.
	 * @param string|array $fields Form fields.
	 * @param int|string   $entry_id Entry ID.
	 *
	 * @return string
	 */
	public function process( $content, $form_data, $fields = '', $entry_id = '' ) {
		preg_match_all( '/\{field_id="(.+?)"\}/', $content, $ids );

		if ( ! empty( $ids[1] ) && ! empty( $fields ) ) {

			foreach ( $ids[1] as $key => $field_id ) {
				$mixed_field_id = explode( '_', $field_id );
				$uploads        = wp_upload_dir();

				if ( 'fullname' !== $field_id && 'email' !== $field_id && 'subject' !== $field_id && 'message' !== $field_id ) {
					$value = ! empty( $fields[ $mixed_field_id[1] ]['value'] ) ? mhk_sanitize_textarea_field( $fields[ $mixed_field_id[1] ]['value'] ) : '';
				} else {
					$value = ! empty( $fields[ $field_id ]['value'] ) ? mhk_sanitize_textarea_field( $fields[ $field_id ]['value'] ) : '';
				}

				if ( count( $mixed_field_id ) > 1 && ! empty( $fields[ $mixed_field_id[1] ] ) ) {
					if ( 'signature' === $fields[ $mixed_field_id[1] ]['type'] ) {
						if ( ! is_array( $value ) && false !== strpos( $value, $uploads['basedir'] ) ) {
							$value = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $value );
						}

						if ( ! empty( $value ) ) {
							$value = sprintf(
								'<img src="%s" style="width:150px;height:80px;max-height:200px;max-width:100px;"/>',
								$value
							);
						}
					}

					if ( isset( $value['image'] ) && 'radio' === $fields[ $mixed_field_id[1] ]['type'] ) {
						if ( ! is_array( $value ) && false !== strpos( $value['image'], $uploads['basedir'] ) ) {
							$value = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $value['image'] );
						}

						if ( ! empty( $value ) ) {
							$value = sprintf(
								"\n" . '<img src="%s" style="width:150px;height:80px;max-height:200px;max-width:100px;"/>' . "\n" . '%s',
								$value['image'],
								$value['label']
							);
						}
					}

					if ( isset( $value['images'] ) && ( 'checkbox' === $fields[ $mixed_field_id[1] ]['type'] || 'payment-checkbox' === $fields[ $mixed_field_id[1] ]['type'] ) ) {
						$checkbox_images = '';
						foreach ( $value['images'] as $image_key => $image_value ) {
							if ( ! is_array( $image_value ) && false !== strpos( $image_value, $uploads['basedir'] ) ) {
								$value = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $image_value );
							}

							if ( ! empty( $value ) ) {
								$checkbox_images .= sprintf(
									"\n" . '<img src="%s" style="width:150px;height:80px;max-height:200px;max-width:100px;"/>' . "\n" . '%s',
									$image_value,
									$value['label'][ $image_key ]
								);
							}
						}
						$value = $checkbox_images;
					}

					if ( 'image-upload' === $fields[ $mixed_field_id[1] ]['type'] || 'file-upload' === $fields[ $mixed_field_id[1] ]['type'] ) {
						$files = '';

						if ( ! empty( $fields[ $mixed_field_id[1] ]['value_raw'] ) ) {
							foreach ( $fields[ $mixed_field_id[1] ]['value_raw'] as $files_key => $files_value ) {
								if ( ! is_array( $files_value['value'] ) && false !== strpos( $files_value['value'], $uploads['basedir'] ) ) {
									$value = trailingslashit( content_url() ) . str_replace( str_replace( 'uploads', '', $uploads['basedir'] ), '', $files_value['value'] );
								}

								if ( ! empty( $value ) ) {
									$files .= sprintf(
										'<a href="%s">%s</a> ' . "\n",
										$files_value['value'],
										$files_value['name']
									);
								}
							}
							$value = $files;
						}
					}
				}

				if ( ! is_array( $value ) ) {
					$content = str_replace( '{field_id="' . $field_id . '"}', $value, $content );
				} else {
					if ( isset( $value['type'], $value['label'] ) ) {
						if ( in_array( $value['type'], array( 'radio', 'payment-multiple' ), true ) ) {
							$value = $value['label'];
						} elseif ( in_array( $value['type'], array( 'checkbox', 'payment-checkbox' ), true ) ) {
							$value = implode( ', ', $value['label'] );
						}
					} elseif ( isset( $value['number_of_rating'], $value['value'] ) ) {
						$value = (string) $value['value'] . '/' . (string) $value['number_of_rating'];
					} else {
						$value = $value[0];
					}

					$content = str_replace( '{field_id="' . $field_id . '"}', $value, $content );
				}
			}
		}
		preg_match_all( '/\{(.+?)\}/', $content, $other_tags );

		if ( ! empty( $other_tags[1] ) ) {

			foreach ( $other_tags[1] as $key => $other_tag ) {

				switch ( $other_tag ) {
					case 'admin_email':
						$admin_email = sanitize_email( get_option( 'admin_email' ) );
						$content     = str_replace( '{' . $other_tag . '}', $admin_email, $content );
						break;

					case 'site_name':
						$site_name = get_option( 'blogname' );
						$content   = str_replace( '{' . $other_tag . '}', $site_name, $content );
						break;

					case 'site_url':
						$site_url = get_option( 'siteurl' );
						$content  = str_replace( '{' . $other_tag . '}', $site_url, $content );
						break;

					case 'page_title':
						$page_title = isset( $form_data ) ? get_the_title( $form_data['page_id'] ) : '';
						$content    = str_replace( '{' . $other_tag . '}', $page_title, $content );
						break;

					case 'page_url':
						$page_url = isset( $form_data ) ? get_permalink( $form_data['page_id'] ) : '';
						$content  = str_replace( '{' . $other_tag . '}', $page_url, $content );
						break;

					case 'page_id':
						$page_id = isset( $form_data ) ? $form_data['page_id'] : '';
						$content = str_replace( '{' . $other_tag . '}', $page_id, $content );
						break;

					case 'form_name':
						if ( isset( $form_data['settings']['form_title'] ) && ! empty( $form_data['settings']['form_title'] ) ) {
							$form_name = $form_data['settings']['form_title'];
						} else {
							$form_name = '';
						}
						$content = str_replace( '{' . $other_tag . '}', $form_name, $content );
						break;

					case 'user_ip_address':
						$user_ip_add = mhk_get_ip_address();
						$content     = str_replace( '{' . $other_tag . '}', $user_ip_add, $content );
						break;

					case 'user_id':
						$user_id = is_user_logged_in() ? get_current_user_id() : '';
						$content = str_replace( '{' . $other_tag . '}', $user_id, $content );
						break;

					case 'user_email':
						if ( is_user_logged_in() ) {
							$user  = wp_get_current_user();
							$email = sanitize_email( $user->user_email );
						} else {
							$email = '';
						}
						$content = str_replace( '{' . $other_tag . '}', $email, $content );
						break;

					case 'user_name':
						if ( is_user_logged_in() ) {
							$user = wp_get_current_user();
							$name = sanitize_text_field( $user->user_login );
						} else {
							$name = '';
						}
						$content = str_replace( '{' . $other_tag . '}', $name, $content );
						break;

					case 'display_name':
						if ( is_user_logged_in() ) {
							$user = wp_get_current_user();
							$name = sanitize_text_field( $user->display_name );
						} else {
							$name = '';
						}
						$content = str_replace( '{' . $other_tag . '}', $name, $content );
						break;

					case 'referrer_url':
						$referer = ! empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';  
						$content = str_replace( '{' . $other_tag . '}', sanitize_text_field( $referer ), $content );
						break;

				}
			}
		}

		return $content;
	}
}

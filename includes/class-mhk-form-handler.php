<?php
/**
 * @package MuhikuPlug
 */

defined( 'ABSPATH' ) || exit;

class MHK_Form_Handler {

	/**
	 * @param  mixed $id   Form ID.
	 * @param  array $args Form Arguments.
	 * @return array|bool|null|WP_Post Form object.
	 */
	public function get( $id = '', $args = array() ) {
		$forms = array();
		$args  = apply_filters( 'muhiku_forms_get_form_args', $args );

		if ( false === $id ) {
			return false;
		}

		if ( ! isset( $args['cap'] ) && ( is_admin() && ! wp_doing_ajax() ) ) {
			$args['cap'] = 'muhiku_forms_view_form';
		}

		if ( ! empty( $id ) ) {
			if ( ! empty( $args['cap'] ) && ! current_user_can( $args['cap'], $id ) ) {
				return false;
			}

			$the_post = get_post( absint( $id ) );

			if ( $the_post && 'muhiku_form' === $the_post->post_type ) {
				$forms = empty( $args['content_only'] ) ? $the_post : mhk_decode( $the_post->post_content );
			}
		} else {
			$args = wp_parse_args(
				$args,
				array(
					'order' => 'DESC',
				)
			);

			$forms = $this->get_multiple( $args );
		}

		if ( empty( $forms ) ) {
			return false;
		}

		return $forms;
	}

	/**
	 * @param array $args Additional arguments array.
	 * @param bool  $content_only True to return post content only.
	 *
	 * @return array
	 */
	public function get_multiple( $args = array(), $content_only = false ) {
		$forms   = array();
		$user_id = get_current_user_id();
		$args    = apply_filters( 'muhiku_forms_get_multiple_forms_args', $args, $content_only );

		$defaults = array(
			'orderby'       => 'id',
			'order'         => 'ASC',
			'no_found_rows' => true,
			'nopaging'      => true,
			'status'        => 'publish',
			'post_status'   => 'publish',
			'numberposts'   => -1,
		);

		$args = wp_parse_args( $args, $defaults );

		$args['post_type'] = 'muhiku_form';

		if ( current_user_can( 'muhiku_forms_view_forms' ) && ! current_user_can( 'muhiku_forms_view_others_forms' ) ) {
			$args['author'] = $user_id;
		}

		if ( ! current_user_can( 'muhiku_forms_view_forms' ) && current_user_can( 'muhiku_forms_view_others_forms' ) ) {
			$args['author__not_in'] = $user_id;
		}

		if ( ! current_user_can( 'muhiku_forms_view_forms' ) && ! current_user_can( 'muhiku_forms_view_others_forms' ) ) {
			$args['post__in'] = array( 0 );
		}

		unset( $args['cap'] );

		$forms = get_posts( $args );

		if ( $content_only ) {
			$forms = array_map( array( $this, 'prepare_post_content' ), $forms );
		}

		return $forms;
	}

	/**
	 * @param object $post Post object.
	 */
	public function prepare_post_content( $post ) {
		return ! empty( $post->post_content ) ? mhk_decode( $post->post_content ) : false;
	}

	/**
	 * @param  array $ids Form IDs.
	 * @return boolean
	 */
	public function delete( $ids = array() ) {
		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {

			if ( ! current_user_can( 'muhiku_forms_delete', $id ) ) {
				return false;
			}

			$form = wp_delete_post( $id, true );

			if ( ! $form ) {
				return false;
			}
		}

		do_action( 'muhiku_forms_delete_form', $ids );

		return true;
	}

	/**
	 * @param  string $title    Form title.
	 * @param  string $template Form template.
	 * @param  array  $args     Form Arguments.
	 * @param  array  $data     Additional data.
	 * @return int|bool Form ID on successful creation else false.
	 */
	public function create( $title = '', $template = 'blank', $args = array(), $data = array() ) {
		if ( empty( $title ) || ! current_user_can( 'muhiku_forms_create_forms' ) ) {
			return false;
		}

		$args         = apply_filters( 'muhiku_forms_create_form_args', $args, $data );
		$form_style   = array();
		$style_needed = false;
		$form_content = array(
			'form_field_id' => '1',
			'settings'      => array(
				'form_title' => sanitize_text_field( $title ),
				'form_desc'  => '',
			),
		);

		$has_kses = ( false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' ) );
		if ( $has_kses ) {
			kses_remove_filters();
		}
		$has_targeted_link_rel_filters = ( false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' ) );
		if ( $has_targeted_link_rel_filters ) {
			wp_remove_targeted_link_rel_filters();
		}

		$form_id = wp_insert_post(
			array(
				'post_title'   => esc_html( $title ),
				'post_status'  => 'publish',
				'post_type'    => 'muhiku_form',
				'post_content' => '{}',
			)
		);

		$templates     = mhk_get_json_file_contents( 'assets/extensions-json/templates/all_templates.json' );

		if ( ! empty( $templates ) ) {
			foreach ( $templates->templates as $template_data ) {
				if ( $template_data->slug === $template && 'blank' !== $template_data->slug ) {
					$form_content = json_decode( base64_decode( $template_data->settings ), true );

					if ( isset( $template_data->styles ) ) {
						$style_needed           = true;
						$form_style[ $form_id ] = json_decode( base64_decode( $template_data->styles ), true );
					}
				}
			}
		}

		if ( $form_id ) {
			$form_content['id']                     = $form_id;
			$form_content['settings']['form_title'] = $title;

			$form_data = wp_parse_args(
				$args,
				array(
					'ID'           => $form_id,
					'post_title'   => esc_html( $title ),
					'post_content' => mhk_encode( array_merge( array( 'id' => $form_id ), $form_content ) ),
				)
			);

			wp_update_post( $form_data );

			if ( ! empty( $form_style ) ) {
				update_option( 'muhiku_forms_styles', $form_style );
			}
		}

		if ( $has_kses ) {
			kses_init_filters();
		}
		if ( $has_targeted_link_rel_filters ) {
			wp_init_targeted_link_rel_filters();
		}

		do_action( 'muhiku_forms_create_form', $form_id, $form_data, $data, $style_needed );

		return $form_id;
	}

	/**
	 * @param string|int $form_id Form ID.
	 * @param array      $data    Data retrieved from $_POST and processed.
	 * @param array      $args    Empty by default, may have custom data not intended to be saved.
	 *
	 * @return   mixed
	 * @internal param string $title
	 */
	public function update( $form_id = '', $data = array(), $args = array() ) {
		if ( empty( $data ) ) {
			return false;
		}

		if ( empty( $form_id ) ) {
			$form_id = $data['form_id'];
		}

		if ( ! isset( $args['cap'] ) ) {
			$args['cap'] = 'muhiku_forms_edit_form';
		}

		if ( ! empty( $args['cap'] ) && ! current_user_can( $args['cap'], $form_id ) ) {
			return false;
		}

		$data = wp_unslash( $data );

		if ( ! empty( $data['settings']['form_title'] ) ) {
			$title = $data['settings']['form_title'];
		} else {
			$title = get_the_title( $form_id );
		}

		if ( ! empty( $data['settings']['form_desc'] ) ) {
			$desc = $data['settings']['form_desc'];
		} else {
			$desc = '';
		}

		$data['form_field_id'] = ! empty( $data['form_field_id'] ) ? absint( $data['form_field_id'] ) : '0';

		remove_filter( 'content_save_pre', 'balanceTags', 50 );

		if ( ! current_user_can( 'unfiltered_html' ) ) {
			$data = map_deep( $data, 'wp_strip_all_tags' );
		}

		$has_kses = ( false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' ) );
		if ( $has_kses ) {
			kses_remove_filters();
		}
		$has_targeted_link_rel_filters = ( false !== has_filter( 'content_save_pre', 'wp_targeted_link_rel' ) );
		if ( $has_targeted_link_rel_filters ) {
			wp_remove_targeted_link_rel_filters();
		}

		$form    = array(
			'ID'           => $form_id,
			'post_title'   => esc_html( $title ),
			'post_excerpt' => $desc,
			'post_content' => mhk_encode( $data ),
		);
		$form    = apply_filters( 'muhiku_forms_save_form_args', $form, $data, $args );
		$form_id = wp_update_post( $form );

		$style_needed = false;
		if ( ! empty( $data['form_styles'] ) ) {
			$style_needed            = true;
			$form_styles             = get_option( 'muhiku_forms_styles', array() );
			$form_styles[ $form_id ] = mhk_decode( $data['form_styles'] );

			update_option( 'muhiku_forms_styles', $form_styles );
		}

		if ( $has_kses ) {
			kses_init_filters();
		}
		if ( $has_targeted_link_rel_filters ) {
			wp_init_targeted_link_rel_filters();
		}

		do_action( 'muhiku_forms_save_form', $form_id, $form, array(), $style_needed );

		return $form_id;
	}

	/**
	 * @param array $ids Form IDs to duplicate.
	 *
	 * @return boolean
	 */
	public function duplicate( $ids = array() ) {
		if ( ! current_user_can( 'muhiku_forms_create_forms' ) ) {
			return false;
		}

		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids = array_map( 'absint', $ids );

		foreach ( $ids as $id ) {

			$form = get_post( $id );

			if ( ! current_user_can( 'muhiku_forms_view_form', $id ) ) {
				return false;
			}

			if ( ! $form || empty( $form ) ) {
				return false;
			}

			$new_form_data = mhk_decode( $form->post_content );

			$form_styles = get_option( 'muhiku_forms_styles', array() );
			if ( ! empty( $form_styles[ $id ] ) ) {
				$new_form_data['form_styles'] = wp_json_encode( $form_styles[ $id ] );
			}

			$new_form_data['settings']['form_title'] = str_replace( '(ID #' . absint( $id ) . ')', '', $new_form_data['settings']['form_title'] );

			$new_form    = array(
				'post_author'  => $form->post_author,
				'post_content' => mhk_encode( $new_form_data ),
				'post_excerpt' => $form->post_excerpt,
				'post_status'  => $form->post_status,
				'post_title'   => $new_form_data['settings']['form_title'],
				'post_type'    => $form->post_type,
			);
			$new_form_id = wp_insert_post( $new_form );

			if ( ! $new_form_id || is_wp_error( $new_form_id ) ) {
				return false;
			}

			$new_form_data['settings']['form_title'] .= ' (ID #' . absint( $new_form_id ) . ')';

			$new_form_data['id'] = absint( $new_form_id );

			$new_form_id = $this->update( $new_form_id, $new_form_data );

			if ( ! $new_form_id || is_wp_error( $new_form_id ) ) {
				return false;
			}

			return $new_form_id;
		}

		return true;
	}

	/**
	 *
	 * @param int    $form_id Form ID.
	 * @param string $field   Field.
	 * @param array  $args    Additional arguments.
	 *
	 * @return false|array
	 */
	public function get_meta( $form_id, $field = '', $args = array() ) {
		if ( empty( $form_id ) ) {
			return false;
		}

		if ( isset( $args['cap'] ) ) {
			$defaults['cap'] = $args['cap'];
		}

		$data = $this->get(
			$form_id,
			array(
				'content_only' => true,
			)
		);

		if ( isset( $data['meta'] ) ) {
			if ( empty( $field ) ) {
				return $data['meta'];
			} elseif ( isset( $data['meta'][ $field ] ) ) {
				return $data['meta'][ $field ];
			}
		}

		return false;
	}

	/**
	 * @param  int $form_id  Form ID.
	 * @return mixed int or false
	 */
	public function field_unique_key( $form_id ) {
		if ( ! current_user_can( 'muhiku_forms_edit_form', $form_id ) ) {
			return false;
		}

		if ( empty( $form_id ) ) {
			return false;
		}

		$form = $this->get(
			$form_id,
			array(
				'content_only' => true,
			)
		);

		if ( ! empty( $form['form_field_id'] ) ) {
			$form_field_id = absint( $form['form_field_id'] );
			$form['form_field_id'] ++;
		} else {
			$form_field_id         = '0';
			$form['form_field_id'] = '1';
		}

		$this->update( $form_id, $form );

		$field_id = mhk_get_random_string() . '-' . $form_field_id;

		return $field_id;
	}

	/**
	 *
	 * @param int    $form_id  Form ID.
	 * @param string $field_id Field ID.
	 * @param array  $args     Additional arguments.
	 *
	 * @return array|bool
	 */
	public function get_field( $form_id, $field_id = '', $args = array() ) {
		if ( empty( $form_id ) ) {
			return false;
		}

		if ( isset( $args['cap'] ) ) {
			$defaults['cap'] = $args['cap'];
		}

		$data = $this->get(
			$form_id,
			array(
				'content_only' => true,
			)
		);

		return isset( $data['form_fields'][ $field_id ] ) ? $data['form_fields'][ $field_id ] : false;
	}

	/**
	 * @param int    $form_id  Form ID.
	 * @param string $field_id Field.
	 * @param array  $args     Additional arguments.
	 *
	 * @return bool
	 */
	public function get_field_meta( $form_id, $field_id = '', $args = array() ) {
		$field = $this->get_field( $form_id, $field_id, $args );
		if ( ! $field ) {
			return false;
		}

		return isset( $field['meta'] ) ? $field['meta'] : false;
	}
}

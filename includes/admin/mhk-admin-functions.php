<?php
/**
 * @package MuhikuPlug/Admin/Functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * @return array
 */
function mhk_get_screen_ids() {
	$mhk_screen_id = sanitize_title( esc_html__( 'Muhiku Plug', 'muhiku-plug' ) );
	$screen_ids    = array(
		'dashboard_page_mhk-welcome',
		'toplevel_page_' . $mhk_screen_id,
		$mhk_screen_id . '_page_mhk-builder',
		$mhk_screen_id . '_page_mhk-entries',
		$mhk_screen_id . '_page_mhk-settings',
		$mhk_screen_id . '_page_mhk-tools',
		$mhk_screen_id . '_page_mhk-addons',
		$mhk_screen_id . '_page_mhk-email-templates',
	);

	return apply_filters( 'muhiku_forms_screen_ids', $screen_ids );
}

/**
 * @param mixed  $slug         
 * @param string $option      
 * @param string $page_title   
 * @param string $page_content 
 * @param int    $post_parent  
 *
 * @return int page ID
 */
function mhk_create_page( $slug, $option = '', $page_title = '', $page_content = '', $post_parent = 0 ) {
	global $wpdb;

	$option_value = get_option( $option );
	$page_object  = get_post( $option_value );

	if ( $option_value > 0 && $page_object ) {
		if ( 'page' === $page_object->post_type && ! in_array(
			$page_object->post_status,
			array(
				'pending',
				'trash',
				'future',
				'auto-draft',
			),
			true
		) ) {
			return $page_object->ID;
		}
	}

	if ( strlen( $page_content ) > 0 ) {
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' ) AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		$valid_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status NOT IN ( 'pending', 'trash', 'future', 'auto-draft' )  AND post_name = %s LIMIT 1;", $slug ) );
	}

	$valid_page_found = apply_filters( 'muhiku_forms_create_page_id', $valid_page_found, $slug, $page_content );

	if ( $valid_page_found ) {
		if ( $option ) {
			update_option( $option, $valid_page_found );
		}

		return $valid_page_found;
	}

	if ( strlen( $page_content ) > 0 ) {
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_content LIKE %s LIMIT 1;", "%{$page_content}%" ) );
	} else {
		$trashed_page_found = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type='page' AND post_status = 'trash' AND post_name = %s LIMIT 1;", $slug ) );
	}

	if ( $trashed_page_found ) {
		$page_id   = $trashed_page_found;
		$page_data = array(
			'ID'          => $page_id,
			'post_status' => 'publish',
		);
		wp_update_post( $page_data );
	} else {
		$page_data = array(
			'post_status'    => 'publish',
			'post_type'      => 'page',
			'post_author'    => 1,
			'post_name'      => $slug,
			'post_title'     => $page_title,
			'post_content'   => $page_content,
			'post_parent'    => $post_parent,
			'comment_status' => 'closed',
		);
		$page_id   = wp_insert_post( $page_data );
	}

	if ( $option ) {
		update_option( $option, $page_id );
	}

	return $page_id;
}

/**
 * @param array[] $options Opens array to output.
 */
function muhiku_forms_admin_fields( $options ) {
	if ( ! class_exists( 'MHK_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-mhk-admin-settings.php';
	}

	MHK_Admin_Settings::output_fields( $options );
}

/**
 * @param array $options 
 * @param array $data   
 */
function muhiku_forms_update_options( $options, $data = null ) {
	if ( ! class_exists( 'MHK_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-mhk-admin-settings.php';
	}

	MHK_Admin_Settings::save_fields( $options, $data );
}

/**
 * @param string $option_name 
 * @param mixed  $default    
 *
 * @return string
 */
function muhiku_forms_settings_get_option( $option_name, $default = '' ) {
	if ( ! class_exists( 'MHK_Admin_Settings', false ) ) {
		include dirname( __FILE__ ) . '/class-mhk-admin-settings.php';
	}

	return MHK_Admin_Settings::get_option( $option_name, $default );
}

/**
 * @param string  $option 
 * @param string  $panel 
 * @param string  $field  
 * @param array   $form_data
 * @param string  $label  
 * @param array   $args   
 * @param boolean $echo   
 *
 * @return string
 */
function muhiku_forms_panel_field( $option, $panel, $field, $form_data, $label, $args = array(), $echo = true ) {

	if ( empty( $option ) || empty( $panel ) || empty( $field ) ) {
		return '';
	}
	$panel       = esc_attr( $panel );
	$field       = esc_attr( $field );
	$panel_id    = sanitize_html_class( $panel );
	$parent      = ! empty( $args['parent'] ) ? esc_attr( $args['parent'] ) : '';
	$subsection  = ! empty( $args['subsection'] ) ? esc_attr( $args['subsection'] ) : '';
	$label       = ! empty( $label ) ? $label : '';
	$class       = ! empty( $args['class'] ) ? esc_attr( $args['class'] ) : '';
	$input_class = ! empty( $args['input_class'] ) ? esc_attr( $args['input_class'] ) : '';
	$default     = isset( $args['default'] ) ? $args['default'] : '';
	$tinymce     = isset( $args['tinymce'] ) ? $args['tinymce'] : '';
	$placeholder = ! empty( $args['placeholder'] ) ? esc_attr( $args['placeholder'] ) : '';
	$data_attr   = '';
	$output      = '';

	if ( ! empty( $parent ) ) {
		if ( ! empty( $subsection ) ) {
			$field_name = sprintf( '%s[%s][%s][%s]', $parent, $panel, $subsection, $field );
			$value      = isset( $form_data[ $parent ][ $panel ][ $subsection ][ $field ] ) ? $form_data[ $parent ][ $panel ][ $subsection ][ $field ] : $default;
			$panel_id   = sanitize_html_class( $panel . '-' . $subsection );
		} else {
			$field_name = sprintf( '%s[%s][%s]', $parent, $panel, $field );
			$value      = isset( $form_data[ $parent ][ $panel ][ $field ] ) ? $form_data[ $parent ][ $panel ][ $field ] : $default;
		}
	} else {

		$field_name = sprintf( '%s[%s]', $panel, $field );
		$value      = isset( $form_data[ $panel ][ $field ] ) ? $form_data[ $panel ][ $field ] : $default;
	}

	if ( ! empty( $args['data'] ) ) {
		foreach ( $args['data'] as $key => $val ) {
			if ( is_array( $val ) ) {
				$val = wp_json_encode( $val );
			}
			$data_attr .= ' data-' . $key . '=\'' . $val . '\'';
		}
	}

	switch ( $option ) {

		case 'text':
			$type   = ! empty( $args['type'] ) ? esc_attr( $args['type'] ) : 'text';
			$output = sprintf(
				'<input type="%s" id="muhiku-plug-panel-field-%s-%s" name="%s" value="%s" placeholder="%s" class="widefat %s" %s>',
				$type,
				sanitize_html_class( $panel_id ),
				sanitize_html_class( $field ),
				$field_name,
				esc_attr( $value ),
				$placeholder,
				$input_class,
				$data_attr
			);
			break;

		case 'textarea':
			$rows   = ! empty( $args['rows'] ) ? (int) $args['rows'] : '3';
			$output = sprintf(
				'<textarea id="muhiku-plug-panel-field-%s-%s" name="%s" rows="%d" placeholder="%s" class="widefat %s" %s>%s</textarea>',
				sanitize_html_class( $panel_id ),
				sanitize_html_class( $field ),
				$field_name,
				$rows,
				$placeholder,
				$input_class,
				$data_attr,
				esc_textarea( $value )
			);
			break;

		case 'tinymce':
			$arguments                  = wp_parse_args(
				$tinymce,
				array(
					'media_buttons' => false,
					'tinymce'       => false,
				)
			);
			$arguments['textarea_name'] = $field_name;
			$arguments['teeny']         = true;
			$id                         = 'muhiku-plug-panel-field-' . sanitize_html_class( $panel_id ) . '-' . sanitize_html_class( $field );
			$id                         = str_replace( '-', '_', $id );
			ob_start();
			wp_editor( $value, $id, $arguments );
			$output = ob_get_clean();
			break;

		case 'checkbox':
			$checked   = checked( '1', $value, false );
			$checkbox  = sprintf(
				'<input type="hidden" name="%s" value="0" class="widefat %s" %s %s>',
				$field_name,
				$input_class,
				$checked,
				$data_attr
			);
			$checkbox .= sprintf(
				'<input type="checkbox" id="muhiku-plug-panel-field-%s-%s" name="%s" value="1" class="%s" %s %s>',
				sanitize_html_class( $panel_id ),
				sanitize_html_class( $field ),
				$field_name,
				$input_class,
				$checked,
				$data_attr
			);
			$output    = sprintf(
				'<label for="muhiku-plug-panel-field-%s-%s" class="inline">%s',
				sanitize_html_class( $panel_id ),
				sanitize_html_class( $field ),
				$checkbox . $label
			);
			if ( ! empty( $args['tooltip'] ) ) {
				$output .= sprintf( ' <i class="dashicons dashicons-editor-help muhiku-plug-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
			}
			$output .= '</label>';
			break;

		case 'radio':
			$options = $args['options'];
			$x       = 1;
			$output  = '';
			foreach ( $options as $key => $item ) {
				if ( empty( $item['label'] ) ) {
					continue;
				}
				$checked = checked( $key, $value, false );
				$output .= sprintf(
					'<span class="row"><input type="radio" id="muhiku-plug-panel-field-%s-%s-%d" name="%s" value="%s" class="widefat %s" %s %s>',
					sanitize_html_class( $panel_id ),
					sanitize_html_class( $field ),
					$x,
					$field_name,
					$key,
					$input_class,
					$checked,
					$data_attr
				);
				$output .= sprintf(
					'<label for="muhiku-plug-panel-field-%s-%s-%d" class="inline">%s',
					sanitize_html_class( $panel_id ),
					sanitize_html_class( $field ),
					$x,
					$item['label']
				);
				if ( ! empty( $item['tooltip'] ) ) {
					$output .= sprintf( ' <i class="dashicons dashicons-editor-help muhiku-plug-help-tooltip" title="%s"></i>', esc_attr( $item['tooltip'] ) );
				}
				$output .= '</label></span>';
				$x ++;
			}
			break;

		case 'select':
			$is_multiple = isset( $args['multiple'] ) && true === $args['multiple'];
			if ( empty( $args['options'] ) && empty( $args['field_map'] ) ) {
				return '';
			}

			if ( ! empty( $args['field_map'] ) ) {
				$options          = array();
				$available_fields = mhk_get_form_fields( $form_data, $args['field_map'] );
				if ( ! empty( $available_fields ) ) {
					foreach ( $available_fields as $id => $available_field ) {
						$lbl            = ! empty( $available_field['label'] ) ? esc_attr( $available_field['label'] ) : esc_html__( 'Field #', 'muhiku-plug' ) . $id;
						$options[ $id ] = $lbl;
					}
				}
				$input_class .= ' muhiku-plug-field-map-select';
				$data_attr   .= ' data-field-map-allowed="' . implode( ' ', $args['field_map'] ) . '"';
				if ( ! empty( $placeholder ) ) {
					$data_attr .= ' data-field-map-placeholder="' . esc_attr( $placeholder ) . '"';
				}
			} else {
				$options = $args['options'];
			}

			if ( true === $is_multiple ) {
				$multiple = 'multiple';
			} else {
				$multiple = '';
			}

			$output = sprintf(
				'<select id="muhiku-plug-panel-field-%s-%s" name="%s" class="widefat %s" %s ' . $multiple . '>',
				sanitize_html_class( $panel_id ),
				sanitize_html_class( $field ),
				$field_name,
				$input_class,
				$data_attr
			);

			if ( ! empty( $placeholder ) ) {
				$output .= '<option value="">' . $placeholder . '</option>';
			}

			foreach ( $options as $key => $item ) {
				if ( true === $is_multiple && is_array( $value ) ) {
					 $output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( in_array( $key, $value, true ), true, false ), $item );
				} else {
					$output .= sprintf( '<option value="%s" %s>%s</option>', esc_attr( $key ), selected( $key, $value, false ), $item );
				}
			}
			$output .= '</select>';
			break;
	}

	$smarttags_class = ! empty( $args['smarttags'] ) ? 'mhk_smart_tag' : '';

	$field_open  = sprintf(
		'<div id="muhiku-plug-panel-field-%s-%s-wrap" class="muhiku-plug-panel-field %s %s %s">',
		sanitize_html_class( $panel_id ),
		sanitize_html_class( $field ),
		$class,
		$smarttags_class,
		'muhiku-plug-panel-field-' . sanitize_html_class( $option )
	);
	$field_open .= ! empty( $args['before'] ) ? $args['before'] : '';
	if ( ! in_array( $option, array( 'checkbox' ), true ) && ! empty( $label ) ) {
		$field_label = sprintf(
			'<label for="muhiku-plug-panel-field-%s-%s">%s',
			sanitize_html_class( $panel_id ),
			sanitize_html_class( $field ),
			$label
		);
		if ( ! empty( $args['tooltip'] ) ) {
			$field_label .= sprintf( ' <i class="dashicons dashicons-editor-help muhiku-plug-help-tooltip" title="%s"></i>', esc_attr( $args['tooltip'] ) );
		}
		if ( ! empty( $args['after_tooltip'] ) ) {
			$field_label .= $args['after_tooltip'];
		}
		if ( ! empty( $args['smarttags'] ) ) {
			$smart_tag = '';

			$type        = ! empty( $args['smarttags']['type'] ) ? esc_attr( $args['smarttags']['type'] ) : 'form_fields';
			$form_fields = ! empty( $args['smarttags']['form_fields'] ) ? esc_attr( $args['smarttags']['form_fields'] ) : '';

			$smart_tag .= '<a href="#" class="mhk-toggle-smart-tag-display" data-type="' . $type . '" data-fields="' . $form_fields . '"><span class="dashicons dashicons-editor-code"></span></a>';
			$smart_tag .= '<div class="mhk-smart-tag-lists" style="display: none">';
			$smart_tag .= '<div class="smart-tag-title">';
			$smart_tag .= esc_html__( 'Available Fields', 'muhiku-plug' );
			$smart_tag .= '</div><ul class="mhk-fields"></ul>';
			if ( 'all' === $type || 'other' === $type ) {
				$smart_tag .= '<div class="smart-tag-title other-tag-title">';
				$smart_tag .= esc_html__( 'Others', 'muhiku-plug' );
				$smart_tag .= '</div><ul class="mhk-others"></ul>';
			}
			$smart_tag .= '</div>';
		} else {
			$smart_tag = '';
		}

		$field_label .= '</label>';
	} else {
		$field_label = '';
		$smart_tag   = '';
	}
	$field_close  = ! empty( $args['after'] ) ? $args['after'] : '';
	$field_close .= '</div>';
	$output       = $field_open . $field_label . $output . $smart_tag . $field_close;

	if ( $echo ) {
		echo wp_kses( $output, mhk_get_allowed_html_tags( 'builder' ) );
	} else {
		return $output;
	}
}

<?php
/**
 * @package EverestForms\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MHK_Admin_Settings', false ) ) :

	class MHK_Admin_Settings {

		/**
		 * @var array
		 */
		private static $settings = array();

		/**
		 * @var array
		 */
		private static $errors = array();

		/**
		 * @var array
		 */
		private static $messages = array();

		public static function get_settings_pages() {
			if ( empty( self::$settings ) ) {
				$settings = array();

				include_once dirname( __FILE__ ) . '/settings/class-mhk-settings-page.php';

				$settings[] = include 'settings/class-mhk-settings-general.php';
				$settings[] = include 'settings/class-mhk-settings-recaptcha.php';
				$settings[] = include 'settings/class-mhk-settings-email.php';
				$settings[] = include 'settings/class-mhk-settings-validation.php';
				$settings[] = include 'settings/class-mhk-settings-integrations.php';

				self::$settings = apply_filters( 'muhiku_forms_get_settings_pages', $settings );
			}

			return self::$settings;
		}

		public static function save() {
			global $current_tab;

			check_admin_referer( 'muhiku-forms-settings' );

			do_action( 'muhiku_forms_settings_save_' . $current_tab );
			do_action( 'muhiku_forms_update_options_' . $current_tab );
			do_action( 'muhiku_forms_update_options' );

			self::add_message( esc_html__( 'Your settings have been saved.', 'muhiku-forms' ) );

			update_option( 'muhiku_forms_queue_flush_rewrite_rules', 'yes' );

			do_action( 'muhiku_forms_settings_saved' );
		}

		/**
		 * @param string $text Message.
		 */
		public static function add_message( $text ) {
			self::$messages[] = $text;
		}

		/**
		 * @param string $text Message.
		 */
		public static function add_error( $text ) {
			self::$errors[] = $text;
		}

		public static function show_messages() {
			if ( count( self::$errors ) > 0 ) {
				foreach ( self::$errors as $error ) {
					echo '<div id="message" class="error inline"><p><strong>' . wp_kses_post( $error ) . '</strong></p></div>';
				}
			} elseif ( count( self::$messages ) > 0 ) {
				foreach ( self::$messages as $message ) {
					echo '<div id="message" class="updated inline"><p><strong>' . esc_html( $message ) . '</strong></p></div>';
				}
			}
		}

		public static function output() {
			global $current_section, $current_tab;

			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			do_action( 'muhiku_forms_settings_start' );

			wp_enqueue_script( 'muhiku_forms_settings', mhk()->plugin_url() . '/assets/js/admin/settings' . $suffix . '.js', array( 'jquery', 'jquery-confirm', 'jquery-ui-datepicker', 'jquery-ui-sortable', 'iris', 'selectWoo' ), mhk()->version, true );

			wp_localize_script(
				'muhiku_forms_settings',
				'muhiku_forms_settings_params',
				array(
					'i18n_nav_warning' => __( 'The changes you made will be lost if you navigate away from this page.', 'muhiku-forms' ),
				)
			);

			$tabs = apply_filters( 'muhiku_forms_settings_tabs_array', array() );

			include dirname( __FILE__ ) . '/views/html-admin-settings.php';
		}

		/**
		 * @param string $option_name Option name.
		 * @param mixed  $default     Default value.
		 * @return mixed
		 */
		public static function get_option( $option_name, $default = '' ) {
			if ( ! $option_name ) {
				return $default;
			}

			if ( strstr( $option_name, '[' ) ) {

				parse_str( $option_name, $option_array );

				$option_name = current( array_keys( $option_array ) );

				$option_values = get_option( $option_name, '' );

				$key = key( $option_array[ $option_name ] );

				if ( isset( $option_values[ $key ] ) ) {
					$option_value = $option_values[ $key ];
				} else {
					$option_value = null;
				}
			} else {
				$option_value = get_option( $option_name, null );
			}

			if ( is_array( $option_value ) ) {
				$option_value = array_map( 'stripslashes', $option_value );
			} elseif ( ! is_null( $option_value ) ) {
				$option_value = stripslashes( $option_value );
			}

			return ( null === $option_value ) ? $default : $option_value;
		}

		/**
		 * @param array[] $options Opens array to output.
		 */
		public static function output_fields( $options ) {
			foreach ( $options as $value ) {
				if ( ! isset( $value['type'] ) ) {
					continue;
				}
				if ( ! isset( $value['id'] ) ) {
					$value['id'] = '';
				}
				if ( ! isset( $value['title'] ) ) {
					$value['title'] = isset( $value['name'] ) ? $value['name'] : '';
				}
				if ( ! isset( $value['class'] ) ) {
					$value['class'] = '';
				}
				if ( ! isset( $value['css'] ) ) {
					$value['css'] = '';
				}
				if ( ! isset( $value['default'] ) ) {
					$value['default'] = '';
				}
				if ( ! isset( $value['desc'] ) ) {
					$value['desc'] = '';
				}
				if ( ! isset( $value['desc_tip'] ) ) {
					$value['desc_tip'] = false;
				}
				if ( ! isset( $value['placeholder'] ) ) {
					$value['placeholder'] = '';
				}
				if ( ! isset( $value['suffix'] ) ) {
					$value['suffix'] = '';
				}
				if ( ! isset( $value['value'] ) ) {
					$value['value'] = self::get_option( $value['id'], $value['default'] );
				}

				$custom_attributes = array();

				if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
					foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
						$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
					}
				}

				$field_description = self::get_field_description( $value );
				$description       = $field_description['description'];
				$tooltip_html      = $field_description['tooltip_html'];

				switch ( $value['type'] ) {

					case 'title':
						if ( ! empty( $value['title'] ) ) {
							echo '<h2>' . esc_html( $value['title'] ) . '</h2>';
						}
						if ( ! empty( $value['desc'] ) ) {
							echo wp_kses_post( wpautop( wptexturize( $value['desc'] ) ) );
						}
						echo '<table class="form-table">' . "\n\n";
						if ( ! empty( $value['id'] ) ) {
							do_action( 'muhiku_forms_settings_' . sanitize_title( $value['id'] ) );
						}
						break;

					case 'sectionend':
						if ( ! empty( $value['id'] ) ) {
							do_action( 'muhiku_forms_settings_' . sanitize_title( $value['id'] ) . '_end' );
						}
						echo '</table>';
						if ( ! empty( $value['id'] ) ) {
							do_action( 'muhiku_forms_settings_' . sanitize_title( $value['id'] ) . '_after' );
						}
						break;

					case 'text':
					case 'password':
					case 'datetime':
					case 'datetime-local':
					case 'date':
					case 'date-time':
					case 'month':
					case 'time':
					case 'week':
					case 'number':
					case 'email':
					case 'url':
					case 'tel':
						$option_value     = $value['value'];
						$visibility_class = array();

						if ( isset( $value['is_visible'] ) ) {
							$visibility_class[] = $value['is_visible'] ? 'muhiku-forms-visible' : 'muhiku-forms-hidden';
						}

						if ( empty( $option_value ) ) {
							$option_value = $value['default'];
						}

						?><tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="<?php echo esc_attr( $value['type'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php
									if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
										foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
											echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
										}
									}
									?>
									/><?php echo esc_html( $value['suffix'] ); ?> <?php echo wp_kses_post( $description ); ?>
							</td>
						</tr>
						<?php
						break;
					case 'image':
						$option_value = $value['value'];
						if ( empty( $option_value ) ) {
							$option_value = $value['default'];
						}
						$visibility_class = array();
						if ( isset( $value['is_visible'] ) ) {
							$visibility_class[] = $value['is_visible'] ? 'muhiku-forms-visible' : 'muhiku-forms-hidden';
						}

						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
							<img src="<?php echo esc_attr( $option_value ); ?>" alt="<?php echo esc_attr__( 'Header Logo', 'muhiku-forms' ); ?>" class="mhk-image-uploader <?php echo empty( $option_value ) ? 'muhiku-forms-hidden' : ''; ?>" height="100" width="auto">
							<button type="button" class="mhk-image-uploader mhk-button button-secondary" <?php echo empty( $option_value ) ? '' : 'style="display:none"'; ?> ><?php echo esc_html__( 'Upload Logo', 'muhiku-forms' ); ?></button>
							<input
								name="<?php echo esc_attr( $value['id'] ); ?>"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								value="<?php echo esc_attr( $option_value ); ?>"
								type="hidden"
							>
						<?php
						wp_enqueue_script( 'jquery' );
						wp_enqueue_media();
						wp_enqueue_script( 'mhk-file-uploader' );
						break;
					case 'color':
						$option_value = $value['value'];

						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
								<span class="colorpickpreview" style="background: <?php echo esc_attr( $option_value ); ?>">&nbsp;</span>
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="text"
									dir="ltr"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									value="<?php echo esc_attr( $option_value ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>colorpick"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php
									if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
										foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
											echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
										}
									}
									?>
									/>&lrm; <?php echo wp_kses_post( $description ); ?>
									<div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
							</td>
						</tr>
						<?php
						break;

					case 'textarea':
						$option_value = $value['value'];

						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<?php echo wp_kses_post( $description ); ?>

								<textarea
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
									<?php
									if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
										foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
											echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
										}
									}
									?>
									><?php echo esc_textarea( $option_value ); ?></textarea>
							</td>
						</tr>
						<?php
						break;

					case 'select':
					case 'multiselect':
						$option_value = $value['value'];

						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<select
									name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									style="<?php echo esc_attr( $value['css'] ); ?>"
									class="<?php echo esc_attr( $value['class'] ); ?>"
									<?php
									if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
										foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
											echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
										}
									}
									?>
									<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
									>
									<?php
									foreach ( $value['options'] as $key => $val ) {
										?>
										<option value="<?php echo esc_attr( $key ); ?>"
											<?php

											if ( is_array( $option_value ) ) {
												selected( in_array( (string) $key, $option_value, true ), true );
											} else {
												selected( $option_value, (string) $key );
											}

											?>
										>
										<?php echo esc_html( $val ); ?></option>
										<?php
									}
									?>
								</select> <?php echo wp_kses_post( $description ); ?>
							</td>
						</tr>
						<?php
						break;

					case 'radio':
						$option_value = $value['value'];

						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<fieldset>
									<?php echo wp_kses_post( $description ); ?>
									<ul class="<?php echo esc_attr( $value['class'] ); ?>">
									<?php
									foreach ( $value['options'] as $key => $val ) {
										?>
										<li>
											<label><input
												name="<?php echo esc_attr( $value['id'] ); ?>"
												id="<?php echo esc_attr( $value['id'] ); ?>"
												value="<?php echo esc_attr( $key ); ?>"
												type="radio"
												style="<?php echo esc_attr( $value['css'] ); ?>"
												class="<?php echo esc_attr( $value['class'] ); ?>"
												<?php
												if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
													foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
														echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
													}
												}
												?>
																								<?php checked( $key, $option_value ); ?>
												/> <?php echo esc_html( $val ); ?></label>
										</li>
										<?php
									}
									?>
									</ul>
								</fieldset>
							</td>
						</tr>
						<?php
						break;

					case 'toggle':
						$option_value = $value['value'];

						if ( empty( $option_value ) ) {
							$option_value = $value['default'];
						}
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<?php echo wp_kses_post( $description ); ?>
								<div class="mhk-toggle-section">
									<span class="muhiku-forms-toggle-form">
										<input
											type="checkbox"
											name="<?php echo esc_attr( $value['id'] ); ?>"
											id="<?php echo esc_attr( $value['id'] ); ?>"
											style="<?php echo esc_attr( $value['css'] ); ?>"
											class="<?php echo esc_attr( $value['class'] ); ?>"
											value="yes"
											<?php checked( 'yes', $option_value, true ); ?>
										>
										<span class="slider round"></span>
									</span>
								</div>
							</td>
						</tr>
						<?php
						break;

					case 'checkbox':
						$option_value     = $value['value'];
						$visibility_class = array();

						if ( ! isset( $value['hide_if_checked'] ) ) {
							$value['hide_if_checked'] = false;
						}
						if ( ! isset( $value['show_if_checked'] ) ) {
							$value['show_if_checked'] = false;
						}
						if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
							$visibility_class[] = 'hidden_option';
						}
						if ( 'option' === $value['hide_if_checked'] ) {
							$visibility_class[] = 'hide_options_if_checked';
						}
						if ( 'option' === $value['show_if_checked'] ) {
							$visibility_class[] = 'show_options_if_checked';
						}
						if ( isset( $value['is_visible'] ) ) {
							$visibility_class[] = $value['is_visible'] ? 'muhiku-forms-visible' : 'muhiku-forms-hidden';
						}

						if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) {
							?>
								<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
									<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
									<td class="forminp forminp-checkbox">
										<fieldset>
							<?php
						} else {
							?>
								<fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
							<?php
						}

						if ( ! empty( $value['title'] ) ) {
							?>
								<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
							<?php
						}

						?>
							<label for="<?php echo esc_attr( $value['id'] ); ?>">
								<input
									name="<?php echo esc_attr( $value['id'] ); ?>"
									id="<?php echo esc_attr( $value['id'] ); ?>"
									type="checkbox"
									class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
									value="1"
									<?php checked( $option_value, 'yes' ); ?>
									<?php
									if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
										foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
											echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
										}
									}
									?>
									/>
									<?php echo wp_kses_post( $description ); ?>
							</label> <?php echo wp_kses_post( $tooltip_html ); ?>
						<?php

						if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) {
							?>
										</fieldset>
									</td>
								</tr>
							<?php
						} else {
							?>
								</fieldset>
							<?php
						}
						break;

					case 'single_select_page':
						$args = array(
							'name'             => $value['id'],
							'id'               => $value['id'],
							'sort_column'      => 'menu_order',
							'sort_order'       => 'ASC',
							'show_option_none' => ' ',
							'class'            => $value['class'],
							'echo'             => false,
							'selected'         => absint( $value['value'] ),
							'post_status'      => 'publish,private,draft',
						);

						if ( isset( $value['args'] ) ) {
							$args = wp_parse_args( $value['args'], $args );
						}

						?>
						<tr valign="top" class="single_select_page">
							<th scope="row" class="titledesc">
								<label><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp">
								<?php echo wp_kses_post( str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'muhiku-forms' ) . "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ) ); ?> <?php echo wp_kses_post( $description ); ?>
							</td>
						</tr>
						<?php
						break;

					// For anchor tag.
					case 'link':
						?>
						<tr valign="top">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
								<?php
								if ( isset( $value['buttons'] ) && is_array( $value['buttons'] ) ) {
									foreach ( $value['buttons'] as $button ) {
										?>
										<a href="<?php echo esc_url( $button['href'] ); ?>" class="button <?php echo esc_attr( $button['class'] ); ?>"
										style="<?php echo esc_attr( $value['css'] ); ?>"
										<?php
										if ( ! empty( $value['custom_attributes'] ) && is_array( $value['custom_attributes'] ) ) {
											foreach ( $value['custom_attributes'] as $attribute => $attribute_value ) {
												echo esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
											}
										}
										?>
										>
											<?php echo esc_html( $button['title'] ); ?>
										</a>
										<?php
									}
								}
								?>
								<?php echo esc_html( $value['suffix'] ); ?> <?php echo wp_kses_post( $description ); ?>
							</td>
						</tr>
						<?php
						break;

					// Default: run an action.
					default:
						do_action( 'muhiku_forms_admin_field_' . $value['type'], $value );
						break;
				}
			}
		}

		/**
		 * @param  array $value 
		 * @return array The description and tip as a 2 element array.
		 */
		public static function get_field_description( $value ) {
			$description  = '';
			$tooltip_html = '';

			if ( true === $value['desc_tip'] ) {
				$tooltip_html = $value['desc'];
			} elseif ( ! empty( $value['desc_tip'] ) ) {
				$description  = $value['desc'];
				$tooltip_html = $value['desc_tip'];
			} elseif ( ! empty( $value['desc'] ) ) {
				$description = $value['desc'];
			}

			if ( $description && in_array( $value['type'], array( 'textarea', 'radio' ), true ) ) {
				$description = '<p style="margin-top:0">' . wp_kses_post( $description ) . '</p>';
			} elseif ( $description && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$description = wp_kses_post( $description );
			} elseif ( $description ) {
				$description = '<p class="description">' . wp_kses_post( $description ) . '</p>';
			}

			if ( $tooltip_html && in_array( $value['type'], array( 'checkbox' ), true ) ) {
				$tooltip_html = '<p class="description">' . $tooltip_html . '</p>';
			} elseif ( $tooltip_html ) {
				$tooltip_html = mhk_help_tip( $tooltip_html );
			}

			return array(
				'description'  => $description,
				'tooltip_html' => $tooltip_html,
			);
		}

		/**
		 * @param array $options Options array to output.
		 * @param array $data    Optional. Data to use for saving. Defaults to $_POST.
		 * @return bool
		 */
		public static function save_fields( $options, $data = null ) {
			if ( is_null( $data ) ) {
				$data = $_POST;
			}
			if ( empty( $data ) ) {
				return false;
			}

			$update_options   = array();
			$autoload_options = array();

			foreach ( $options as $option ) {
				if ( ! isset( $option['id'] ) || ! isset( $option['type'] ) || ( isset( $option['is_option'] ) && false === $option['is_option'] ) ) {
					continue;
				}

				if ( strstr( $option['id'], '[' ) ) {
					parse_str( $option['id'], $option_name_array );
					$option_name  = current( array_keys( $option_name_array ) );
					$setting_name = key( $option_name_array[ $option_name ] );
					$raw_value    = isset( $data[ $option_name ][ $setting_name ] ) ? wp_unslash( $data[ $option_name ][ $setting_name ] ) : null;
				} else {
					$option_name  = $option['id'];
					$setting_name = '';
					$raw_value    = isset( $data[ $option['id'] ] ) ? wp_unslash( $data[ $option['id'] ] ) : null;
				}

				switch ( $option['type'] ) {
					case 'checkbox':
						$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
						break;
					case 'toggle':
						$value = '1' === $raw_value || 'yes' === $raw_value ? 'yes' : 'no';
						break;
					case 'textarea':
						$value = wp_kses_post( trim( $raw_value ) );
						break;
					case 'select':
						$allowed_values = empty( $option['options'] ) ? array() : array_map( 'strval', array_keys( $option['options'] ) );
						if ( empty( $option['default'] ) && empty( $allowed_values ) ) {
							$value = null;
							break;
						}
						$default = ( empty( $option['default'] ) ? $allowed_values[0] : $option['default'] );
						$value   = in_array( $raw_value, $allowed_values, true ) ? $raw_value : $default;
						break;
					default:
						$value = mhk_clean( $raw_value );
						break;
				}

				$value = apply_filters( 'muhiku_forms_admin_settings_sanitize_option', $value, $option, $raw_value );

				$value = apply_filters( "muhiku_forms_admin_settings_sanitize_option_$option_name", $value, $option, $raw_value );

				if ( is_null( $value ) ) {
					continue;
				}

				if ( $option_name && $setting_name ) {
					if ( ! isset( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = get_option( $option_name, array() );
					}
					if ( ! is_array( $update_options[ $option_name ] ) ) {
						$update_options[ $option_name ] = array();
					}
					$update_options[ $option_name ][ $setting_name ] = $value;
				} else {
					$update_options[ $option_name ] = $value;
				}

				$autoload_options[ $option_name ] = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;

				do_action( 'muhiku_forms_update_option', $option );
			}

			foreach ( $update_options as $name => $value ) {
				update_option( $name, $value, $autoload_options[ $name ] ? 'yes' : 'no' );
			}

			return true;
		}
	}

endif;

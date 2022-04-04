<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Builder_Fields', false ) ) {
	return new MHK_Builder_Fields();
}

class MHK_Builder_Fields extends MHK_Builder_Page {

	/**
	 * @var array
	 */
	public static $parts = array();

	public function __construct() {
		$this->id      = 'fields';
		$this->label   = __( 'Alanlar', 'muhiku-plug' );
		$this->sidebar = true;

		parent::__construct();
	}
	public function init_hooks() {
		if ( is_object( $this->form ) ) {
			add_action( 'muhiku_forms_builder_fields', array( $this, 'output_fields' ) );
			add_action( 'muhiku_forms_builder_fields_options', array( $this, 'output_fields_options' ) );
			add_action( 'muhiku_forms_builder_fields_preview', array( $this, 'output_fields_preview' ) );
		}
	}

	public function output_sidebar() {
		?>
		<div class="muhiku-plug-fields-tab">
			<a href="#" id="add-fields" class="fields active"><?php esc_html_e( 'Alan Ekle', 'muhiku-plug' ); ?></a>
			<a href="#" id="field-options" class="options"><?php esc_html_e( 'Alan Özellikleri', 'muhiku-plug' ); ?></a>
			<?php do_action( 'muhiku_forms_builder_fields_tab', $this->form ); ?>
		</div>
		<div class="muhiku-plug-tab-content">
			<div class="muhiku-plug-add-fields">
				<div class="muhiku-plug-input-group muhiku-plug-search-input mhk-mb-3">
					<input id="muhiku-plug-search-fields" class="muhiku-plug-input-control muhiku-plug-search-fields" type="text" placeholder="<?php esc_attr_e( 'Alan ara&hellip;', 'muhiku-plug' ); ?>" />
					<div class="muhiku-plug-input-group__append">
						<div class="muhiku-plug-input-group__text">
							<svg xmlns="http://www.w3.org/2000/svg" height="20px" width="20px" viewBox="0 0 24 24" fill="#a1a4b9"><path d="M21.71,20.29,18,16.61A9,9,0,1,0,16.61,18l3.68,3.68a1,1,0,0,0,1.42,0A1,1,0,0,0,21.71,20.29ZM11,18a7,7,0,1,1,7-7A7,7,0,0,1,11,18Z"/></svg>
						</div>
					</div>
				</div>
				<div class="hidden muhiku-plug-fields-not-found">
					<img src="<?php echo esc_attr( plugin_dir_url( MHK_PLUGIN_FILE ) . 'assets/images/fields-not-found.png' ); ?>" />
					<h3 class="muhiku-plug-fields-not-found__title"><?php esc_html_e( 'Oops!', 'muhiku-plug' ); ?></h3>
					<span><?php esc_html_e( 'There is not such field that you are searching for.', 'muhiku-plug' ); ?></span>
				</div>
				<?php do_action( 'muhiku_forms_builder_fields', $this->form ); ?>
			</div>
			<div class="muhiku-plug-field-options">
				<?php do_action( 'muhiku_forms_builder_fields_options', $this->form ); ?>
			</div>
			<?php do_action( 'muhiku_forms_builder_fields_tab_content', $this->form ); ?>
		</div>
		<?php
	}

	public function output_content() {
		?>
		<div class="muhiku-plug-preview-wrap">
			<div class="muhiku-plug-preview">
				<div class="muhiku-plug-title-desc">
					<input id= "mhk-edit-form-name" type="text" class="muhiku-plug-form-name muhiku-plug-name-input" value ="<?php echo isset( $this->form->post_title ) ? esc_html( $this->form->post_title ) : esc_html__( 'Form not found.', 'muhiku-plug' ); ?>" disabled autocomplete="off" required>
					<span id="edit-form-name" class = "mhk-icon dashicons dashicons-edit"></span>
				</div>
				<div class="muhiku-plug-field-wrap">
					<?php do_action( 'muhiku_forms_builder_fields_preview', $this->form ); ?>
				</div>
				<?php mhk_debug_data( $this->form_data ); ?>
			</div>
		</div>
		<?php
	}

	public function output_fields() {
		$form_fields = mhk()->form_fields->form_fields();

		if ( ! empty( $form_fields ) ) {
			foreach ( $form_fields as $group => $form_field ) {
				?>
				<div class="muhiku-plug-add-fields-group open">
					<a href="#" class="muhiku-plug-add-fields-heading" data-group="<?php echo esc_attr( $group ); ?>"><?php echo esc_html( mhk_get_fields_group( $group ) ); ?><i class="handlediv"></i></a>
					<div class="mhk-registered-buttons">
						<?php foreach ( $form_field as $field ) : ?>
							<button type="button" id="muhiku-plug-add-fields-<?php echo esc_attr( $field->type ); ?>" class="mhk-registered-item <?php echo sanitize_html_class( $field->class ); ?>" data-field-type="<?php echo esc_attr( $field->type ); ?>">
								<?php if ( isset( $field->icon ) ) : ?>
									<i class="<?php echo esc_attr( $field->icon ); ?>"></i>
								<?php endif; ?>
								<?php echo esc_html( $field->name ); ?>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
				<?php
			}
		}
	}

	public function output_fields_options() {
		$fields = isset( $this->form_data['form_fields'] ) ? $this->form_data['form_fields'] : array();

		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				if ( in_array( $field['type'], mhk()->form_fields->get_pro_form_field_types(), true ) ) {
					continue;
				}

				$field_option_class = apply_filters(
					'muhiku_forms_builder_field_option_class',
					array(
						'muhiku-plug-field-option',
						'muhiku-plug-field-option-' . esc_attr( $field['type'] ),
					),
					$field
				);

				?>
				<div class="<?php echo esc_attr( implode( ' ', $field_option_class ) ); ?>" id="muhiku-plug-field-option-<?php echo esc_attr( $field['id'] ); ?>" data-field-id="<?php echo esc_attr( $field['id'] ); ?>" >
					<input type="hidden" name="form_fields[<?php echo esc_attr( $field['id'] ); ?>][id]" value="<?php echo esc_attr( $field['id'] ); ?>" class="muhiku-plug-field-option-hidden-id" />
					<input type="hidden" name="form_fields[<?php echo esc_attr( $field['id'] ); ?>][type]" value="<?php echo esc_attr( $field['type'] ); ?>" class="muhiku-plug-field-option-hidden-type" />
					<?php do_action( 'muhiku_forms_builder_fields_options_' . $field['type'], $field ); ?>
				</div>
				<?php
			}
		} else {
			printf( '<p class="no-fields">%s</p>', esc_html__( 'You don\'t have any fields yet.', 'muhiku-plug' ) );
		}
	}

	public function output_fields_preview() {
		$form_data = $this->form_data;
		$form_id   = absint( $form_data['id'] );
		$fields    = isset( $form_data['form_fields'] ) ? $form_data['form_fields'] : array();
		$structure = isset( $form_data['structure'] ) ? $form_data['structure'] : array( 'row_1' => array() );
		$row_ids   = array_map(
			function( $row_id ) {
				return str_replace( 'row_', '', $row_id );
			},
			array_keys( $structure )
		);

		if ( defined( 'MHK_MULTI_PART_PLUGIN_FILE' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugin_data = get_plugin_data( MHK_MULTI_PART_PLUGIN_FILE, false, false );

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

		self::$parts[ $form_id ] = apply_filters( 'muhiku_forms_parts_data', self::$parts, $form_data, $form_id );

		echo '<div class="mhk-admin-field-container">';
		echo '<div class="mhk-admin-field-wrapper">';

		do_action( 'muhiku_forms_display_builder_fields_before', $form_data, $form_id );

		foreach ( $structure as $row_id => $row_data ) {
			$row         = str_replace( 'row_', '', $row_id );
			$row_grid    = isset( $form_data['structure'][ 'row_' . $row ] ) ? $form_data['structure'][ 'row_' . $row ] : array();
			$form_grid   = apply_filters( 'muhiku_forms_default_form_grid', 4 );
			$total_grid  = $form_grid;
			$active_grid = count( $row_grid ) > 0 ? count( $row_grid ) : 2;
			$active_grid = $active_grid > $total_grid ? $total_grid : $active_grid;

			do_action( 'muhiku_forms_display_builder_row_before', $row_id, $form_data, $form_id );

			$repeater_field = apply_filters( 'muhiku_forms_display_repeater_fields', false, $row_grid, $fields );

			echo '<div class="mhk-admin-row" data-row-id="' . absint( $row ) . '"' . ( ! empty( $repeater_field ) ? esc_attr( $repeater_field ) : '' ) . '>';
			echo '<div class="mhk-toggle-row">';
			if ( empty( $repeater_field ) ) {
				echo '<div class="mhk-duplicate-row"><span class="dashicons dashicons-media-default" title="Duplicate Row"></span></div>';
				echo '<div class="mhk-delete-row"><span class="dashicons dashicons-trash" title="Delete Row"></span></div>';
				echo '<div class="mhk-show-grid"><span class="dashicons dashicons-edit" title="Edit"></span></div>';
			}
			echo '<div class="mhk-toggle-row-content">';
			echo '<span>' . esc_html__( 'Row Settings', 'muhiku-plug' ) . '</span>';
			echo '<small>' . esc_html__( 'Select the type of row', 'muhiku-plug' ) . '</small>';
			echo '<div class="clear"></div>';

			for ( $grid_active = 1; $grid_active <= $total_grid; $grid_active ++ ) {
				$class = 'mhk-grid-selector';

				if ( $grid_active === $active_grid ) {
					$class .= ' active';
				}

				echo '<div class="' . esc_attr( $class ) . '" data-mhk-grid="' . absint( $grid_active ) . '">';

				$gaps   = 15;
				$width  = ( 100 - $gaps ) / $grid_active;
				$margin = ( $gaps / $grid_active ) / 2;

				for ( $row_icon = 1; $row_icon <= $grid_active; $row_icon ++ ) {
					echo '<span style="width:' . (float) $width . '%; margin-left:' . (float) $margin . '%; margin-right:' . (float) $margin . '%"></span>';
				}

				echo '</div>';
			}

			echo '</div>';
			echo '</div>';
			echo '<div class="clear mhk-clear"></div>';

			$grid_class = 'mhk-admin-grid mhk-grid-' . ( $active_grid );
			for ( $grid_start = 1; $grid_start <= $active_grid; $grid_start ++ ) {
				echo '<div class="' . esc_attr( $grid_class ) . ' " data-grid-id="' . absint( $grid_start ) . '">';
				$grid_fields = isset( $row_grid[ 'grid_' . $grid_start ] ) && is_array( $row_grid[ 'grid_' . $grid_start ] ) ? $row_grid[ 'grid_' . $grid_start ] : array();
				foreach ( $grid_fields as $field_id ) {
					if ( isset( $fields[ $field_id ] ) && ! in_array( $fields[ $field_id ]['type'], mhk()->form_fields->get_pro_form_field_types(), true ) ) {
						$this->field_preview( $fields[ $field_id ] );
					}
				}
				echo '</div>';
			}
			echo '<div class="clear mhk-clear"></div>';
			echo '</div >';

			do_action( 'muhiku_forms_display_builder_row_after', $row_id, $form_data, $form_id );
		}

		do_action( 'muhiku_forms_display_builder_fields_after', $form_data, $form_id );

		echo '</div>';
		echo '<div class="clear mhk-clear"></div>';
		if ( defined( 'MHK_REPEATER_FIELDS_VERSION' ) ) {
			echo '<div class="mhk-repeater-row-wrapper">'; // Repeater Row Wrapper starts.
		}

		echo '<div class="mhk-add-row" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><span class="muhiku-plug-btn muhiku-plug-btn-primary dashicons dashicons-plus-alt">' . esc_html__( 'Add Row', 'muhiku-plug' ) . '</span></div>';

		if ( defined( 'MHK_REPEATER_FIELDS_VERSION' ) ) {
			echo '<div class="mhk-add-row repeater-row" data-total-rows="' . count( $structure ) . '" data-next-row-id="' . (int) max( $row_ids ) . '"><span class="muhiku-plug-btn muhiku-plug-btn-primary dashicons dashicons-plus-alt">' . esc_html__( 'Add Repeater Row', 'muhiku-plug' ) . '</span></div>';
			echo '</div>'; // Repeater Row Wrapper ends.
		}
		echo '</div >';
	}

	/**
	 * @param array $field Field data and settings.
	 */
	public function field_preview( $field ) {
		$css  = ! empty( $field['size'] ) ? 'size-' . esc_attr( $field['size'] ) : '';
		$css .= ! empty( $field['label_hide'] ) && '1' === $field['label_hide'] ? ' label_hide' : '';
		$css .= ! empty( $field['sublabel_hide'] ) && '1' === $field['sublabel_hide'] ? ' sublabel_hide' : '';
		$css .= ! empty( $field['required'] ) && '1' === $field['required'] ? ' required' : '';
		$css .= ! empty( $field['input_columns'] ) && '2' === $field['input_columns'] ? ' muhiku-plug-list-2-columns' : '';
		$css .= ! empty( $field['input_columns'] ) && '3' === $field['input_columns'] ? ' muhiku-plug-list-3-columns' : '';
		$css .= ! empty( $field['input_columns'] ) && 'inline' === $field['input_columns'] ? ' muhiku-plug-list-inline' : '';
		$css  = apply_filters( 'muhiku_forms_field_preview_class', $css, $field );

		printf( '<div class="muhiku-plug-field muhiku-plug-field-%1$s %2$s" id="muhiku-plug-field-%3$s" data-field-id="%3$s" data-field-type="%4$s">', esc_attr( $field['type'] ), esc_attr( $css ), esc_attr( $field['id'] ), esc_attr( $field['type'] ) );
		printf( '<div class="mhk-field-action">' );
		if ( 'repeater-fields' !== $field['type'] ) {
			printf( '<a href="#" class="muhiku-plug-field-duplicate" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Field', 'muhiku-plug' ) );
			printf( '<a href="#" class="muhiku-plug-field-delete" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Field', 'muhiku-plug' ) );
			printf( '<a href="#" class="muhiku-plug-field-setting" title="%s"><span class="dashicons dashicons-admin-generic"></span></a>', esc_html__( 'Settings', 'muhiku-plug' ) );
		} else {
			printf( '<a href="#" class="mhk-duplicate-row" title="%s"><span class="dashicons dashicons-media-default"></span></a>', esc_html__( 'Duplicate Repeater', 'muhiku-plug' ) );
			printf( '<a href="#" class="mhk-delete-row" title="%s"><span class="dashicons dashicons-trash"></span></a>', esc_html__( 'Delete Repeater', 'muhiku-plug' ) );
		}
		printf( '</div>' );

		do_action( 'muhiku_forms_builder_fields_preview_' . $field['type'], $field );

		echo '</div>';
	}
}

return new MHK_Builder_Fields();

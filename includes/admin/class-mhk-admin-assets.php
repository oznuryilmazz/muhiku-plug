<?php
/**
 * Load assets
 *
 * @package MuhikuPlug/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Admin_Assets', false ) ) {
	return new MHK_Admin_Assets();
}

/**
 * MHK_Admin_Assets Class.
 */
class MHK_Admin_Assets {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}

	/**
	 * Enqueue styles.
	 */
	public function admin_styles() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		// Register admin styles.
		wp_register_style( 'muhiku-plug-admin', mhk()->plugin_url() . '/assets/css/admin.css', array(), MHK_VERSION );
		wp_register_style( 'muhiku-plug-admin-menu', mhk()->plugin_url() . '/assets/css/menu.css', array(), MHK_VERSION );
		wp_register_style( 'jquery-ui-style', mhk()->plugin_url() . '/assets/css/jquery-ui/jquery-ui.min.css', array(), MHK_VERSION );
		wp_register_style( 'jquery-confirm', mhk()->plugin_url() . '/assets/css/jquery-confirm/jquery-confirm.min.css', array(), '3.3.0' );
		wp_register_style( 'perfect-scrollbar', mhk()->plugin_url() . '/assets/css/perfect-scrollbar/perfect-scrollbar.css', array(), '1.4.0' );
		wp_register_style( 'flatpickr', mhk()->plugin_url() . '/assets/css/flatpickr.css', array(), MHK_VERSION );

		// Add RTL support for admin styles.
		wp_style_add_data( 'muhiku-plug-admin', 'rtl', 'replace' );
		wp_style_add_data( 'muhiku-plug-admin-menu', 'rtl', 'replace' );

		// Sitewide menu CSS.
		wp_enqueue_style( 'muhiku-plug-admin-menu' );

		// Admin styles for MHK pages only.
		if ( in_array( $screen_id, mhk_get_screen_ids(), true ) ) {
			wp_enqueue_style( 'muhiku-plug-admin' );
			wp_enqueue_style( 'jquery-confirm' );
			wp_enqueue_style( 'jquery-ui-style' );
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'flatpickr' );

			if ( 'muhiku-plug_page_mhk-tools' !== $screen_id ) {
				wp_enqueue_style( 'perfect-scrollbar' );
			}
		}
	}

	/**
	 * Enqueue scripts.
	 */
	public function admin_scripts() {
		global $post;

		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$suffix    = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		// Register scripts.
		wp_register_script( 'muhiku-plug-admin', mhk()->plugin_url() . '/assets/js/admin/admin' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'tooltipster', 'wp-color-picker', 'perfect-scrollbar' ), MHK_VERSION, true );
		wp_register_script( 'muhiku-plug-extensions', mhk()->plugin_url() . '/assets/js/admin/extensions' . $suffix . '.js', array( 'jquery', 'updates' ), MHK_VERSION, true );
		wp_register_script( 'muhiku-plug-email-admin', mhk()->plugin_url() . '/assets/js/admin/mhk-admin-email' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'tooltipster', 'wp-color-picker', 'perfect-scrollbar' ), MHK_VERSION, true );
		wp_register_script( 'muhiku-plug-editor', mhk()->plugin_url() . '/assets/js/admin/editor' . $suffix . '.js', array( 'jquery' ), MHK_VERSION, true );
		wp_register_script( 'jquery-blockui', mhk()->plugin_url() . '/assets/js/jquery-blockui/jquery.blockUI' . $suffix . '.js', array( 'jquery' ), '2.70', true );
		wp_register_script( 'jquery-confirm', mhk()->plugin_url() . '/assets/js/jquery-confirm/jquery-confirm' . $suffix . '.js', array( 'jquery' ), '3.3.0', true );
		wp_register_script( 'jquery-tiptip', mhk()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip' . $suffix . '.js', array( 'jquery' ), MHK_VERSION, true ); 
		wp_register_script( 'tooltipster', mhk()->plugin_url() . '/assets/js/tooltipster/tooltipster.bundle' . $suffix . '.js', array( 'jquery' ), '4.6.2', true );
		wp_register_script( 'perfect-scrollbar', mhk()->plugin_url() . '/assets/js/perfect-scrollbar/perfect-scrollbar' . $suffix . '.js', array( 'jquery' ), '1.5.0', true );
		wp_register_script( 'mhk-clipboard', mhk()->plugin_url() . '/assets/js/admin/mhk-clipboard' . $suffix . '.js', array( 'jquery' ), MHK_VERSION, true );
		wp_register_script( 'selectWoo', mhk()->plugin_url() . '/assets/js/selectWoo/selectWoo.full' . $suffix . '.js', array( 'jquery' ), '1.0.8', true );
		wp_register_script( 'mhk-enhanced-select', mhk()->plugin_url() . '/assets/js/admin/mhk-enhanced-select' . $suffix . '.js', array( 'jquery', 'selectWoo' ), MHK_VERSION, true );
		wp_register_script( 'mhk-template-controller', mhk()->plugin_url() . '/assets/js/admin/form-template-controller' . $suffix . '.js', array( 'jquery' ), MHK_VERSION, true );
		wp_register_script( 'flatpickr', mhk()->plugin_url() . '/assets/js/flatpickr/flatpickr' . $suffix . '.js', array( 'jquery' ), '4.6.3', true );
		wp_register_script( 'mhk-file-uploader', mhk()->plugin_url() . '/assets/js/admin/mhk-file-uploader' . $suffix . '.js', array(), MHK_VERSION, true );
		wp_localize_script(
			'mhk-file-uploader',
			'mhk_file_uploader',
			array(
				'upload_file' => __( 'Resim yükle', 'muhiku-plug' ),
			)
		);
		wp_localize_script(
			'mhk-template-controller',
			'mhk_templates',
			array(
				'mhk_template_all' => MHK_Admin_Forms::get_template_data(),
				'i18n_get_started' => esc_html__( 'Get Started', 'muhiku-plug' ),
				'i18n_get_preview' => esc_html__( 'Önizleme', 'muhiku-plug' ),
				'mhk_plugin_url'   => esc_url( mhk()->plugin_url() ),
			)
		);
		wp_localize_script(
			'mhk-enhanced-select',
			'mhk_enhanced_select_params',
			array(
				'i18n_no_matches'           => _x( 'Hiçbir sonuç bulunamadı', 'enhanced select', 'muhiku-plug' ),
				'i18n_ajax_error'           => _x( 'Yükleme başarısız', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_short_1'    => _x( 'Lütfen 1 veya daha fazla karakter girin', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_short_n'    => _x( 'Lütfen %qty% veya daha fazla karakter girin', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_long_1'     => _x( 'Lütfen 1 karakter silin', 'enhanced select', 'muhiku-plug' ),
				'i18n_input_too_long_n'     => _x( 'Lütfen %qty% karakteri silin', 'enhanced select', 'muhiku-plug' ),
				'i18n_selection_too_long_1' => _x( 'Yalnızca 1 öğe seçebilirsiniz', 'enhanced select', 'muhiku-plug' ),
				'i18n_selection_too_long_n' => _x( 'Yalnızca %qty% öğeleri seçebilirsiniz', 'enhanced select', 'muhiku-plug' ),
				'i18n_load_more'            => _x( 'Daha fazla sonuç yükleniyor&hellip;', 'enhanced select', 'muhiku-plug' ),
				'i18n_searching'            => _x( 'Arama&hellip;', 'enhanced select', 'muhiku-plug' ),
			)
		);
		wp_register_script( 'mhk-form-builder', mhk()->plugin_url() . '/assets/js/admin/form-builder' . $suffix . '.js', array( 'jquery', 'jquery-blockui', 'tooltipster', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-ui-tabs', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-datepicker', 'jquery-confirm', 'mhk-clipboard', 'flatpickr' ), MHK_VERSION, true );
		wp_localize_script(
			'mhk-form-builder',
			'mhk_data',
			apply_filters(
				'muhiku_forms_builder_strings',
				array(
					'post_id'                      => isset( $post->ID ) ? $post->ID : '',
					'ajax_url'                     => admin_url( 'admin-ajax.php' ),
					'tab'                          => isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '',  .
					'mhk_field_drop_nonce'         => wp_create_nonce( 'muhiku_forms_field_drop' ),
					'mhk_save_form'                => wp_create_nonce( 'muhiku_forms_save_form' ),
					'mhk_get_next_id'              => wp_create_nonce( 'muhiku_forms_get_next_id' ),
					'mhk_enabled_form'             => wp_create_nonce( 'muhiku_forms_enabled_form' ),
					'form_id'                      => isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0,  
					'field'                        => esc_html__( 'field', 'muhiku-plug' ),
					'i18n_ok'                      => esc_html__( 'OK', 'muhiku-plug' ),
					'i18n_installing'              => esc_html__( 'Yükleniyor', 'muhiku-plug' ),
					'i18n_activating'              => esc_html__( 'Aktive Ediliyor', 'muhiku-plug' ),
					'i18n_install_activate'        => esc_html__( 'İndir & Aktive Et', 'muhiku-plug' ),
					'i18n_install_only'            => esc_html__( 'Plugini Aktifleştir', 'muhiku-plug' ),
					'i18n_copy'                    => esc_html__( '(copy)', 'muhiku-plug' ),
					'i18n_close'                   => esc_html__( 'Kapat', 'muhiku-plug' ),
					'i18n_cancel'                  => esc_html__( 'Vazgeç', 'muhiku-plug' ),
					'i18n_row_locked'              => esc_html__( 'Satır kilitlendi', 'muhiku-plug' ),
					'i18n_row_locked_msg'          => esc_html__( 'Tek satırlar silinemez.', 'muhiku-plug' ),
					'i18n_field_locked'            => esc_html__( 'Alan Kilitlendi.', 'muhiku-plug' ),
					'i18n_field_locked_msg'        => esc_html__( 'Bu alan kopyalanamaz ve silinemez.', 'muhiku-plug' ),
					'i18n_row_locked_msg'          => esc_html__( 'Bu satır kopyalanamaz veya silinemez.', 'muhiku-plug' ),
					'i18n_field_error_choice'      => esc_html__( 'Bu öğe en az bir seçenek içermelidir.', 'muhiku-plug' ),
					'i18n_delete_row_confirm'      => esc_html__( 'Bu satırı silmek istediğinizden emin misiniz?', 'muhiku-plug' ),
					'i18n_delete_field_confirm'    => esc_html__( 'Bu alanı silmek istediğinizden emin misiniz?', 'muhiku-plug' ),
					'i18n_duplicate_field_confirm' => esc_html__( 'Bu alanı çoğaltmak istediğinizden emin misiniz?', 'muhiku-plug' ),
					'i18n_duplicate_row_confirm'   => esc_html__( 'Bu satırı çoğaltmak istediğinizden emin misiniz?', 'muhiku-plug' ),
					'i18n_upload_image_title'      => esc_html__( 'Bir resim seçin', 'muhiku-plug' ),
					'i18n_upload_image_button'     => esc_html__( 'Resmi kullan', 'muhiku-plug' ),
					'i18n_upload_image_remove'     => esc_html__( 'Resmi kaldır', 'muhiku-plug' ),
					'i18n_field_title_empty'       => esc_html__( 'Boş form ismi', 'muhiku-plug' ),
					'i18n_field_title_payload'     => esc_html__( 'Form boş olamaz', 'muhiku-plug' ),
					'email_fields'                 => mhk_get_all_email_fields_by_form_id( isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0 ),  
					'all_fields'                   => mhk_get_all_form_fields_by_form_id( isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0 ),  
					'smart_tags_other'             => mhk()->smart_tags->other_smart_tags(),
					'entries_url'                  => ! empty( $_GET['form_id'] ) ? esc_url( admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . absint( $_GET['form_id'] ) ) ) : '',  
					'preview_url'                  => ! empty( $_GET['form_id'] ) ? esc_url(  
						add_query_arg(
							array(
								'form_id'     => absint( $_GET['form_id'] ),  
								'mhk_preview' => 'true',
								),
							home_url()
						)
					) : '',
				)
			)
		);

		// MuhikuPlug admin pages.
		if ( in_array( $screen_id, mhk_get_screen_ids(), true ) ) {
			wp_enqueue_script( 'muhiku-plug-admin' );
			wp_enqueue_script( 'muhiku-plug-email-admin' );
			wp_enqueue_script( 'mhk-enhanced-select' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_script( 'jquery-ui-autocomplete' );

			wp_localize_script(
				'muhiku-plug-email-admin',
				'mhk_email_params',
				array(
					'i18n_email_connection'  => esc_html__( 'Bir E-posta takma adı girin', 'muhiku-plug' ),
					'i18n_email_placeholder' => esc_html__( 'Örn: Destek E-postası', 'muhiku-plug' ),
					'i18n_email_error_name'  => esc_html__( 'Bir E-posta takma adı sağlamalısınız', 'muhiku-plug' ),
					'i18n_email_ok'          => esc_html__( 'OK', 'muhiku-plug' ),
					'ajax_email_nonce'       => wp_create_nonce( 'process-ajax-nonce' ),
					'ajax_url'               => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_email_cancel'      => esc_html__( 'Vazgeç', 'muhiku-plug' ),
					'i18n_default_address'   => get_option( 'admin_email' ),
					'from_name'              => get_bloginfo( 'name', 'display' ),
					'email_subject'          => esc_html__( 'Yeni Form Girişi', 'muhiku-plug' ),
				)
			);

			wp_localize_script(
				'muhiku-plug-admin',
				'muhiku_forms_admin',
				array(
					'ajax_import_nonce'             => wp_create_nonce( 'process-import-ajax-nonce' ),
					'ajax_url'                      => admin_url( 'admin-ajax.php', 'relative' ),
					'i18n_field_meta_key_error'     => esc_html__( 'Lütfen alfanümerik karakterler, tireler ve alt çizgilerle meta anahtarı girin.', 'muhiku-plug' ),
					'i18n_field_min_value_greater'  => esc_html__( 'Minimum değer, Maksimum değerden büyük.', 'muhiku-plug' ),
					'i18n_field_max_value_smaller'  => esc_html__( 'Maksimum değer Minimum değerden küçük.', 'muhiku-plug' ),
					'i18n_form_export_action_error' => esc_html__( 'Lütfen dışa aktarmak istediğiniz formu seçin.', 'muhiku-plug' ),
				)
			);
		}

		// MuhikuPlug builder pages.
		if ( in_array( $screen_id, array( 'muhiku-plug_page_mhk-builder' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'mhk-upgrade' );
			wp_enqueue_script( 'mhk-form-builder' );

			// De-register scripts.
			wp_dequeue_script( 'colorpick' );

			// MuhikuPlug builder setup page.
			if ( isset( $_GET['create-form'] ) ) {  
				wp_register_script( 'mhk-setup', mhk()->plugin_url() . '/assets/js/admin/mhk-setup' . $suffix . '.js', array( 'jquery', 'muhiku-plug-extensions', 'mhk-template-controller' ), MHK_VERSION, true );
				wp_enqueue_script( 'mhk-setup' );
				wp_localize_script(
					'mhk-setup',
					'mhk_setup_params',
					array(
						'ajax_url'                     => admin_url( 'admin-ajax.php' ),
						'create_form_nonce'            => wp_create_nonce( 'muhiku_forms_create_form' ),
						'template_licence_check_nonce' => wp_create_nonce( 'muhiku_forms_template_licence_check' ),
						'i18n_form_name'               => esc_html__( 'Bu Forma Bir İsim Ver', 'muhiku-plug' ),
						'i18n_form_error_name'         => esc_html__( 'Bu forma bir isim vermelisin!', 'muhiku-plug' ),
						'i18n_form_ok'                 => esc_html__( 'Oluştur', 'muhiku-plug' ),
						'i18n_form_placeholder'        => esc_html__( 'İsimsiz Form', 'muhiku-plug' ),
						'i18n_form_title'              => esc_html__( '', 'muhiku-plug' ),
					)
				);
			}
		}

		// Tools page.
		if ( 'muhiku-plug_page_mhk-tools' === $screen_id ) {
			wp_register_script( 'mhk-admin-tools', mhk()->plugin_url() . '/assets/js/admin/tools' . $suffix . '.js', array( 'jquery' ), MHK_VERSION, true );
			wp_enqueue_script( 'mhk-admin-tools' );
			wp_localize_script(
				'mhk-admin-tools',
				'muhiku_forms_admin_tools',
				array(
					'delete_log_confirmation' => esc_js( esc_html__( 'Bu günlüğü silmek istediğinizden emin misiniz?', 'muhiku-plug' ) ),
				)
			);
		}

		// Plugins page.
		if ( in_array( $screen_id, array( 'plugins' ), true ) ) {
			wp_register_script( 'mhk-plugins', mhk()->plugin_url() . '/assets/js/admin/plugins' . $suffix . '.js', array( 'jquery' ), MHK_VERSION, true );
			wp_enqueue_script( 'mhk-plugins' );
			wp_localize_script(
				'mhk-plugins',
				'mhk_plugins_params',
				array(
					'ajax_url'           => admin_url( 'admin-ajax.php' ),
					'deactivation_nonce' => wp_create_nonce( 'deactivation-notice' ),
				)
			);
		}
	}
}

return new MHK_Admin_Assets();

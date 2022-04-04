<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'MHK_Builder_Settings', false ) ) {
	return new MHK_Builder_Settings();
}

class MHK_Builder_Settings extends MHK_Builder_Page {

	public function __construct() {
		$this->id      = 'settings';
		$this->label   = esc_html__( 'Ayarlar', 'muhiku-plug' );
		$this->sidebar = true;

		add_action( 'muhiku_forms_settings_connections_email', array( $this, 'output_connections_list' ) );

		parent::__construct();
	}

	public function output_sidebar() {
		$sections = apply_filters(
			'muhiku_forms_builder_settings_section',
			array(
				'general' => esc_html__( 'Genel Ayarlar', 'muhiku-plug' ),
			),
			$this->form_data
		);

		if ( ! empty( $sections ) ) {
			foreach ( $sections as $slug => $section ) {
				$this->add_sidebar_tab( $section, $slug );
				do_action( 'muhiku_forms_settings_connections_' . $slug, $section );
			}
		}
	}

	/**
	 * @return array form data.
	 */
	private function form_data() {
		$form_data = array();

		if ( ! empty( $_GET['form_id'] ) ) {  
			$form_data = mhk()->form->get( absint( $_GET['form_id'] ), array( 'content_only' => true ) );  
		}

		return $form_data;
	}

	public function output_connections_list() {
		$form_data = $this->form_data();
		$email     = isset( $form_data['settings']['email'] ) ? $form_data['settings']['email'] : array();

		if ( empty( $email ) ) {
			$email['connection_1'] = array( 'connection_name' => __( 'Admin Notification', 'muhiku-plug' ) );
		}
	}

	public function output_content() {
		$settings = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();

		echo '<div class="mhk-content-section mhk-content-general-settings">';
		echo '<div class="mhk-content-section-title">';
		esc_html_e( 'Genel Ayarlar', 'muhiku-plug' );
		echo '</div>';
		muhiku_forms_panel_field(
			'text',
			'settings',
			'form_title',
			$this->form_data,
			esc_html__( 'Form İsmi', 'muhiku-plug' ),
			array(
				'default' => isset( $this->form->post_title ) ? $this->form->post_title : '',
			)
		);
		muhiku_forms_panel_field(
			'textarea',
			'settings',
			'form_description',
			$this->form_data,
			esc_html__( 'Form Açıklaması', 'muhiku-plug' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->form_description ) ? $this->form->form_description : '',
			)
		);
		muhiku_forms_panel_field(
			'textarea',
			'settings',
			'form_disable_message',
			$this->form_data,
			esc_html__( 'Form Aktif Olmadığında Verilecek Mesaj', 'muhiku-plug' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->form_disable_message ) ? $this->form->form_disable_message : __( 'Bu form şuan kullanıma açık değildir.', 'muhiku-plug' ),
			)
		);
		muhiku_forms_panel_field(
			'textarea',
			'settings',
			'successful_form_submission_message',
			$this->form_data,
			esc_html__( 'Başarılı Form Gönderme Mesajı', 'muhiku-plug' ),
			array(
				'input_class' => 'short',
				'default'     => isset( $this->form->successful_form_submission_message ) ? $this->form->successful_form_submission_message : __( 'Bizimle önerini paylaştığın için teşekkür ederiz!', 'muhiku-plug' ),)
		);
		muhiku_forms_panel_field(
			'checkbox',
			'settings',
			'submission_message_scroll',
			$this->form_data,
			__( 'Gönderim mesajına otomatik olarak kaydır', 'muhiku-plug' ),
			array(
				'default' => '1',
			)
		);

		echo '<div class="muhiku-plug-border-container"><h4 class="muhiku-plug-border-container-title">' . esc_html__( 'Gönderim Yönlendirme', 'muhiku-plug' ) . '</h4>';

		muhiku_forms_panel_field(
			'select',
			'settings',
			'redirect_to',
			$this->form_data,
			esc_html__( 'Şuraya gönder', 'muhiku-plug' ),
			array(
				'default' => 'same',
				'options' => array(
					'same'         => esc_html__( 'Aynı Sayfa', 'muhiku-plug' ),
					'custom_page'  => esc_html__( 'Sayfa Seç', 'muhiku-plug' ),
					'external_url' => esc_html__( 'URL', 'muhiku-plug' ),
				),
			)
		);

		muhiku_forms_panel_field(
			'select',
			'settings',
			'custom_page',
			$this->form_data,
			esc_html__( 'Sayfa Seç', 'muhiku-plug' ),
			array(
				'default' => '0',
				'options' => $this->get_all_pages(),
			)
		);

		muhiku_forms_panel_field(
			'text',
			'settings',
			'external_url',
			$this->form_data,
			esc_html__( 'External URL', 'muhiku-plug' ),
			array(
				'default' => isset( $this->form->external_url ) ? $this->form->external_url : '',
			)
		);

		do_action( 'muhiku_forms_submission_redirection_settings', $this, 'submission_redirection' );

		echo '</div>';

		do_action( 'muhiku_forms_field_required_indicators', $this->form_data, $settings );

		echo '<div class="muhiku-plug-border-container"><h4 class="muhiku-plug-border-container-title">' . esc_html__( 'Gönder Düğmesi', 'muhiku-plug' ) . '</h4>';
		muhiku_forms_panel_field(
			'text',
			'settings',
			'submit_button_text',
			$this->form_data,
			esc_html__( 'Submit button text', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['submit_button_text'] ) ? $settings['submit_button_text'] : __( 'Gönder', 'muhiku-plug' ),
				)
		);
		muhiku_forms_panel_field(
			'text',
			'settings',
			'submit_button_processing_text',
			$this->form_data,
			__( 'Gönderme butonu yükleme mesajı ', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['submit_button_processing_text'] ) ? $settings['submit_button_processing_text'] : __( 'Gönderiliyor&hellip;', 'muhiku-plug' ),
			)
		);
		do_action( 'muhiku_forms_inline_submit_settings', $this, 'submit', 'connection_1' );
		echo '</div>';
		do_action( 'muhiku_forms_inline_integrations_settings', $this->form_data, $settings );
		muhiku_forms_panel_field(
			'checkbox',
			'settings',
			'honeypot',
			$this->form_data,
			esc_html__( 'İstenmeyen posta önleme balküpünü etkinleştir', 'muhiku-plug' ),
			array(
				'default' => '1',
			)
		);
		muhiku_forms_panel_field(
			'checkbox',
			'settings',
			'ajax_form_submission',
			$this->form_data,
			esc_html__( 'Ajax Form Gönderimini Etkinleştir', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['ajax_form_submission'] ) ? $settings['ajax_form_submission'] : 0,
			)
		);
		muhiku_forms_panel_field(
			'checkbox',
			'settings',
			'disabled_entries',
			$this->form_data,
			esc_html__( 'Giriş bilgilerini saklamayı devre dışı bırak', 'muhiku-plug' ),
			array(
				'default' => isset( $settings['disabled_entries'] ) ? $settings['disabled_entries'] : 0,
			)
		);

		do_action( 'muhiku_forms_inline_general_settings', $this );

		echo '</div>';
	}

	public function get_all_pages() {
		$pages = array();
		foreach ( get_pages() as $page ) {
			$pages[ $page->ID ] = $page->post_title;
		}

		return $pages;
	}
}

return new MHK_Builder_Settings();

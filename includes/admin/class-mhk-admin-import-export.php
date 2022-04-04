<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

class MHK_Admin_Import_Export {

	public function __construct() {
		add_action( 'admin_init', array( $this, 'export_json' ) );
	}

	public function export_json() {
		if ( ! isset( $_POST['muhiku-plug-export-form'] ) || ! isset( $_POST['muhiku-plug-export-nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['muhiku-plug-export-nonce'] ) ), 'muhiku_forms_export_nonce' ) ) {
			wp_die( esc_html__( 'Eylem başarısız. Lütfen sayfayı yenileyin ve tekrar deneyin.', 'muhiku-plug' ) );
		}

		$form_id = isset( $_POST['form_id'] ) ? absint( wp_unslash( $_POST['form_id'] ) ) : 0;

		if ( empty( $form_id ) || ! current_user_can( 'export' ) ) {
			return;
		}

		$form_post   = get_post( $form_id );
		$export_data = array(
			'form_post' => array(
				'post_content' => $form_post->post_content,
				'post_title'   => $form_post->post_title,
				'post_name'    => $form_post->post_name,
				'post_type'    => $form_post->post_type,
				'post_status'  => $form_post->post_status,
			),
		);
		$form_name   = strtolower( str_replace( ' ', '-', get_the_title( $form_id ) ) );
		$file_name   = html_entity_decode( $form_name, ENT_QUOTES, 'UTF-8' ) . '-' . current_time( 'Y-m-d_H:i:s' ) . '.json';

		$form_styles = get_option( 'muhiku_forms_styles', array() );
		if ( ! empty( $form_styles[ $form_id ] ) ) {
			$export_data['form_styles'] = wp_json_encode( $form_styles[ $form_id ] );
		}

		if ( ob_get_contents() ) {
			ob_clean();
		}

		header( 'Content-Type: application/force-download' );
		header( "Content-Disposition: attachment;filename={$file_name}; charset=utf-8" );
		header( 'Content-type: application/json' );
		echo wp_json_encode( $export_data );
		exit();
	}
}

new MHK_Admin_Import_Export();

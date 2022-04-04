<?php
/**
 * @package MuhikuPlug/Admin
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MHK_Admin_Editor', false ) ) {
	return new MHK_Admin_Editor();
}

class MHK_Admin_Editor {

	public function __construct() {
		add_action( 'media_buttons', array( $this, 'media_button' ), 15 );
	}

	/**
	 * @param string $editor_id Unique editor identifier, e.g. 'content'.
	 */
	public function media_button( $editor_id ) {
		if ( ! apply_filters( 'muhiku_forms_show_media_button', is_admin(), $editor_id ) ) {
			return;
		}

		// Setup the svg icon.
		printf(
			'<a href="#" class="button mhk-insert-form-button" data-editor="%s" title="%s"><span class="wp-media-buttons-icon">%s</span> %s</a>',
			esc_attr( $editor_id ),
			esc_attr__( 'Add Muhiku Form', 'muhiku-plug' ),
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path fill="#82878c" d="M18.1 4h-3.8l1.2 2h3.9zM20.6 8h-3.9l1.2 2h3.9zM20.6 18H5.8L12 7.9l2.5 4.1H12l-1.2 2h7.3L12 4.1 2.2 20h19.6z"/></g></svg>',
			esc_html__( 'Add Form', 'muhiku-plug' )
		);

		// If we have made it, then load the JS.
		wp_enqueue_script( 'muhiku-plug-editor' );

		add_action( 'admin_footer', array( $this, 'shortcode_modal' ) );
	}

	/**
	 * Modal window for inserting the form shortcode into TinyMCE.
	 */
	public function shortcode_modal() {
		?>
		<div id="mhk-modal-backdrop" style="display: none"></div>
		<div id="mhk-modal-wrap" style="display: none">
			<form id="mhk-modal" tabindex="-1">
				<div id="mhk-modal-title">
					<?php esc_html_e( 'Insert Form', 'muhiku-plug' ); ?>
					<button type="button" id="mhk-modal-close"><span class="screen-reader-text"><?php esc_html_e( 'Close', 'muhiku-plug' ); ?></span></button>
				</div>
				<div id="mhk-modal-inner">
					<div id="mhk-modal-options">
						<?php
						$forms = mhk_get_all_forms();

						if ( ! empty( $forms ) ) {
							printf( '<p><label for="mhk-modal-select-form">%s</label></p>', esc_html__( 'Select a form below to insert', 'muhiku-plug' ) );
							echo '<select id="mhk-modal-select-form">';
							foreach ( $forms as $form_id => $form_value ) {
								printf( '<option value="%d">%s</option>', esc_attr( $form_id ), esc_html( $form_value ) );
							}
							echo '</select>';
						} else {
							echo '<p>';
							printf(
								wp_kses(
									__( 'Whoops, you haven\'t created a form yet. Want to <a href="%s">give it a go</a>?', 'muhiku-plug' ),
									array(
										'a' => array(
											'href' => array(),
										),
									)
								),
								esc_url( admin_url( 'admin.php?page=mhk-builder' ) )
							);
							echo '</p>';
						}
						?>
					</div>
				</div>
				<div class="submitbox">
					<div id="mhk-modal-cancel">
						<a class="submitdelete deletion" href="#"><?php esc_html_e( 'Cancel', 'muhiku-plug' ); ?></a>
					</div>
					<?php if ( ! empty( $forms ) ) : ?>
						<div id="mhk-modal-update">
							<button class="button button-primary" id="mhk-modal-submit"><?php esc_html_e( 'Add Form', 'muhiku-plug' ); ?></button>
						</div>
					<?php endif; ?>
				</div>
			</form>
		</div>
		<?php
	}
}

return new MHK_Admin_Editor();

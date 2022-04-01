<?php
/**
 * Admin View: Page - Export
 *
 * @package MuhikuPlug/Admin/Export
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="muhiku-plug-export-form">
	<h3><?php esc_html_e( 'Export Muhiku Plug with Settings', 'muhiku-plug' ); ?></h3>
	<p><?php esc_html_e( 'Export your forms along with their settings as JSON file.', 'muhiku-plug' ); ?></p>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mhk-tools&tab=export' ) ); ?>">
		<?php
		$forms = mhk_get_all_forms( true );
		if ( ! empty( $forms ) ) {
			echo '<select id="muhiku-plug-form-export" style="min-width: 350px;" name="form_id" data-placeholder="' . esc_attr__( 'Select form', 'muhiku-plug' ) . '"><option value="">' . esc_html__( 'Select a form', 'muhiku-plug' ) . '</option>';
			foreach ( $forms as $id => $form ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
				echo '<option value="' . esc_attr( $id ) . '">' . esc_html( $form ) . '</option>';
			}
			echo '</select>';
		} else {
			echo '<p>' . esc_html__( 'You need to create a form before you can use form export.', 'muhiku-plug' ) . '</p>';
		}
		?>
		<div class="publishing-action">
			<?php wp_nonce_field( 'everest_forms_export_nonce', 'muhiku-plug-export-nonce' ); ?>
			<button type="submit" class="muhiku-plug-btn muhiku-plug-btn-primary muhiku-plug-export-form-action" name="muhiku-plug-export-form"><?php esc_html_e( 'Export', 'muhiku-plug' ); ?></button>
		</div>
	</form>
</div>

<?php
/**
 * Admin View: Page - Import
 *
 * @package MuhikuPlug/Admin/Import
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="muhiku-plug-import-form">
	<h3><?php esc_html_e( 'Import Muhiku Plug', 'muhiku-plug' ); ?></h3>
	<p><?php esc_html_e( 'Select JSON file to import the form.', 'muhiku-plug' ); ?></p>
	<div class="muhiku-plug-file-upload">
		<input type="file" name="file" id="muhiku-plug-import" <?php esc_attr_e( 'files selected', 'muhiku-plug' ); ?>" accept=".json" />
		<label for="muhiku-plug-import"><span class="muhiku-plug-btn dashicons dashicons-upload">Choose File</span><span id="import-file-name"><?php esc_html_e( 'No file selected', 'muhiku-plug' ); ?></span></label>
	</div>
	<p class="description">
		<i class="dashicons dashicons-info"></i>
		<?php
		/* translators: %s: File format */
		printf( esc_html__( 'Only %s file is allowed.', 'muhiku-plug' ), '<strong>JSON</strong>' );
		?>
	</p>
	<div class="publishing-action">
		<button type="submit" class="muhiku-plug-btn muhiku-plug-btn-primary muhiku_forms_import_action" name="muhiku-plug-import-form"><?php esc_html_e( 'Import Form', 'muhiku-plug' ); ?></button>
		<?php wp_nonce_field( 'muhiku_forms_import_nonce', 'muhiku-plug-import-nonce' ); ?>
	</div>
</div>

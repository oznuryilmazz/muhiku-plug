<?php
/**
 * Admin View: Notice - Update
 *
 * @package MuhikuPlug\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

$update_url = wp_nonce_url(
	add_query_arg( 'do_update_muhiku_forms', 'true', admin_url( 'admin.php?page=mhk-settings' ) ),
	'mhk_db_update',
	'mhk_db_update_nonce'
);
?>
<div id="message" class="updated muhiku-plug-message mhk-connect">
	<p>
		<strong><?php esc_html_e( 'Muhiku Plug database update required', 'muhiku-plug' ); ?></strong>
	</p>
	<p>
		<?php esc_html_e( 'Muhiku Plug has been updated! To keep things running smoothly, we have to update your database to the newest version. The database update process runs in the background and may take a little while, so please be patient.', 'muhiku-plug' ); ?>
	</p>
	<p class="submit">
		<a href="<?php echo esc_url( $update_url ); ?>" class="mhk-update-now button-primary">
			<?php esc_html_e( 'Update Muhiku Plug Database', 'muhiku-plug' ); ?>
		</a>
	</p>
</div>
<script type="text/javascript">
	jQuery( '.mhk-update-now' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'muhiku-plug' ) ); ?>' );
	});
</script>
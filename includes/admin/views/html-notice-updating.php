<?php
/**
 * Admin View: Notice - Updating
 *
 * @package MuhikuPlug\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated muhiku-plug-message mhk-connect">
	<p><strong><?php esc_html_e( 'Muhiku Plug data update', 'muhiku-plug' ); ?></strong> &#8211; <?php esc_html_e( 'Your database is being updated in the background.', 'muhiku-plug' ); ?> <a href="<?php echo esc_url( add_query_arg( 'force_update_muhiku_forms', 'true', admin_url( 'admin.php?page=mhk-settings' ) ) ); ?>"><?php esc_html_e( 'Taking a while? Click here to run it now.', 'muhiku-plug' ); ?></a></p>
</div>
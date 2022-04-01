<?php
/**
 * Admin View: Notice - Updated
 *
 * @package MuhikuPlug\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated muhiku-plug-message mhk-connect muhiku-plug-message--success">
	<a class="muhiku-plug-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'mhk-hide-notice', 'update', remove_query_arg( 'do_update_muhiku_forms' ) ), 'muhiku_forms_hide_notices_nonce', '_mhk_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'muhiku-plug' ); ?></a>

	<p><?php esc_html_e( 'Muhiku Plug data update complete. Thank you for updating to the latest version!', 'muhiku-plug' ); ?></p>
</div>

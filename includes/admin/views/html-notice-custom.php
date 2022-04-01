<?php
/**
 * Admin View: Custom Notices
 *
 * @package MuhikuPlug\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated muhiku-plug-message">
	<a class="muhiku-plug-message-close notice-dismiss" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'mhk-hide-notice', $notice ), 'everest_forms_hide_notices_nonce', '_mhk_notice_nonce' ) ); ?>"><?php esc_html_e( 'Dismiss', 'muhiku-plug' ); ?></a>

	<?php echo wp_kses_post( wpautop( $notice_html ) ); ?>
</div>

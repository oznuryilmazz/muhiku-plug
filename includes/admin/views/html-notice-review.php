<?php
/**
 * Admin View: Notice - Review
 *
 * @package MuhikuPlug\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated muhiku-plug-message mhk-review-notice">
	<div class="muhiku-plug-logo">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.15,4l1.23,2H15.49L14.26,4ZM20,20H2.21L12,4.09,18.1,14H10.77L12,12h2.52L12,7.91,5.79,18H20.56l1.23,2ZM17.94,10,16.71,8H20.6l1.23,2Z"/></svg>
	</div>
	<div class="muhiku-plug-message--content">
		<h3 class="muhiku-plug-message__title"><?php esc_html_e( 'HAKUNA MATATA!', 'muhiku-plug' ); ?></h3>
		<p class="muhiku-plug-message__description">
			<?php
			/* translators: %1$s: Plugin Name, %2$s: Rating link */
			printf( esc_html__( 'Hope you are having nice experience with %1$s plugin. Please provide this plugin a nice review. %3$s %2$s
				Basically, it would encourage us to release updates regularly with new features & bug fixes so that you can keep on using the plugin without any issues and also to provide free support like we have been doing. %4$s', 'muhiku-plug' ), '<strong>Muhiku Plug</strong>', '<strong>What benefit would you have?</strong> <br>', '<br>','<span class="dashicons dashicons-smiley smile-icon"></span>' );
			?>
		</p>
		<p class="muhiku-plug-message__action submit">
			<a href="https://wordpress.org/support/plugin/muhiku-plug/reviews?rate=5#new-post" class="button button-primary mhk-dismiss-review-notice mhk-review-received" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'Sure, I\'d love to!', 'muhiku-plug' ); ?></a>
			<a href="#" class="button button-secondary mhk-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><span  class="dashicons dashicons-smiley"></span><?php esc_html_e( 'Remind me later', 'muhiku-plug' ); ?></a>
			<a href="#" class="button button-secondary mhk-dismiss-review-notice" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'I already did', 'muhiku-plug' ); ?></a>
			<a href="https://wpeverest.com/support-forum/" class="button button-secondary mhk-have-query" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-testimonial"></span><?php esc_html_e( 'I have a query', 'muhiku-plug' ); ?></a>
		</p>
	</div>
</div>
<script type="text/javascript">
	jQuery( document ).ready( function ( $ ) {
		$( document ).on( 'click', '.mhk-dismiss-review-notice, .mhk-review-notice button', function ( event ) {
			if ( ! $( this ).hasClass( 'mhk-review-received' ) ) {
				event.preventDefault();
			}
			$.post( ajaxurl, {
				action: 'everest_forms_review_dismiss'
			} );
			$( '.mhk-review-notice' ).remove();
		} );
	} );
</script>

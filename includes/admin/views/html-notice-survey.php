<?php
/**
 * Admin View: Notice - Survey
 *
 * @package MuhikuPlug\Admin\Notice
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="message" class="updated muhiku-plug-message mhk-survey-notice">
	<div class="muhiku-plug-logo">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18.15,4l1.23,2H15.49L14.26,4ZM20,20H2.21L12,4.09,18.1,14H10.77L12,12h2.52L12,7.91,5.79,18H20.56l1.23,2ZM17.94,10,16.71,8H20.6l1.23,2Z"/></svg>
	</div>
	<div class="muhiku-plug-message--content">
		<h3 class="muhiku-plug-message__title"><?php esc_html_e( 'Muhiku Plug Survey!', 'muhiku-plug' ); ?></h3>
		<p class="muhiku-plug-message__description">
		<p>
		<?php
		printf(
			'%s<br><p>%s<p><p>%s<p>',
			esc_html__( 'Hey there!', 'muhiku-plug' ),
			esc_html__( 'We would be grateful if you could spare a moment and help us fill this survey', 'muhiku-plug' ),
			esc_html__( 'This survey will take approximately 4 minutes to complete.', 'muhiku-plug' )
		);
		?>
		</p>
		<p class="extra-pad">
		<?php
		printf(
			'<strong>%s</strong><br>%s<span class="dashicons dashicons-smiley smile-icon"></span><br>',
			esc_html__( 'What benefit would you have?', 'muhiku-plug' ),
			esc_html__( 'We will take your feedback from the survey which will eventually help to improve the Muhiku Plug plugin. Thank you in advance for participating.', 'muhiku-plug' )
		);
		?>
		</p>
		</p>
		<p class="muhiku-plug-message__action submit">
			<a href="https://survey.wpeverest.com/muhiku-plug/" class="button button-primary mhk-dismiss-review-notice mhk-survey-received" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'Start Survey', 'muhiku-plug' ); ?></a>
			<a href="#" class="button button-secondary mhk-dismiss-survey-notice" target="_blank" rel="noopener noreferrer"><span  class="dashicons dashicons-smiley"></span><?php esc_html_e( 'I already did', 'muhiku-plug' ); ?></a>
		</p>
	</div>
</div>
<script type="text/javascript">
	jQuery( document ).ready( function ( $ ) {
		$( document ).on( 'click', '.mhk-dismiss-survey-notice, .mhk-survey-notice button', function ( event ) {
			if ( ! $( this ).hasClass( 'mhk-survey-received' ) ) {
				event.preventDefault();
			}
			$.post( ajaxurl, {
				action: 'everest_forms_survey_dismiss'
			} );
			$( '.mhk-survey-notice' ).remove();
		} );
	} );
</script>

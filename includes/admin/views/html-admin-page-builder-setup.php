<?php
/**
 * Admin View: Builder setup
 *
 * @package MuhikuPlug/Admin/Builder
 *
 * @var string $view
 * @var object $templates
 */

defined( 'ABSPATH' ) || exit;

?>
<div class ="wrap muhiku-plug">
	<div class="muhiku-plug-loader-overlay" style="display:none">
		<div class="mhk-loading mhk-loading-active"></div>
	</div>
	<div class="muhiku-plug-setup muhiku-plug-setup--form">
		<div class="muhiku-plug-setup-header">
			<div class="muhiku-plug-logo">
				<svg xmlns="http://www.w3.org/2000/svg" height="32" width="32" viewBox="0 0 24 24"><path fill="#7e3bd0" d="M21.23,10H17.79L16.62,8h3.46ZM17.77,4l1.15,2H15.48L14.31,4Zm-15,16L12,4l5.77,10H10.85L12,12h2.31L12,8,6.23,18H20.08l1.16,2Z"/></svg>
			</div>
			<h4><?php esc_html_e( 'Yeni Bir Form Oluştur', 'muhiku-plug' ); ?></h4>
			<?php if ( apply_filters( 'muhiku_forms_refresh_templates', true ) ) : ?>
				<a href="<?php echo esc_url( $refresh_url ); ?>" class="muhiku-plug-btn page-title-action"><?php esc_html_e( 'Refresh Templates', 'muhiku-plug' ); ?></a>
			<?php endif; ?>
			<nav class="muhiku-plug-tab">
				<ul>
					<li class="muhiku-plug-tab-nav active">
						<a href="#" id="mhk-form-all" class="muhiku-plug-tab-nav-link"><?php esc_html_e( 'Hepsi', 'muhiku-plug' ); ?></a>
					</li>
				</ul>
			</nav>
		</div>
		<?php
		if ( 'false' === filter_input( INPUT_GET, 'mhk-templates-fetch' ) ) {
			echo '<div id="message" class="notice notice-warning is-dismissible"><p>' . esc_html__( 'Couldn\'t connect to templates server. Please reload again.', 'muhiku-plug' ) . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">x</span></button></div>';
		}
		?>
		<div class="muhiku-plug-form-template mhk-setup-templates" data-license-type="<?php echo esc_attr( $license_plan ); ?>">
			<?php
			if ( empty( $templates ) ) {
				echo '<div id="message" class="error"><p>' . esc_html__( 'Something went wrong. Please refresh your templates.', 'muhiku-plug' ) . '</p></div>';
			} else {
				foreach ( $templates as $template ) :
					$badge         = '';
					$upgrade_class = 'mhk-template-select';
					$preview_link  = isset( $template->preview_link ) ? $template->preview_link : '';
					$click_class   = '';
					if ( ! in_array( 'free', $template->plan, true ) ) {
						if ( in_array( 'personal', $template->plan, true ) ) {
							$badge_text = esc_html( 'Personal' );
						} elseif ( in_array( 'plus', $template->plan, true ) ) {
							$badge_text = esc_html( 'Plus' );
						} elseif ( in_array( 'professional', $template->plan, true ) ) {
							$badge_text = esc_html( 'Professional' );
						} else {
							$badge_text = esc_html( 'Agency' );
						}
						$badge = '<span class="muhiku-plug-badge muhiku-plug-badge--success">' . $badge_text . '</span>';
					}

					if ( 'blank' === $template->slug ) {
						$click_class = 'mhk-template-select';
					}

					// Upgrade checks.
					if ( empty( $license_plan ) && ! in_array( 'free', $template->plan, true ) ) {
						$upgrade_class = 'upgrade-modal';
					} elseif ( ! in_array( str_replace( '-lifetime', '', $license_plan ), $template->plan, true ) && ! in_array( 'free', $template->plan, true ) ) {
						$upgrade_class = 'upgrade-modal';
					}

					/* translators: %s: Template title */
					$template_name = sprintf( esc_attr_x( '%s template', 'Template name', 'muhiku-plug' ), esc_attr( $template->title ) );
					?>
					<div class="muhiku-plug-template-wrap mhk-template"  id="muhiku-plug-template-<?php echo esc_attr( $template->slug ); ?>">
						<figure class="muhiku-plug-screenshot <?php echo esc_attr( $click_class ); ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>" data-template-name="<?php echo esc_attr( $template_name ); ?>">
							<img src="<?php echo esc_url( mhk()->plugin_url() . '/assets/' . $template->image ); ?>"/>
							<?php echo wp_kses_post( $badge ); ?>
							<?php if ( 'blank' !== $template->slug ) : ?>
								<div class="form-action">
									<a href="#" class="muhiku-plug-btn muhiku-plug-btn-primary <?php echo esc_attr( $upgrade_class ); ?>" data-licence-plan="<?php echo esc_attr( $license_plan ); ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template-name="<?php echo esc_attr( $template_name ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>"><?php esc_html_e( 'Get Started', 'muhiku-plug' ); ?></a>
									<a href="<?php echo esc_url( $preview_link ); ?>" target="_blank" class="muhiku-plug-btn muhiku-plug-btn-secondary mhk-template-preview"><?php esc_html_e( 'Preview', 'muhiku-plug' ); ?></a>
								</div>
							<?php endif; ?>
						</figure>
						<div class="muhiku-plug-form-id-container">
							<a class="muhiku-plug-template-name <?php echo esc_attr( $upgrade_class ); ?>" href="#" data-licence-plan="<?php echo esc_attr( $license_plan ); ?>" data-template-name-raw="<?php echo esc_attr( $template->title ); ?>" data-template="<?php echo esc_attr( $template->slug ); ?>" data-template-name="<?php echo esc_attr( $template_name ); ?>"><?php echo esc_html( $template->title ); ?></a>
						</div>
					</div>
					<?php
				endforeach;
			}
			?>
		</div>
	</div>
</div>
<?php
/**
 * Prints the JavaScript templates for install admin notices.
 *
 * Template takes one argument with four values:
 *
 *     param {object} data {
 *         Arguments for admin notice.
 *
 *         @type string id        ID of the notice.
 *         @type string className Class names for the notice.
 *         @type string message   The notice's message.
 *         @type string type      The type of update the notice is for. Either 'plugin' or 'theme'.
 *     }
 *
 * @since 1.6.0
 */
function muhiku_forms_print_admin_notice_templates() {
	?>
	<script id="tmpl-wp-installs-admin-notice" type="text/html">
		<div <# if ( data.id ) { #>id="{{ data.id }}"<# } #> class="notice {{ data.className }}"><p>{{{ data.message }}}</p></div>
	</script>
	<script id="tmpl-wp-bulk-installs-admin-notice" type="text/html">
		<div id="{{ data.id }}" class="{{ data.className }} notice <# if ( data.errors ) { #>notice-error<# } else { #>notice-success<# } #>">
			<p>
				<# if ( data.successes ) { #>
					<# if ( 1 === data.successes ) { #>
						<# if ( 'plugin' === data.type ) { #>
							<?php
							/* translators: %s: Number of plugins */
							printf( esc_html__( '%s plugin successfully installed.', 'muhiku-plug' ), '{{ data.successes }}' );
							?>
						<# } #>
					<# } else { #>
						<# if ( 'plugin' === data.type ) { #>
							<?php
							/* translators: %s: Number of plugins */
							printf( esc_html__( '%s plugins successfully installed.', 'muhiku-plug' ), '{{ data.successes }}' );
							?>
						<# } #>
					<# } #>
				<# } #>
				<# if ( data.errors ) { #>
					<button class="button-link bulk-action-errors-collapsed" aria-expanded="false">
						<# if ( 1 === data.errors ) { #>
							<?php
							/* translators: %s: Number of failed installs */
							printf( esc_html__( '%s install failed.', 'muhiku-plug' ), '{{ data.errors }}' );
							?>
						<# } else { #>
							<?php
							/* translators: %s: Number of failed installs */
							printf( esc_html__( '%s installs failed.', 'muhiku-plug' ), '{{ data.errors }}' );
							?>
						<# } #>
						<span class="screen-reader-text"><?php esc_html_e( 'Show more details', 'muhiku-plug' ); ?></span>
						<span class="toggle-indicator" aria-hidden="true"></span>
					</button>
				<# } #>
			</p>
			<# if ( data.errors ) { #>
				<ul class="bulk-action-errors hidden">
					<# _.each( data.errorMessages, function( errorMessage ) { #>
						<li>{{ errorMessage }}</li>
					<# } ); #>
				</ul>
			<# } #>
		</div>
	</script>
	<?php
}
muhiku_forms_print_admin_notice_templates();

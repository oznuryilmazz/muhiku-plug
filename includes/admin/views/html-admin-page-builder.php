<?php
/**
 * Admin View: Builder
 *
 * @package MuhikuPlug/Admin/Builder
 */

defined( 'ABSPATH' ) || exit;

$form_data['form_field_id'] = isset( $form_data['form_field_id'] ) ? $form_data['form_field_id'] : 0;
$form_data['form_enabled']  = isset( $form_data['form_enabled'] ) ? $form_data['form_enabled'] : 1;

// Get tabs for the builder panel.
$tabs = apply_filters( 'muhiku_forms_builder_tabs_array', array() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride

// Get preview link.
$preview_link = add_query_arg(
	array(
		'form_id'     => absint( $form_data['id'] ),
		'mhk_preview' => 'true',
	),
	home_url()
);

?>
<div id="muhiku-plug-builder" class="muhiku-plug">
	<div class="muhiku-plug-overlay">
		<div class="muhiku-plug-overlay-content">
			<svg xmlns="http://www.w3.org/2000/svg" id="Bk5ao7MMX" viewBox="0 0 301 154"><style>@-webkit-keyframes r1U56i7MzQ_Animation{0%,to{opacity:.5}50%{opacity:1}}@keyframes r1U56i7MzQ_Animation{0%,to{opacity:.5}50%{opacity:1}}@-webkit-keyframes HkVqTomfGX_Animation{0%,83.33%,to{opacity:.5}33.33%{opacity:1}}@keyframes HkVqTomfGX_Animation{0%,83.33%,to{opacity:.5}33.33%{opacity:1}}@-webkit-keyframes H1G56i7GGm_Animation{0%,66.67%,to{opacity:.5}16.67%{opacity:1}}@keyframes H1G56i7GGm_Animation{0%,66.67%,to{opacity:.5}16.67%{opacity:1}}#Bk5ao7MMX *{-webkit-animation-duration:.6s;animation-duration:.6s;-webkit-animation-iteration-count:infinite;animation-iteration-count:infinite;-webkit-animation-timing-function:cubic-bezier(0,0,1,1);animation-timing-function:cubic-bezier(0,0,1,1)}#H1G56i7GGm_HyOMAQfzm{-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box}</style><g id="H1e9TjXff7" data-name="Layer 2"><g id="S1Wc6j7zMQ" data-name="Layer 1"><path fill="#5891ff" style="-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box;-webkit-animation-name:H1G56i7GGm_Animation;animation-name:H1G56i7GGm_Animation" d="M160.12 154H12.66A12.65 12.65 0 0 1 2.4 134l74-101.9a12.64 12.64 0 0 1 20.54 0l14.79 19.82L170.4 134a12.64 12.64 0 0 1-10.28 20z"/><path d="M158.79 153.56H50.46A13.1 13.1 0 0 1 43.59 133l65.12-85.06 60.72 84.95a13.08 13.08 0 0 1-10.64 20.67z" opacity=".1"/><path fill="#50abe8" style="-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box;-webkit-animation-name:HkVqTomfGX_Animation;animation-name:HkVqTomfGX_Animation" d="M261.06 154H63.29a12.65 12.65 0 0 1-10-20.33L152.49 5a12.64 12.64 0 0 1 20.1 0l45.63 59.13 52.9 69.6A12.64 12.64 0 0 1 261.06 154z"/><path d="M258.38 154.45h-81.64c-8-2.25-12.17-12.22-8.05-19.92L215.47 61l53 72.35c6.32 8.65.38 21.1-10.09 21.1z" opacity=".1"/><path fill="#65eaff" style="-webkit-transform-origin:50% 50%;transform-origin:50% 50%;transform-box:fill-box;-webkit-animation-name:r1U56i7MzQ_Animation;animation-name:r1U56i7MzQ_Animation" d="M186.49 154h102.74a12.65 12.65 0 0 0 10.57-19.58l-51.19-77.13a12.64 12.64 0 0 0-21.11 0L176 134.4a12.64 12.64 0 0 0 10.49 19.6z"/></g></g></svg>
			<span class="loading"><?php esc_html_e( 'Loading&hellip;', 'muhiku-plug' ); ?></span>
		</div>
	</div>
	<form id="muhiku-plug-builder-form" name="muhiku-plug-builder" method="post" data-id="<?php echo absint( $form_id ); ?>">
		<input type="hidden" name="id" value="<?php echo absint( $form_id ); ?>">
		<input type="hidden" name="form_enabled" value="<?php echo absint( $form_data['form_enabled'] ); ?>">
		<input type="hidden" value="<?php echo absint( $form_data['form_field_id'] ); ?>" name="form_field_id" id="muhiku-plug-field-id">

		<div class="muhiku-plug-nav-wrapper clearfix">
			<nav class="nav-tab-wrapper mhk-nav-tab-wrapper">
				<?php
				foreach ( $tabs as $slug => $tab ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride
					echo '<a href="#" class="mhk-panel-' . esc_attr( $slug ) . '-button nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '" data-panel="' . esc_attr( $slug ) . '"><span class="mhk-nav-icon ' . esc_attr( $slug ) . '"></span>' . esc_html( $tab['label'] ) . '</a>';
				}

				do_action( 'muhiku_forms_builder_tabs' );
				?>
			</nav>
			<div class="mhk-forms-nav-right">
				<div class="mhk-shortcode-field">
					<input type="text" class="large-text code" onfocus="this.select();" value="<?php printf( esc_html( '[muhiku_form id="%s"]' ), isset( $_GET['form_id'] ) ? absint( sanitize_text_field( wp_unslash( $_GET['form_id'] ) ) ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification ?>" id="mhk-form-shortcode" readonly="readonly" />
					<button id="copy-shortcode" class="muhiku-plug-btn help_tip dashicons copy-shortcode" href="#" data-tip="<?php esc_attr_e( 'Copy Shortcode!', 'muhiku-plug' ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'muhiku-plug' ); ?>">
						<span class="screen-reader-text"><?php esc_html_e( 'Copy shortcode', 'muhiku-plug' ); ?></span>
					</button>
				</div>
				<a class="muhiku-plug-btn muhiku-plug-preview-button" href="<?php echo esc_url( $preview_link ); ?>" rel="bookmark" target="_blank"><?php esc_html_e( 'Preview', 'muhiku-plug' ); ?></a>
				<button name="save_form" class="muhiku-plug-btn muhiku-plug-save-button" type="button" value="<?php esc_attr_e( 'Save', 'muhiku-plug' ); ?>"><?php esc_html_e( 'Save', 'muhiku-plug' ); ?></button>
			</div>
		</div>
		<div class="mhk-tab-content">
			<?php foreach ( $tabs as $slug => $tab ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride ?>
				<div id="muhiku-plug-panel-<?php echo esc_attr( $slug ); ?>" class="muhiku-plug-panel<?php echo $current_tab === $slug ? ' active' : ''; ?>">
					<div class="muhiku-plug-panel-<?php echo $tab['sidebar'] ? 'sidebar-content' : 'full-content'; ?>">
						<?php if ( $tab['sidebar'] ) : ?>
							<div class="muhiku-plug-panel-sidebar">
								<?php do_action( 'muhiku_forms_builder_sidebar_' . $slug ); ?>
							</div>
						<?php endif; ?>
						<div class="panel-wrap muhiku-plug-panel-content-wrap">
							<div class="muhiku-plug-panel-content">
								<?php do_action( 'muhiku_forms_builder_content_' . $slug ); ?>
							</div>
							<?php do_action( 'muhiku_forms_builder_after_content_' . $slug ); ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
			<?php do_action( 'muhiku_forms_builder_output' ); ?>
		</div>
	</form>
</div>
<script type="text/html" id="tmpl-muhiku-plug-field-preview-choices">
	<# if ( data.settings.choices_images ) { #>
		<ul class="widefat primary-input muhiku-plug-image-choices">
			<# _.each( data.order, function( choiceID, key ) {  #>
				<li class="muhiku-plug-image-choices-item<# if ( 1 === data.settings.choices[choiceID].default ) { print( ' muhiku-plug-selected' ); } #>">
					<label>
						<span class="muhiku-plug-image-choices-image">
							<# if ( ! _.isEmpty( data.settings.choices[choiceID].image ) ) { #>
								<img src="{{ data.settings.choices[choiceID].image }}" alt="{{ data.settings.choices[choiceID].label }}"<# if ( data.settings.choices[choiceID].label ) { #> title="{{ data.settings.choices[choiceID].label }}"<# } #>>
							<# } else { #>
								<img src="<?php echo esc_url( mhk()->plugin_url() . '/assets/images/muhiku-plug-placeholder.png' ); ?>" alt="{{ data.settings.choices[choiceID].label }}"<# if ( data.settings.choices[choiceID].label ) { #> title="{{ data.settings.choices[choiceID].label }}"<# } #>>
							<# } #>
						</span>
						<input type="{{ data.type }}" disabled<# if ( 1 === data.settings.choices[choiceID].default ) { print( ' checked' ); } #>>
						<span class="muhiku-plug-image-choices-label">{{{ data.settings.choices[choiceID].label }}} <# if(( 'payment-checkbox' === data.settings.type ) || ( 'payment-multiple' === data.settings.type )) { print ( ' - ' + data.amountFilter( mhk_data, data.settings.choices[choiceID].value )) }#></span>
					</label>
				</li>
			<# }) #>
		</ul>
	<# } else { #>
		<ul class="widefat primary-input">
			<# _.each( data.order, function( choiceID, key ) {  #>
				<li>
					<input type="{{ data.type }}" disabled<# if ( 1 === data.settings.choices[choiceID].default ) { print( ' checked' ); } #>>{{{ data.settings.choices[choiceID].label }}} <# if(( 'payment-checkbox' === data.settings.type ) || ( 'payment-multiple' === data.settings.type )) { print ( ' - ' + data.amountFilter( mhk_data, data.settings.choices[choiceID].value )) }#>
				</li>
			<# }) #>
		</ul>
	<# } #>
</script>

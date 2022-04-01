<?php
/**
 * Admin View: Entries
 *
 * @package MuhikuPlug/Admin/Entries/Views
 */

defined( 'ABSPATH' ) || exit;

$form_id    = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$entry_id   = isset( $_GET['view-entry'] ) ? absint( $_GET['view-entry'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
$entry      = mhk_get_entry( $entry_id, true );
$form_data  = mhk()->form->get( $form_id, array( 'content_only' => true ) );
$hide_empty = isset( $_COOKIE['muhiku_forms_entry_hide_empty'] ) && 'true' === $_COOKIE['muhiku_forms_entry_hide_empty'];
$trash_link = wp_nonce_url(
	add_query_arg(
		array(
			'trash' => $entry_id,
		),
		admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . $form_id )
	),
	'trash-entry'
);

?>
<div class="wrap muhiku-plug">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'View Entry', 'muhiku-plug' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=mhk-entries&amp;form_id=' . $form_id ) ); ?>" class="page-title-action"><?php esc_html_e( 'Back to All Entries', 'muhiku-plug' ); ?></a>
	<hr class="wp-header-end">
	<?php do_action( 'muhiku_forms_view_entries_notices' ); ?>
	<div class="muhiku-plug-entry">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2">
				<!-- Entry Fields metabox -->
				<div id="post-body-content" style="position: relative;">
					<div id="muhiku-plug-entry-fields" class="stuffbox">
						<h2 class="hndle">
							<?php do_action( 'muhiku_forms_before_entry_details_hndle', $entry ); ?>
							<span>
							<?php
							/* translators: %s: Entry ID */
							printf( esc_html__( '%1$s: Entry #%2$s', 'muhiku-plug' ), esc_html( _draft_or_post_title( $form_id ) ), absint( $entry_id ) );
							?>
							</span>
							<?php do_action( 'muhiku_forms_after_entry_details_hndle', $entry ); ?>
							<a href="#" class="muhiku-plug-empty-field-toggle">
								<?php $hide_empty ? esc_html_e( 'Show Empty Fields', 'muhiku-plug' ) : esc_html_e( 'Hide Empty Fields', 'muhiku-plug' ); ?>
							</a>
						</h2>
						<div class="inside">
							<table class="wp-list-table widefat fixed striped posts">
								<tbody>
								<?php
								$entry_meta = apply_filters( 'muhiku_forms_entry_single_data', $entry->meta, $entry, $form_data );

								if ( empty( $entry_meta ) ) {
									// Whoops, no fields! This shouldn't happen under normal use cases.
									echo '<p class="no-fields">' . esc_html__( 'This entry does not have any fields.', 'muhiku-plug' ) . '</p>';
								} else {
									// Display the fields and their values.
									foreach ( $entry_meta as $meta_key => $meta_value ) {
										// Check if hidden fields exists.
										if ( in_array( $meta_key, apply_filters( 'muhiku_forms_hidden_entry_fields', array() ), true ) ) {
											continue;
										}

										$meta_value = is_serialized( $meta_value ) ? $meta_value : wp_strip_all_tags( $meta_value );

										// Check for empty serialized value.
										if ( is_serialized( $meta_value ) ) {
											$raw_meta_val = unserialize( $meta_value ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize
											if ( preg_match( '/dropdown_/', $meta_key ) && empty( $raw_meta_val[0] ) ) {
												$meta_value = '';
											} elseif ( ! preg_match( '/dropdown_/', $meta_key ) && empty( $raw_meta_val['label'][0] ) ) {
												$meta_value = '';
											}
										}

										if ( mhk_is_json( $meta_value ) ) {
											$meta_value = json_decode( $meta_value, true );
											$meta_value = $meta_value['value'];
										}

										$field_value     = apply_filters( 'muhiku_forms_html_field_value', $meta_value, $entry_meta[ $meta_key ], $entry_meta, 'entry-single' );
										$field_class     = is_string( $field_value ) && ( '(empty)' === wp_strip_all_tags( $field_value ) || '' === $field_value ) ? ' empty' : '';
										$field_style     = $hide_empty && empty( $field_value ) ? 'display:none;' : '';
										$correct_answers = false;

										// Field name.
										echo '<tr class="muhiku-plug-entry-field field-name' . esc_attr( $field_class ) . '" style="' . esc_attr( $field_style ) . '"><th>';

										$value = mhk_get_form_data_by_meta_key( $form_id, $meta_key, json_decode( $entry->fields ) );

										if ( $value ) {
											if ( apply_filters( 'muhiku_forms_html_field_label', false ) ) {
												$correct_answers = apply_filters( 'muhiku_forms_single_entry_label', $value, $meta_key, $field_value );
											} else {
												echo '<strong>' . esc_html( make_clickable( $value ) ) . '</strong>';
											}
										} else {
											echo '<strong>' . esc_html__( 'Field ID', 'muhiku-plug' ) . '</strong>';
										}

										echo '</th></tr>';

										// Field value.
										echo '<tr class="muhiku-plug-entry-field field-value' . esc_attr( $field_class ) . '" style="' . esc_attr( $field_style ) . '"><td>';

										if ( ! empty( $field_value ) ) {
											if ( is_serialized( $field_value ) ) {
												$field_value = maybe_unserialize( $field_value );
												$field_label = isset( $field_value['label'] ) ? $field_value['label'] : $field_value;

												if ( ! empty( $field_label ) && is_array( $field_label ) ) {
													foreach ( $field_label as $field => $value ) {
														$answer_class = '';
														if ( $correct_answers ) {
															if ( in_array( $value, $correct_answers, true ) ) {
																$answer_class = 'correct_answer';
															} else {
																$answer_class = 'wrong_answer';
															}
														}
														echo '<span class="list ' . esc_attr( $answer_class ) . '">' . esc_html( wp_strip_all_tags( $value ) ) . '</span>';
													}
												} else {
													echo nl2br( make_clickable( $field_label ) ); // @codingStandardsIgnoreLine
												}
											} else {
												if ( $correct_answers && false !== $correct_answers ) {
													if ( in_array( $field_value, $correct_answers, true ) ) {
														$answer_class = 'correct_answer';
													} else {
														$answer_class = 'wrong_answer';
													}
													echo '<span class="list ' . esc_attr( $answer_class ) . '">' . esc_html( wp_strip_all_tags( $field_value ) ) . '</span>';
												} else {
													echo nl2br( make_clickable( $field_value ) ); // @codingStandardsIgnoreLine
												}
											}
										} else {
											esc_html_e( 'Empty', 'muhiku-plug' );
										}

										echo '</td></tr>';
									}
								}
								?>
								</tbody>
							</table>
						</div>
					</div>

					<?php do_action( 'muhiku_forms_entry_details_content', $entry, $form_id ); ?>
				</div>
				<!-- Entry Details metabox -->
				<div id="postbox-container-1" class="postbox-container">
					<div id="muhiku-plug-entry-details" class="stuffbox">
						<h2><?php esc_html_e( 'Entry Details', 'muhiku-plug' ); ?></h2>
						<div class="inside">
							<div id="submitbox" class="submitbox">
								<div class="muhiku-plug-entry-details-meta">
									<p class="muhiku-plug-entry-id">
										<span class="dashicons dashicons-admin-network"></span>
										<?php esc_html_e( 'Entry ID:', 'muhiku-plug' ); ?>
										<strong><?php echo absint( $entry_id ); ?></strong>
									</p>

									<p class="muhiku-plug-entry-date">
										<span class="dashicons dashicons-calendar"></span>
										<?php esc_html_e( 'Submitted:', 'muhiku-plug' ); ?>
										<strong><?php echo esc_html( date_i18n( esc_html__( 'M j, Y @ g:ia', 'muhiku-plug' ), strtotime( $entry->date_created ) + ( get_option( 'gmt_offset' ) * 3600 ) ) ); ?> </strong>
									</p>

									<?php if ( ! empty( $entry->date_modified ) ) : ?>
										<p class="muhiku-plug-entry-modified">
											<span class="dashicons dashicons-calendar"></span>
											<?php esc_html_e( 'Modified:', 'muhiku-plug' ); ?>
											<strong><?php echo esc_html( date_i18n( esc_html__( 'M j, Y @ g:ia', 'muhiku-plug' ), strtotime( $entry->date_modified ) + ( get_option( 'gmt_offset' ) * 3600 ) ) ); ?> </strong>
										</p>
									<?php endif; ?>

									<?php if ( ! empty( $entry->user_id ) && 0 !== $entry->user_id ) : ?>
										<p class="muhiku-plug-entry-user">
											<span class="dashicons dashicons-admin-users"></span>
											<?php
											esc_html_e( 'User:', 'muhiku-plug' );
											$user      = get_userdata( $entry->user_id );
											$user_name = ! empty( $user->display_name ) ? $user->display_name : $user->user_login;
											// phpcs:ignore WordPress.WP.GlobalVariablesOverride
											$user_url = add_query_arg(
												array(
													'user_id' => absint( $user->ID ),
												),
												admin_url( 'user-edit.php' )
											);
											?>
											<strong><a href="<?php echo esc_url( $user_url ); ?>"><?php echo esc_html( $user_name ); ?></a></strong>
										</p>
									<?php endif; ?>

									<?php if ( ! empty( $entry->user_ip_address ) ) : ?>
										<p class="muhiku-plug-entry-ip">
											<span class="dashicons dashicons-location"></span>
											<?php esc_html_e( 'User IP:', 'muhiku-plug' ); ?>
											<strong><?php echo esc_html( $entry->user_ip_address ); ?></strong>
										</p>
									<?php endif; ?>

									<?php if ( ! empty( $entry->referer ) ) : ?>
										<p class="muhiku-plug-entry-referer">
											<span class="dashicons dashicons-admin-links"></span>
											<?php esc_html_e( 'Referer Link:', 'muhiku-plug' ); ?>
											<strong><a href="<?php echo esc_url( $entry->referer ); ?>" target="_blank"><?php esc_html_e( 'Görüntüle', 'muhiku-plug' ); ?></a></strong>
										</p>
									<?php endif; ?>

									<?php if ( apply_filters( 'muhiku_forms_entry_details_sidebar_details_status', false, $entry, $form_data ) ) : ?>
										<p class="muhiku-plug-entry-status">
											<span class="dashicons dashicons-category"></span>
											<?php esc_html_e( 'Status:', 'muhiku-plug' ); ?>
											<strong><?php echo ! empty( $entry->status ) ? esc_html( ucwords( sanitize_text_field( $entry->status ) ) ) : esc_html__( 'Completed', 'muhiku-plug' ); ?></strong>
										</p>
									<?php endif; ?>

									<?php do_action( 'muhiku_forms_entry_details_sidebar_details', $entry, $entry_meta, $form_data ); ?>
								</div>

								<?php if ( current_user_can( 'muhiku_forms_edit_entry', $entry->entry_id ) || current_user_can( 'muhiku_forms_delete_entry', $entry->entry_id ) ) : ?>
									<div id="major-publishing-actions">
										<?php do_action( 'muhiku_forms_entry_details_sidebar_action', $entry, $form_data ); ?>
										<?php if ( current_user_can( 'muhiku_forms_delete_entry', $entry->entry_id ) ) : ?>
											<div id="delete-action">
												<a class="submitdelete" aria-label="<?php echo esc_attr__( 'Move to trash', 'muhiku-plug' ); ?>" href="<?php echo esc_url( $trash_link ); ?>"><?php esc_html_e( 'Move to trash', 'muhiku-plug' ); ?></a>
											</div>
										<?php endif; ?>
										<div class="clear"></div>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
					<?php do_action( 'muhiku_forms_after_entry_details', $entry, $entry_meta, $form_data ); ?>
				</div>
			</div>
		</div>
	</div>
</div>
<!--  Toggle displaying empty fields. -->
<script type="text/javascript">
	jQuery( document ).on( 'click', '#muhiku-plug-entry-fields .muhiku-plug-empty-field-toggle', function( event ) {
		event.preventDefault();

		// Handle cookie.
		if ( wpCookies.get( 'muhiku_forms_entry_hide_empty' ) === 'true' ) {

			// User was hiding empty fields, so now display them.
			wpCookies.remove( 'muhiku_forms_entry_hide_empty' );
			jQuery( this ).text( 'Hide Empty Fields' );
		} else {

			// User was seeing empty fields, so now hide them.
			wpCookies.set( 'muhiku_forms_entry_hide_empty', 'true', 2592000 ); // 1month.
			jQuery( this ).text( 'Show Empty Fields' );
		}

		jQuery( '.muhiku-plug-entry-field.empty' ).toggle();
	});
</script>

<?php
/**
 * @package MuhikuPlug/Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$classes = apply_filters(
	'muhiku_forms_success_notice_class',
	array(
		'muhiku-plug-notice--success',
	)
);

global $__muhiku_form_id;
global $__muhiku_form_entry_id;

?>

<?php if ( $messages ) : ?>
	<?php foreach ( $messages as $message ) : ?>
		<div class="muhiku-plug-notice <?php echo esc_attr( implode( ' ', $classes ) ); ?>" role="alert">
		<?php
			echo wp_kses(
				$message,
				array(
					'div'   => array(
						'class' => true,
						'style' => true,
					),
					'input' => array(
						'type'     => true,
						'value'    => true,
						'class'    => true,
						'disabled' => true,
						'checked'  => true,
					),
					'ul'    => array(
						'class' => true,
					),
					'li'    => array(
						'class' => true,
					),
				)
			);

		if ( ! empty( $__muhiku_form_id ) && ! empty( $__muhiku_form_entry_id ) ) {
			$pdf_download_message = get_option( 'muhiku_forms_pdf_custom_download_text', '' );

			if ( empty( $pdf_download_message ) ) {
				$pdf_download_message = __( 'Download your form submission in PDF format', 'muhiku-plug' );
			}

			printf(
				'%s%s%s',
				'<br><small><a href="?page=mhk-entries-pdf&form_id=' . esc_attr( $__muhiku_form_id ) . '&entry_id=' . esc_attr( $__muhiku_form_entry_id ) . '">',
				esc_html( $pdf_download_message ),
				'</a></small>'
			);
		}

		?>
	</div>
	<?php endforeach; ?>
<?php endif; ?>

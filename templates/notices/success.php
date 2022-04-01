<?php
/**
 * Show success messages
 *
 * This template can be overridden by copying it to yourtheme/muhiku-plug/notices/success.php.
 *
 * HOWEVER, on occasion Muhiku Plug will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.wpmuhiku.com/docs/muhiku-plug/template-structure/
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

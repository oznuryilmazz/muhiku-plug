<?php
/**
 * Show error messages
 *
 * This template can be overridden by copying it to yourtheme/muhiku-plug/notices/error.php.
 *
 * HOWEVER, on occasion Muhiku Plug will need to update template files and you
 * and you (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.wpeverest.com/docs/muhiku-plug/template-structure/
 * @package MuhikuPlug/Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( $messages ) : ?>
	<div class="muhiku-plug-notice muhiku-plug-notice--error" role="alert">
		<?php if ( 1 === count( $messages ) ) : ?>
			<?php echo wp_kses_post( $messages[0] ); ?>
		<?php else : ?>
			<ul class="muhiku-plug-notice-list">
				<?php foreach ( $messages as $message ) : ?>
					<li class="muhiku-plug-notice-list__item"><?php echo wp_kses_post( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>
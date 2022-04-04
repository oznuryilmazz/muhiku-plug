<?php
/**
 * @package MuhikuPlug/Templates
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

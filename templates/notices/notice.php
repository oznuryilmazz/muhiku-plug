<?php
/**
 * @package MuhikuPlug/Templates
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<?php if ( $messages ) : ?>
	<?php foreach ( $messages as $message ) : ?>
		<div class="muhiku-plug-notice"><?php echo wp_kses_post( $message ); ?></div>
	<?php endforeach; ?>
<?php endif; ?>

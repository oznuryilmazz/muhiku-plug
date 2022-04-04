<?php
/**
 * Admin View: Page - Status
 *
 * @package MuhikuPlug/Admin/Logs
 */

defined( 'ABSPATH' ) || exit;

$tabs        = apply_filters(
	'muhiku_forms_admin_status_tabs',
	array(
		'import' => __( 'Import', 'muhiku-plug' ),
		'export' => __( 'Export', 'muhiku-plug' ),
	)
);
$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( wp_unslash( $_REQUEST['tab'] ) ) : 'import'; // phpcs:ignore WordPress.Security.NonceVerification

if ( 'yes' === get_option( 'muhiku_forms_enable_log', 'no' ) ) {
	$tabs['logs'] = __( 'Logs', 'muhiku-plug' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}

?>
<div class="wrap muhiku-plug">
	<nav class="nav-tab-wrapper mhk-nav-tab-wrapper">
		<?php
		foreach ( $tabs as $slug => $label ) {
			echo '<a href="' . esc_html( admin_url( 'admin.php?page=mhk-tools&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '"><span class="mhk-nav-icon ' . esc_attr( $slug ) . '"></span>' . esc_html( $label ) . '</a>';
		}
		?>
	</nav>
	<div class="muhiku-plug-tools">
		<h1 class="screen-reader-text"><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>
		<?php
		switch ( $current_tab ) {
			case 'logs':
				MHK_Admin_Tools::status_logs();
				break;
			case 'import':
				MHK_Admin_Tools::import();
				break;
			case 'export':
				MHK_Admin_Tools::export();
				break;
			default:
				if ( array_key_exists( $current_tab, $tabs ) && has_action( 'muhiku_forms_admin_status_content_' . $current_tab ) ) {
					do_action( 'muhiku_forms_admin_status_content_' . $current_tab );
				} else {
					MHK_Admin_Tools::import();
				}
				break;
		}
		?>
	</div>
</div>
<?php
/**
 * EverestForm Elementor
 *
 * @package EverstForms\Class
 * @version 1.8.5
 */

defined( 'ABSPATH' ) || exit;

use Elementor\Plugin as ElementorPlugin;

/**
 * Elementor class.
 */
class MHK_Elementor {

	/**
	 * Initialize.
	 */
	public function __construct() {

		$this->init();

	}

	/**
	 * Initialize elementor hooks.
	 *
	 * @since 1.6.0
	 */
	public function init() {

		if ( ! class_exists( '\Elementor\Plugin' ) ) {
			return;
		}

		add_action( 'elementor/widgets/register', array( $this, 'register_widget' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'mhk_elementor_widget_categories' ) );
		add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'editor_assets' ) );
	}

	/**
	 * Register Everest forms Widget.
	 *
	 * @since 1.8.5
	 */
	public function register_widget() {
			// Include Widget files.
			require_once MHK_ABSPATH . 'includes/elementor/class-mhk-widget.php';

			ElementorPlugin::instance()->widgets_manager->register( new MHK_Widget() );
	}

	/**
	 * Custom Widgets Category.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 *
	 * @since 1.8.5
	 */
	public function mhk_elementor_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'muhiku-plug',
			array(
				'title' => esc_html__( 'Muhiku Plug', 'muhiku-plug' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

		/**
		 * Load assets in the elementor document.
		 */
	public function editor_assets() {
		wp_register_style( 'muhiku-plug-admin', mhk()->plugin_url() . '/assets/css/admin.css', array(), MHK_VERSION );
		wp_enqueue_style( 'muhiku-plug-admin' );
	}
}

new MHK_Elementor();

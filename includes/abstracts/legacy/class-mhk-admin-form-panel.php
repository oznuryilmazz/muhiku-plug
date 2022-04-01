<?php
/**
 * Abstract form panel
 *
 * @package    MuhikuPlug\Abstracts
 * @deprecated 1.2.0
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract MHK_Admin_Form_Panel Class
 *
 * @deprecated 1.2.0
 */
abstract class MHK_Admin_Form_Panel {

	/**
	 * Full name of the panel.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Slug.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Font Awesome Icon used for the editor button.
	 *
	 * @var mixed
	 */
	protected $icon = false;

	/**
	 * Priority order the field button should show inside the "Add Fields" tab.
	 *
	 * @var integer
	 */
	public $order = 50;

	/**
	 * If panel contains a sidebar element or is full width.
	 *
	 * @var boolean
	 */
	protected $sidebar = false;

	/**
	 * Contains form object if we have one.
	 *
	 * @var object
	 */
	protected $form;

	/**
	 * Contains array of the form data (post_content).
	 *
	 * @var array
	 */
	protected $form_data;

	/**
	 * Form Settings.
	 *
	 * @var array
	 */
	public $form_setting;

	/**
	 * Class constructor.
	 */
	public function __construct() {
		// Load form if found.
		$form_id            = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;
		$this->form         = mhk()->form->get( $form_id );
		$this->form_data    = is_object( $this->form ) ? mhk_decode( $this->form->post_content ) : array();
		$this->form_setting = isset( $this->form_data['settings'] ) ? $this->form_data['settings'] : array();
		$this->init();

		// Hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueues' ), 15 );
		add_action( 'everest_forms_builder_tabs', array( $this, 'button' ), $this->order );
		add_action( 'everest_forms_builder_output', array( $this, 'panel_output' ), $this->order );
	}

	/**
	 * Hook in tabs.
	 */
	public function init() {}

	/**
	 * Enqueue assets
	 */
	public function enqueues() {}

	/**
	 * Primary panel button in the left panel navigation.
	 */
	public function button() {
		global $current_tab;

		$active = $current_tab === $this->slug ? 'nav-tab-active' : '';

		printf( '<a href="#" class="mhk-panel-%1$s-button nav-tab %2$s" data-panel="%1$s">', $this->slug, $active );
		printf( '<span class="%s"></span>', $this->icon );
		printf( '%s</a>', $this->name );
	}

	/**
	 * Outputs the contents of the panel.
	 *
	 * @param object $form
	 * @param string $view
	 */
	public function panel_output() {
		global $current_tab;

		$active = $current_tab == $this->slug ? 'active' : '';

		$wrap = $this->sidebar ? 'muhiku-plug-panel-sidebar-content' : 'muhiku-plug-panel-full-content';

		printf( '<div id="muhiku-plug-panel-%s" class="muhiku-plug-panel %s">', $this->slug, $active );

		printf( '<div class="%s">', $wrap );

		if ( true == $this->sidebar ) {

			echo '<div class="muhiku-plug-panel-sidebar">';

			do_action( 'everest_forms_builder_before_panel_sidebar', $this->form, $this->slug );

			$this->panel_sidebar();

			do_action( 'everest_forms_builder_after_panel_sidebar', $this->form, $this->slug );

			echo '</div>';
		}

		echo '<div class="muhiku-plug-panel-content-wrap">';
		echo '<div class="muhiku-plug-panel-content">';

		do_action( 'everest_forms_builder_before_panel_content', $this->form, $this->slug );

		$this->panel_content();

		do_action( 'everest_forms_builder_after_panel_content', $this->form, $this->slug );

		echo '</div></div></div></div>';
	}

	/**
	 * Outputs the panel's sidebar if we have one.
	 */
	public function panel_sidebar() {}

	/**
	 * Outputs panel sidebar sections.
	 */
	public function panel_sidebar_section( $name, $slug, $icon = '' ) {

		$class  = '';
		$class .= $slug == 'default' ? ' default' : '';
		$class .= ! empty( $icon ) ? ' icon' : '';

		echo '<a href="#" class="mhk-panel-tab mhk-setting-panel muhiku-plug-panel-sidebar-section muhiku-plug-panel-sidebar-section-' . esc_attr( $slug ) . $class . '" data-section="' . esc_attr( $slug ) . '">';

		if ( ! empty( $icon ) ) {
			echo '<img src="' . esc_url( $icon ) . '">';
		}

		echo esc_html( $name );

		echo '<i class="dashicons dashicons-arrow-right-alt2 muhiku-plug-toggle-arrow"></i>';

		echo '</a>';
	}

	/**
	 * Outputs the panel's primary content.
	 *
	 * @since 1.0.0
	 */
	public function panel_content() {}
}

<?php
/**
 * @package MuhikuPlug\Admin
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MHK_Admin_Form_Panel', false ) ) {
	include_once dirname( MHK_PLUGIN_FILE ) . '/includes/abstracts/legacy/class-mhk-admin-form-panel.php';
}

if ( ! class_exists( 'MHK_Builder_Page', false ) ) :

	abstract class MHK_Builder_Page extends MHK_Admin_Form_Panel {

		/**
		 * @var object
		 */
		protected $form;

		/**
		 * @var string
		 */
		protected $id = '';

		/**
		 * @var string
		 */
		protected $label = '';

		/**
		 * @var boolean
		 */
		protected $sidebar = false;

		/**
		 * @var array
		 */
		public $form_data = array();

		public function __construct() {
			$form_id         = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : 0;  
			$this->form      = mhk()->form->get( $form_id );
			$this->form_data = is_object( $this->form ) ? mhk_decode( $this->form->post_content ) : array();

			$this->init_hooks();

			add_filter( 'muhiku_forms_builder_tabs_array', array( $this, 'add_builder_page' ), 20 );
			add_action( 'muhiku_forms_builder_sidebar_' . $this->id, array( $this, 'output_sidebar' ) );
			add_action( 'muhiku_forms_builder_content_' . $this->id, array( $this, 'output_content' ) );
		}

		/**
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * @return string
		 */
		public function get_label() {
			return $this->label;
		}

		/**
		 * @return string
		 */
		public function get_sidebar() {
			return $this->sidebar;
		}

		/**
		 * @return string
		 */
		public function get_form_data() {
			return $this->form_data;
		}

		/**
		 * @param  array $pages Builder pages.
		 * @return mixed
		 */
		public function add_builder_page( $pages ) {
			$pages[ $this->id ] = array(
				'label'   => $this->label,
				'sidebar' => $this->sidebar,
			);

			return $pages;
		}

		/**
		 * @param string $name
		 * @param string $slug 
		 * @param string $icon 
		 * @param string $container_name 
		 */
		public function add_sidebar_tab( $name, $slug, $icon = '', $container_name = 'setting' ) {
			$class  = '';
			$class .= 'default' === $slug ? ' default' : '';
			$class .= ! empty( $icon ) ? ' icon' : '';

			echo '<a href="#" class="mhk-panel-tab mhk-' . esc_attr( $container_name ) . '-panel muhiku-plug-panel-sidebar-section muhiku-plug-panel-sidebar-section-' . esc_attr( $slug ) . esc_attr( $class ) . '" data-section="' . esc_attr( $slug ) . '">';
			if ( ! empty( $icon ) ) {
				echo '<figure class="logo"><img src="' . esc_url( $icon ) . '"></figure>';
			}
			echo esc_html( $name );
			echo '<i class="dashicons dashicons-arrow-right-alt2 muhiku-plug-toggle-arrow"></i>';
			echo '</a>';
		}

		public function init_hooks() {}

		public function output_sidebar() {}

		public function output_content() {}
	}

endif;

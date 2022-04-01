<?php
/**
 * Muhiku Plug for Elementor.
 *
 * @package muhkuForms\Class
 * @version 1.8.5
 */

use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

/**
 * Muhiku Plug Widget for Elementor.
 *
 * @since 1.8.5
 */
class MHK_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve shortcode widget name.
	 *
	 * @since 1.8.5
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'muhiku-plug';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve shortcode widget title.
	 *
	 * @since 1.8.5
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Muhiku Plug', 'muhiku-plug' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve shortcode widget icon.
	 *
	 * @since 1.8.5
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'muhiku-icon';
	}


	/**
	 * Get widget categories.
	 *
	 * @since 1.8.5
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		if ( class_exists( 'MuhikuPlug_Style_Customizer' ) ) {
			return array(
				'muhiku-plug',
			);
		} else {
			return array(
				'basic',
			);
		}

	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 1.8.5
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'form', 'forms', 'muhiku-plug', 'contact form', 'muhiku', 'muhikuforms' );
	}

	/**
	 * Register controls.
	 *
	 * @since 1.8.5
	 */
	protected function register_controls() { // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore

		$this->start_controls_section(
			'section_content_layout',
			array(
				'label' => esc_html__( 'Form', 'muhiku-plug' ),
			)
		);
		$forms = $this->get_forms();
		$this->add_control(
			'muhiku_form',
			array(
				'label'   => esc_html__( 'Select Form', 'muhiku-plug' ),
				'type'    => Controls_Manager::SELECT,
				'options' => $forms,
			)
		);
		$this->end_controls_section();

		do_action( 'muhiku_form_elemntor_style', $this );

	}

	/**
	 * Retrieve the shortcode.
	 *
	 * @since 1.8.5
	 */
	private function get_shortcode() {

		$settings = $this->get_settings_for_display();
		if ( ! $settings['muhiku_form'] ) {
			return '<p>' . __( 'Please select a Muhiku Plug.', 'muhiku-plug' ) . '</p>';
		}

		$attributes = array(
			'id' => $settings['muhiku_form'],
		);

		$this->add_render_attribute( 'shortcode', $attributes );

		$shortcode   = array();
		$shortcode[] = sprintf( '[muhiku_form %s]', $this->get_render_attribute_string( 'shortcode' ) );

		return implode( '', $shortcode );
	}

	/**
	 * Render widget output.
	 *
	 * @since 1.8.5
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();
		?>
			<?php echo do_shortcode( $this->get_shortcode() ); ?>
		<?php
	}

	/**
	 * Retrieve the  available mhk forms.
	 *
	 * @since 1.8.5
	 */
	public function get_forms() {

		$muhiku_forms = array();

		if ( empty( $muhiku_forms ) ) {

			$mhk_forms = mhk()->form->get();
			if ( ! empty( $mhk_forms ) ) {
				foreach ( $mhk_forms as $mhk_form ) {
					$muhiku_forms[ $mhk_form->ID ] = $mhk_form->post_title;
				}
			} else {
				$muhiku_forms[0] = esc_html__( 'Yo have not created a form, Please Create a form first', 'muhiku-plug' );
			}

			return $muhiku_forms;
		}
	}
}

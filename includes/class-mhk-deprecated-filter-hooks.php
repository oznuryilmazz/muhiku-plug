<?php
/**
 * @package MuhikuPlug\Abstracts
 */

defined( 'ABSPATH' ) || exit;

class MHK_Deprecated_Filter_Hooks extends MHK_Deprecated_Hooks {

	/**
	 * @var array
	 */
	protected $deprecated_hooks = array(
		'muhiku_forms_fields'                        => 'muhiku_forms_load_fields',
		'muhiku_forms_show_media_button'             => 'mhk_display_media_button',
		'muhiku_forms_show_admin_bar_menus'          => 'muhiku_forms_show_admin_bar',
		'muhiku_forms_builder_fields_groups'         => 'muhiku_forms_builder_fields_buttons',
		'muhiku_forms_field_data'                    => 'mhk_field_data',
		'muhiku_forms_field_properties'              => 'mhk_field_properties',
		'muhiku_forms_field_properties_{field_type}' => 'mhk_field_properties_{field_type}',
		'muhiku_forms_field_submit'                  => 'mhk_field_submit',
		'muhiku_forms_field_required_label'          => 'mhk_field_required_label',
		'muhiku_forms_frontend_load'                 => 'mhk_frontend_load',
		'muhiku_forms_frontend_form_action'          => 'mhk_frontend_form_action',
		'muhiku_forms_process_smart_tags'            => 'mhk_process_smart_tags',
		'muhiku_forms_recaptcha_disabled'            => 'muhiku_forms_logged_in_user_recaptcha_disabled',
		'muhiku_forms_welcome_cap'                   => 'mhk_welcome_cap',
	);

	/**
	 * @var array
	 */
	protected $deprecated_version = array(
		'muhiku_forms_load_fields'                       => '1.2.0',
		'mhk_display_media_button'                        => '1.2.0',
		'muhiku_forms_show_admin_bar'                    => '1.2.0',
		'muhiku_forms_builder_fields_buttons'            => '1.2.0',
		'mhk_field_data'                                  => '1.3.0',
		'mhk_field_properties'                            => '1.3.0',
		'mhk_field_properties_{field_type}'               => '1.3.0',
		'mhk_field_submit'                                => '1.3.2',
		'mhk_field_required_label'                        => '1.3.2',
		'mhk_frontend_load'                               => '1.3.2',
		'mhk_frontend_form_action'                        => '1.3.2',
		'mhk_process_smart_tags'                          => '1.4.2',
		'muhiku_forms_logged_in_user_recaptcha_disabled' => '1.7.0.1',
		'mhk_welcome_cap'                                 => '1.7.5',
	);

	/**
	 * @param string $hook_name Hook name.
	 */
	public function hook_in( $hook_name ) {
		add_filter( $hook_name, array( $this, 'maybe_handle_deprecated_hook' ), -1000, 8 );
	}

	/**
	 * @param  string $new_hook          New hook name.
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @param  mixed  $return_value      Returned value.
	 * @return mixed
	 */
	public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value ) {
		if ( has_filter( $old_hook ) ) {
			$this->display_notice( $old_hook, $new_hook );
			$return_value = $this->trigger_hook( $old_hook, $new_callback_args );
		}
		return $return_value;
	}

	/**
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @return mixed
	 */
	protected function trigger_hook( $old_hook, $new_callback_args ) {
		return apply_filters_ref_array( $old_hook, $new_callback_args );
	}
}

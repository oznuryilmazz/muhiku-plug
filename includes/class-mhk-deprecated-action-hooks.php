<?php
/**
 * Deprecated action hooks
 *
 * @package MuhikuPlug\Abstracts
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles deprecation notices and triggering of legacy action hooks.
 */
class MHK_Deprecated_Action_Hooks extends MHK_Deprecated_Hooks {

	/**
	 * Array of deprecated hooks we need to handle. Format of 'new' => 'old'.
	 *
	 * @var array
	 */
	protected $deprecated_hooks = array(
		'muhiku_forms_builder_page_init'          => array(
			'muhiku_forms_page_init',
			'muhiku_forms_builder_init',
		),
		'admin_enqueue_scripts'                    => array(
			'muhiku_forms_page_init',
			'muhiku_forms_builder_scripts',
			'muhiku_forms_builder_enqueues_before',
		),
		'muhiku_forms_builder_tabs'               => 'muhiku_forms_builder_panel_buttons',
		'muhiku_forms_builder_output'             => 'muhiku_forms_builder_panels',
		'muhiku_forms_builder_fields_preview'     => 'muhiku_forms_builder_preview',
		'muhiku_forms_display_field_before'       => 'mhk_display_field_before',
		'muhiku_forms_display_field_after'        => 'mhk_display_field_after',
		'muhiku_forms_display_fields_before'      => 'mhk_display_fields_before',
		'muhiku_forms_display_fields_after'       => 'mhk_display_fields_after',
		'muhiku_forms_display_field_{field_type}' => 'mhk_display_field_{field_type}',
		'muhiku_forms_frontend_output_before'     => 'mhk_frontend_output_before',
		'muhiku_forms_frontend_output_success'    => 'mhk_frontend_output_success',
		'muhiku_forms_frontend_output'            => 'mhk_frontend_output',
		'muhiku_forms_frontend_output_after'      => 'mhk_frontend_output_after',
		'muhiku_forms_display_submit_before'      => 'mhk_display_submit_before',
		'muhiku_forms_display_submit_after'       => 'mhk_display_submit_after',
	);

	/**
	 * Array of versions on each hook has been deprecated.
	 *
	 * @var array
	 */
	protected $deprecated_version = array(
		'muhiku_forms_page_init'               => '1.2.0',
		'muhiku_forms_builder_init'            => '1.2.0',
		'muhiku_forms_builder_scripts'         => '1.2.0',
		'muhiku_forms_builder_enqueues_before' => '1.2.0',
		'muhiku_forms_builder_panel_buttons'   => '1.2.0',
		'muhiku_forms_builder_panels'          => '1.2.0',
		'muhiku_forms_builder_preview'         => '1.2.0',
		'mhk_display_field_before'              => '1.2.0',
		'mhk_display_field_after'               => '1.2.0',
		'mhk_display_fields_before'             => '1.3.0',
		'mhk_display_fields_after'              => '1.3.0',
		'mhk_frontend_output_before'            => '1.3.2',
		'mhk_frontend_output_success'           => '1.3.2',
		'mhk_frontend_output'                   => '1.3.2',
		'mhk_frontend_output_after'             => '1.3.2',
		'mhk_display_submit_before'             => '1.3.2',
		'mhk_display_submit_after'              => '1.3.2',
	);

	/**
	 * Hook into the new hook so we can handle deprecated hooks once fired.
	 *
	 * @param string $hook_name Hook name.
	 */
	public function hook_in( $hook_name ) {
		add_action( $hook_name, array( $this, 'maybe_handle_deprecated_hook' ), -1000, 8 );
	}

	/**
	 * If the old hook is in-use, trigger it.
	 *
	 * @param  string $new_hook          New hook name.
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @param  mixed  $return_value      Returned value.
	 * @return mixed
	 */
	public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value ) {
		if ( has_action( $old_hook ) ) {
			$this->display_notice( $old_hook, $new_hook );
			$return_value = $this->trigger_hook( $old_hook, $new_callback_args );
		}
		return $return_value;
	}

	/**
	 * Fire off a legacy hook with it's args.
	 *
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @return mixed
	 */
	protected function trigger_hook( $old_hook, $new_callback_args ) {
		do_action_ref_array( $old_hook, $new_callback_args );
	}
}

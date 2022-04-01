<?php
/**
 * @package MuhikuPlug\Abstracts
 * @since   1.2.0
 */

defined( 'ABSPATH' ) || exit;

abstract class MHK_Deprecated_Hooks {

	/**
	 * @var array
	 */
	protected $deprecated_hooks = array();

	/**
	 * @var array
	 */
	protected $deprecated_version = array();

	public function __construct() {
		$fields = mhk()->form_fields->get_form_field_types();

		foreach ( $this->deprecated_hooks as $new_hook => $old_hook ) {
			if ( is_string( $old_hook ) && false !== strpos( $new_hook, '{field_type}' ) ) {
				foreach ( $fields as $field ) {
					$new_dynamic_hooks = str_replace( '{field_type}', $field, $new_hook );
					$old_dynamic_hooks = str_replace( '{field_type}', $field, $old_hook );

					$this->deprecated_hooks[ $new_dynamic_hooks ]   = $old_dynamic_hooks;
					$this->deprecated_version[ $old_dynamic_hooks ] = $this->get_deprecated_version( $old_hook );
				}

				unset( $this->deprecated_hooks[ $new_hook ] );
				unset( $this->deprecated_version[ $old_hook ] );
			}
		}

		$new_hooks = array_keys( $this->deprecated_hooks );
		array_walk( $new_hooks, array( $this, 'hook_in' ) );
	}

	/**
	 * @param string $hook_name Hook name.
	 */
	abstract public function hook_in( $hook_name );

	/**
	 * @param  string $new_hook New hook name.
	 * @return array
	 */
	public function get_old_hooks( $new_hook ) {
		$old_hooks = isset( $this->deprecated_hooks[ $new_hook ] ) ? $this->deprecated_hooks[ $new_hook ] : array();
		$old_hooks = is_array( $old_hooks ) ? $old_hooks : array( $old_hooks );

		return $old_hooks;
	}
	public function maybe_handle_deprecated_hook() {
		$new_hook          = current_filter();
		$old_hooks         = $this->get_old_hooks( $new_hook );
		$new_callback_args = func_get_args();
		$return_value      = $new_callback_args[0];

		foreach ( $old_hooks as $old_hook ) {
			$return_value = $this->handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value );
		}

		return $return_value;
	}

	/**
	 *
	 * @param  string $new_hook          New hook name.
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @param  mixed  $return_value      Returned value.
	 * @return mixed
	 */
	abstract public function handle_deprecated_hook( $new_hook, $old_hook, $new_callback_args, $return_value );

	/**
	 * @param string $old_hook Old hook name.
	 * @return string
	 */
	protected function get_deprecated_version( $old_hook ) {
		return ! empty( $this->deprecated_version[ $old_hook ] ) ? $this->deprecated_version[ $old_hook ] : MHK_VERSION;
	}

	/**
	 * @param string $old_hook Old hook.
	 * @param string $new_hook New hook.
	 */
	protected function display_notice( $old_hook, $new_hook ) {
		mhk_deprecated_hook( esc_html( $old_hook ), esc_html( $this->get_deprecated_version( $old_hook ) ), esc_html( $new_hook ) );
	}

	/**
	 * @param  string $old_hook          Old hook name.
	 * @param  array  $new_callback_args New callback args.
	 * @return mixed
	 */
	abstract protected function trigger_hook( $old_hook, $new_callback_args );
}

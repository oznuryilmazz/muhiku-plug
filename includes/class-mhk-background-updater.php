<?php
/**
 * @package MuhikuPlug\Classes
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'MHK_Background_Process', false ) ) {
	include_once dirname( __FILE__ ) . '/abstracts/class-mhk-background-process.php';
}

class MHK_Background_Updater extends MHK_Background_Process {

	public function __construct() {
		$this->prefix = 'wp_' . get_current_blog_id();
		$this->action = 'mhk_updater';

		parent::__construct();
	}

	public function dispatch() {
		$dispatched = parent::dispatch();
		$logger     = mhk_get_logger();

		if ( is_wp_error( $dispatched ) ) {
			$logger->error(
				sprintf( 'Unable to dispatch MuhikuPlugupdater: %s', $dispatched->get_error_message() ),
				array( 'source' => 'mhk_db_updates' )
			);
		}
	}

	public function handle_cron_healthcheck() {
		if ( $this->is_process_running() ) {
			return;
		}

		if ( $this->is_queue_empty() ) {
			$this->clear_scheduled_event();
			return;
		}

		$this->handle();
	}
	protected function schedule_event() {
		if ( ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time() + 10, $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * @return boolean
	 */
	public function is_updating() {
		return false === $this->is_queue_empty();
	}

	/**
	 * @param  string $callback Update callback function.
	 * @return string|bool
	 */
	protected function task( $callback ) {
		mhk_maybe_define_constant( 'MHK_UPDATING', true );

		$logger = mhk_get_logger();

		include_once dirname( __FILE__ ) . '/mhk-update-functions.php';

		$result = false;

		if ( is_callable( $callback ) ) {
			$logger->info( sprintf( 'Running %s callback', $callback ), array( 'source' => 'mhk_db_updates' ) );
			$result = (bool) call_user_func( $callback );

			if ( $result ) {
				$logger->info( sprintf( '%s callback needs to run again', $callback ), array( 'source' => 'mhk_db_updates' ) );
			} else {
				$logger->info( sprintf( 'Finished running %s callback', $callback ), array( 'source' => 'mhk_db_updates' ) );
			}
		} else {
			$logger->notice( sprintf( 'Could not find %s callback', $callback ), array( 'source' => 'mhk_db_updates' ) );
		}

		return $result ? $callback : false;
	}

	protected function complete() {
		$logger = mhk_get_logger();
		$logger->info( 'Data update complete', array( 'source' => 'mhk_db_updates' ) );
		MHK_Install::update_db_version();
		parent::complete();
	}
}

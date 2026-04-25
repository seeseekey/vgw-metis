<?php

namespace WP_VGWORT;

/**
 * Handles WordPress cron registration, scheduling, and legacy migration.
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 */
class Cron {

	/**
	 * Cron hook for the daily pixel status check.
	 */
	const HOOK = 'vgw_metis_daily_pixel_check';

	/**
	 * WordPress core schedule used for the daily pixel status check.
	 */
	const SCHEDULE = 'daily';

	/**
	 * Custom schedule slug used by older plugin versions.
	 *
	 * WordPress cannot reschedule events with this slug unless the plugin
	 * registers it on every request. It is kept only to detect and replace legacy
	 * cron events with the built-in daily schedule.
	 */
	const LEGACY_SCHEDULE = 'everyday';

	/**
	 * Generic cron hook name used by older plugin versions.
	 *
	 * Existing events with this hook are cleared during migration because the
	 * current plugin uses the prefixed HOOK to avoid global hook collisions.
	 */
	const LEGACY_HOOK = 'do_cron_hook';

	/**
	 * Register the daily pixel check cron hook.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( self::HOOK, [ Services::class, 'check_all_pixels' ] );
		add_action( 'init', [ self::class, 'schedule' ] );
	}

	/**
	 * Schedule or migrate the daily pixel check cron event.
	 *
	 * Older plugin versions stored the event on the generic hook "do_cron_hook"
	 * and used the custom schedule slug "everyday". Both are replaced here with
	 * the prefixed hook and WordPress core's "daily" schedule.
	 *
	 * @return void
	 */
	public static function schedule(): void {
		$event = wp_get_scheduled_event( self::HOOK );

		if ( wp_next_scheduled( self::LEGACY_HOOK ) ) {
			wp_clear_scheduled_hook( self::LEGACY_HOOK );
		}

		if ( $event && self::LEGACY_SCHEDULE === $event->schedule ) {
			wp_clear_scheduled_hook( self::HOOK );
			$event = false;
		}

		if ( ! $event ) {
			wp_schedule_event( time(), self::SCHEDULE, self::HOOK );
		}
	}

	/**
	 * Clear current and legacy scheduled pixel check events.
	 *
	 * @return void
	 */
	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::HOOK );
		wp_clear_scheduled_hook( self::LEGACY_HOOK );
	}
}

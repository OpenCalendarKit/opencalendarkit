<?php
/**
 * Calendar event shortcode rendering.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the current day's calendar event.
 */
class OpenCalendarKit_Shortcode_CalendarEvent {
	/**
	 * Get the current datetime for a timezone.
	 *
	 * @param DateTimeZone $timezone Timezone to use.
	 * @return DateTime
	 */
	protected static function get_current_datetime( DateTimeZone $timezone ): DateTime {
		return new DateTime( 'now', $timezone );
	}

	/**
	 * Render the calendar event shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		return OpenCalendarKit_I18n::with_locale(
			function () use ( $atts ) {
				$atts = shortcode_atts(
					array(
						'date' => '',
					),
					$atts,
					OpenCalendarKit_Plugin::SHORTCODE_CALENDAR_EVENT
				);

				$timezone = new DateTimeZone( wp_timezone_string() );
				$date     = is_string( $atts['date'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $atts['date'] )
					? $atts['date']
					: static::get_current_datetime( $timezone )->format( 'Y-m-d' );

				$text = OpenCalendarKit_Admin_CalendarEvents::get_event_text( $date );
				if ( '' === $text ) {
					return '';
				}

				ob_start();
				?>
				<div class="bkit-calendar-event bkit-ui-callout bkit-ui-callout--calendar-event">
					<div class="bkit-calendar-event__inner">
						<div class="bkit-calendar-event__text"><?php echo esc_html( $text ); ?></div>
					</div>
				</div>
				<?php

				return (string) ob_get_clean();
			}
		);
	}
}

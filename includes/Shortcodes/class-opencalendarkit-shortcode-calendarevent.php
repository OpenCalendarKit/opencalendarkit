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
				$event = OpenCalendarKit_Admin_CalendarEvents::get_event_display_data(
					$date,
					OpenCalendarKit_Admin_Settings::get_time_format()
				);
				if ( ! is_array( $event ) || '' === $event['summary'] ) {
					return '';
				}

				if ( empty( $event['show_in_shortcode'] ) ) {
					return '';
				}

				ob_start();
				?>
				<div class="bkit-status-today bkit-calendar-event-callout">
					<div class="bkit-ui-callout bkit-ui-callout--calendar-event">
						<div class="bkit-ui-callout__inner bkit-calendar-event__inner">
							<span class="bkit-calendar-event__text">
								<?php echo esc_html( $event['title'] ); ?>
								<?php if ( '' !== $event['time_label'] ) : ?>
									<br />
									<span class="bkit-calendar-event__meta"><?php echo esc_html( $event['time_label'] ); ?></span>
								<?php endif; ?>
							</span>
						</div>
					</div>
				</div>
				<?php

				return (string) ob_get_clean();
			}
		);
	}
}

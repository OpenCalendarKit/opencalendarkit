<?php
/**
 * Status-today shortcode rendering.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the current opening status for today.
 */
class OpenCalendarKit_Shortcode_StatusToday {
	/**
	 * Get the WordPress timezone object.
	 *
	 * @return DateTimeZone
	 */
	protected static function get_wordpress_timezone(): DateTimeZone {
		return wp_timezone();
	}

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
	 * Build the opening-range datetimes for a day row.
	 *
	 * @param array<string, mixed> $row      Opening-hours row.
	 * @param DateTimeZone         $timezone Timezone to use.
	 * @param DateTime             $now      Current date context.
	 * @return array{0:?DateTime,1:?DateTime}
	 */
	protected static function get_time_range_for_row( array $row, DateTimeZone $timezone, DateTime $now ): array {
		$from = trim( (string) ( $row['from'] ?? '' ) );
		$to   = trim( (string) ( $row['to'] ?? '' ) );

		$start = $from ? DateTime::createFromFormat( '!H:i', $from, $timezone ) : null;
		$end   = ( stripos( $to, 'open' ) !== false ) ? null : ( $to ? DateTime::createFromFormat( '!H:i', $to, $timezone ) : null );

		if ( $start ) {
			$start->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'm' ), (int) $now->format( 'd' ) );
		}

		if ( $end ) {
			$end->setDate( (int) $now->format( 'Y' ), (int) $now->format( 'm' ), (int) $now->format( 'd' ) );
		}

		return array( $start, $end );
	}

	/**
	 * Render the status shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		return OpenCalendarKit_I18n::with_locale(
			function () use ( $atts ) {
				$atts = shortcode_atts(
					array(
						'enabled'          => '',
						'time_format_mode' => '',
					),
					$atts,
					OpenCalendarKit_Plugin::SHORTCODE_STATUS_TODAY
				);

				$is_enabled = OpenCalendarKit_Admin_Settings::resolve_bool(
					$atts['enabled'],
					OpenCalendarKit_Admin_Settings::is_enabled( 'show_status_today' )
				);

				if ( ! $is_enabled ) {
					return '';
				}

				$time_format    = OpenCalendarKit_Admin_Settings::get_time_format( $atts['time_format_mode'] );
				$timezone       = static::get_wordpress_timezone();
				$now            = static::get_current_datetime( $timezone );
				$day_of_week    = (int) $now->format( 'N' );
				$date           = $now->format( 'Y-m-d' );
				$closed_event   = OpenCalendarKit_Admin_ClosedDays::is_closed_on( $date );
				$open_override  = OpenCalendarKit_Admin_ClosedDays::is_open_exception_on( $date );
				$hours          = OpenCalendarKit_Admin_OpeningHours::get_hours();
				$row            = $hours[ $day_of_week ] ?? array(
					'closed' => 1,
					'from'   => '',
					'to'     => '',
				);
				$is_rule_closed = ! empty( $row['closed'] ) && ! $open_override;

				$label = __( 'Today closed', 'open-calendar-kit' );
				$class = 'closed';

				if ( ! $closed_event && ! $is_rule_closed ) {
					[ $start, $end ] = static::get_time_range_for_row( $row, $timezone, $now );

					if ( ! $start ) {
						$label = __( 'Today closed', 'open-calendar-kit' );
						$class = 'closed';
					} elseif ( $now < $start ) {
						/* translators: %s: opening time for today. */
						$label = sprintf( __( 'Opens today at %s', 'open-calendar-kit' ), $start->format( $time_format ) );
						$class = 'upcoming';
					} elseif ( ! $end || $now <= $end ) {
						if ( $end ) {
							/* translators: %s: closing time for today. */
							$label = sprintf( __( 'Open now until %s', 'open-calendar-kit' ), $end->format( $time_format ) );
						} else {
							$label = __( 'Open now', 'open-calendar-kit' );
						}
						$class = 'open';
					} else {
						$label = __( 'Closed now', 'open-calendar-kit' );
						$class = 'ended';
					}
				}

				ob_start();
				?>
				<div class="bkit-status-today" role="status" aria-live="polite">
					<div class="bkit-ui-callout bkit-ui-callout--status bkit-ui-callout--<?php echo esc_attr( $class ); ?>">
						<div class="bkit-ui-callout__inner">
							<span class="bkit-status-today__indicator" aria-hidden="true"></span>
							<span class="bkit-status-today__text"><?php echo esc_html( $label ); ?></span>
						</div>
					</div>
				</div>
				<?php

				return ob_get_clean();
			}
		);
	}
}

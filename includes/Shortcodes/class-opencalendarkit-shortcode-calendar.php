<?php
/**
 * Calendar shortcode rendering.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the monthly availability calendar.
 */
class OpenCalendarKit_Shortcode_Calendar {
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
	 * Build the translated month title.
	 *
	 * @param DateTimeInterface $date     Month date.
	 * @param DateTimeZone      $timezone Timezone to use.
	 * @return string
	 */
	private static function get_month_title( DateTimeInterface $date, DateTimeZone $timezone ) {
		if ( function_exists( 'wp_date' ) ) {
			return wp_date( 'F Y', $date->getTimestamp(), $timezone );
		}

		$local_date = new DateTime( '@' . $date->getTimestamp() );
		$local_date->setTimezone( $timezone );

		return $local_date->format( 'F Y' );
	}

	/**
	 * Return weekday labels respecting the configured week start.
	 *
	 * @param string $week_starts_on Week start value.
	 * @return array<int, string>
	 */
	private static function get_weekday_labels( $week_starts_on ) {
		global $wp_locale;

		$weekday_abbreviations = array_values( $wp_locale->weekday_abbrev );
		foreach ( $weekday_abbreviations as &$weekday ) {
			$weekday = preg_replace( '/\.$/', '', (string) $weekday );
		}
		unset( $weekday );

		if ( 'sunday' === $week_starts_on ) {
			return $weekday_abbreviations;
		}

		return array_merge( array_slice( $weekday_abbreviations, 1 ), array( $weekday_abbreviations[0] ) );
	}

	/**
	 * Normalize the requested month.
	 *
	 * @param mixed        $month    Requested month.
	 * @param DateTimeZone $timezone Timezone to use.
	 * @return DateTime
	 */
	private static function normalize_month( $month, DateTimeZone $timezone ): DateTime {
		if ( is_string( $month ) && preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
			$date = DateTime::createFromFormat( '!Y-m', $month, $timezone );
			if ( $date ) {
				return $date;
			}
		}

		$current = static::get_current_datetime( $timezone );
		$current->modify( 'first day of this month' );
		$current->setTime( 0, 0, 0 );

		return $current;
	}

	/**
	 * Render the calendar shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		return OpenCalendarKit_I18n::with_locale(
			function () use ( $atts ) {
				$atts = shortcode_atts(
					array(
						'month'          => '',
						'show_legend'    => '',
						'week_starts_on' => '',
						'max_width'      => '380px',
					),
					$atts,
					OpenCalendarKit_Plugin::SHORTCODE_CALENDAR
				);

				$timezone       = new DateTimeZone( wp_timezone_string() );
				$week_starts_on = OpenCalendarKit_Admin_Settings::normalize_week_starts_on(
					'' !== $atts['week_starts_on'] ? $atts['week_starts_on'] : OpenCalendarKit_Admin_Settings::get( 'week_starts_on' )
				);
				$show_legend    = OpenCalendarKit_Admin_Settings::resolve_bool(
					$atts['show_legend'],
					OpenCalendarKit_Admin_Settings::is_enabled( 'show_calendar_legend' )
				);

				$date              = self::normalize_month( $atts['month'], $timezone );
				$year              = (int) $date->format( 'Y' );
				$month             = (int) $date->format( 'n' );
					$days_in_month = (int) $date->format( 't' );

					$day_zero_to_n = static function ( int $day_zero ): int {
						return 0 === $day_zero ? 7 : $day_zero;
					};

				if ( function_exists( 'jddayofweek' ) && function_exists( 'cal_to_jd' ) ) {
					$first_day_zero = jddayofweek( cal_to_jd( CAL_GREGORIAN, $month, 1, $year ), 0 );
					$first_day_n    = $day_zero_to_n( $first_day_zero );
				} else {
					$first_day_n = (int) ( new DateTime( sprintf( '%04d-%02d-01', $year, $month ), $timezone ) )->format( 'N' );
				}
				$first_day_offset = 'sunday' === $week_starts_on ? ( $first_day_n % 7 ) : ( $first_day_n - 1 );

				$hours = OpenCalendarKit_Admin_OpeningHours::get_hours();
				$today = static::get_current_datetime( $timezone )->format( 'Y-m-d' );

				$get_hours_row = static function ( int $day_of_week_n ) use ( $hours ) {
					if ( isset( $hours[ $day_of_week_n ] ) && is_array( $hours[ $day_of_week_n ] ) ) {
						return $hours[ $day_of_week_n ];
					}

					$day_of_week_zero = ( $day_of_week_n + 6 ) % 7;
					if ( isset( $hours[ $day_of_week_zero ] ) && is_array( $hours[ $day_of_week_zero ] ) ) {
						return $hours[ $day_of_week_zero ];
					}

					return array( 'closed' => 0 );
				};

				$cells = array();
				for ( $day = 1; $day <= $days_in_month; $day++ ) {
					$cell_date = sprintf( '%04d-%02d-%02d', $year, $month, $day );

					if ( function_exists( 'jddayofweek' ) && function_exists( 'cal_to_jd' ) ) {
						$day_zero = jddayofweek( cal_to_jd( CAL_GREGORIAN, $month, $day, $year ), 0 );
						$day_n    = $day_zero_to_n( $day_zero );
					} else {
						$day_n = (int) ( new DateTime( $cell_date, $timezone ) )->format( 'N' );
					}

					$config          = $get_hours_row( $day_n );
					$closed_by_rule  = ! empty( $config['closed'] );
					$closed_by_event = OpenCalendarKit_Admin_ClosedDays::is_closed_on( $cell_date );
					$open_override   = OpenCalendarKit_Admin_ClosedDays::is_open_exception_on( $cell_date );
					$event_text      = OpenCalendarKit_Admin_CalendarEvents::get_event_text( $cell_date );
					$state           = ( $closed_by_event || ( $closed_by_rule && ! $open_override ) ) ? 'closed' : 'open';
					$past            = $cell_date < $today;

					$cells[] = array(
						'day'        => $day,
						'date'       => $cell_date,
						'state'      => $state,
						'past'       => $past,
						'event_text' => $event_text,
					);
				}

				$current_first = static::get_current_datetime( $timezone );
				$current_first->modify( 'first day of this month' );
				$current_first->setTime( 0, 0, 0 );
				$next         = ( clone $date )->modify( '+1 month' );
				$prev         = ( clone $date )->modify( '-1 month' );
				$prev_allowed = $prev >= $current_first;

				ob_start();
				?>
				<div class="bkit-calendar"
					data-openkit-calendar="1"
					data-month="<?php echo esc_attr( $date->format( 'Y-m' ) ); ?>"
					data-show-legend="<?php echo esc_attr( $show_legend ? '1' : '0' ); ?>"
					data-week-starts-on="<?php echo esc_attr( $week_starts_on ); ?>"
					data-max-width="<?php echo esc_attr( $atts['max_width'] ); ?>"
					style="max-width: <?php echo esc_attr( $atts['max_width'] ); ?>">
					<div class="bkit-cal-head">
						<a class="bkit-nav prev<?php echo $prev_allowed ? '' : ' disabled'; ?>"
							href="<?php echo esc_attr( '#openkit-month=' . $prev->format( 'Y-m' ) ); ?>"
							data-target-month="<?php echo esc_attr( $prev->format( 'Y-m' ) ); ?>"
							aria-label="<?php echo esc_attr__( 'Previous month', 'open-calendar-kit' ); ?>">‹</a>
						<span class="bkit-cal-title"><?php echo esc_html( self::get_month_title( $date, $timezone ) ); ?></span>
						<a class="bkit-nav next"
							href="<?php echo esc_attr( '#openkit-month=' . $next->format( 'Y-m' ) ); ?>"
							data-target-month="<?php echo esc_attr( $next->format( 'Y-m' ) ); ?>"
							aria-label="<?php echo esc_attr__( 'Next month', 'open-calendar-kit' ); ?>">›</a>
					</div>

					<table class="bkit-cal-table" data-bk-cal>
						<thead>
						<tr>
						<?php foreach ( self::get_weekday_labels( $week_starts_on ) as $weekday ) : ?>
							<th class="bkit-cell bkit-wd"><?php echo esc_html( $weekday ); ?></th>
						<?php endforeach; ?>
						</tr>
						</thead>
						<tbody>
						<?php
						$day_index   = 0;
						$total_cells = $first_day_offset + count( $cells );
						$weeks       = (int) ceil( $total_cells / 7 );
						for ( $week = 0; $week < $weeks; $week++ ) :
							echo '<tr>';
							for ( $column = 1; $column <= 7; $column++ ) :
								$cell_position = ( $week * 7 ) + $column;
								if ( $cell_position <= $first_day_offset || $day_index >= count( $cells ) ) {
									echo '<td class="bkit-cell bkit-empty"></td>';
									continue;
								}

								$cell         = $cells[ $day_index++ ];
								$has_event    = '' !== $cell['event_text'];
								$is_clickable = $has_event || ( ! $cell['past'] && 'closed' === $cell['state'] );
								$reason           = '';
								if ( 'closed' === $cell['state'] ) {
									$reason = OpenCalendarKit_Admin_ClosedDays::get_reason( $cell['date'] );
								}

								$classes = 'bkit-cell day ' . ( $cell['past'] ? 'past disabled' : $cell['state'] ) . ( $has_event ? ' has-event event' : '' ) . ( $is_clickable ? ' clickable' : '' );

								echo '<td class="bkit-td">';
								printf(
									'<button class="%s" data-date="%s"%s%s type="button" %s><span class="num">%d</span></button>',
									esc_attr( $classes ),
									esc_attr( $cell['date'] ),
									'' !== $reason ? ' data-reason="' . esc_attr( $reason ) . '"' : '',
									$has_event ? ' data-event-text="' . esc_attr( $cell['event_text'] ) . '"' : '',
									$cell['past'] ? 'aria-disabled="true"' : '',
									(int) $cell['day']
								);
								echo '</td>';
							endfor;
							echo '</tr>';
						endfor;
						?>
						</tbody>
					</table>

					<?php if ( $show_legend ) : ?>
						<div class="bkit-legend">
							<span class="legend open"><?php echo esc_html__( 'Open', 'open-calendar-kit' ); ?></span>
							<span class="legend closed"><?php echo esc_html__( 'Closed', 'open-calendar-kit' ); ?></span>
						</div>
					<?php endif; ?>

					<div class="bkit-modal" style="display:none;">
						<div class="bkit-modal-box bkit-modal-box--closed">
							<button class="bkit-close" type="button" aria-label="<?php echo esc_attr__( 'Close', 'open-calendar-kit' ); ?>">×</button>

							<div class="bkit-closed-info" style="display:none;">
								<div class="bkit-closed-title"><?php echo esc_html__( 'Closed', 'open-calendar-kit' ); ?></div>
								<div class="bkit-closed-date"></div>
								<div class="bkit-closed-reason"></div>

								<div class="bkit-modal-actions">
									<button type="button" class="button bkit-cancel"><?php echo esc_html__( 'Close', 'open-calendar-kit' ); ?></button>
								</div>
							</div>

							<div class="bkit-event-info" style="display:none;">
								<div class="bkit-event-title"><?php echo esc_html__( 'Event', 'open-calendar-kit' ); ?></div>
								<div class="bkit-event-date"></div>
								<div class="bkit-event-text"></div>

								<div class="bkit-modal-actions">
									<button type="button" class="button bkit-cancel"><?php echo esc_html__( 'Close', 'open-calendar-kit' ); ?></button>
								</div>
							</div>

							<div class="bkit-feedback" style="display:none;"></div>
						</div>
					</div>
				</div>
				<?php

				return ob_get_clean();
			}
		);
	}
}

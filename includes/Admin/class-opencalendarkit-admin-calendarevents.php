<?php
/**
 * Calendar event administration and lookup helpers.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages simple day-based calendar events.
 */
class OpenCalendarKit_Admin_CalendarEvents {
	private const NONCE_ACTION = 'openkit_save_calendar_events';
	private const TYPE_TEXT    = 'text';
	private const TYPE_TIME    = 'time';

	/**
	 * Handle event-list form submissions on the calendar page.
	 *
	 * @return void
	 */
	public static function handle_request() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( OpenCalendarKit_Plugin::PAGE_CALENDAR !== $page ) {
			return;
		}

		$nonce = isset( $_POST['openkit_calendar_events_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['openkit_calendar_events_nonce'] ) )
			: '';

		if ( '' === $nonce ) {
			return;
		}

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE ) ) {
			return;
		}

		$events = filter_input( INPUT_POST, 'openkit_calendar_events', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$events = is_array( $events ) ? wp_unslash( $events ) : array();

		update_option( OpenCalendarKit_Plugin::OPTION_CALENDAR_EVENTS, self::normalize_events( $events ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'                   => OpenCalendarKit_Plugin::PAGE_CALENDAR,
					'openkit_events_updated' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Return normalized, sorted event rows.
	 *
	 * @return array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}>
	 */
	public static function get_events(): array {
		$events = get_option( OpenCalendarKit_Plugin::OPTION_CALENDAR_EVENTS, array() );

		return self::normalize_events( $events );
	}

	/**
	 * Return the normalized event row for a date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return array{date:string,type:string,text:string,open_time:string,close_time:string}|null
	 */
	public static function get_event( $date ): ?array {
		$date = is_string( $date ) ? $date : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		foreach ( self::get_events() as $event ) {
			if ( $event['date'] === $date ) {
				return $event;
			}
		}

		return null;
	}

	/**
	 * Return the event text for a given date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return string
	 */
	public static function get_event_text( $date ): string {
		$event = self::get_event( $date );
		if ( ! is_array( $event ) ) {
			return '';
		}

		if ( self::TYPE_TIME === $event['type'] ) {
			$title = self::get_event_title( $event );
			return '' !== $title ? $title : __( 'Special opening hours', 'open-calendar-kit' );
		}

		return $event['text'];
	}

	/**
	 * Return display-oriented event data for a given date.
	 *
	 * @param string $date        Date in Y-m-d format.
	 * @param string $time_format Time format string.
	 * @return array{date:string,type:string,title:string,text:string,time_label:string,summary:string}|null
	 */
	public static function get_event_display_data( string $date, string $time_format ): ?array {
		$event = self::get_event( $date );
		if ( ! is_array( $event ) ) {
			return null;
		}

		$title      = self::get_event_title( $event );
		$time_label = self::get_time_range_label( $event, $time_format );
		$summary    = self::get_event_summary( $event, $time_format );

		return array(
			'date'       => $event['date'],
			'type'       => $event['type'],
			'title'      => $title,
			'text'       => $event['text'],
			'time_label' => $time_label,
			'summary'    => $summary,
		);
	}

	/**
	 * Render the calendar-event management section.
	 *
	 * @return string
	 */
	public static function render_admin_section(): string {
		$events         = self::get_events();
		$events_updated = filter_input( INPUT_GET, 'openkit_events_updated', FILTER_SANITIZE_SPECIAL_CHARS );
		if ( empty( $events ) ) {
			$events[] = array(
				'date'       => '',
				'type'       => self::TYPE_TEXT,
				'text'       => '',
				'open_time'  => '',
				'close_time' => '',
			);
		}

		ob_start();
		?>
		<div class="openkit-calendar-events">
			<?php if ( is_string( $events_updated ) && '' !== $events_updated ) : ?>
				<div class="updated"><p><?php esc_html_e( 'Calendar events saved.', 'open-calendar-kit' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Calendar Events', 'open-calendar-kit' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Manage highlighted event dates for the frontend calendar. Each day can store either a text event or special opening hours. The shortcode [openkit_calendar_event] renders the event output for the current day or for a requested date. Only one event is stored per day; if a date is entered more than once, the last row wins.', 'open-calendar-kit' ); ?>
			</p>

			<form method="post" class="openkit-calendar-events__form">
				<?php wp_nonce_field( self::NONCE_ACTION, 'openkit_calendar_events_nonce' ); ?>

				<div class="openkit-calendar-events__header">
					<span><?php esc_html_e( 'Date', 'open-calendar-kit' ); ?></span>
					<span><?php esc_html_e( 'Text', 'open-calendar-kit' ); ?></span>
					<span><?php esc_html_e( 'Type', 'open-calendar-kit' ); ?></span>
					<span><?php esc_html_e( 'Opening time', 'open-calendar-kit' ); ?></span>
					<span><?php esc_html_e( 'Closing time', 'open-calendar-kit' ); ?></span>
					<span><?php esc_html_e( 'Delete', 'open-calendar-kit' ); ?></span>
				</div>

				<div class="openkit-calendar-events__rows" data-openkit-calendar-event-rows="1">
					<?php foreach ( $events as $index => $event ) : ?>
						<?php echo self::render_event_row( (int) $index, $event['date'], $event['text'], $event['type'], $event['open_time'], $event['close_time'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>

				<div class="openkit-calendar-events__template" data-openkit-calendar-event-template="1" style="display:none;">
					<?php echo self::render_event_row( '__INDEX__', '', '', self::TYPE_TEXT, '', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="openkit-calendar-events__actions">
					<button type="button" class="button" data-openkit-add-calendar-event="1"><?php esc_html_e( '+ Add event', 'open-calendar-kit' ); ?></button>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save calendar events', 'open-calendar-kit' ); ?></button>
				</div>
			</form>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Normalize raw event rows and sort them by date.
	 *
	 * @param mixed $events Raw event rows.
	 * @return array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}>
	 */
	private static function normalize_events( $events ): array {
		if ( ! is_array( $events ) ) {
			return array();
		}

		$normalized_by_date = array();

		foreach ( $events as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			$normalized = self::normalize_event_row( $event );
			if ( ! is_array( $normalized ) ) {
				continue;
			}

			$normalized_by_date[ $normalized['date'] ] = $normalized;
		}

		$normalized = array_values( $normalized_by_date );

		usort(
			$normalized,
			static function ( array $left, array $right ): int {
				if ( $left['date'] === $right['date'] ) {
					return strcmp( $left['type'], $right['type'] );
				}

				return strcmp( $left['date'], $right['date'] );
			}
		);

		return array_values( $normalized );
	}

	/**
	 * Render one event row for the admin form.
	 *
	 * @param int|string $index      Row index.
	 * @param string     $date       Event date.
	 * @param string     $text       Event text.
	 * @param string     $type       Event type.
	 * @param string     $open_time  Opening time override.
	 * @param string     $close_time Closing time override.
	 * @return string
	 */
	private static function render_event_row( $index, string $date, string $text, string $type, string $open_time, string $close_time ): string {
		$type = self::normalize_type( $type );

		ob_start();
		?>
		<div class="openkit-calendar-events__row<?php echo self::TYPE_TIME === $type ? ' is-time-event' : ''; ?>" data-openkit-calendar-event-row="1">
			<input
				type="date"
				name="openkit_calendar_events[<?php echo esc_attr( (string) $index ); ?>][date]"
				value="<?php echo esc_attr( $date ); ?>"
				class="openkit-calendar-events__date"
			/>
			<input
				type="text"
				name="openkit_calendar_events[<?php echo esc_attr( (string) $index ); ?>][text]"
				value="<?php echo esc_attr( $text ); ?>"
				class="regular-text openkit-calendar-events__text"
				placeholder="<?php echo esc_attr__( 'Event text', 'open-calendar-kit' ); ?>"
			/>
			<select
				name="openkit_calendar_events[<?php echo esc_attr( (string) $index ); ?>][type]"
				class="openkit-calendar-events__type"
				data-openkit-event-type="1"
			>
				<option value="<?php echo esc_attr( self::TYPE_TEXT ); ?>"<?php selected( $type, self::TYPE_TEXT ); ?>><?php esc_html_e( 'Text', 'open-calendar-kit' ); ?></option>
				<option value="<?php echo esc_attr( self::TYPE_TIME ); ?>"<?php selected( $type, self::TYPE_TIME ); ?>><?php esc_html_e( 'Time', 'open-calendar-kit' ); ?></option>
			</select>
			<input
				type="time"
				name="openkit_calendar_events[<?php echo esc_attr( (string) $index ); ?>][open_time]"
				value="<?php echo esc_attr( $open_time ); ?>"
				class="openkit-calendar-events__time openkit-calendar-events__time--open"
				data-openkit-event-open-time="1"
			/>
			<input
				type="time"
				name="openkit_calendar_events[<?php echo esc_attr( (string) $index ); ?>][close_time]"
				value="<?php echo esc_attr( $close_time ); ?>"
				class="openkit-calendar-events__time openkit-calendar-events__time--close"
				data-openkit-event-close-time="1"
			/>
			<button type="button" class="button-link-delete openkit-calendar-events__remove" data-openkit-remove-calendar-event="1" aria-label="<?php echo esc_attr__( 'Delete event row', 'open-calendar-kit' ); ?>">×</button>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Normalize a raw event row.
	 *
	 * @param array<string, mixed> $event Raw event row.
	 * @return array{date:string,type:string,text:string,open_time:string,close_time:string}|null
	 */
	private static function normalize_event_row( array $event ): ?array {
		$date       = isset( $event['date'] ) ? sanitize_text_field( (string) $event['date'] ) : '';
		$type       = self::normalize_type( $event['type'] ?? self::TYPE_TEXT );
		$text       = isset( $event['text'] ) ? sanitize_text_field( (string) $event['text'] ) : '';
		$open_time  = self::normalize_time_value( $event['open_time'] ?? '' );
		$close_time = self::normalize_time_value( $event['close_time'] ?? '' );

		if ( '' === $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		if ( self::TYPE_TIME === $type ) {
			if ( '' === $open_time ) {
				return null;
			}
		} elseif ( '' === $text ) {
			return null;
		}

		return array(
			'date'       => $date,
			'type'       => $type,
			'text'       => $text,
			'open_time'  => $open_time,
			'close_time' => $close_time,
		);
	}

	/**
	 * Normalize the event type value.
	 *
	 * @param mixed $type Raw type value.
	 * @return string
	 */
	private static function normalize_type( $type ): string {
		return self::TYPE_TIME === sanitize_key( (string) $type ) ? self::TYPE_TIME : self::TYPE_TEXT;
	}

	/**
	 * Normalize a stored time value to H:i.
	 *
	 * @param mixed $time Raw time value.
	 * @return string
	 */
	private static function normalize_time_value( $time ): string {
		$time = sanitize_text_field( (string) $time );
		if ( preg_match( '/^\d{2}:\d{2}(?::\d{2})?$/', $time ) ) {
			return substr( $time, 0, 5 );
		}

		return '';
	}

	/**
	 * Return a human-facing title for an event.
	 *
	 * @param array{date:string,type:string,text:string,open_time:string,close_time:string} $event Event row.
	 * @return string
	 */
	public static function get_event_title( array $event ): string {
		if ( self::TYPE_TIME === ( $event['type'] ?? self::TYPE_TEXT ) ) {
			$text = trim( (string) ( $event['text'] ?? '' ) );
			return '' !== $text ? $text : __( 'Special opening hours', 'open-calendar-kit' );
		}

		return (string) ( $event['text'] ?? '' );
	}

	/**
	 * Return the formatted time range for a time-event.
	 *
	 * @param array{date:string,type:string,text:string,open_time:string,close_time:string} $event       Event row.
	 * @param string                                                                  $time_format Time format string.
	 * @return string
	 */
	public static function get_time_range_label( array $event, string $time_format ): string {
		if ( self::TYPE_TIME !== ( $event['type'] ?? self::TYPE_TEXT ) ) {
			return '';
		}

		$open_time  = self::format_time_value( (string) ( $event['open_time'] ?? '' ), $time_format );
		$close_time = self::format_time_value( (string) ( $event['close_time'] ?? '' ), $time_format );

		if ( '' === $open_time ) {
			return '';
		}

		if ( '' !== $close_time ) {
			/* translators: 1: opening time, 2: closing time. */
			return sprintf( __( '%1$s to %2$s', 'open-calendar-kit' ), $open_time, $close_time );
		}

		/* translators: %s: opening time. */
		return sprintf( __( 'from %s', 'open-calendar-kit' ), $open_time );
	}

	/**
	 * Return a summary text for an event.
	 *
	 * @param array{date:string,type:string,text:string,open_time:string,close_time:string} $event       Event row.
	 * @param string                                                                  $time_format Time format string.
	 * @return string
	 */
	public static function get_event_summary( array $event, string $time_format ): string {
		if ( self::TYPE_TIME !== ( $event['type'] ?? self::TYPE_TEXT ) ) {
			return (string) ( $event['text'] ?? '' );
		}

		$title      = self::get_event_title( $event );
		$time_label = self::get_time_range_label( $event, $time_format );

		if ( '' !== trim( (string) ( $event['text'] ?? '' ) ) && '' !== $time_label ) {
			/* translators: 1: event title, 2: time range. */
			return sprintf( __( '%1$s (%2$s)', 'open-calendar-kit' ), $title, $time_label );
		}

		if ( '' !== $time_label ) {
			/* translators: %s: time range for a special opening day. */
			return sprintf( __( 'Special opening hours: %s', 'open-calendar-kit' ), $time_label );
		}

		return $title;
	}

	/**
	 * Format a stored H:i value using the active time format.
	 *
	 * @param string $value       Stored H:i value.
	 * @param string $time_format Time format string.
	 * @return string
	 */
	private static function format_time_value( string $value, string $time_format ): string {
		if ( '' === $value ) {
			return '';
		}

		$date = DateTime::createFromFormat( '!H:i', $value, wp_timezone() );
		if ( ! $date ) {
			return $value;
		}

		return $date->format( $time_format );
	}
}

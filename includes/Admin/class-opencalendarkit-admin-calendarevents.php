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
	private const FORM_STATE_OPTION = 'openkit_calendar_events_form_state';
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

		$events = isset( $_POST['openkit_calendar_events'] ) ? wp_unslash( $_POST['openkit_calendar_events'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized and validated in build_submission_result().
		$events = is_array( $events ) ? $events : array();
		$result = self::build_submission_result( $events );

		if ( ! empty( $result['errors'] ) ) {
			self::store_form_state( $result['rows'], $result['errors'] );

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'                 => OpenCalendarKit_Plugin::PAGE_CALENDAR,
						'openkit_events_error' => '1',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}

		self::clear_form_state();
		update_option( OpenCalendarKit_Plugin::OPTION_CALENDAR_EVENTS, $result['normalized'] );

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
		$form_state     = self::consume_form_state();
		$events         = ! empty( $form_state['rows'] ) ? $form_state['rows'] : self::get_events();
		$form_errors    = ! empty( $form_state['errors'] ) ? $form_state['errors'] : array();
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
			<?php if ( ! empty( $form_errors ) ) : ?>
				<div class="notice notice-error">
					<?php foreach ( $form_errors as $error_message ) : ?>
						<p><?php echo esc_html( $error_message ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Calendar Events', 'open-calendar-kit' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Manage highlighted event dates for the frontend calendar. Each day can store either a text event or special opening hours. Time events require an opening time; the closing time is optional. The shortcode [openkit_calendar_event] renders the event output for the current day or for a requested date. Only one event is stored per day; if a date is entered more than once, the last row wins.', 'open-calendar-kit' ); ?>
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
		$result = self::build_submission_result( is_array( $events ) ? $events : array() );

		return $result['normalized'];
	}

	/**
	 * Validate raw submitted rows and return sanitized results.
	 *
	 * @param array<int|string, array<string, mixed>> $events Raw event rows.
	 * @return array{rows:array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}>, normalized:array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}>, errors:array<int, string>}
	 */
	public static function validate_submission_rows( array $events ): array {
		return self::build_submission_result( $events );
	}

	/**
	 * Validate raw submitted rows and return sanitized results.
	 *
	 * @param array<int|string, mixed> $events Raw event rows.
	 * @return array{rows:array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}>, normalized:array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}>, errors:array<int, string>}
	 */
	private static function build_submission_result( array $events ): array {
		if ( ! is_array( $events ) ) {
			return array(
				'rows'       => array(),
				'normalized' => array(),
				'errors'     => array(),
			);
		}

		$rows               = array();
		$normalized_by_date = array();
		$errors             = array();

		foreach ( $events as $event ) {
			if ( ! is_array( $event ) ) {
				continue;
			}

			$row = self::prepare_event_row( $event );
			if ( self::is_empty_row( $row ) ) {
				continue;
			}

			$rows[] = $row;

			$error = self::get_row_validation_error( $row );
			if ( '' !== $error ) {
				$errors[] = $error;
				continue;
			}

			$normalized_by_date[ $row['date'] ] = $row;
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

		return array(
			'rows'       => array_values( $rows ),
			'normalized' => array_values( $normalized ),
			'errors'     => array_values( array_unique( $errors ) ),
		);
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
	private static function prepare_event_row( array $event ): array {
		$date       = isset( $event['date'] ) ? sanitize_text_field( (string) $event['date'] ) : '';
		$type       = self::normalize_type( $event['type'] ?? self::TYPE_TEXT );
		$text       = isset( $event['text'] ) ? sanitize_text_field( (string) $event['text'] ) : '';
		$open_time  = self::normalize_time_value( $event['open_time'] ?? '' );
		$close_time = self::normalize_time_value( $event['close_time'] ?? '' );

		return array(
			'date'       => $date,
			'type'       => $type,
			'text'       => $text,
			'open_time'  => $open_time,
			'close_time' => $close_time,
		);
	}

	/**
	 * Check whether a sanitized row is completely empty.
	 *
	 * @param array{date:string,type:string,text:string,open_time:string,close_time:string} $row Sanitized row.
	 * @return bool
	 */
	private static function is_empty_row( array $row ): bool {
		return '' === $row['date'] && '' === $row['text'] && '' === $row['open_time'] && '' === $row['close_time'];
	}

	/**
	 * Return a validation message for a row, or an empty string when valid.
	 *
	 * @param array{date:string,type:string,text:string,open_time:string,close_time:string} $row Sanitized row.
	 * @return string
	 */
	private static function get_row_validation_error( array $row ): string {
		if ( '' === $row['date'] ) {
			return __( 'Each calendar event needs a date.', 'open-calendar-kit' );
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $row['date'] ) ) {
			return __( 'Each calendar event needs a valid date.', 'open-calendar-kit' );
		}

		if ( self::TYPE_TIME === $row['type'] ) {
			if ( '' === $row['open_time'] ) {
				return __( 'Time events need an opening time. The closing time is optional.', 'open-calendar-kit' );
			}

			return '';
		}

		if ( '' === $row['text'] ) {
			return __( 'Text events need a text.', 'open-calendar-kit' );
		}

		return '';
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

	/**
	 * Persist submitted form state for the next admin-page load.
	 *
	 * @param array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}> $rows   Submitted rows.
	 * @param array<int, string>                                                                          $errors Error messages.
	 * @return void
	 */
	private static function store_form_state( array $rows, array $errors ): void {
		update_option(
			self::FORM_STATE_OPTION,
			array(
				'rows'   => array_values( $rows ),
				'errors' => array_values( array_unique( $errors ) ),
			)
		);
	}

	/**
	 * Fetch and clear submitted form state from the previous request.
	 *
	 * @return array{rows:array<int, array{date:string,type:string,text:string,open_time:string,close_time:string}>, errors:array<int, string>}
	 */
	private static function consume_form_state(): array {
		$state = get_option( self::FORM_STATE_OPTION, array() );
		update_option( self::FORM_STATE_OPTION, array() );

		return array(
			'rows'   => isset( $state['rows'] ) && is_array( $state['rows'] ) ? $state['rows'] : array(),
			'errors' => isset( $state['errors'] ) && is_array( $state['errors'] ) ? $state['errors'] : array(),
		);
	}

	/**
	 * Clear any stored form state.
	 *
	 * @return void
	 */
	private static function clear_form_state(): void {
		update_option( self::FORM_STATE_OPTION, array() );
	}
}

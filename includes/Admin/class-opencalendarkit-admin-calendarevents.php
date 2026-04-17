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

		$events = isset( $_POST['openkit_calendar_events'] ) ? wp_unslash( $_POST['openkit_calendar_events'] ) : array();

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
	 * @return array<int, array{date:string,text:string}>
	 */
	public static function get_events(): array {
		$events = get_option( OpenCalendarKit_Plugin::OPTION_CALENDAR_EVENTS, array() );

		return self::normalize_events( $events );
	}

	/**
	 * Return the event text for a given date.
	 *
	 * @param string $date Date in Y-m-d format.
	 * @return string
	 */
	public static function get_event_text( $date ): string {
		$date = is_string( $date ) ? $date : '';
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return '';
		}

		foreach ( self::get_events() as $event ) {
			if ( $event['date'] === $date ) {
				return $event['text'];
			}
		}

		return '';
	}

	/**
	 * Render the calendar-event management section.
	 *
	 * @return string
	 */
	public static function render_admin_section(): string {
		$events = self::get_events();
		if ( empty( $events ) ) {
			$events[] = array(
				'date' => '',
				'text' => '',
			);
		}

		ob_start();
		?>
		<div class="openkit-calendar-events">
			<?php if ( isset( $_GET['openkit_events_updated'] ) ) : ?>
				<div class="updated"><p><?php esc_html_e( 'Calendar events saved.', 'open-calendar-kit' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Calendar Events', 'open-calendar-kit' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Manage highlighted event dates for the frontend calendar. The shortcode [openkit_calendar_event] renders the event text for the current day or for a requested date. Only one event is stored per day; if a date is entered more than once, the last row wins.', 'open-calendar-kit' ); ?>
			</p>

			<form method="post" class="openkit-calendar-events__form">
				<?php wp_nonce_field( self::NONCE_ACTION, 'openkit_calendar_events_nonce' ); ?>

				<div class="openkit-calendar-events__header">
					<span><?php esc_html_e( 'Date', 'open-calendar-kit' ); ?></span>
					<span><?php esc_html_e( 'Text', 'open-calendar-kit' ); ?></span>
					<span><?php esc_html_e( 'Delete', 'open-calendar-kit' ); ?></span>
				</div>

				<div class="openkit-calendar-events__rows" data-openkit-calendar-event-rows="1">
					<?php foreach ( $events as $index => $event ) : ?>
						<?php echo self::render_event_row( (int) $index, $event['date'], $event['text'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>

				<div class="openkit-calendar-events__template" data-openkit-calendar-event-template="1" style="display:none;">
					<?php echo self::render_event_row( '__INDEX__', '', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
	 * @return array<int, array{date:string,text:string}>
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

			$date = isset( $event['date'] ) ? sanitize_text_field( (string) $event['date'] ) : '';
			$text = isset( $event['text'] ) ? sanitize_text_field( (string) $event['text'] ) : '';

			if ( '' === $date || '' === $text ) {
				continue;
			}

			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
				continue;
			}

			$normalized_by_date[ $date ] = array(
				'date' => $date,
				'text' => $text,
			);
		}

		$normalized = array_values( $normalized_by_date );

		usort(
			$normalized,
			static function ( array $left, array $right ): int {
				if ( $left['date'] === $right['date'] ) {
					return strcmp( $left['text'], $right['text'] );
				}

				return strcmp( $left['date'], $right['date'] );
			}
		);

		return array_values( $normalized );
	}

	/**
	 * Render one event row for the admin form.
	 *
	 * @param int|string $index Row index.
	 * @param string     $date  Event date.
	 * @param string     $text  Event text.
	 * @return string
	 */
	private static function render_event_row( $index, string $date, string $text ): string {
		ob_start();
		?>
		<div class="openkit-calendar-events__row" data-openkit-calendar-event-row="1">
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
			<button type="button" class="button-link-delete openkit-calendar-events__remove" data-openkit-remove-calendar-event="1" aria-label="<?php echo esc_attr__( 'Delete event row', 'open-calendar-kit' ); ?>">×</button>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

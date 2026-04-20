<?php
/**
 * Opening hours administration.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles opening-hours admin screens and option access.
 */
class OpenCalendarKit_Admin_OpeningHours {
	/**
	 * Register admin menu entries for opening hours.
	 *
	 * @return void
	 */
	public static function register_menu() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				add_menu_page(
					__( 'OpenCalendarKit', 'open-calendar-kit' ),
					__( 'OpenCalendarKit', 'open-calendar-kit' ),
					OpenCalendarKit_Plugin::CAP_MANAGE,
					OpenCalendarKit_Plugin::MENU_SLUG,
					array( __CLASS__, 'render_opening_hours_page' ),
					'dashicons-calendar',
					26
				);

				add_submenu_page(
					OpenCalendarKit_Plugin::MENU_SLUG,
					__( 'Opening Hours', 'open-calendar-kit' ),
					__( 'Opening Hours', 'open-calendar-kit' ),
					OpenCalendarKit_Plugin::CAP_MANAGE,
					OpenCalendarKit_Plugin::MENU_SLUG,
					array( __CLASS__, 'render_opening_hours_page' )
				);
			}
		);
	}

	/**
	 * Return an empty weekly opening-hours structure.
	 *
	 * @return array<int, array<string, int|string>>
	 */
	public static function default_hours() {
		return array(
			1 => array(
				'closed' => 0,
				'from'   => '',
				'to'     => '',
			),
			2 => array(
				'closed' => 0,
				'from'   => '',
				'to'     => '',
			),
			3 => array(
				'closed' => 0,
				'from'   => '',
				'to'     => '',
			),
			4 => array(
				'closed' => 0,
				'from'   => '',
				'to'     => '',
			),
			5 => array(
				'closed' => 0,
				'from'   => '',
				'to'     => '',
			),
			6 => array(
				'closed' => 0,
				'from'   => '',
				'to'     => '',
			),
			7 => array(
				'closed' => 0,
				'from'   => '',
				'to'     => '',
			),
		);
	}

	/**
	 * Determine whether a row has at least one configured time value.
	 *
	 * @param array<string, mixed> $row Opening-hours row.
	 * @return bool
	 */
	public static function has_configured_hours( array $row ): bool {
		return '' !== trim( (string) ( $row['from'] ?? '' ) ) || '' !== trim( (string) ( $row['to'] ?? '' ) );
	}

	/**
	 * Render and process the opening-hours admin page.
	 *
	 * @return void
	 */
	public static function render_opening_hours_page() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				$nonce = isset( $_POST['openkit_opening_hours_nonce'] )
					? sanitize_text_field( wp_unslash( $_POST['openkit_opening_hours_nonce'] ) )
					: '';

				if (
					$nonce &&
					wp_verify_nonce( $nonce, OpenCalendarKit_Plugin::NONCE_OPENING_HOURS ) &&
					current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE )
				) {
					$hours = array();
					for ( $day_index = 1; $day_index <= 7; $day_index++ ) {
						$hours[ $day_index ] = array(
							'closed' => isset( $_POST[ "openkit_day{$day_index}_closed" ] ) ? 1 : 0,
							'from'   => isset( $_POST[ "openkit_day{$day_index}_from" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "openkit_day{$day_index}_from" ] ) ) : '',
							'to'     => isset( $_POST[ "openkit_day{$day_index}_to" ] ) ? sanitize_text_field( wp_unslash( $_POST[ "openkit_day{$day_index}_to" ] ) ) : '',
						);
					}

					$note = isset( $_POST['openkit_opening_hours_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['openkit_opening_hours_note'] ) ) : '';

					update_option( OpenCalendarKit_Plugin::OPTION_OPENING_HOURS, $hours );
					update_option( OpenCalendarKit_Plugin::OPTION_OPENING_HOURS_NOTE, $note );

					echo '<div class="updated"><p>' . esc_html__( 'Saved.', 'open-calendar-kit' ) . '</p></div>';
				}

				$hours = self::get_hours();
				$note  = self::get_note();

				$days = array(
					1 => __( 'Monday', 'open-calendar-kit' ),
					2 => __( 'Tuesday', 'open-calendar-kit' ),
					3 => __( 'Wednesday', 'open-calendar-kit' ),
					4 => __( 'Thursday', 'open-calendar-kit' ),
					5 => __( 'Friday', 'open-calendar-kit' ),
					6 => __( 'Saturday', 'open-calendar-kit' ),
					7 => __( 'Sunday', 'open-calendar-kit' ),
				);
				?>
				<div class="wrap">
					<h1><?php echo esc_html__( 'Opening Hours', 'open-calendar-kit' ); ?></h1>

					<form method="post">
						<?php wp_nonce_field( OpenCalendarKit_Plugin::NONCE_OPENING_HOURS, 'openkit_opening_hours_nonce' ); ?>

						<table class="form-table bk-table-hours">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Day', 'open-calendar-kit' ); ?></th>
									<th><?php esc_html_e( 'Closed', 'open-calendar-kit' ); ?></th>
									<th><?php esc_html_e( 'From', 'open-calendar-kit' ); ?></th>
									<th><?php esc_html_e( 'To', 'open-calendar-kit' ); ?></th>
								</tr>
							</thead>
							<tbody>
							<?php foreach ( $days as $index => $label ) : ?>
								<?php
								$row = $hours[ $index ] ?? array(
									'closed' => 0,
									'from'   => '',
									'to'     => '',
								);
								?>
								<tr>
									<th scope="row"><?php echo esc_html( $label ); ?></th>
									<td>
										<label>
											<input type="checkbox" name="openkit_day<?php echo esc_attr( (string) $index ); ?>_closed" <?php checked( 1, (int) $row['closed'] ); ?> />
											<?php esc_html_e( 'Closed', 'open-calendar-kit' ); ?>
										</label>
									</td>
									<td>
										<input type="text" name="openkit_day<?php echo esc_attr( (string) $index ); ?>_from" value="<?php echo esc_attr( $row['from'] ); ?>" class="regular-text" />
									</td>
									<td>
										<input type="text" name="openkit_day<?php echo esc_attr( (string) $index ); ?>_to" value="<?php echo esc_attr( $row['to'] ); ?>" class="regular-text" />
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>

						<table class="form-table">
							<tr>
								<th scope="row">
									<label for="openkit_opening_hours_note"><?php esc_html_e( 'Note below opening hours', 'open-calendar-kit' ); ?></label>
								</th>
								<td>
									<textarea
										name="openkit_opening_hours_note"
										id="openkit_opening_hours_note"
										rows="3"
										class="large-text"
										placeholder="<?php echo esc_attr__( 'Optional note for visitors', 'open-calendar-kit' ); ?>"
									><?php echo esc_textarea( $note ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'This text is shown below the opening hours on the frontend.', 'open-calendar-kit' ); ?>
									</p>
								</td>
							</tr>
						</table>

						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'open-calendar-kit' ); ?></button>
							<a href="<?php echo esc_url( admin_url() ); ?>" class="button"><?php esc_html_e( 'Cancel', 'open-calendar-kit' ); ?></a>
						</p>
					</form>

					<p><?php esc_html_e( 'Use shortcodes: [openkit_opening_hours], [openkit_status_today], [openkit_calendar]', 'open-calendar-kit' ); ?></p>
				</div>
				<?php
			}
		);
	}

	/**
	 * Fetch normalized opening hours from the database.
	 *
	 * @return array<int, array<string, int|string>>
	 */
	public static function get_hours() {
		$hours = get_option( OpenCalendarKit_Plugin::OPTION_OPENING_HOURS, null );
		if ( ! is_array( $hours ) ) {
			$hours = get_option( OpenCalendarKit_Plugin::LEGACY_OPTION_OPENING_HOURS, null );
		}

		if ( ! is_array( $hours ) ) {
			return self::default_hours();
		}

		if ( isset( $hours[1], $hours[7] ) && ! isset( $hours[0] ) ) {
			$normalized = self::default_hours();
			for ( $index = 1; $index <= 7; $index++ ) {
				if ( isset( $hours[ $index ] ) && is_array( $hours[ $index ] ) ) {
					$normalized[ $index ] = wp_parse_args(
						$hours[ $index ],
						array(
							'closed' => 0,
							'from'   => '',
							'to'     => '',
						)
					);
				}
			}

			return $normalized;
		}

		if ( isset( $hours[0], $hours[6] ) ) {
			$normalized = self::default_hours();
			for ( $index = 0; $index <= 6; $index++ ) {
				$target                = 0 === $index ? 7 : $index;
				$normalized[ $target ] = is_array( $hours[ $index ] ) ? wp_parse_args(
					$hours[ $index ],
					array(
						'closed' => 0,
						'from'   => '',
						'to'     => '',
					)
				) : array(
					'closed' => 0,
					'from'   => '',
					'to'     => '',
				);
			}

			return $normalized;
		}

		return self::default_hours();
	}

	/**
	 * Get the optional note shown below the opening hours.
	 *
	 * @return string
	 */
	public static function get_note() {
		$note = get_option( OpenCalendarKit_Plugin::OPTION_OPENING_HOURS_NOTE, null );
		if ( ! is_string( $note ) ) {
			$note = get_option( OpenCalendarKit_Plugin::LEGACY_OPTION_OPENING_HOURS_NOTE, '' );
		}

		return is_string( $note ) ? $note : '';
	}
}

<?php
/**
 * Event notice administration.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the event-notice admin page and stored content.
 */
class OpenCalendarKit_Admin_EventNotice {
	private const DEFAULT_THEME = 'blue';

	/**
	 * Register the event notice submenu page.
	 *
	 * @return void
	 */
	public static function register_menu() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				add_submenu_page(
					OpenCalendarKit_Plugin::MENU_SLUG,
					__( 'Announcement', 'open-calendar-kit' ),
					__( 'Announcement', 'open-calendar-kit' ),
					OpenCalendarKit_Plugin::CAP_MANAGE,
					OpenCalendarKit_Plugin::PAGE_EVENT_NOTICE,
					array( __CLASS__, 'render_admin_page' )
				);
			}
		);
	}

	/**
	 * Render and process the event notice admin page.
	 *
	 * @return void
	 */
	public static function render_admin_page() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				$nonce = isset( $_POST['openkit_event_notice_nonce'] )
					? sanitize_text_field( wp_unslash( $_POST['openkit_event_notice_nonce'] ) )
					: '';

				if (
					$nonce &&
					wp_verify_nonce( $nonce, OpenCalendarKit_Plugin::NONCE_EVENT_NOTICE ) &&
					current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE )
				) {
					$enabled = isset( $_POST['openkit_event_notice_enabled'] ) ? '1' : '0';
					$content = isset( $_POST['openkit_event_notice_content'] )
						? wp_kses_post( wp_unslash( $_POST['openkit_event_notice_content'] ) )
						: '';
					$theme   = isset( $_POST['openkit_event_notice_theme'] )
						? self::normalize_theme( sanitize_key( wp_unslash( $_POST['openkit_event_notice_theme'] ) ) )
						: self::DEFAULT_THEME;

					update_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_ENABLED, $enabled );
					update_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_CONTENT, $content );
					update_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_THEME, $theme );

					echo '<div class="updated"><p>' . esc_html__( 'Saved.', 'open-calendar-kit' ) . '</p></div>';
				}

				$enabled = self::is_enabled();
				$content = self::get_content();
				$theme   = self::get_theme();
				?>
				<div class="wrap">
					<h1><?php echo esc_html__( 'Announcement', 'open-calendar-kit' ); ?></h1>

					<form method="post">
						<?php wp_nonce_field( OpenCalendarKit_Plugin::NONCE_EVENT_NOTICE, 'openkit_event_notice_nonce' ); ?>

						<table class="form-table" role="presentation">
							<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Announcement active', 'open-calendar-kit' ); ?></th>
								<td>
									<label for="openkit_event_notice_enabled">
										<input
											type="checkbox"
											id="openkit_event_notice_enabled"
											name="openkit_event_notice_enabled"
											value="1"
											<?php checked( $enabled ); ?>
										/>
										<?php esc_html_e( 'Show the announcement on the frontend when content is available.', 'open-calendar-kit' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="openkit_event_notice_theme"><?php esc_html_e( 'Announcement theme', 'open-calendar-kit' ); ?></label>
								</th>
								<td>
									<select id="openkit_event_notice_theme" name="openkit_event_notice_theme">
										<?php foreach ( self::get_theme_options() as $theme_value => $theme_label ) : ?>
											<option value="<?php echo esc_attr( $theme_value ); ?>" <?php selected( $theme, $theme_value ); ?>>
												<?php echo esc_html( $theme_label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row">
									<label for="openkit_event_notice_content"><?php esc_html_e( 'Announcement text', 'open-calendar-kit' ); ?></label>
								</th>
								<td>
									<?php
									wp_editor(
										$content,
										'openkit_event_notice_content',
										array(
											'textarea_name' => 'openkit_event_notice_content',
											'textarea_rows' => 8,
											'media_buttons' => false,
											'teeny' => true,
										)
									);
									?>
									<p class="description">
										<?php esc_html_e( 'Use the shortcode [openkit_event_notice] to output this announcement on the frontend.', 'open-calendar-kit' ); ?>
									</p>
								</td>
							</tr>
							</tbody>
						</table>

						<p class="submit">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'open-calendar-kit' ); ?></button>
						</p>
					</form>
				</div>
				<?php
			}
		);
	}

	/**
	 * Determine whether the event notice is active.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled = get_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_ENABLED, null );
		if ( ! is_string( $enabled ) ) {
			$enabled = get_option( OpenCalendarKit_Plugin::LEGACY_OPTION_EVENT_NOTICE_ENABLED, '0' );
		}

		return '1' === $enabled;
	}

	/**
	 * Get the stored event notice content.
	 *
	 * @return string
	 */
	public static function get_content() {
		$content = get_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_CONTENT, null );
		if ( ! is_string( $content ) ) {
			$content = get_option( OpenCalendarKit_Plugin::LEGACY_OPTION_EVENT_NOTICE_CONTENT, '' );
		}

		return is_string( $content ) ? $content : '';
	}

	/**
	 * Get the stored announcement theme.
	 *
	 * @return string
	 */
	public static function get_theme(): string {
		return self::normalize_theme( get_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_THEME, self::DEFAULT_THEME ) );
	}

	/**
	 * Return available announcement theme options.
	 *
	 * @return array<string, string>
	 */
	public static function get_theme_options(): array {
		return array_merge(
			array(
				'green' => __( 'Green', 'open-calendar-kit' ),
				'red'   => __( 'Red', 'open-calendar-kit' ),
			),
			OpenCalendarKit_Admin_CalendarEvents::get_color_options()
		);
	}

	/**
	 * Normalize an announcement theme key.
	 *
	 * @param mixed $theme Raw theme value.
	 * @return string
	 */
	private static function normalize_theme( $theme ): string {
		$theme = sanitize_key( (string) $theme );
		if ( isset( self::get_theme_options()[ $theme ] ) ) {
			return $theme;
		}

		return self::DEFAULT_THEME;
	}
}

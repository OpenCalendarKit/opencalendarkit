<?php
/**
 * Settings administration for OpenCalendarKit.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin settings registration, sanitization, and rendering.
 */
class OpenCalendarKit_Admin_Settings {
	const OPTION_NAME = OpenCalendarKit_Plugin::SETTINGS_OPTION;

	/**
	 * Register the settings option.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			OpenCalendarKit_Plugin::SETTINGS_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Register the settings submenu page.
	 *
	 * @return void
	 */
	public static function register_menu() {
		OpenCalendarKit_I18n::with_locale(
			function () {
				add_submenu_page(
					OpenCalendarKit_Plugin::MENU_SLUG,
					__( 'Settings', 'open-calendar-kit' ),
					__( 'Settings', 'open-calendar-kit' ),
					OpenCalendarKit_Plugin::CAP_MANAGE,
					OpenCalendarKit_Plugin::PAGE_SETTINGS,
					array( __CLASS__, 'render_settings_page' )
				);
			}
		);
	}

	/**
	 * Return default plugin settings.
	 *
	 * @return array<string, string>
	 */
	public static function defaults() {
		return array(
			'show_status_today'        => '1',
			'show_calendar_legend'     => '1',
			'week_starts_on'           => 'monday',
			'time_format_mode'         => 'site_default',
			'show_opening_hours_title' => '1',
			'plugin_locale'            => OpenCalendarKit_I18n::SITE_DEFAULT,
		);
	}

	/**
	 * Fetch stored settings merged with defaults.
	 *
	 * @return array<string, string>
	 */
	public static function get_settings() {
		$settings = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $settings ) ) {
			$settings = get_option( OpenCalendarKit_Plugin::LEGACY_OPTION_SETTINGS, array() );
		}

		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		return wp_parse_args( $settings, self::defaults() );
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$settings = self::get_settings();

		return $settings[ $key ] ?? null;
	}

	/**
	 * Determine whether a boolean-like setting is enabled.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function is_enabled( $key ) {
		return self::get( $key ) === '1';
	}

	/**
	 * Sanitize settings submitted from the admin UI.
	 *
	 * @param mixed $input Raw input.
	 * @return array<string, string>
	 */
	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'show_status_today'        => ! empty( $input['show_status_today'] ) ? '1' : '0',
			'show_calendar_legend'     => ! empty( $input['show_calendar_legend'] ) ? '1' : '0',
			'week_starts_on'           => self::normalize_week_starts_on( $input['week_starts_on'] ?? '' ),
			'time_format_mode'         => self::normalize_time_format_mode( $input['time_format_mode'] ?? '' ),
			'show_opening_hours_title' => ! empty( $input['show_opening_hours_title'] ) ? '1' : '0',
			'plugin_locale'            => OpenCalendarKit_I18n::normalize_plugin_locale( $input['plugin_locale'] ?? '' ),
		);
	}

	/**
	 * Normalize the configured first day of week.
	 *
	 * @param string $value Candidate value.
	 * @return string
	 */
	public static function normalize_week_starts_on( $value ) {
		return in_array( $value, array( 'monday', 'sunday' ), true ) ? $value : self::defaults()['week_starts_on'];
	}

	/**
	 * Normalize the configured time format mode.
	 *
	 * @param string $value Candidate value.
	 * @return string
	 */
	public static function normalize_time_format_mode( $value ) {
		return in_array( $value, array( 'site_default', '24h', '12h' ), true ) ? $value : self::defaults()['time_format_mode'];
	}

	/**
	 * Resolve a user-provided boolean-like value.
	 *
	 * @param mixed $value    Raw value.
	 * @param mixed $fallback Fallback value when the input is empty or invalid.
	 * @return bool
	 */
	public static function resolve_bool( $value, $fallback ) {
		if ( '' === $value || null === $value ) {
			return (bool) $fallback;
		}

		if ( is_bool( $value ) ) {
			return $value;
		}

		$value = strtolower( trim( (string) $value ) );

		if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}

		if ( in_array( $value, array( '0', 'false', 'no', 'off' ), true ) ) {
			return false;
		}

		return (bool) $fallback;
	}

	/**
	 * Get the active PHP date format string for time output.
	 *
	 * @param string $mode Optional override.
	 * @return string
	 */
	public static function get_time_format( $mode = '' ) {
		$mode = self::normalize_time_format_mode( '' !== $mode ? $mode : self::get( 'time_format_mode' ) );

		if ( '24h' === $mode ) {
			return 'H:i';
		}

		if ( '12h' === $mode ) {
			return 'g:i A';
		}

		$site_format = get_option( 'time_format' );

		return is_string( $site_format ) && '' !== $site_format ? $site_format : 'H:i';
	}

	/**
	 * Format a HH:ii value using the selected output mode.
	 *
	 * @param string $time Time value.
	 * @param string $mode Optional output mode override.
	 * @return string
	 */
	public static function format_time_value( $time, $mode = '' ) {
		$time = trim( (string) $time );
		if ( '' === $time ) {
			return '';
		}

		$date_time = DateTime::createFromFormat( '!H:i', $time, wp_timezone() );
		if ( ! $date_time ) {
			return $time;
		}

		return $date_time->format( self::get_time_format( $mode ) );
	}

	/**
	 * Render the settings admin page.
	 *
	 * @return void
	 */
	public static function render_settings_page() {
		$settings = self::get_settings();

		OpenCalendarKit_I18n::with_locale(
			function () use ( $settings ) {
				?>
				<div class="wrap">
					<h1><?php echo esc_html__( 'Settings', 'open-calendar-kit' ); ?></h1>

					<form method="post" action="options.php">
						<?php settings_fields( OpenCalendarKit_Plugin::SETTINGS_GROUP ); ?>

						<table class="form-table" role="presentation">
							<tbody>
							<tr>
								<th scope="row"><?php esc_html_e( 'Plugin language', 'open-calendar-kit' ); ?></th>
								<td>
									<select name="openkit_settings[plugin_locale]" id="openkit_settings_plugin_locale">
										<?php foreach ( OpenCalendarKit_I18n::get_available_locales() as $locale_code => $label ) : ?>
											<option value="<?php echo esc_attr( $locale_code ); ?>" <?php selected( $settings['plugin_locale'], $locale_code ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
									<p class="description">
										<?php esc_html_e( 'Use the website language by default, or choose a language for OpenCalendarKit only.', 'open-calendar-kit' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Show status today by default', 'open-calendar-kit' ); ?></th>
								<td>
									<label for="openkit_settings_show_status_today">
										<input
											type="checkbox"
											id="openkit_settings_show_status_today"
											name="openkit_settings[show_status_today]"
											value="1"
											<?php checked( $settings['show_status_today'], '1' ); ?>
										/>
										<?php esc_html_e( 'Allow the status shortcode to output by default.', 'open-calendar-kit' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Show calendar legend by default', 'open-calendar-kit' ); ?></th>
								<td>
									<label for="openkit_settings_show_calendar_legend">
										<input
											type="checkbox"
											id="openkit_settings_show_calendar_legend"
											name="openkit_settings[show_calendar_legend]"
											value="1"
											<?php checked( $settings['show_calendar_legend'], '1' ); ?>
										/>
										<?php esc_html_e( 'Display the open/closed legend below the calendar unless a shortcode overrides it.', 'open-calendar-kit' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Week starts on', 'open-calendar-kit' ); ?></th>
								<td>
									<select name="openkit_settings[week_starts_on]" id="openkit_settings_week_starts_on">
										<option value="monday" <?php selected( $settings['week_starts_on'], 'monday' ); ?>><?php esc_html_e( 'Monday', 'open-calendar-kit' ); ?></option>
										<option value="sunday" <?php selected( $settings['week_starts_on'], 'sunday' ); ?>><?php esc_html_e( 'Sunday', 'open-calendar-kit' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Time format mode', 'open-calendar-kit' ); ?></th>
								<td>
									<select name="openkit_settings[time_format_mode]" id="openkit_settings_time_format_mode">
										<option value="site_default" <?php selected( $settings['time_format_mode'], 'site_default' ); ?>><?php esc_html_e( 'Use WordPress setting', 'open-calendar-kit' ); ?></option>
										<option value="24h" <?php selected( $settings['time_format_mode'], '24h' ); ?>><?php esc_html_e( '24-hour', 'open-calendar-kit' ); ?></option>
										<option value="12h" <?php selected( $settings['time_format_mode'], '12h' ); ?>><?php esc_html_e( '12-hour', 'open-calendar-kit' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Show opening hours title by default', 'open-calendar-kit' ); ?></th>
								<td>
									<label for="openkit_settings_show_opening_hours_title">
										<input
											type="checkbox"
											id="openkit_settings_show_opening_hours_title"
											name="openkit_settings[show_opening_hours_title]"
											value="1"
											<?php checked( $settings['show_opening_hours_title'], '1' ); ?>
										/>
										<?php esc_html_e( 'Display the heading above opening hours unless a shortcode overrides it.', 'open-calendar-kit' ); ?>
									</label>
								</td>
							</tr>
							</tbody>
						</table>

						<?php submit_button( __( 'Save settings', 'open-calendar-kit' ) ); ?>
					</form>
				</div>
				<?php
			}
		);
	}
}

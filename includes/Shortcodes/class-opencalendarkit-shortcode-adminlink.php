<?php
/**
 * Admin-link shortcode rendering.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders a small login or admin link for plugin maintenance.
 */
class OpenCalendarKit_Shortcode_AdminLink {
	/**
	 * Render the shortcode output.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		return OpenCalendarKit_I18n::with_locale(
			function () use ( $atts ) {
				$atts = shortcode_atts(
					array(
						'target' => 'calendar',
					),
					$atts,
					OpenCalendarKit_Plugin::SHORTCODE_ADMIN_LINK
				);

				$target    = self::normalize_target( (string) $atts['target'] );
				$admin_url = self::get_target_url( $target );

				if ( is_user_logged_in() ) {
					if ( ! current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE ) ) {
						return '<span class="bkit-admin-link bkit-admin-link--denied">' . esc_html__( 'No access.', 'open-calendar-kit' ) . '</span>';
					}

					$label = self::get_logged_in_label( $target );
					$url   = $admin_url;
				} else {
					$label = self::get_logged_out_label( $target );
					$url   = wp_login_url( $admin_url );
				}

				return sprintf(
					'<a class="bkit-admin-link" href="%1$s">%2$s</a>',
					esc_url( $url ),
					esc_html( $label )
				);
			}
		);
	}

	/**
	 * Normalize supported targets.
	 *
	 * @param string $target Raw target attribute.
	 * @return string
	 */
	private static function normalize_target( string $target ): string {
		return 'opening-hours' === $target ? 'opening-hours' : 'calendar';
	}

	/**
	 * Return the backend URL for a target.
	 *
	 * @param string $target Normalized target attribute.
	 * @return string
	 */
	private static function get_target_url( string $target ): string {
		if ( 'opening-hours' === $target ) {
			return add_query_arg(
				array(
					'page' => OpenCalendarKit_Plugin::MENU_SLUG,
				),
				admin_url( 'admin.php' )
			);
		}

		return add_query_arg(
			array(
				'page' => OpenCalendarKit_Plugin::PAGE_CALENDAR,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Return the default label for authenticated users.
	 *
	 * @param string $target Normalized target attribute.
	 * @return string
	 */
	private static function get_logged_in_label( string $target ): string {
		if ( 'opening-hours' === $target ) {
			return __( 'Edit opening hours', 'open-calendar-kit' );
		}

		return __( 'Edit calendar', 'open-calendar-kit' );
	}

	/**
	 * Return the default label for logged-out users.
	 *
	 * @param string $target Normalized target attribute.
	 * @return string
	 */
	private static function get_logged_out_label( string $target ): string {
		if ( 'opening-hours' === $target ) {
			return __( 'Log in to edit opening hours', 'open-calendar-kit' );
		}

		return __( 'Log in to edit calendar', 'open-calendar-kit' );
	}
}

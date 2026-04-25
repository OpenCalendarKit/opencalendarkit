<?php
/**
 * Event notice shortcode rendering.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the optional event notice on the frontend.
 */
class OpenCalendarKit_Shortcode_EventNotice {
	/**
	 * Render the event notice shortcode.
	 *
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public static function render( $atts = array() ) {
		$atts = is_array( $atts ) ? $atts : array();

		return OpenCalendarKit_I18n::with_locale(
			function () {
				if ( ! class_exists( 'OpenCalendarKit_Admin_EventNotice' ) ) {
					return '';
				}

				if ( ! OpenCalendarKit_Admin_EventNotice::is_enabled() ) {
					return '';
				}

				$content = OpenCalendarKit_Admin_EventNotice::get_content();
				if ( trim( wp_strip_all_tags( $content ) ) === '' ) {
					return '';
				}

				$content = wpautop( wp_kses_post( $content ) );
				$theme   = OpenCalendarKit_Admin_EventNotice::get_theme();

				ob_start();
				?>
				<div class="bkit-event-notice bkit-event-notice--<?php echo esc_attr( $theme ); ?> bkit-ui-callout bkit-ui-callout--notice bkit-ui-callout--notice-<?php echo esc_attr( $theme ); ?>" role="note">
					<div class="bkit-ui-callout__inner bkit-event-notice__inner">
						<div class="bkit-event-notice__body">
							<?php echo wp_kses_post( $content ); ?>
						</div>
					</div>
				</div>
				<?php

				return ob_get_clean();
			}
		);
	}
}

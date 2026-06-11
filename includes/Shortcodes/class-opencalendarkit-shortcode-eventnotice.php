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

				$content = self::normalize_notice_html( OpenCalendarKit_Admin_EventNotice::get_content() );
				if ( trim( wp_strip_all_tags( $content ) ) === '' ) {
					return '';
				}

				$theme               = OpenCalendarKit_Admin_EventNotice::get_theme();
				$font_size_css_value = OpenCalendarKit_Admin_EventNotice::get_font_size_css_value();

				return sprintf(
					'<div class="bkit-event-notice bkit-event-notice--%1$s bkit-ui-callout bkit-ui-callout--notice bkit-ui-callout--notice-%1$s" role="note" style="%2$s"><div class="bkit-ui-callout__inner bkit-event-notice__inner"><div class="bkit-event-notice__body">%3$s</div></div></div>',
					esc_attr( $theme ),
					esc_attr( '--okit-event-notice-font-size: ' . $font_size_css_value . ';' ),
					wp_kses_post( $content )
				);
			}
		);
	}

	/**
	 * Normalize editor HTML while preserving useful inline formatting.
	 *
	 * @param string $content Stored notice HTML.
	 * @return string
	 */
	private static function normalize_notice_html( string $content ): string {
		$content = wpautop( wp_kses_post( $content ) );
		$content = self::remove_editor_fillers( $content );

		return trim( $content );
	}

	/**
	 * Remove invisible editor filler markup that creates extra visual height.
	 *
	 * @param string $content Rendered notice HTML.
	 * @return string
	 */
	private static function remove_editor_fillers( string $content ): string {
		$filler = '(?:\s|&nbsp;|&#160;|<br\s*\/?>|<span[^>]*>\s*<\/span>)*';

		do {
			$previous = $content;

			$content = preg_replace( '/<(p|div)\b[^>]*>' . $filler . '<\/\1>/i', '', $content );
			$content = is_string( $content ) ? $content : $previous;

			$content = preg_replace(
				'/(?:\s|&nbsp;|&#160;|<br\s*\/?>)+((?:<\/(?:strong|b|em|i|span)>)*<\/(?:p|div)>)/i',
				'$1',
				$content
			);
			$content = is_string( $content ) ? $content : $previous;
			$content = trim( $content );
		} while ( $content !== $previous );

		return $content;
	}
}

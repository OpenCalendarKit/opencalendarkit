<?php
/**
 * Locale and translation helpers.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides locale selection and temporary locale switching helpers.
 */
class OpenCalendarKit_I18n {
	const TEXT_DOMAIN  = 'open-calendar-kit';
	const SITE_DEFAULT = 'site_default';

	/**
	 * Get the configured WordPress locale.
	 *
	 * @return string
	 */
	public static function get_wordpress_locale() {
		if ( function_exists( 'get_locale' ) ) {
			$locale = get_locale();
			if ( is_string( $locale ) && '' !== $locale ) {
				return $locale;
			}
		}

		return 'en_US';
	}

	/**
	 * Get the currently active runtime locale.
	 *
	 * @return string
	 */
	public static function get_runtime_locale() {
		if ( function_exists( 'determine_locale' ) ) {
			$locale = determine_locale();
			if ( is_string( $locale ) && '' !== $locale ) {
				return $locale;
			}
		}

		return self::get_wordpress_locale();
	}

	/**
	 * Normalize a plugin-specific locale selection.
	 *
	 * @param mixed $locale Locale candidate.
	 * @return string
	 */
	public static function normalize_plugin_locale( $locale ) {
		$locale = is_string( $locale ) ? trim( $locale ) : '';

		return array_key_exists( $locale, self::get_available_locales() )
			? $locale
			: self::SITE_DEFAULT;
	}

	/**
	 * Return selectable plugin locales.
	 *
	 * @return array<string, string>
	 */
	public static function get_available_locales() {
		return array(
			self::SITE_DEFAULT => __( 'Use WordPress language', 'open-calendar-kit' ),
			'de_DE'            => 'Deutsch',
			'en_US'            => 'English',
			'fr_FR'            => 'Français',
		);
	}

	/**
	 * Get the locale configured in plugin settings.
	 *
	 * @return string
	 */
	public static function get_configured_locale() {
		if ( class_exists( 'OpenCalendarKit_Admin_Settings' ) ) {
			return self::normalize_plugin_locale( OpenCalendarKit_Admin_Settings::get( 'plugin_locale' ) );
		}

		return self::SITE_DEFAULT;
	}

	/**
	 * Resolve the effective locale used by the plugin.
	 *
	 * @return string
	 */
	public static function get_effective_locale() {
		$locale = self::get_configured_locale();

		return self::SITE_DEFAULT === $locale ? self::get_wordpress_locale() : $locale;
	}

	/**
	 * Convert the effective locale to a JavaScript-friendly value.
	 *
	 * @return string
	 */
	public static function get_js_locale() {
		return str_replace( '_', '-', self::get_effective_locale() );
	}

	/**
	 * Run a callback within the plugin's effective locale.
	 *
	 * @param callable $callback Callback to run.
	 * @return mixed
	 */
	public static function with_locale( callable $callback ) {
		$target_locale  = self::get_effective_locale();
		$runtime_locale = self::get_runtime_locale();
		$switched       = false;

		if (
			function_exists( 'switch_to_locale' ) &&
			function_exists( 'restore_previous_locale' ) &&
			'' !== $target_locale &&
			$target_locale !== $runtime_locale
		) {
			$switched = (bool) switch_to_locale( $target_locale );
		}

		try {
			return $callback();
		} finally {
			if ( $switched ) {
				restore_previous_locale();
			}
		}
	}
}

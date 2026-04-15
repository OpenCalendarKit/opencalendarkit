<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpenCalendarKit_I18n {
    const TEXT_DOMAIN = 'open-calendar-kit';
    const SITE_DEFAULT = 'site_default';

    public static function get_wordpress_locale() {
        if ( function_exists( 'get_locale' ) ) {
            $locale = get_locale();
            if ( is_string( $locale ) && $locale !== '' ) {
                return $locale;
            }
        }

        return 'en_US';
    }

    public static function get_runtime_locale() {
        if ( function_exists( 'determine_locale' ) ) {
            $locale = determine_locale();
            if ( is_string( $locale ) && $locale !== '' ) {
                return $locale;
            }
        }

        return self::get_wordpress_locale();
    }

    public static function normalize_plugin_locale( $locale ) {
        $locale = is_string( $locale ) ? trim( $locale ) : '';

        return array_key_exists( $locale, self::get_available_locales() )
            ? $locale
            : self::SITE_DEFAULT;
    }

    public static function get_available_locales() {
        return [
            self::SITE_DEFAULT => __( 'Use WordPress language', 'open-calendar-kit' ),
            'de_DE'            => 'Deutsch',
            'en_US'            => 'English',
            'fr_FR'            => 'Français',
        ];
    }

    public static function get_configured_locale() {
        if ( class_exists( 'OpenCalendarKit_Admin_Settings' ) ) {
            return self::normalize_plugin_locale( OpenCalendarKit_Admin_Settings::get( 'plugin_locale' ) );
        }

        return self::SITE_DEFAULT;
    }

    public static function get_effective_locale() {
        $locale = self::get_configured_locale();

        return $locale === self::SITE_DEFAULT ? self::get_wordpress_locale() : $locale;
    }

    public static function get_js_locale() {
        return str_replace( '_', '-', self::get_effective_locale() );
    }

    public static function with_locale( callable $callback ) {
        $target_locale = self::get_effective_locale();
        $runtime_locale = self::get_runtime_locale();
        $switched = false;

        if (
            function_exists( 'switch_to_locale' ) &&
            function_exists( 'restore_previous_locale' ) &&
            $target_locale !== '' &&
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

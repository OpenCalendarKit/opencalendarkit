<?php
/**
 * Plugin Name: OpenCalendarKit
 * Description: Public WordPress plugin for opening hours, closed days, monthly calendars, event notices, and optional status displays.
 * Version: 0.3.6
 * Author: Jörn / ChatGPT
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: open-calendar-kit
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/bootstrap.php';

/**
 * Load translations for the public plugin shell.
 */
function open_calendar_kit_load_textdomain() {
	OpenCalendarKit_I18n::load_textdomain();
}

add_action( 'plugins_loaded', 'open_calendar_kit_load_textdomain' );

new OpenCalendarKit_Plugin();

register_activation_hook( __FILE__, [ 'OpenCalendarKit_Plugin', 'activate' ] );
register_uninstall_hook( __FILE__, [ 'OpenCalendarKit_Plugin', 'uninstall' ] );

<?php
/**
 * Plugin Name: OpenCalendarKit
 * Description: Public WordPress plugin for opening hours, closed days, monthly calendars, event notices, and optional status displays.
 * Version: 1.0.4
 * Author: Jörn / ChatGPT
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: open-calendar-kit
 * Domain Path: /languages
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-opencalendarkit-plugin.php';

new OpenCalendarKit_Plugin();

register_activation_hook( __FILE__, array( 'OpenCalendarKit_Plugin', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'OpenCalendarKit_Plugin', 'uninstall' ) );

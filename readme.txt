=== OpenCalendarKit ===
Contributors: replace-with-wordpress-org-slug
Tags: opening-hours, calendar, business-hours, shortcode, events
Requires at least: 6.0
Tested up to: 6.8.2
Requires PHP: 7.4
Stable tag: 0.3.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Public WordPress plugin for opening hours, closed days, monthly calendars, event notices, and optional status displays.

== Description ==

OpenCalendarKit provides a lightweight public plugin for recurring opening hours, closed days, a monthly calendar, an optional event notice, and an optional "status today" output.

The current Public 1.0 scope includes:

* Opening hours management in the WordPress admin
* Closed days managed in the plugin calendar
* Monthly calendar shortcode output
* Optional status display for the current day
* Optional event notice output
* Global display settings for legend, title, week start, and time format
* Internationalization-ready visible strings and WordPress-aware date/time output

The current Public 1.0 scope does not include:

* Reservation or booking workflows
* Multi-location support
* Block editor blocks
* Advanced event management

Available shortcodes:

* `[okit_opening_hours]`
* `[okit_status_today]`
* `[okit_calendar]`
* `[okit_event_notice]`

== Installation ==

1. Upload the `open-calendar-kit` folder to `/wp-content/plugins/`, or install it as a ZIP package in the WordPress admin.
2. Activate **OpenCalendarKit** through the Plugins screen.
3. Open **OpenCalendarKit** in the WordPress admin.
4. Configure display settings, opening hours, closed days, and event notice.
5. Insert the provided `okit_*` shortcodes into pages, posts, or widget areas.

== Frequently Asked Questions ==

= Does this plugin include reservations or bookings? =

No. Reservation functionality is intentionally out of scope for the public plugin.

= Which settings are available? =

OpenCalendarKit currently provides these global settings:

* Plugin Language
* Show Status Today
* Show Calendar Legend
* Week Starts On
* Time Format Mode
* Show Opening Hours Title

= How are closed days handled? =

Closed days are managed in the OpenCalendarKit calendar area in the WordPress admin. They are reflected in the calendar output and can include an optional reason.

= Is the plugin translation-ready? =

Yes. Visible strings use the `open-calendar-kit` text domain, and date/time output follows WordPress locale and time-format handling.
Translation files belong in the plugin `languages/` directory. A starter template is included as `languages/open-calendar-kit.pot`.
By default, the plugin follows the WordPress website language. You can override the plugin language in the plugin settings.
The same language source is applied to admin pages, frontend shortcodes, calendar titles, weekday labels, legends, and JS-localized texts.
Included example language files: `de_DE` and `fr_FR`. English uses the source strings.
Editorial content such as event notice content, opening-hours notes, and closed-day reasons is not translated automatically by this setting.

= Does uninstall remove my content? =

No. The uninstall behavior is intentionally data-friendly and does not delete managed content automatically.

== Changelog ==

= 0.3.6 =

* Public plugin shell completed for OpenCalendarKit
* Public `okit_*` shortcode API established
* Reservation legacy removed from the public plugin scope
* Visible UI prepared for translation and localization
* Central Public 1.0 display settings added
* Initial automated test foundation added
* Distribution and packaging documentation prepared

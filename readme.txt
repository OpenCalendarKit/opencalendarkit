=== OpenCalendarKit ===
Contributors: voigtjo
Tags: opening-hours, calendar, business-hours, shortcode, events
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Public WordPress plugin for opening hours, closed days, monthly calendars, calendar events, event notices, and optional status displays.

== Description ==

OpenCalendarKit provides a lightweight public plugin for recurring opening hours, closed days, calendar events, a monthly calendar, an optional event notice, and an optional "status today" output.

It is built for restaurants, cafes, shops, and other small businesses that want a simple and clear way to show:

* opening hours
* whether they are open right now
* special closed days
* a current notice or announcement

Unlike large event or booking plugins, OpenCalendarKit focuses on business visibility, not on tickets, appointments, or complex reservation workflows.

The current Public 1.1 scope includes:

* Opening hours management in the WordPress admin
* Closed days managed in the plugin calendar
* Monthly calendar shortcode output
* Day-based calendar events with frontend highlighting
* Optional status display for the current day
* Optional event notice output
* Global display settings for legend, title, week start, and time format
* Bundled translation files and JavaScript locale data for German and French
* Internationalization-ready visible strings and WordPress-aware date/time output
* CSS custom properties prepared for future theme variants

The current Public 1.1 scope does not include:

* Reservation or booking workflows
* Multi-location support
* Block editor blocks
* Advanced event management

Available shortcodes:

* `[openkit_opening_hours]`
* `[openkit_status_today]`
* `[openkit_calendar]`
* `[openkit_calendar_event]`
* `[openkit_event_notice]`

== Installation ==

1. Upload the `open-calendar-kit` folder to `/wp-content/plugins/`, or install it as a ZIP package in the WordPress admin.
2. Activate **OpenCalendarKit** through the Plugins screen.
3. Open **OpenCalendarKit** in the WordPress admin.
4. Configure display settings, opening hours, closed days, and event notice.
5. Insert the provided `openkit_*` shortcodes into pages, posts, or widget areas.

The same plugin ZIP also contains the bundled language files from `languages/`, including `.po`, `.mo`, and JavaScript translation JSON files.

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

= Are the language files included in the plugin ZIP? =

Yes. The release ZIP contains the plugin code together with the files in `languages/`, including the `.pot`, bundled `.po` and `.mo` files, and the JavaScript translation JSON files.

= Can the design be themed later? =

Yes. The frontend stylesheet already exposes CSS custom properties for status colors, calendar day states, event highlights, notices, and event callouts. That gives later theme variants a stable base without changing the shortcode API.

= Does uninstall remove my content? =

No. The uninstall behavior is intentionally data-friendly and does not delete managed content automatically.

== Changelog ==

= 1.1.0 =

* Added simple day-based calendar events in the admin calendar screen
* Added blue highlighted event days in the frontend calendar
* Added the new shortcode `[openkit_calendar_event]` for the current day or a requested date
* Added bundled German and French language files, updated `.pot`, and JavaScript translation JSON files
* Aligned frontend and admin strings with the `open-calendar-kit` text domain
* Prepared the frontend stylesheet with CSS custom properties for future theme variants

= 1.0.4 =

* Renamed plugin constants to use a clearer plugin-specific prefix
* Cleared the remaining Plugin Check warning from the WordPress Playground review flow

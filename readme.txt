=== OpenCalendarKit ===
Contributors: voigtjo
Tags: opening-hours, calendar, business-hours, shortcode, events
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight plugin for opening hours, today status, closed days, special opening times, and calendar events.

== Description ==

OpenCalendarKit displays opening hours, today status, closed days, special opening times, and calendar events for small business websites.

[Open live demo: opencalendarkit.com](https://opencalendarkit.com/)

Good fit for:

* restaurants
* cafes
* local shops
* studios
* practices

Main features:

* weekly opening hours
* open / closed status for today
* closed days and exceptions
* special opening times for a single day
* monthly frontend calendar
* calendar events for individual days
* simple backend screens for opening hours and calendar management

Available shortcodes:

* `[openkit_opening_hours]`
* `[openkit_status_today]`
* `[openkit_calendar]`
* `[openkit_calendar_event]`
* `[openkit_event_notice]`
* `[openkit_admin_link]`

== Installation ==

1. Install the plugin in WordPress or upload the `open-calendar-kit` folder to `/wp-content/plugins/`.
2. Activate **OpenCalendarKit**.
3. Open **OpenCalendarKit** in the admin menu.
4. Add your opening hours, closed days, and optional calendar events.
5. Place the shortcodes on your pages or posts.

== Frequently Asked Questions ==

= What does OpenCalendarKit do? =

It helps you publish opening hours, today status, closed days, special opening times, and calendar events on your website.

= Can I add special opening times for one specific day? =

Yes. A day can store special opening times and optional event text.

= Is it suitable for restaurants and other small local businesses? =

Yes. The plugin is built for clear public opening information on small business websites.

== Screenshots ==

1. Frontend view with opening hours, current status, calendar, and day message.
2. Frontend calendar popup for a closed day with closure reason.
3. Frontend calendar popup for day-specific opening hours.
4. Backend calendar and calendar-event management.
5. Backend weekly opening-hours management.

== Changelog ==

= 1.1.6 =

* stop shipping default opening hours on new installs
* keep opening hours empty until users configure their own values
* shorten and tighten the WordPress.org readme

= 1.1.5 =

* Simplified calendar-event editing and special opening times
* Added editable event text blocks and easier multi-day closed-day entry

= 1.1.4 =

* Added time-based day events and improved daily status handling
* Added the admin-link shortcode for quick staff access

= 1.1.0 =

* Added calendar events, frontend event output, and bundled translations

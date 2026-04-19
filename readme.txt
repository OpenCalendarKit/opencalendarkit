=== OpenCalendarKit ===
Contributors: voigtjo
Tags: opening-hours, calendar, business-hours, shortcode, events
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.5
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight business-hours plugin for opening hours, status today, closed days, special opening times, and calendar events.

== Description ==

OpenCalendarKit helps small websites show clear public opening information without turning the site into a booking system.

It is a good fit for:

* restaurants
* cafes
* studios
* practices
* local shops

It helps you:

* show recurring opening hours
* show whether you are open today, opening later today, or already closed
* manage closed days and exceptions
* add calendar events for specific days
* add special opening times for a single day
* show a monthly calendar on the frontend
* show a current event or special-opening card for today or a requested date
* offer staff a simple login or backend link to the plugin screens

OpenCalendarKit stays focused on public business information. It does not add reservations, appointments, ticketing, or other large workflows.

Each calendar day can store one event. That event can include:

* a text
* an opening time
* a closing time
* or a practical combination of these values

For the daily opening logic, the priority is:

1. Closed day
2. Time event
3. Open exception
4. Normal weekly opening hours

That means a closed day still wins, but a time event can override the regular weekday rule for that one date.

Available shortcodes:

* `[openkit_opening_hours]`
* `[openkit_status_today]`
* `[openkit_calendar]`
* `[openkit_calendar_event]`
* `[openkit_event_notice]`
* `[openkit_admin_link]`

The plugin ZIP includes the bundled language files from `languages/`, including `.pot`, `.po`, `.mo`, and JavaScript translation JSON files.

== Installation ==

1. Upload the `open-calendar-kit` folder to `/wp-content/plugins/`, or install it as a ZIP package in the WordPress admin.
2. Activate **OpenCalendarKit** through the Plugins screen.
3. Open **OpenCalendarKit** in the WordPress admin.
4. Configure settings, opening hours, closed days, calendar events, and the optional notice.
5. Place the provided shortcodes in pages, posts, or widget areas.

== Frequently Asked Questions ==

= Is this a booking or reservation plugin? =

No. OpenCalendarKit is intentionally focused on public schedule display.

= What can I manage in the calendar area? =

You can manage:

* closed days
* exceptional openings
* one event per day
* text events
* special opening times for a single date

= How do calendar events work? =

A calendar event can store a text, an opening time, a closing time, or all of them together for one date. This makes it useful both for day-specific opening hours and for simple notes in the calendar.

= What does the admin-link shortcode do? =

`[openkit_admin_link]` gives staff a simple frontend link.

If the user is logged out, it links to `wp-login.php` and redirects to the requested OpenCalendarKit backend page after login.

If the user is logged in and has the `openkit_manage` capability, it links directly to the requested backend screen.

Supported targets:

* `calendar`
* `opening-hours`

Examples:

* `[openkit_admin_link target="calendar"]`
* `[openkit_admin_link target="opening-hours"]`

= Can I theme it later? =

Yes. The frontend stylesheet already uses CSS custom properties for day states, notices, and event callouts so later theme variants can build on a stable base.

== Changelog ==

= 1.1.5 =

- simplified the calendar-events admin table by removing the event-type and shortcode-output columns
- made calendar events flexible per day: text, opening time, closing time, or any useful combination
- added a configurable default text for newly created calendar-event rows
- switched calendar-event time entry to simple typed text values such as `10`, `10:00`, or `1030`
- updated `[openkit_calendar_event]` and `[openkit_status_today]` so day-specific times also work with only an opening time or only a closing time

= 1.1.4 =

- added day-specific time events with opening and closing times in the calendar events table
- updated `[openkit_status_today]` to respect time events with the priority closed day > time event > open exception > weekly opening hours
- added the `[openkit_admin_link]` shortcode for staff login and quick backend access
- improved the backend calendar-events table layout and the frontend calendar-event callout styling

= 1.1.3 =

- Removed the generic "Event" popup title for text-based calendar events on the frontend.
- Show calendar event days in the backend calendar with the same blue highlight used on the frontend.
- Kept the previously added admin-link text setting and calendar-event save fixes together in the public release.

= 1.1.2 =

- Fixed the calendar-events admin form so it stays a real POST form after admin HTML sanitization.
- Preserved the event type selector and form attributes on the calendar admin page to prevent white-screen redirects on save.

= 1.1.1 =

* Added day-specific time events with opening and closing times in the calendar-events table
* Updated the daily status logic to respect time events with the priority closed day > time event > open exception > weekly opening hours
* Added the new `[openkit_admin_link]` shortcode for staff login and quick backend access
* Updated frontend event output for special opening times
* Clarified the public plugin description, shortcode overview, and feature summary

= 1.1.0 =

* Added simple day-based calendar events below the admin calendar
* Added frontend event highlights and the `[openkit_calendar_event]` shortcode
* Bundled German and French language files and JavaScript translation files
* Prepared frontend CSS custom properties for future theme variants

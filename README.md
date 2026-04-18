# OpenCalendarKit

OpenCalendarKit is a lightweight WordPress plugin for small websites that need clear opening hours, a simple status for today, and a calendar with special days and notes.

It is built for places like:

- restaurants
- cafes
- studios
- practices
- local shops

It is not a booking system. It focuses on showing visitors when you are open, when you are closed, and what is special on certain days.

## What The Plugin Does

OpenCalendarKit helps you:

- show recurring opening hours
- show whether you are open today, opening later today, or already closed
- manage closed days and exceptions
- add calendar events for specific days
- override a single day with special opening times
- show a monthly calendar on the frontend
- show a current event or special-opening card for a specific date
- add a simple staff login link to the plugin backend

## Shortcodes

- `[openkit_opening_hours]` shows the opening-hours table
- `[openkit_status_today]` shows the current-day status
- `[openkit_calendar]` shows the monthly calendar
- `[openkit_calendar_event]` shows the event or special opening card for today or a requested date
- `[openkit_event_notice]` shows the optional notice box
- `[openkit_admin_link]` shows a login or backend link for staff

### Admin Link Targets

- `[openkit_admin_link target="calendar"]` links to the calendar management page
- `[openkit_admin_link target="opening-hours"]` links to the opening-hours page

If the user is logged out, the shortcode links to `wp-login.php` and redirects to the requested OpenCalendarKit admin page after login.

## Calendar Events

Each day can store one calendar event. That event can be either:

- a text event
- a time event with special opening hours for that day

Time events add day-specific opening times on top of the normal weekly schedule. The priority is:

1. Closed day
2. Time event
3. Open exception
4. Normal weekly opening hours

This means a closed day still wins, but a time event can override the normal weekday rule or an open exception for that date.

## Why Use It

OpenCalendarKit is useful when you want a simple public schedule without extra complexity.

It avoids:

- reservations
- appointments
- ticketing
- multi-location management
- oversized event workflows

## Installation

1. Copy `open-calendar-kit` to `wp-content/plugins/`, or upload the plugin ZIP in WordPress.
2. Activate **OpenCalendarKit**.
3. Open the **OpenCalendarKit** menu in the WordPress admin.
4. Configure opening hours, closed days, calendar events, notices, and settings.
5. Place the shortcodes on a page for testing.

## Included Languages

The release ZIP contains the plugin code and the bundled files inside `languages/`.

Currently included:

- `open-calendar-kit.pot`
- German translation files
- French translation files
- JavaScript translation JSON files

## Styling

The frontend stylesheet uses CSS custom properties for calendar states, notices, and event callouts. That keeps the current default design simple while preparing the plugin for future theme variants.

## Changelog

## 1.1.4

- added day-specific time events with opening and closing times in the calendar-events table
- updated `[openkit_status_today]` to respect the priority closed day > time event > open exception > weekly opening hours
- added the `[openkit_admin_link]` shortcode for staff login and quick backend access
- replaced the unreliable per-event shortcode-output checkbox with a clear Show/Calendar only selector
- improved the backend calendar-events table layout and the frontend calendar-event callout styling

## 1.1.3

- Removed the generic "Event" popup title for text-based calendar events on the frontend.
- Show calendar event days in the backend calendar with the same blue highlight used on the frontend.
- Bundled the recent admin-link text setting and calendar-event save fixes into the public patch release.

## 1.1.2

- Fixed the calendar-events admin save form so it keeps the POST method on live sites.
- Preserved the event type selector markup inside the sanitized calendar admin screen.

## 1.1.1

- Added day-specific time events with opening and closing times in the calendar-events table
- Updated the daily status logic to respect time events with the priority closed day > time event > open exception > weekly opening hours
- Added the new `[openkit_admin_link]` shortcode for staff login and quick backend access
- Updated frontend event output for special opening times
- Clarified the public plugin description, shortcode overview, and feature summary

## 1.1.0

- Added simple day-based calendar events below the admin calendar
- Added blue highlighted frontend calendar event days with click-to-view details
- Added the `[openkit_calendar_event]` shortcode
- Bundled German and French language files and JavaScript translation files
- Prepared frontend CSS custom properties for future theme variants

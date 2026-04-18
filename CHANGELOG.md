# Changelog

## 1.1.3.11

- restored the calendar-event callout spacing to the preferred local-test variant from 1.1.3.7

## 1.1.3.10

- slightly increased the line spacing between the event title and time in the restored calendar-event callout

## 1.1.3.9

- restored the previous calendar-event callout variant after the unsuccessful two-line rebuild experiment

## 1.1.3.8

- rebuilt the calendar-event callout with clean two-line markup and tighter vertical spacing

## 1.1.3.7

- reduced the top and bottom padding in the calendar-event callout

## 1.1.3.6

- simplified the calendar-event callout typography to use normal text flow and spacing

## 1.1.3.5

- changed the calendar-event callout to one compact text block with a fixed line break between title and time

## 1.1.3.4

- removed the visible blank line between the event title and time inside the calendar-event callout

## 1.1.3.3

- tightened the calendar-event shortcode callout so it sits closer to the status callout proportions
- restored a usable width for the calendar-event text field in the backend event table

## 1.1.3.2

- Matched the `[openkit_calendar_event]` callout dimensions more closely to the compact status-today callout.
- Replaced the per-event shortcode-output checkbox with an explicit Show/Calendar only selector.

## 1.1.3

- Removed the generic "Event" popup title for text-based calendar events on the frontend.
- Show calendar event days in the backend calendar with the same blue highlight used on the frontend.
- Bundled the recent admin-link text setting and calendar-event save fixes into the public patch release.

## 1.1.2

- Fixed the calendar-events admin form allowlist so saving stays on the calendar page and uses POST correctly.
- Preserved the event type selector markup inside the sanitized calendar admin screen.

## 1.1.1

- Added day-specific time events with opening and closing times in the calendar-events table.
- Updated the current-day status logic to respect the priority closed day > time event > open exception > weekly opening hours.
- Added the `[openkit_admin_link]` shortcode for staff login and quick backend access.
- Updated frontend event output for special opening times and refined event callout styling.
- Clarified README and WordPress.org plugin descriptions around the core use case.

## 1.1.0

- Added simple day-based calendar events below the admin calendar.
- Added blue highlighted frontend calendar event days with click-to-view text.
- Added the `[openkit_calendar_event]` shortcode for the current day or a specific date.
- Bundled German and French language files, updated the POT template, and shipped JavaScript translation JSON files.
- Completed WordPress-standard i18n wiring for PHP and JavaScript using the `open-calendar-kit` text domain.
- Prepared the frontend stylesheet with CSS custom properties for future theme variants.

## 1.0.4

- Renamed plugin constants to use a clearer plugin-specific prefix.
- Cleared the remaining Plugin Check warning from the WordPress Playground review flow.

# Changelog

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

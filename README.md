# OpenCalendarKit

Public WordPress plugin for opening hours, closed days, monthly calendars, event notices, and optional status displays.

## What It Does

OpenCalendarKit is a lightweight WordPress plugin for sites that need a clear public-facing schedule without restaurant-specific legacy features.

Public 1.0 currently includes:

- Opening hours managed in the WordPress admin
- Closed days managed through the plugin calendar
- Monthly calendar output
- Optional "status today" output
- Optional event notice output
- Small global display settings for legend, title, week start, and time format

Out of scope for the current public release:

- Reservation or booking workflows
- Multi-location support
- WordPress blocks
- Advanced event management

## Installation

1. Copy the `open-calendar-kit` directory into `wp-content/plugins/`.
2. Activate **OpenCalendarKit** in the WordPress admin.
3. Open the **OpenCalendarKit** menu.
4. Configure opening hours, closed days, event notice, and settings.
5. Insert the shortcodes where you want the public output to appear.

You can also create a release ZIP from the plugin directory and upload it through **Plugins > Add New > Upload Plugin**.

Recommended first setup order on a fresh test site:

1. Review **Settings**
2. Configure **Opening Hours**
3. Add **Closed Days**
4. Configure **Event Notice**
5. Place the public shortcodes on a test page

## Shortcodes

- `[okit_opening_hours]` renders the opening-hours table.
- `[okit_status_today]` renders the optional status output for the current day.
- `[okit_calendar]` renders the monthly calendar with closed days.
- `[okit_event_notice]` renders the optional event notice.

Shortcode attributes can explicitly override matching global settings where supported.

## Configuration

### Opening Hours

Use the **OpenCalendarKit > Opening Hours** screen to define recurring weekly opening hours and an optional note.

### Calendar and Closed Days

Use **OpenCalendarKit > Calendar** to manage closed days. Closed days affect the frontend calendar output and can include an optional reason.

### Event Notice

Use **OpenCalendarKit > Event Notice** to activate or deactivate a public notice without deleting its stored content.

### Settings

Use **OpenCalendarKit > Settings** to control:

- Plugin Language
- Show Status Today
- Show Calendar Legend
- Week Starts On
- Time Format Mode
- Show Opening Hours Title

## Internationalization and Localization

- Visible strings use the `open-calendar-kit` text domain.
- Date and time output follows the active OpenCalendarKit language decision.
- The plugin is designed for UTF-8/Unicode-safe content.
- By default, OpenCalendarKit uses the website language configured in WordPress.
- You can override the plugin language in **OpenCalendarKit > Settings** without changing the global website language.
- The same language source is applied consistently to admin pages, shortcodes, calendar month titles, weekday labels, legends, modal texts, and JS-localized labels.
- Translation files belong in `open-calendar-kit/languages/`.
- The plugin now ships with a starter template at `open-calendar-kit/languages/open-calendar-kit.pot`.
- Included example language files:
  - `open-calendar-kit-de_DE.po` / `.mo`
  - `open-calendar-kit-fr_FR.po` / `.mo`
- English uses the source strings and does not require a separate language pack.
- If a plugin-specific override has no matching translation file, OpenCalendarKit falls back cleanly to the WordPress website language and then to the built-in source strings.
- Editorial content remains user-managed content in this release and is not auto-translated by the plugin language setting:
  - event notice content
  - opening-hours note
  - closed-day reasons
- A later end-to-end acceptance step should still verify translation files and real locale switching in a full WordPress installation.

## Project Structure

- `open-calendar-kit/` contains the installable plugin.
- `open-calendar-kit-spec/` contains the functional specifications.
- `open-calendar-kit-tests/` contains the automated test layers.

## Packaging

For a release ZIP, package only the contents of `open-calendar-kit/` as the top-level plugin directory.

Distribution package should include:

- `open-calendar-kit.php`
- `bootstrap.php`
- `uninstall.php`
- `assets/`
- `includes/`
- `languages/`
- `readme.txt`
- `LICENSE`

Distribution package should not include development leftovers such as nested `.git` data. The plugin-level `.distignore` marks files that should stay out of a release archive.

## License

OpenCalendarKit is licensed under the GNU General Public License v2.0 or later. See [LICENSE](./LICENSE).

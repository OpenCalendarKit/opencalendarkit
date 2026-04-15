<?php
/**
 * Uninstall routine placeholder.
 *
 * Plugin data is intentionally retained after uninstall.
 *
 * @package OpenCalendarKit
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Benutzerdaten bewusst NICHT löschen.
 *
 * Grund:
 * - gepflegte Öffnungszeiten sollen bei Deaktivieren / Löschen / Neuinstallieren
 *   nicht verloren gehen
 * - sonst fallen Frontend und Backend wieder auf Defaultwerte zurück
 *
 * Falls später einmal ein echter "Daten restlos löschen"-Modus gewünscht ist,
 * sollte das bewusst per eigener Option/Checkbox gesteuert werden.
 */

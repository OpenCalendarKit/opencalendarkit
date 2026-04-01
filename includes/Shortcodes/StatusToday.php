<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_Shortcode_StatusToday {
    protected static function get_wordpress_timezone(): DateTimeZone {
        return wp_timezone();
    }

    protected static function get_current_datetime(DateTimeZone $tz) {
        return new DateTime('now', $tz);
    }

    protected static function get_time_range_for_row(array $row, DateTimeZone $tz, DateTime $now): array {
        $from = trim($row['from'] ?? '');
        $to = trim($row['to'] ?? '');

        $start = $from ? DateTime::createFromFormat('!H:i', $from, $tz) : null;
        $end   = (stripos($to, 'open') !== false) ? null : ($to ? DateTime::createFromFormat('!H:i', $to, $tz) : null);

        if ($start) {
            $start->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));
        }

        if ($end) {
            $end->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));
        }

        return [$start, $end];
    }

    public static function render($atts = []) {
        return OpenCalendarKit_I18n::with_locale(function () use ($atts) {
            $atts = shortcode_atts([
                'enabled'          => '',
                'time_format_mode' => '',
            ], $atts, 'okit_status_today');

            $is_enabled = BKIT_MVP_Settings::resolve_bool(
                $atts['enabled'],
                BKIT_MVP_Settings::is_enabled('show_status_today')
            );

            if (!$is_enabled) {
                return '';
            }

            $time_format = BKIT_MVP_Settings::get_time_format($atts['time_format_mode']);
            $tz = static::get_wordpress_timezone();
            $now = static::get_current_datetime($tz);
            $dow = intval($now->format('N'));
            $ymd = $now->format('Y-m-d');
            $closed_event = BKIT_MVP_ClosedDays_Admin::is_closed_on($ymd);
            $hours = BKIT_MVP_OpeningHours_Admin::get_hours();
            $row = $hours[$dow] ?? ['closed' => 1, 'from' => '', 'to' => ''];

            $label = __('Today closed', 'open-calendar-kit');
            $class = 'closed';

            if (!$closed_event && empty($row['closed'])) {
                [$start, $end] = static::get_time_range_for_row($row, $tz, $now);

                if (!$start) {
                    $label = __('Today closed', 'open-calendar-kit');
                    $class = 'closed';
                } elseif ($now < $start) {
                    /* translators: %s is a localized opening time, e.g. 09:00. */
                    $label = sprintf(__('Opens today at %s', 'open-calendar-kit'), $start->format($time_format));
                    $class = 'upcoming';
                } elseif (!$end || $now <= $end) {
                    if ($end) {
                        /* translators: %s is a localized closing time, e.g. 18:00. */
                        $label = sprintf(__('Open now until %s', 'open-calendar-kit'), $end->format($time_format));
                    } else {
                        $label = __('Open now', 'open-calendar-kit');
                    }
                    $class = 'open';
                } else {
                    $label = __('Closed now', 'open-calendar-kit');
                    $class = 'ended';
                }
            }
            ob_start(); ?>
            <div class="bkit-status-today" role="status" aria-live="polite">
                <div class="bkit-ui-callout bkit-ui-callout--status bkit-ui-callout--<?php echo esc_attr($class); ?>">
                    <div class="bkit-ui-callout__inner">
                        <span class="bkit-status-today__indicator" aria-hidden="true"></span>
                        <span class="bkit-status-today__text"><?php echo esc_html($label); ?></span>
                    </div>
                </div>
            </div>
            <?php return ob_get_clean();
        });
    }
}

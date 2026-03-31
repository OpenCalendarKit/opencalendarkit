<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_Shortcode_StatusToday {
    protected static function get_current_datetime(DateTimeZone $tz) {
        return new DateTime('now', $tz);
    }

    public static function render($atts = []) {
        return OpenCalendarKit_I18n::with_locale(function () use ($atts) {
            $atts = shortcode_atts([
                'timezone'         => wp_timezone_string(),
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
            $tz = new DateTimeZone($atts['timezone']); $now = static::get_current_datetime($tz); $dow = intval($now->format('N')); $ymd = $now->format('Y-m-d');
            $closed_event = BKIT_MVP_ClosedDays_Admin::is_closed_on($ymd); $hours = BKIT_MVP_OpeningHours_Admin::get_hours(); $row = $hours[$dow] ?? ['closed'=>1,'from'=>'','to'=>''];
            $label = __('Today closed', 'open-calendar-kit'); $class = 'ended';
            if ($closed_event || !empty($row['closed'])) { $label = __('Today closed', 'open-calendar-kit'); $class = 'closed'; }
            else {
                $from = trim($row['from'] ?? ''); $to = trim($row['to'] ?? '');
                $start = $from ? DateTime::createFromFormat('!H:i', $from, $tz) : null;
                $end   = (stripos($to, 'open') !== false) ? null : ($to ? DateTime::createFromFormat('!H:i', $to, $tz) : null);

                if ($start) {
                    $start->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));
                }

                if ($end) {
                    $end->setDate((int) $now->format('Y'), (int) $now->format('m'), (int) $now->format('d'));
                }

                if ($start && $now < $start) {
                    /* translators: %s is a localized opening time, e.g. 09:00. */
                    $label = sprintf(__('Today open from %s', 'open-calendar-kit'), $start->format($time_format));
                    $class='open';
                }
                else {
                    if (!$end) { if ($start && $now >= $start) { $label = __('Open now', 'open-calendar-kit'); $class='open'; } }
                    else {
                        if ($start && $end && $now >= $start && $now <= $end) {
                            /* translators: %s is a localized closing time, e.g. 18:00. */
                            $label = sprintf(__('Today open until %s', 'open-calendar-kit'), $end->format($time_format));
                            $class='open';
                        }
                        else { $label = __('Today closed', 'open-calendar-kit'); $class='ended'; }
                    }
                }
            }
            ob_start(); ?>
            <div class="bkit-status-today <?php echo esc_attr($class); ?>"><span class="badge"><?php echo esc_html($label); ?></span></div>
            <?php return ob_get_clean();
        });
    }
}

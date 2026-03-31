<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_Shortcode_StatusToday {
    public static function render($atts = []) {
        $atts = shortcode_atts(['timezone' => wp_timezone_string()], $atts, 'bk_status_today');
        $tz = new DateTimeZone($atts['timezone']); $now = new DateTime('now', $tz); $dow = intval($now->format('N')); $ymd = $now->format('Y-m-d');
        $closed_event = BKIT_MVP_ClosedDays_Admin::is_closed_on($ymd); $hours = BKIT_MVP_OpeningHours_Admin::get_hours(); $row = $hours[$dow] ?? ['closed'=>1,'from'=>'','to'=>''];
        // Sprache fix auf Deutsch (MVP)
        $label = 'Heute geschlossen'; $class = 'ended';
        if ($closed_event || !empty($row['closed'])) { $label = 'Heute geschlossen'; $class = 'closed'; }
        else {
            $from = trim($row['from'] ?? ''); $to = trim($row['to'] ?? '');
            $start = $from ? DateTime::createFromFormat('H:i', $from, $tz) : null;
            $end   = (stripos($to, 'open') !== false) ? null : ($to ? DateTime::createFromFormat('H:i', $to, $tz) : null);
            if ($start && $now < $start) { $label = sprintf('Heute geöffnet ab %s', $start->format('H:i')); $class='open'; }
            else {
                if (!$end) { if ($start && $now >= $start) { $label = 'Jetzt geöffnet'; $class='open'; } }
                else {
                    if ($start && $end && $now >= $start && $now <= $end) { $label = sprintf('Heute geöffnet bis %s', $end->format('H:i')); $class='open'; }
                    else { $label = 'Heute geschlossen'; $class='ended'; }
                }
            }
        }
        ob_start(); ?>
        <div class="bkit-status-today <?php echo esc_attr($class); ?>"><span class="badge"><?php echo esc_html($label); ?></span></div>
        <?php return ob_get_clean();
    }
}

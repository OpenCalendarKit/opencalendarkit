<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_Shortcode_OpeningHours {

    private static function get_day_names() {
        return [
            1 => __('Monday', 'open-calendar-kit'),
            2 => __('Tuesday', 'open-calendar-kit'),
            3 => __('Wednesday', 'open-calendar-kit'),
            4 => __('Thursday', 'open-calendar-kit'),
            5 => __('Friday', 'open-calendar-kit'),
            6 => __('Saturday', 'open-calendar-kit'),
            7 => __('Sunday', 'open-calendar-kit'),
        ];
    }

    private static function get_day_short_names() {
        return [
            1 => __('Mon', 'open-calendar-kit'),
            2 => __('Tue', 'open-calendar-kit'),
            3 => __('Wed', 'open-calendar-kit'),
            4 => __('Thu', 'open-calendar-kit'),
            5 => __('Fri', 'open-calendar-kit'),
            6 => __('Sat', 'open-calendar-kit'),
            7 => __('Sun', 'open-calendar-kit'),
        ];
    }

    private static function is_open_end($time) {
        $time = trim((string) $time);

        return $time === '' || stripos($time, 'open') !== false;
    }

    private static function get_open_end_label() {
        return __('Open end', 'open-calendar-kit');
    }

    private static function format_time_text($row, $time_format_mode = '') {
        if (!empty($row['closed'])) {
            return __('Closed', 'open-calendar-kit');
        }

        $from = BKIT_MVP_Settings::format_time_value($row['from'] ?? '', $time_format_mode);
        $to   = trim($row['to'] ?? '');
        $to   = self::is_open_end($to)
            ? self::get_open_end_label()
            : BKIT_MVP_Settings::format_time_value($to, $time_format_mode);

        return $from . ' – ' . $to;
    }

    public static function render($atts = []) {
        return OpenCalendarKit_I18n::with_locale(function () use ($atts) {
            if ( !class_exists('BKIT_MVP_OpeningHours_Admin') ) return '';

            $atts = shortcode_atts([
                'title'            => '',
                'time_format_mode' => '',
            ], $atts, 'okit_opening_hours');

            $hours = BKIT_MVP_OpeningHours_Admin::get_hours();
            $note  = BKIT_MVP_OpeningHours_Admin::get_note();

            $names = self::get_day_names();
            $show_title = BKIT_MVP_Settings::resolve_bool(
                $atts['title'],
                BKIT_MVP_Settings::is_enabled('show_opening_hours_title')
            );

            ob_start(); ?>
            <div class="bkit-opening-hours">
                <?php if ($show_title): ?>
                    <h3><?php echo esc_html__('Opening Hours', 'open-calendar-kit'); ?></h3>
                <?php endif; ?>

                <table class="bkit-oh-table" role="table">
                    <tbody>
                    <?php foreach ($names as $i=>$label):
                        $r = $hours[$i] ?? [];
                        $closed = !empty($r['closed']);
                        $timeText = self::format_time_text($r, $atts['time_format_mode']);
                    ?>
                        <tr>
                            <th scope="row" class="day"><?php echo esc_html($label); ?></th>
                            <td class="time<?php echo $closed ? ' is-closed' : ''; ?>"><?php echo esc_html($timeText); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (!empty($note)): ?>
                    <div class="bkit-opening-hours-note">
                        <?php echo nl2br(esc_html($note)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
        });
    }

}

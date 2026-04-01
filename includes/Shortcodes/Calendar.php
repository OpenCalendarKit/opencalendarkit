<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_Shortcode_Calendar {
    protected static function get_current_datetime(DateTimeZone $tz) {
        return new DateTime('now', $tz);
    }

    private static function get_month_title(DateTimeInterface $date, DateTimeZone $tz) {
        if (function_exists('wp_date')) {
            return wp_date('F Y', $date->getTimestamp(), $tz);
        }

        $local_date = new DateTime('@' . $date->getTimestamp());
        $local_date->setTimezone($tz);

        return $local_date->format('F Y');
    }

    private static function get_weekday_labels($week_starts_on) {
        global $wp_locale;

        $weekday_abbreviations = array_values($wp_locale->weekday_abbrev); // [Sun., Mon., Tue., ...]
        foreach ($weekday_abbreviations as &$weekday) {
            $weekday = preg_replace('/\.$/', '', (string) $weekday);
        }
        unset($weekday);

        if ($week_starts_on === 'sunday') {
            return $weekday_abbreviations;
        }

        return array_merge(array_slice($weekday_abbreviations, 1), [$weekday_abbreviations[0]]);
    }

    public static function render($atts = []) {
        return OpenCalendarKit_I18n::with_locale(function () use ($atts) {

            $atts = shortcode_atts([
                'month'          => '',
                'show_legend'    => '',
                'week_starts_on' => '',
                'max_width'      => '380px',
            ], $atts, 'okit_calendar');

            $tz = new DateTimeZone( wp_timezone_string() );
            $week_starts_on = BKIT_MVP_Settings::normalize_week_starts_on(
                $atts['week_starts_on'] !== '' ? $atts['week_starts_on'] : BKIT_MVP_Settings::get('week_starts_on')
            );
            $show_legend = BKIT_MVP_Settings::resolve_bool(
                $atts['show_legend'],
                BKIT_MVP_Settings::is_enabled('show_calendar_legend')
            );

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public month navigation is a read-only view parameter and is validated against YYYY-MM before use.
            $req_month = isset($_GET['okit_month']) ? sanitize_text_field(wp_unslash($_GET['okit_month'])) : '';
            if (!empty($req_month)) {
                $atts['month'] = $req_month;
            }

            $d = empty($atts['month'])
                ? (function () use ($tz) {
                    $current = static::get_current_datetime($tz);
                    $current->modify('first day of this month');
                    $current->setTime(0, 0, 0);
                    return $current;
                })()
                : DateTime::createFromFormat('!Y-m', $atts['month'], $tz);

            if (!$d) {
                $d = new DateTime('first day of this month', $tz);
            }

            $year        = (int) $d->format('Y');
            $month       = (int) $d->format('n');
            $daysInMonth = (int) $d->format('t');

            $dow0_to_N = static function (int $dow0): int { return ($dow0 === 0) ? 7 : $dow0; };

            if (function_exists('jddayofweek') && function_exists('cal_to_jd')) {
                $firstDow0 = jddayofweek(cal_to_jd(CAL_GREGORIAN, $month, 1, $year), 0);
                $firstDowN = $dow0_to_N($firstDow0);
            } else {
                $firstDowN = (int) (new DateTime(sprintf('%04d-%02d-01', $year, $month), $tz))->format('N');
            }
            $firstDayOffset = ($week_starts_on === 'sunday') ? ($firstDowN % 7) : ($firstDowN - 1);

            $hours = BKIT_MVP_OpeningHours_Admin::get_hours();
            $today = static::get_current_datetime($tz)->format('Y-m-d');

            $getHoursRow = function(int $dowN) use ($hours) {
                if (isset($hours[$dowN]) && is_array($hours[$dowN])) return $hours[$dowN];
                $dow0 = ($dowN + 6) % 7;
                if (isset($hours[$dow0]) && is_array($hours[$dow0])) return $hours[$dow0];
                return ['closed' => 0];
            };

            $cells = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

                if (function_exists('jddayofweek') && function_exists('cal_to_jd')) {
                    $dow0 = jddayofweek(cal_to_jd(CAL_GREGORIAN, $month, $day, $year), 0);
                    $dowN = $dow0_to_N($dow0);
                } else {
                    $dowN = (int) (new DateTime($date, $tz))->format('N');
                }

                $cfg = $getHoursRow($dowN);

                $closed_by_rule  = !empty($cfg['closed']);
                $closed_by_event = BKIT_MVP_ClosedDays_Admin::is_closed_on($date);

                $state = ($closed_by_rule || $closed_by_event) ? 'closed' : 'open';
                $past  = ($date < $today);

                $cells[] = ['day'=>$day, 'date'=>$date, 'state'=>$state, 'past'=>$past];
            }

            ob_start(); ?>
            <div class="bkit-calendar"
                 data-okit-calendar="1"
                 data-month="<?php echo esc_attr($d->format('Y-m')); ?>"
                 data-show-legend="<?php echo esc_attr($show_legend ? '1' : '0'); ?>"
                 data-week-starts-on="<?php echo esc_attr($week_starts_on); ?>"
                 data-max-width="<?php echo esc_attr($atts['max_width']); ?>"
                 style="max-width: <?php echo esc_attr($atts['max_width']); ?>">

            <?php
            $curFirst = static::get_current_datetime($tz); $curFirst->modify('first day of this month'); $curFirst->setTime(0,0,0);
            $next = (clone $d)->modify('+1 month');
            $prev = (clone $d)->modify('-1 month');

            $prev_allowed = ($prev >= $curFirst);
            $prev_q = add_query_arg(['okit_month' => $prev->format('Y-m')]);
            $next_q = add_query_arg(['okit_month' => $next->format('Y-m')]);
            ?>
            <div class="bkit-cal-head">
                <a class="bkit-nav prev<?php echo $prev_allowed ? '' : ' disabled'; ?>"
                   href="<?php echo $prev_allowed ? esc_url($prev_q) : '#'; ?>"
                   aria-label="<?php echo esc_attr__('Previous month', 'open-calendar-kit'); ?>">‹</a>
                <span class="bkit-cal-title"><?php echo esc_html( self::get_month_title($d, $tz) ); ?></span>
                <a class="bkit-nav next" href="<?php echo esc_url($next_q); ?>"
                   aria-label="<?php echo esc_attr__('Next month', 'open-calendar-kit'); ?>">›</a>
            </div>

            
<table class="bkit-cal-table" data-bk-cal>
    <thead>
    <tr>
    <?php
    foreach (self::get_weekday_labels($week_starts_on) as $wd) {
        echo '<th class="bkit-cell bkit-wd">'. esc_html($wd) .'</th>';
    }
    ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $dayIdx = 0;
    $totalCells = $firstDayOffset + count($cells);
    $weeks = (int) ceil($totalCells / 7);
    for ($w = 0; $w < $weeks; $w++) {
        echo '<tr>';
        for ($col = 1; $col <= 7; $col++) {
            $cellPos = ($w * 7) + $col; // 1..N
            if ($cellPos <= $firstDayOffset || $dayIdx >= count($cells)) {
                echo '<td class="bkit-cell bkit-empty"></td>';
                continue;
            }

            $c = $cells[$dayIdx++];
            // Closed days remain clickable so the frontend can show additional context.
            $isClickable = (!$c['past'] && $c['state'] === 'closed');

            // Reason für geschlossene Tage mitgeben
            $reason = '';
            if ($c['state'] === 'closed') {
                $reason = BKIT_MVP_ClosedDays_Admin::get_reason($c['date']);
            }

            $classes = 'bkit-cell day ' . ($c['past'] ? 'past disabled' : $c['state']) . ($isClickable ? ' clickable' : '');

            echo '<td class="bkit-td">';
            printf(
                '<button class="%s" data-date="%s"%s type="button" %s>' .
                '<span class="num">%d</span></button>',
                esc_attr($classes),
                esc_attr($c['date']),
                $reason !== '' ? ' data-reason="' . esc_attr($reason) . '"' : '',
                $c['past'] ? 'aria-disabled="true"' : '',
                (int) $c['day']
            );
            echo '</td>';
        }
        echo '</tr>';
    }
    ?>
    </tbody>
</table>

            <?php if ($show_legend): ?>
                <div class="bkit-legend">
                    <span class="legend open"><?php echo esc_html__('Open', 'open-calendar-kit'); ?></span>
                    <span class="legend closed"><?php echo esc_html__('Closed', 'open-calendar-kit'); ?></span>
                </div>
            <?php endif; ?>

            <!-- Modal -->
            <div class="bkit-modal" style="display:none;">
            <div class="bkit-modal-box bkit-modal-box--closed">
                <button class="bkit-close" type="button" aria-label="<?php echo esc_attr__('Close', 'open-calendar-kit'); ?>">×</button>

                <div class="bkit-closed-info" style="display:none;">
                <div class="bkit-closed-title"><?php echo esc_html__('Closed', 'open-calendar-kit'); ?></div>
                <div class="bkit-closed-date"></div>
                <div class="bkit-closed-reason"></div>

                <div class="bkit-modal-actions">
                    <button type="button" class="button bkit-cancel"><?php echo esc_html__('Close', 'open-calendar-kit'); ?></button>
                </div>
                </div>
            </div>
            </div>

            </div>
            <?php
            return ob_get_clean();
        });
    }
}

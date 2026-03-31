<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_Shortcode_Calendar {

    public static function render($atts = []) {

        $atts = shortcode_atts([
            'month'       => '',
            'show_legend' => '1',
            'max_width'   => '380px',
        ], $atts, 'bk_calendar');

        $tz = new DateTimeZone( wp_timezone_string() );

        $req_month = isset($_GET['bk_month']) ? sanitize_text_field($_GET['bk_month']) : '';
        if (!empty($req_month)) {
            $atts['month'] = $req_month;
        }

        $d = empty($atts['month'])
            ? new DateTime('first day of this month', $tz)
            : DateTime::createFromFormat('Y-m', $atts['month'], $tz);

        if (!$d) {
            $d = new DateTime('first day of this month', $tz);
        }

        $year        = (int) $d->format('Y');
        $month       = (int) $d->format('n');
        $daysInMonth = (int) $d->format('t');

        // 0=So..6=Sa -> 1=Mo..7=So
        $dow0_to_N = static function (int $dow0): int { return ($dow0 === 0) ? 7 : $dow0; };

        // erster Wochentag (1..7)
        if (function_exists('jddayofweek') && function_exists('cal_to_jd')) {
            $firstDow0 = jddayofweek(cal_to_jd(CAL_GREGORIAN, $month, 1, $year), 0);
            $firstDowN = $dow0_to_N($firstDow0);
        } else {
            $firstDowN = (int) (new DateTime(sprintf('%04d-%02d-01', $year, $month), $tz))->format('N');
        }

        $hours = BKIT_MVP_OpeningHours_Admin::get_hours();
        $today = (new DateTime('now', $tz))->format('Y-m-d');

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
        <div class="bkit-calendar" data-bkit-calendar="1" data-month="<?php echo esc_attr($d->format('Y-m')); ?>" style="max-width: <?php echo esc_attr($atts['max_width']); ?>">

            <?php
            $curFirst = new DateTime('first day of this month', $tz); $curFirst->setTime(0,0,0);
            $next = (clone $d)->modify('+1 month');
            $prev = (clone $d)->modify('-1 month');

            $prev_allowed = ($prev >= $curFirst);
            $prev_q = esc_url( add_query_arg(['bk_month' => $prev->format('Y-m')]) );
            $next_q = esc_url( add_query_arg(['bk_month' => $next->format('Y-m')]) );
            ?>
            <div class="bkit-cal-head">
                <a class="bkit-nav prev<?php echo $prev_allowed ? '' : ' disabled'; ?>"
                   href="<?php echo $prev_allowed ? $prev_q : '#'; ?>"
                   aria-label="<?php echo esc_attr('Vorheriger Monat'); ?>">‹</a>
                <span class="bkit-cal-title"><?php echo esc_html( date_i18n('F Y', $d->getTimestamp()) ); ?></span>
                <a class="bkit-nav next" href="<?php echo $next_q; ?>"
                   aria-label="<?php echo esc_attr('Nächster Monat'); ?>">›</a>
            </div>

            
<table class="bkit-cal-table" data-bk-cal>
    <thead>
    <tr>
    <?php
    // Wochentagsköpfe (Montag zuerst) – ohne Punkte (Mo statt Mo.)
    global $wp_locale;
    $wd_abbr      = array_values($wp_locale->weekday_abbrev);             // [So., Mo., Di., ...]
    $wd_mon_first = array_merge(array_slice($wd_abbr, 1), [$wd_abbr[0]]); // [Mo..Sa, So]
    foreach ($wd_mon_first as $wd) {
        $wd = preg_replace('/\.$/', '', (string) $wd);
        echo '<th class="bkit-cell bkit-wd">'. esc_html($wd) .'</th>';
    }
    ?>
    </tr>
    </thead>
    <tbody>
    <?php
    $dayIdx = 0;
    $totalCells = ($firstDowN - 1) + count($cells);
    $weeks = (int) ceil($totalCells / 7);
    for ($w = 0; $w < $weeks; $w++) {
        echo '<tr>';
        for ($col = 1; $col <= 7; $col++) {
            $cellPos = ($w * 7) + $col; // 1..N
            if ($cellPos < $firstDowN || $dayIdx >= count($cells)) {
                echo '<td class="bkit-cell bkit-empty"></td>';
                continue;
            }

            $c = $cells[$dayIdx++];
            // MVP: Keine Reservierungs-Modalität. Klick nur auf geschlossene Tage (Info anzeigen).
            $isClickable = (!$c['past'] && $c['state'] === 'closed');

            // Reason für geschlossene Tage mitgeben
            $reasonAttr = '';
            if ($c['state'] === 'closed') {
                $reason = BKIT_MVP_ClosedDays_Admin::get_reason($c['date']);
                if ($reason !== '') {
                    $reasonAttr = ' data-reason="' . esc_attr($reason) . '"';
                }
            }

            $classes = 'bkit-cell day ' . ($c['past'] ? 'past disabled' : $c['state']) . ($isClickable ? ' clickable' : '');

            echo '<td class="bkit-td">';
            printf(
                '<button class="%s" data-date="%s"%s type="button" %s>' .
                '<span class="num">%d</span></button>',
                esc_attr($classes),
                esc_attr($c['date']),
                $reasonAttr,
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

            <?php if ($atts['show_legend'] === '1'): ?>
                <div class="bkit-legend">
                    <span class="legend open"><?php echo esc_html('Geöffnet'); ?></span>
                    <span class="legend closed"><?php echo esc_html('Geschlossen'); ?></span>
                </div>
            <?php endif; ?>

            <!-- Modal -->
            <div class="bkit-modal" style="display:none;">
            <div class="bkit-modal-box bkit-modal-box--closed">
                <button class="bkit-close" type="button" aria-label="<?php echo esc_attr('Schließen'); ?>">×</button>

                <div class="bkit-closed-info" style="display:none;">
                <div class="bkit-closed-title"><?php echo esc_html('Geschlossen'); ?></div>
                <div class="bkit-closed-date"></div>
                <div class="bkit-closed-reason"></div>

                <div class="bkit-modal-actions">
                    <button type="button" class="button bkit-cancel"><?php echo esc_html('Schließen'); ?></button>
                </div>
                </div>
            </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}

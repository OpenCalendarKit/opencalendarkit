<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_ClosedDays_Admin {
    private const STATE_CLOSED = 'closed';
    private const STATE_OPEN = 'open';

    private static function get_month_title(DateTimeInterface $date, DateTimeZone $tz) {
        if (function_exists('wp_date')) {
            return wp_date('F Y', $date->getTimestamp(), $tz);
        }

        $local_date = new DateTime('@' . $date->getTimestamp());
        $local_date->setTimezone($tz);

        return $local_date->format('F Y');
    }

    private static function normalize_exception_state($state): string {
        return $state === self::STATE_OPEN ? self::STATE_OPEN : self::STATE_CLOSED;
    }

    private static function build_exception_title(string $date, string $state): string {
        if ($state === self::STATE_OPEN) {
            /* translators: %s is a date in YYYY-MM-DD format that is opened exceptionally. */
            return sprintf(__('Open exceptionally: %s', 'open-calendar-kit'), $date);
        }

        /* translators: %s is a closed-day date in YYYY-MM-DD format. */
        return sprintf(__('Closed: %s', 'open-calendar-kit'), $date);
    }

    /* ====== CPT & Listen-Ansicht ====== */
    public static function register_cpt() {
        OpenCalendarKit_I18n::with_locale(function () {
            $labels = [
                'name'          => __('Closed Days', 'open-calendar-kit'),
                'singular_name' => __('Closed Day', 'open-calendar-kit')
            ];

            register_post_type('bk_closed_day', [
                'labels'       => $labels,
                'public'       => false,
                'show_ui'      => true,
                'menu_icon'    => 'dashicons-no-alt',
                'supports'     => ['title'],
                'show_in_menu' => false,
            ]);

            add_filter('manage_bk_closed_day_posts_columns', [__CLASS__, 'cols']);
            add_action('manage_bk_closed_day_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
            add_filter('manage_edit-bk_closed_day_sortable_columns', [__CLASS__, 'sortable']);
            add_action('pre_get_posts', [__CLASS__, 'default_order']);

            add_action('wp_ajax_okit_save_closed_day',    [__CLASS__, 'ajax_save']);
            add_action('wp_ajax_okit_delete_closed_day',  [__CLASS__, 'ajax_delete']);
            add_action('wp_ajax_okit_save_open_exception', [__CLASS__, 'ajax_save_open_exception']);
            add_action('wp_ajax_okit_delete_open_exception', [__CLASS__, 'ajax_delete_open_exception']);
        });
    }

    public static function cols($cols){
        return OpenCalendarKit_I18n::with_locale(function () use ($cols) {
            $new = [];
            $new['cb']         = $cols['cb'];
            $new['title']      = __('Title', 'open-calendar-kit');
            $new['_bk_date']   = __('Date', 'open-calendar-kit');
            $new['_bk_reason'] = __('Reason', 'open-calendar-kit');
            $new['_edit']      = __('Actions', 'open-calendar-kit');
            return $new;
        });
    }

    public static function col_content($col, $post_id){
        OpenCalendarKit_I18n::with_locale(function () use ($col, $post_id) {
            if ($col === '_bk_date')   { echo esc_html(get_post_meta($post_id, '_bk_date', true));   return; }
            if ($col === '_bk_reason') { echo esc_html(get_post_meta($post_id, '_bk_reason', true)); return; }
            if ($col === '_edit') {
                $url = admin_url('post.php?post='.(int)$post_id.'&action=edit');
                echo '<a class="button" href="'.esc_url($url).'">'.esc_html__('Edit', 'open-calendar-kit').'</a>';
            }
        });
    }

    public static function sortable($cols){ $cols['_bk_date']='_bk_date'; return $cols; }

    public static function default_order($q){
        if (!is_admin() || $q->get('post_type') !== 'bk_closed_day') return;
        if (!$q->get('orderby')){
            $q->set('meta_key','_bk_date');
            $q->set('orderby','meta_value');
            $q->set('order','ASC');
        } elseif ($q->get('orderby') === '_bk_date'){
            $q->set('meta_key','_bk_date');
            $q->set('orderby','meta_value');
        }
    }

    /* ====== Metabox ====== */
    public static function register_metabox() {
        OpenCalendarKit_I18n::with_locale(function () {
            add_meta_box('bk_closed_day_meta', __('Closed Day Details', 'open-calendar-kit'), [__CLASS__, 'render_metabox'], 'bk_closed_day', 'normal', 'default');
        });
    }

    public static function render_metabox($post) {
        OpenCalendarKit_I18n::with_locale(function () use ($post) {
            wp_nonce_field('bk_closed_day_meta', 'bk_closed_day_meta_nonce');
            $date   = get_post_meta($post->ID, '_bk_date', true);
            $reason = get_post_meta($post->ID, '_bk_reason', true); ?>
            <input type="hidden" name="bk_state" value="<?php echo esc_attr(self::normalize_exception_state(get_post_meta($post->ID, '_bk_state', true))); ?>" />
            <p><label for="bk_date"><strong><?php esc_html_e('Date (YYYY-MM-DD)', 'open-calendar-kit'); ?></strong></label><br/>
            <input type="date" id="bk_date" name="bk_date" value="<?php echo esc_attr($date); ?>" /></p>
            <p><label for="bk_reason"><strong><?php esc_html_e('Reason (optional)', 'open-calendar-kit'); ?></strong></label><br/>
            <input type="text" id="bk_reason" name="bk_reason" value="<?php echo esc_attr($reason); ?>" class="regular-text" /></p>
            <?php
        });
    }

    public static function save_metabox($post_id) {
        $nonce = isset($_POST['bk_closed_day_meta_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['bk_closed_day_meta_nonce']))
            : '';
        if ( ! $nonce || ! wp_verify_nonce($nonce, 'bk_closed_day_meta')) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can('edit_post', $post_id) ) return;

        $date   = isset($_POST['bk_date']) ? sanitize_text_field(wp_unslash($_POST['bk_date'])) : '';
        $reason = isset($_POST['bk_reason']) ? sanitize_text_field(wp_unslash($_POST['bk_reason'])) : '';
        $state  = isset($_POST['bk_state']) ? self::normalize_exception_state(sanitize_text_field(wp_unslash($_POST['bk_state']))) : self::normalize_exception_state(get_post_meta($post_id, '_bk_state', true));
        update_post_meta($post_id, '_bk_date', $date);
        update_post_meta($post_id, '_bk_reason', $reason);
        update_post_meta($post_id, '_bk_state', $state);

        if ( empty(get_the_title($post_id)) && $date ) {
            wp_update_post(['ID'=>$post_id, 'post_title'=> self::build_exception_title($date, $state)]);
        }
    }

    /* ====== Helper ====== */
    private static function get_exception_post_ids($date): array {
        $q = new WP_Query([
            'post_type'      => 'bk_closed_day',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => [[ 'key'=>'_bk_date','value'=>$date,'compare'=>'=' ]],
        ]);

        $ids = !empty($q->posts) ? array_map('intval', $q->posts) : [];
        wp_reset_postdata();
        return $ids;
    }

    private static function find_exception_post_id($date, $state) {
        $normalized_state = self::normalize_exception_state($state);

        foreach (self::get_exception_post_ids($date) as $post_id) {
            $post_state = self::normalize_exception_state(get_post_meta($post_id, '_bk_state', true));
            if ($post_state === $normalized_state) {
                return (int) $post_id;
            }
        }

        return 0;
    }

    private static function delete_exception_post($date, $state): void {
        $post_id = self::find_exception_post_id($date, $state);
        if ($post_id) {
            wp_delete_post($post_id, true);
        }
    }

    public static function is_closed_on($ymd) {
        return self::find_exception_post_id((string) $ymd, self::STATE_CLOSED) > 0;
    }

    public static function is_open_exception_on($ymd) {
        return self::find_exception_post_id((string) $ymd, self::STATE_OPEN) > 0;
    }

    public static function get_reason($ymd){
        $post_id = self::find_exception_post_id((string) $ymd, self::STATE_CLOSED);
        if (!$post_id) {
            return '';
        }

        return trim((string) get_post_meta($post_id, '_bk_reason', true));
    }

    public static function register_menu() {
        OpenCalendarKit_I18n::with_locale(function () {
            add_submenu_page(
                OpenCalendarKit_Plugin::MENU_SLUG,
                __('Calendar', 'open-calendar-kit'),
                __('Calendar', 'open-calendar-kit'),
                OpenCalendarKit_Plugin::CAP_MANAGE,
                OpenCalendarKit_Plugin::PAGE_CALENDAR,
                [__CLASS__, 'render_admin_page']
            );
        });
    }

    /* ====== Admin-Kalender ====== */
    public static function render_admin_page(){
        OpenCalendarKit_I18n::with_locale(function () {

        $tz = new DateTimeZone( wp_timezone_string() );
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only month navigation in the admin calendar uses a validated month query parameter and does not change state.
        $req_month = isset($_GET['okit_month']) ? sanitize_text_field(wp_unslash($_GET['okit_month'])) : '';
        $d = $req_month ? DateTime::createFromFormat('!Y-m', $req_month, $tz) : new DateTime('first day of this month', $tz);
        if (!$d) $d = new DateTime('first day of this month', $tz);

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
            $closed_by_event = self::is_closed_on($date);
            $open_override = self::is_open_exception_on($date);
            $state = ($closed_by_event || ($closed_by_rule && !$open_override)) ? 'closed' : 'open';
            $past  = ($date < $today);
            $reason = $closed_by_event ? self::get_reason($date) : '';
            $cells[] = [
                'day'=>$day,
                'date'=>$date,
                'state'=>$state,
                'past'=>$past,
                'reason'=>$reason,
                'closed_event'=>$closed_by_event,
                'closed_rule'=>$closed_by_rule,
                'open_override'=>$open_override,
            ];
        }

        echo '<div class="wrap"><h1>'.esc_html__('Calendar', 'open-calendar-kit').'</h1>';
        echo '<div class="bkit-calendar bkit-admin-cal" style="max-width:380px;">';

        // Navigation
        $next = (clone $d)->modify('+1 month');
        $prev = (clone $d)->modify('-1 month');
        $prev_q = add_query_arg(['okit_month' => $prev->format('Y-m')]);
        $next_q = add_query_arg(['okit_month' => $next->format('Y-m')]);

        echo '<div class="bkit-cal-head">';
        echo '<a class="bkit-nav prev" href="'.esc_url($prev_q).'">‹</a>';
        echo '<span class="bkit-cal-title">'.esc_html( self::get_month_title($d, $tz) ).'</span>';
        echo '<a class="bkit-nav next" href="'.esc_url($next_q).'">›</a>';
        echo '</div>';

        echo '<div class="bkit-grid">';
        global $wp_locale;
        $wd_abbr = array_values($wp_locale->weekday_abbrev); // [So, Mo, Di, Mi, Do, Fr, Sa]
        $wd_mon_first = array_merge(array_slice($wd_abbr,1), [$wd_abbr[0]]);
        foreach ($wd_mon_first as $wd) echo '<div class="bkit-cell bkit-wd">'.esc_html($wd).'</div>';

        for ($i=1; $i < $firstDowN; $i++) echo '<div class="bkit-cell bkit-empty"></div>';

        foreach ($cells as $c) {
            $classes = 'bkit-cell day ' . ($c['past'] ? 'past' : $c['state']);
            printf(
                '<button class="%s" data-date="%s"%s%s%s%s type="button"><span class="num">%d</span></button>',
                esc_attr($classes),
                esc_attr($c['date']),
                $c['reason'] !== '' ? ' data-reason="' . esc_attr($c['reason']) . '"' : '',
                ' data-closed-event="' . esc_attr($c['closed_event'] ? '1' : '0') . '"',
                ' data-closed-rule="' . esc_attr($c['closed_rule'] ? '1' : '0') . '"',
                ' data-open-override="' . esc_attr($c['open_override'] ? '1' : '0') . '"',
                (int)$c['day']
            );
        }
        echo '</div>'; // grid

        // Modal
        ?>
        <div id="bkit-closedday-modal" class="bkit-modal" style="display:none;">
          <div class="bkit-modal-box">
            <div class="bkit-modal-head">
              <span class="title"><?php esc_html_e('Set Closed Day', 'open-calendar-kit'); ?></span>
              <button type="button" class="bkit-close" aria-label="<?php esc_attr_e('Close', 'open-calendar-kit'); ?>">×</button>
            </div>
            <form id="bkit-closedday-form">
              <div class="row">
                <label><?php esc_html_e('Date', 'open-calendar-kit'); ?></label>
                <input type="date" name="date" value="">
              </div>
              <div class="row">
                <label><?php esc_html_e('Reason (optional)', 'open-calendar-kit'); ?></label>
                <input type="text" name="reason" value="">
              </div>
              <div class="actions">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save closed day', 'open-calendar-kit'); ?></button>
                <button id="bkit-open-day" type="button" class="button" style="display:none;">
                  <?php esc_html_e('Open day again', 'open-calendar-kit'); ?>
                </button>
                <button id="bkit-toggle-open-exception" type="button" class="button" style="display:none;"></button>
                <button id="bkit-cancel" type="button" class="button"><?php esc_html_e('Cancel', 'open-calendar-kit'); ?></button>
              </div>
              <div class="bkit-feedback" style="display:none;"></div>
            </form>
          </div>
        </div>
        <?php
        echo '</div></div>';
        });
    }

    /* ====== AJAX: Save ====== */
    public static function ajax_save() {
        OpenCalendarKit_I18n::with_locale(function () {
            check_ajax_referer('okit_admin','nonce');

            if ( ! current_user_can(OpenCalendarKit_Plugin::CAP_MANAGE) ) {
                wp_send_json_error(['msg' => __('Not allowed', 'open-calendar-kit')], 403);
            }

            $date   = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
            $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : '';

            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                wp_send_json_error(['msg' => __('Invalid date', 'open-calendar-kit')], 400);
            }

            $post_id = self::find_exception_post_id($date, self::STATE_CLOSED);

            if ($post_id) {
                update_post_meta($post_id, '_bk_reason', $reason);
                update_post_meta($post_id, '_bk_state', self::STATE_CLOSED);
                if (empty(get_the_title($post_id))) {
                    wp_update_post(['ID'=>$post_id, 'post_title'=> self::build_exception_title($date, self::STATE_CLOSED)]);
                }
                $open_override_post_id = self::find_exception_post_id($date, self::STATE_OPEN);
                if ($open_override_post_id) {
                    wp_delete_post($open_override_post_id, true);
                }
                wp_send_json_success(['msg' => __('Updated closed day', 'open-calendar-kit')]);
            } else {
                $post_id = wp_insert_post([
                    'post_type'   => 'bk_closed_day',
                    'post_status' => 'publish',
                    'post_title'  => self::build_exception_title($date, self::STATE_CLOSED),
                ], true);

                if (is_wp_error($post_id)) {
                    wp_send_json_error(['msg'=>$post_id->get_error_message()], 500);
                }

                update_post_meta($post_id, '_bk_date', $date);
                update_post_meta($post_id, '_bk_reason', $reason);
                update_post_meta($post_id, '_bk_state', self::STATE_CLOSED);
                $open_override_post_id = self::find_exception_post_id($date, self::STATE_OPEN);
                if ($open_override_post_id) {
                    wp_delete_post($open_override_post_id, true);
                }
                wp_send_json_success(['msg' => __('Created closed day', 'open-calendar-kit')]);
            }
        });
    }

    /* ====== AJAX: Delete (Open again) ====== */
    public static function ajax_delete() {
        OpenCalendarKit_I18n::with_locale(function () {
            check_ajax_referer('okit_admin','nonce');

            if ( ! current_user_can(OpenCalendarKit_Plugin::CAP_MANAGE) ) {
                wp_send_json_error(['msg' => __('Not allowed', 'open-calendar-kit')], 403);
            }

            $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                wp_send_json_error(['msg' => __('Invalid date', 'open-calendar-kit')], 400);
            }

            $post_id = self::find_exception_post_id($date, self::STATE_CLOSED);
            if (!$post_id) {
                wp_send_json_success(['msg' => __('Day is already open', 'open-calendar-kit')]);
            }

            wp_delete_post($post_id, true);
            wp_send_json_success(['msg' => __('Closed day removed (open again)', 'open-calendar-kit')]);
        });
    }

    public static function ajax_save_open_exception() {
        OpenCalendarKit_I18n::with_locale(function () {
            check_ajax_referer('okit_admin','nonce');

            if ( ! current_user_can(OpenCalendarKit_Plugin::CAP_MANAGE) ) {
                wp_send_json_error(['msg' => __('Not allowed', 'open-calendar-kit')], 403);
            }

            $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';

            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                wp_send_json_error(['msg' => __('Invalid date', 'open-calendar-kit')], 400);
            }

            $post_id = self::find_exception_post_id($date, self::STATE_OPEN);

            if ($post_id) {
                update_post_meta($post_id, '_bk_state', self::STATE_OPEN);
                update_post_meta($post_id, '_bk_reason', '');
                self::delete_exception_post($date, self::STATE_CLOSED);
                wp_send_json_success(['msg' => __('Exceptional opening saved', 'open-calendar-kit')]);
            }

            $post_id = wp_insert_post([
                'post_type'   => 'bk_closed_day',
                'post_status' => 'publish',
                'post_title'  => self::build_exception_title($date, self::STATE_OPEN),
            ], true);

            if (is_wp_error($post_id)) {
                wp_send_json_error(['msg'=>$post_id->get_error_message()], 500);
            }

            update_post_meta($post_id, '_bk_date', $date);
            update_post_meta($post_id, '_bk_reason', '');
            update_post_meta($post_id, '_bk_state', self::STATE_OPEN);
            self::delete_exception_post($date, self::STATE_CLOSED);
            wp_send_json_success(['msg' => __('Exceptional opening saved', 'open-calendar-kit')]);
        });
    }

    public static function ajax_delete_open_exception() {
        OpenCalendarKit_I18n::with_locale(function () {
            check_ajax_referer('okit_admin','nonce');

            if ( ! current_user_can(OpenCalendarKit_Plugin::CAP_MANAGE) ) {
                wp_send_json_error(['msg' => __('Not allowed', 'open-calendar-kit')], 403);
            }

            $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : '';
            if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                wp_send_json_error(['msg' => __('Invalid date', 'open-calendar-kit')], 400);
            }

            $post_id = self::find_exception_post_id($date, self::STATE_OPEN);
            if (!$post_id) {
                wp_send_json_success(['msg' => __('Exceptional opening already removed', 'open-calendar-kit')]);
            }

            wp_delete_post($post_id, true);
            wp_send_json_success(['msg' => __('Exceptional opening removed', 'open-calendar-kit')]);
        });
    }
}

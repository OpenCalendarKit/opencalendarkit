<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_ClosedDays_Admin {

    /* ====== CPT & Listen-Ansicht ====== */
    public static function register_cpt() {
        $labels = [
            'name'          => __('Closed Days', 'bookingkit-mvp'),
            'singular_name' => __('Closed Day', 'bookingkit-mvp')
        ];

        register_post_type('bk_closed_day', [
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => true,
            'menu_icon'    => 'dashicons-no-alt',
            'supports'     => ['title'],
            // Keep CPT for storage/queries, but hide it from the admin menu.
            'show_in_menu' => false,
        ]);

        add_filter('manage_bk_closed_day_posts_columns', [__CLASS__, 'cols']);
        add_action('manage_bk_closed_day_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
        add_filter('manage_edit-bk_closed_day_sortable_columns', [__CLASS__, 'sortable']);
        add_action('pre_get_posts', [__CLASS__, 'default_order']);

        // AJAX speichern + löschen
        add_action('wp_ajax_bkit_mvp_save_closed_day',    [__CLASS__, 'ajax_save']);
        add_action('wp_ajax_bkit_mvp_delete_closed_day',  [__CLASS__, 'ajax_delete']);
    }

    public static function cols($cols){
        $new = [];
        $new['cb']         = $cols['cb'];
        $new['title']      = __('Title');
        $new['_bk_date']   = __('Date','bookingkit-mvp');
        $new['_bk_reason'] = __('Reason','bookingkit-mvp');
        $new['_edit']      = __('Actions','bookingkit-mvp');
        return $new;
    }

    public static function col_content($col, $post_id){
        if ($col === '_bk_date')   { echo esc_html(get_post_meta($post_id, '_bk_date', true));   return; }
        if ($col === '_bk_reason') { echo esc_html(get_post_meta($post_id, '_bk_reason', true)); return; }
        if ($col === '_edit') {
            $url = admin_url('post.php?post='.(int)$post_id.'&action=edit');
            echo '<a class="button" href="'.esc_url($url).'">'.esc_html__('Edit','bookingkit-mvp').'</a>';
        }
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
        add_meta_box('bk_closed_day_meta', __('Closed Day Details', 'bookingkit-mvp'), [__CLASS__, 'render_metabox'], 'bk_closed_day', 'normal', 'default');
    }

    public static function render_metabox($post) {
        wp_nonce_field('bk_closed_day_meta', 'bk_closed_day_meta_nonce');
        $date   = get_post_meta($post->ID, '_bk_date', true);
        $reason = get_post_meta($post->ID, '_bk_reason', true); ?>
        <p><label for="bk_date"><strong><?php esc_html_e('Date (YYYY-MM-DD)', 'bookingkit-mvp'); ?></strong></label><br/>
        <input type="date" id="bk_date" name="bk_date" value="<?php echo esc_attr($date); ?>" /></p>
        <p><label for="bk_reason"><strong><?php esc_html_e('Reason (optional)', 'bookingkit-mvp'); ?></strong></label><br/>
        <input type="text" id="bk_reason" name="bk_reason" value="<?php echo esc_attr($reason); ?>" class="regular-text" /></p>
        <?php
    }

    public static function save_metabox($post_id) {
        if ( !isset($_POST['bk_closed_day_meta_nonce']) || !wp_verify_nonce($_POST['bk_closed_day_meta_nonce'], 'bk_closed_day_meta')) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can('edit_post', $post_id) ) return;

        $date   = sanitize_text_field($_POST['bk_date'] ?? '');
        $reason = sanitize_text_field($_POST['bk_reason'] ?? '');
        update_post_meta($post_id, '_bk_date', $date);
        update_post_meta($post_id, '_bk_reason', $reason);

        if ( empty(get_the_title($post_id)) && $date ) {
            wp_update_post(['ID'=>$post_id, 'post_title'=> sprintf(__('Closed: %s','bookingkit-mvp'), $date)]);
        }
    }

    /* ====== Helper ====== */
    public static function is_closed_on($ymd) {
        $q = new WP_Query([
            'post_type'      => 'bk_closed_day',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [[ 'key' => '_bk_date', 'value' => $ymd, 'compare' => '=' ]],
        ]);
        $closed = $q->have_posts();
        wp_reset_postdata();
        return $closed;
    }

    public static function get_reason($ymd){
        $q = new WP_Query([
            'post_type'      => 'bk_closed_day',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'meta_query'     => [[ 'key' => '_bk_date', 'value' => $ymd, 'compare' => '=' ]],
        ]);
        $reason = '';
        if ($q->have_posts()){
            $post_id = $q->posts[0]->ID;
            $reason  = (string) get_post_meta($post_id, '_bk_reason', true);
        }
        wp_reset_postdata();
        return trim($reason);
    }

    private static function find_closed_day_post_id($date) {
        $q = new WP_Query([
            'post_type'      => 'bk_closed_day',
            'posts_per_page' => 1,
            'post_status'    => 'publish',
            'fields'         => 'ids',
            'meta_query'     => [[ 'key'=>'_bk_date','value'=>$date,'compare'=>'=' ]]
        ]);
        $id = (!empty($q->posts) ? (int)$q->posts[0] : 0);
        wp_reset_postdata();
        return $id;
    }

    public static function register_menu() {
        add_submenu_page(
            'calendarkit',
            __('Calendar', 'bookingkit-mvp'),
            __('Calendar', 'bookingkit-mvp'),
            'calendarkit_manage',
            'calendarkit_calendar',
            [__CLASS__, 'render_admin_page']
        );
    }

    /* ====== Admin-Kalender ====== */
    public static function render_admin_page(){

        $tz = new DateTimeZone( wp_timezone_string() );
        $req_month = isset($_GET['bk_month']) ? sanitize_text_field($_GET['bk_month']) : '';
        $d = $req_month ? DateTime::createFromFormat('Y-m', $req_month, $tz) : new DateTime('first day of this month', $tz);
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
            $state = ($closed_by_rule || $closed_by_event) ? 'closed' : 'open';
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
            ];
        }

        echo '<div class="wrap"><h1>'.esc_html__('Calendar','bookingkit-mvp').'</h1>';
        echo '<div class="bkit-calendar bkit-admin-cal" style="max-width:380px;">';

        // Navigation
        $next = (clone $d)->modify('+1 month');
        $prev = (clone $d)->modify('-1 month');
        $prev_q = esc_url( add_query_arg(['bk_month' => $prev->format('Y-m')]) );
        $next_q = esc_url( add_query_arg(['bk_month' => $next->format('Y-m')]) );

        echo '<div class="bkit-cal-head">';
        echo '<a class="bkit-nav prev" href="'.$prev_q.'">‹</a>';
        echo '<span class="bkit-cal-title">'.esc_html( date_i18n('F Y', $d->getTimestamp()) ).'</span>';
        echo '<a class="bkit-nav next" href="'.$next_q.'">›</a>';
        echo '</div>';

        echo '<div class="bkit-grid">';
        global $wp_locale;
        $wd_abbr = array_values($wp_locale->weekday_abbrev); // [So, Mo, Di, Mi, Do, Fr, Sa]
        $wd_mon_first = array_merge(array_slice($wd_abbr,1), [$wd_abbr[0]]);
        foreach ($wd_mon_first as $wd) echo '<div class="bkit-cell bkit-wd">'.esc_html($wd).'</div>';

        for ($i=1; $i < $firstDowN; $i++) echo '<div class="bkit-cell bkit-empty"></div>';

        foreach ($cells as $c) {
            $classes = 'bkit-cell day ' . ($c['past'] ? 'past' : $c['state']);
            $reasonAttr = $c['reason'] !== '' ? ' data-reason="'.esc_attr($c['reason']).'"' : '';
            $closedEventAttr = ' data-closed-event="'.($c['closed_event'] ? '1' : '0').'"';
            $closedRuleAttr  = ' data-closed-rule="'.($c['closed_rule'] ? '1' : '0').'"';

            printf(
                '<button class="%s" data-date="%s"%s%s%s type="button"><span class="num">%d</span></button>',
                esc_attr($classes),
                esc_attr($c['date']),
                $reasonAttr,
                $closedEventAttr,
                $closedRuleAttr,
                (int)$c['day']
            );
        }
        echo '</div>'; // grid

        // Modal
        ?>
        <div id="bkit-closedday-modal" class="bkit-modal" style="display:none;">
          <div class="bkit-modal-box">
            <div class="bkit-modal-head">
              <span class="title"><?php esc_html_e('Set Closed Day','bookingkit-mvp'); ?></span>
              <button type="button" class="bkit-close" aria-label="<?php esc_attr_e('Close','bookingkit-mvp'); ?>">×</button>
            </div>
            <form id="bkit-closedday-form">
              <div class="row">
                <label><?php esc_html_e('Date','bookingkit-mvp'); ?></label>
                <input type="date" name="date" value="">
              </div>
              <div class="row">
                <label><?php esc_html_e('Reason (optional)','bookingkit-mvp'); ?></label>
                <input type="text" name="reason" value="">
              </div>
              <div class="actions">
                <button type="submit" class="button button-primary"><?php esc_html_e('Save closed day','bookingkit-mvp'); ?></button>
                <button id="bkit-open-day" type="button" class="button" style="display:none;">
                  <?php esc_html_e('Open day again','bookingkit-mvp'); ?>
                </button>
                <button id="bkit-cancel" type="button" class="button"><?php esc_html_e('Cancel','bookingkit-mvp'); ?></button>
              </div>
              <div class="bkit-feedback" style="display:none;"></div>
            </form>
          </div>
        </div>
        <?php
        echo '</div></div>';
    }

    /* ====== AJAX: Save ====== */
    public static function ajax_save() {
        check_ajax_referer('bkit_mvp_admin','nonce');

        if ( ! current_user_can('calendarkit_manage') ) {
            wp_send_json_error(['msg' => __('Not allowed','bookingkit-mvp')], 403);
        }

        $date   = sanitize_text_field($_POST['date'] ?? '');
        $reason = sanitize_text_field($_POST['reason'] ?? '');

        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(['msg'=>__('Invalid date','bookingkit-mvp')], 400);
        }

        $post_id = self::find_closed_day_post_id($date);

        if ($post_id) {
            update_post_meta($post_id, '_bk_reason', $reason);
            if (empty(get_the_title($post_id))) {
                wp_update_post(['ID'=>$post_id, 'post_title'=> sprintf(__('Closed: %s','bookingkit-mvp'), $date)]);
            }
            wp_send_json_success(['msg'=>__('Updated closed day','bookingkit-mvp')]);
        } else {
            $post_id = wp_insert_post([
                'post_type'   => 'bk_closed_day',
                'post_status' => 'publish',
                'post_title'  => sprintf(__('Closed: %s','bookingkit-mvp'), $date),
            ], true);

            if (is_wp_error($post_id)) {
                wp_send_json_error(['msg'=>$post_id->get_error_message()], 500);
            }

            update_post_meta($post_id, '_bk_date', $date);
            update_post_meta($post_id, '_bk_reason', $reason);
            wp_send_json_success(['msg'=>__('Created closed day','bookingkit-mvp')]);
        }
    }

    /* ====== AJAX: Delete (Open again) ====== */
    public static function ajax_delete() {
        check_ajax_referer('bkit_mvp_admin','nonce');

        if ( ! current_user_can('calendarkit_manage') ) {
            wp_send_json_error(['msg' => __('Not allowed','bookingkit-mvp')], 403);
        }

        $date = sanitize_text_field($_POST['date'] ?? '');
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            wp_send_json_error(['msg'=>__('Invalid date','bookingkit-mvp')], 400);
        }

        $post_id = self::find_closed_day_post_id($date);
        if (!$post_id) {
            wp_send_json_success(['msg'=>__('Day is already open','bookingkit-mvp')]);
        }

        wp_delete_post($post_id, true);
        wp_send_json_success(['msg'=>__('Closed day removed (open again)','bookingkit-mvp')]);
    }
}

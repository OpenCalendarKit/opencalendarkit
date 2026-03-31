<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_OpeningHours_Admin {

    public static function register_menu() {
        OpenCalendarKit_I18n::with_locale(function () {
            add_menu_page(
                __('OpenCalendarKit', 'open-calendar-kit'),
                __('OpenCalendarKit', 'open-calendar-kit'),
                OpenCalendarKit_Plugin::CAP_MANAGE,
                OpenCalendarKit_Plugin::MENU_SLUG,
                [__CLASS__, 'render_opening_hours_page'],
                'dashicons-calendar',
                26
            );

            add_submenu_page(
                OpenCalendarKit_Plugin::MENU_SLUG,
                __('Opening Hours', 'open-calendar-kit'),
                __('Opening Hours', 'open-calendar-kit'),
                OpenCalendarKit_Plugin::CAP_MANAGE,
                OpenCalendarKit_Plugin::MENU_SLUG,
                [__CLASS__, 'render_opening_hours_page']
            );
        });
    }

    public static function default_hours() {
        return [
            1 => ['closed' => 1, 'from' => '',      'to' => ''],
            2 => ['closed' => 0, 'from' => '16:30', 'to' => '22:00'],
            3 => ['closed' => 0, 'from' => '16:30', 'to' => '22:00'],
            4 => ['closed' => 0, 'from' => '16:30', 'to' => '22:00'],
            5 => ['closed' => 0, 'from' => '16:30', 'to' => '22:00'],
            6 => ['closed' => 0, 'from' => '12:00', 'to' => ''],
            7 => ['closed' => 0, 'from' => '12:00', 'to' => '21:00'],
        ];
    }

    public static function render_opening_hours_page() {
        OpenCalendarKit_I18n::with_locale(function () {
            if ( isset($_POST['bkit_hours_nonce']) && wp_verify_nonce($_POST['bkit_hours_nonce'], 'save_bkit_hours') ) {
                $hours = [];
                for ($d = 1; $d <= 7; $d++) {
                    $hours[$d] = [
                        'closed' => isset($_POST["day{$d}_closed"]) ? 1 : 0,
                        'from'   => sanitize_text_field($_POST["day{$d}_from"] ?? ''),
                        'to'     => sanitize_text_field($_POST["day{$d}_to"] ?? ''),
                    ];
                }

                $note = sanitize_textarea_field($_POST['bkit_opening_hours_note'] ?? '');

                update_option('bkit_mvp_opening_hours', $hours);
                update_option('bkit_mvp_opening_hours_note', $note);

                echo '<div class="updated"><p>' . esc_html__('Saved.', 'open-calendar-kit') . '</p></div>';
            }

            $hours = self::get_hours();
            $note  = self::get_note();

            $days  = [
                1 => __('Monday', 'open-calendar-kit'),
                2 => __('Tuesday', 'open-calendar-kit'),
                3 => __('Wednesday', 'open-calendar-kit'),
                4 => __('Thursday', 'open-calendar-kit'),
                5 => __('Friday', 'open-calendar-kit'),
                6 => __('Saturday', 'open-calendar-kit'),
                7 => __('Sunday', 'open-calendar-kit')
            ];
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('Opening Hours', 'open-calendar-kit'); ?></h1>

                <form method="post">
                    <?php wp_nonce_field('save_bkit_hours', 'bkit_hours_nonce'); ?>

                    <table class="form-table bk-table-hours">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Day', 'open-calendar-kit'); ?></th>
                                <th><?php esc_html_e('Closed', 'open-calendar-kit'); ?></th>
                                <th><?php esc_html_e('From', 'open-calendar-kit'); ?></th>
                                <th><?php esc_html_e('To', 'open-calendar-kit'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($days as $idx => $label): $row = $hours[$idx] ?? ['closed'=>0,'from'=>'','to'=>'']; ?>
                            <tr>
                                <th scope="row"><?php echo esc_html($label); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="day<?php echo $idx; ?>_closed" <?php checked(1, intval($row['closed'])); ?> />
                                        <?php esc_html_e('Closed', 'open-calendar-kit'); ?>
                                    </label>
                                </td>
                                <td>
                                    <input type="text" name="day<?php echo $idx; ?>_from" value="<?php echo esc_attr($row['from']); ?>" class="regular-text" />
                                </td>
                                <td>
                                    <input type="text" name="day<?php echo $idx; ?>_to" value="<?php echo esc_attr($row['to']); ?>" class="regular-text" />
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="bkit_opening_hours_note"><?php esc_html_e('Note below opening hours', 'open-calendar-kit'); ?></label>
                            </th>
                            <td>
                                <textarea
                                    name="bkit_opening_hours_note"
                                    id="bkit_opening_hours_note"
                                    rows="3"
                                    class="large-text"
                                    placeholder="<?php echo esc_attr__('Optional note for visitors', 'open-calendar-kit'); ?>"
                                ><?php echo esc_textarea($note); ?></textarea>
                                <p class="description">
                                    <?php esc_html_e('This text is shown below the opening hours on the frontend.', 'open-calendar-kit'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Save', 'open-calendar-kit'); ?></button>
                        <a href="<?php echo esc_url(admin_url()); ?>" class="button"><?php esc_html_e('Cancel', 'open-calendar-kit'); ?></a>
                    </p>
                </form>

                <p><?php esc_html_e('Use shortcodes: [okit_opening_hours], [okit_status_today], [okit_calendar]', 'open-calendar-kit'); ?></p>
            </div>
            <?php
        });
    }

    public static function get_hours() {
        $hours = get_option('bkit_mvp_opening_hours', null);

        if (!is_array($hours)) {
            return self::default_hours();
        }

        if (isset($hours[1]) && isset($hours[7]) && !isset($hours[0])) {
            return $hours;
        }

        if (isset($hours[0]) && isset($hours[6])) {
            $norm = [];
            for ($i = 0; $i <= 6; $i++) {
                $target = ($i === 0) ? 7 : $i;
                $norm[$target] = is_array($hours[$i]) ? $hours[$i] : ['closed' => 0, 'from' => '', 'to' => ''];
            }
            return $norm;
        }

        return self::default_hours();
    }

    public static function get_note() {
        return get_option('bkit_mvp_opening_hours_note', '');
    }
}

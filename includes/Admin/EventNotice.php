<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_EventNotice_Admin {

    public static function register_menu() {
        OpenCalendarKit_I18n::with_locale(function () {
            add_submenu_page(
                OpenCalendarKit_Plugin::MENU_SLUG,
                __('Event Notice', 'open-calendar-kit'),
                __('Event Notice', 'open-calendar-kit'),
                OpenCalendarKit_Plugin::CAP_MANAGE,
                OpenCalendarKit_Plugin::PAGE_EVENT_NOTICE,
                [__CLASS__, 'render_admin_page']
            );
        });
    }

    public static function render_admin_page() {
        OpenCalendarKit_I18n::with_locale(function () {
            if (
                isset($_POST['bkit_event_notice_nonce']) &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bkit_event_notice_nonce'])), 'save_bkit_event_notice') &&
                current_user_can(OpenCalendarKit_Plugin::CAP_MANAGE)
            ) {
                $enabled = isset($_POST['bkit_mvp_event_notice_enabled']) ? '1' : '0';
                $content = wp_kses_post(wp_unslash($_POST['bkit_mvp_event_notice_content'] ?? ''));

                update_option('bkit_mvp_event_notice_enabled', $enabled);
                update_option('bkit_mvp_event_notice_content', $content);

                echo '<div class="updated"><p>' . esc_html__('Saved.', 'open-calendar-kit') . '</p></div>';
            }

            $enabled = self::is_enabled();
            $content = self::get_content();
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('Event Notice', 'open-calendar-kit'); ?></h1>

                <form method="post">
                    <?php wp_nonce_field('save_bkit_event_notice', 'bkit_event_notice_nonce'); ?>

                    <table class="form-table" role="presentation">
                        <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Event announcement active', 'open-calendar-kit'); ?></th>
                            <td>
                                <label for="bkit_mvp_event_notice_enabled">
                                    <input
                                        type="checkbox"
                                        id="bkit_mvp_event_notice_enabled"
                                        name="bkit_mvp_event_notice_enabled"
                                        value="1"
                                        <?php checked($enabled); ?>
                                    />
                                    <?php esc_html_e('Show the event notice on the frontend when content is available.', 'open-calendar-kit'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="bkit_mvp_event_notice_content"><?php esc_html_e('Event text', 'open-calendar-kit'); ?></label>
                            </th>
                            <td>
                                <?php
                                wp_editor($content, 'bkit_mvp_event_notice_content', [
                                    'textarea_name' => 'bkit_mvp_event_notice_content',
                                    'textarea_rows' => 8,
                                    'media_buttons' => false,
                                    'teeny'         => true,
                                ]);
                                ?>
                                <p class="description">
                                    <?php esc_html_e('Use the shortcode [okit_event_notice] to output this content on the frontend.', 'open-calendar-kit'); ?>
                                </p>
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php esc_html_e('Save', 'open-calendar-kit'); ?></button>
                    </p>
                </form>
            </div>
            <?php
        });
    }

    public static function is_enabled() {
        return get_option('bkit_mvp_event_notice_enabled', '0') === '1';
    }

    public static function get_content() {
        $content = get_option('bkit_mvp_event_notice_content', '');

        return is_string($content) ? $content : '';
    }
}

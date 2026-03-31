<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_EventNotice_Admin {

    public static function register_menu() {
        add_submenu_page(
            'calendarkit',
            __('Event Notice', 'bookingkit-mvp'),
            __('Event Notice', 'bookingkit-mvp'),
            'calendarkit_manage',
            'calendarkit_event_notice',
            [__CLASS__, 'render_admin_page']
        );
    }

    public static function render_admin_page() {
        if (
            isset($_POST['bkit_event_notice_nonce']) &&
            wp_verify_nonce(wp_unslash($_POST['bkit_event_notice_nonce']), 'save_bkit_event_notice') &&
            current_user_can('calendarkit_manage')
        ) {
            $enabled = isset($_POST['bkit_mvp_event_notice_enabled']) ? '1' : '0';
            $content = wp_kses_post(wp_unslash($_POST['bkit_mvp_event_notice_content'] ?? ''));

            update_option('bkit_mvp_event_notice_enabled', $enabled);
            update_option('bkit_mvp_event_notice_content', $content);

            echo '<div class="updated"><p>' . esc_html__('Saved.', 'bookingkit-mvp') . '</p></div>';
        }

        $enabled = self::is_enabled();
        $content = self::get_content();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Event Notice', 'bookingkit-mvp'); ?></h1>

            <form method="post">
                <?php wp_nonce_field('save_bkit_event_notice', 'bkit_event_notice_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row"><?php esc_html_e('Event announcement active', 'bookingkit-mvp'); ?></th>
                        <td>
                            <label for="bkit_mvp_event_notice_enabled">
                                <input
                                    type="checkbox"
                                    id="bkit_mvp_event_notice_enabled"
                                    name="bkit_mvp_event_notice_enabled"
                                    value="1"
                                    <?php checked($enabled); ?>
                                />
                                <?php esc_html_e('Show the event notice on the frontend when content is available.', 'bookingkit-mvp'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="bkit_mvp_event_notice_content"><?php esc_html_e('Event text', 'bookingkit-mvp'); ?></label>
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
                                <?php esc_html_e('Use the shortcode [bk_event_notice] to output this content on the frontend.', 'bookingkit-mvp'); ?>
                            </p>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Save', 'bookingkit-mvp'); ?></button>
                </p>
            </form>
        </div>
        <?php
    }

    public static function is_enabled() {
        return get_option('bkit_mvp_event_notice_enabled', '0') === '1';
    }

    public static function get_content() {
        $content = get_option('bkit_mvp_event_notice_content', '');

        return is_string($content) ? $content : '';
    }
}

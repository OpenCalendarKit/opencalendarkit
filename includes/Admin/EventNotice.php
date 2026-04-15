<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class OpenCalendarKit_Admin_EventNotice {
    public static function register_menu() {
        OpenCalendarKit_I18n::with_locale(
            function () {
                add_submenu_page(
                    OpenCalendarKit_Plugin::MENU_SLUG,
                    __( 'Event Notice', 'open-calendar-kit' ),
                    __( 'Event Notice', 'open-calendar-kit' ),
                    OpenCalendarKit_Plugin::CAP_MANAGE,
                    OpenCalendarKit_Plugin::PAGE_EVENT_NOTICE,
                    [ __CLASS__, 'render_admin_page' ]
                );
            }
        );
    }

    public static function render_admin_page() {
        OpenCalendarKit_I18n::with_locale(
            function () {
                $nonce = isset( $_POST['openkit_event_notice_nonce'] )
                    ? sanitize_text_field( wp_unslash( $_POST['openkit_event_notice_nonce'] ) )
                    : '';

                if (
                    $nonce &&
                    wp_verify_nonce( $nonce, OpenCalendarKit_Plugin::NONCE_EVENT_NOTICE ) &&
                    current_user_can( OpenCalendarKit_Plugin::CAP_MANAGE )
                ) {
                    $enabled = isset( $_POST['openkit_event_notice_enabled'] ) ? '1' : '0';
                    $content = isset( $_POST['openkit_event_notice_content'] )
                        ? wp_kses_post( wp_unslash( $_POST['openkit_event_notice_content'] ) )
                        : '';

                    update_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_ENABLED, $enabled );
                    update_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_CONTENT, $content );

                    echo '<div class="updated"><p>' . esc_html__( 'Saved.', 'open-calendar-kit' ) . '</p></div>';
                }

                $enabled = self::is_enabled();
                $content = self::get_content();
                ?>
                <div class="wrap">
                    <h1><?php echo esc_html__( 'Event Notice', 'open-calendar-kit' ); ?></h1>

                    <form method="post">
                        <?php wp_nonce_field( OpenCalendarKit_Plugin::NONCE_EVENT_NOTICE, 'openkit_event_notice_nonce' ); ?>

                        <table class="form-table" role="presentation">
                            <tbody>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Event announcement active', 'open-calendar-kit' ); ?></th>
                                <td>
                                    <label for="openkit_event_notice_enabled">
                                        <input
                                            type="checkbox"
                                            id="openkit_event_notice_enabled"
                                            name="openkit_event_notice_enabled"
                                            value="1"
                                            <?php checked( $enabled ); ?>
                                        />
                                        <?php esc_html_e( 'Show the event notice on the frontend when content is available.', 'open-calendar-kit' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="openkit_event_notice_content"><?php esc_html_e( 'Event text', 'open-calendar-kit' ); ?></label>
                                </th>
                                <td>
                                    <?php
                                    wp_editor(
                                        $content,
                                        'openkit_event_notice_content',
                                        [
                                            'textarea_name' => 'openkit_event_notice_content',
                                            'textarea_rows' => 8,
                                            'media_buttons' => false,
                                            'teeny'         => true,
                                        ]
                                    );
                                    ?>
                                    <p class="description">
                                        <?php esc_html_e( 'Use the shortcode [openkit_event_notice] to output this content on the frontend.', 'open-calendar-kit' ); ?>
                                    </p>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e( 'Save', 'open-calendar-kit' ); ?></button>
                        </p>
                    </form>
                </div>
                <?php
            }
        );
    }

    public static function is_enabled() {
        $enabled = get_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_ENABLED, null );
        if ( ! is_string( $enabled ) ) {
            $enabled = get_option( OpenCalendarKit_Plugin::LEGACY_OPTION_EVENT_NOTICE_ENABLED, '0' );
        }

        return $enabled === '1';
    }

    public static function get_content() {
        $content = get_option( OpenCalendarKit_Plugin::OPTION_EVENT_NOTICE_CONTENT, null );
        if ( ! is_string( $content ) ) {
            $content = get_option( OpenCalendarKit_Plugin::LEGACY_OPTION_EVENT_NOTICE_CONTENT, '' );
        }

        return is_string( $content ) ? $content : '';
    }
}

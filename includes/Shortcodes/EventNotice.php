<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BKIT_MVP_Shortcode_EventNotice {

    public static function render($atts = []) {
        return OpenCalendarKit_I18n::with_locale(function () {
            if ( ! class_exists('BKIT_MVP_EventNotice_Admin') ) {
                return '';
            }

            if ( ! BKIT_MVP_EventNotice_Admin::is_enabled() ) {
                return '';
            }

            $content = BKIT_MVP_EventNotice_Admin::get_content();

            if (trim(wp_strip_all_tags($content)) === '') {
                return '';
            }

            ob_start();
            ?>
            <div class="bkit-event-notice">
                <div class="bkit-event-notice__inner">
                    <?php echo wpautop(wp_kses_post($content)); ?>
                </div>
            </div>
            <?php

            return ob_get_clean();
        });
    }
}

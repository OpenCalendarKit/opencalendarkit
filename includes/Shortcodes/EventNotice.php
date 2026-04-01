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

            $content = wpautop(wp_kses_post($content));

            ob_start();
            ?>
            <div class="bkit-event-notice bkit-ui-callout bkit-ui-callout--notice" role="note">
                <div class="bkit-ui-callout__inner bkit-event-notice__inner">
                    <div class="bkit-event-notice__body">
                    <?php echo wp_kses_post($content); ?>
                    </div>
                </div>
            </div>
            <?php

            return ob_get_clean();
        });
    }
}

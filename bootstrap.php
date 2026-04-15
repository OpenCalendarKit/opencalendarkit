<?php
/**
 * Internal bootstrap for OpenCalendarKit.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'OPEN_CALENDAR_KIT_PATH' ) ) {
    define( 'OPEN_CALENDAR_KIT_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'OPEN_CALENDAR_KIT_URL' ) ) {
    define( 'OPEN_CALENDAR_KIT_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'OPEN_CALENDAR_KIT_VERSION' ) ) {
    define( 'OPEN_CALENDAR_KIT_VERSION', '0.3.6' );
}

if ( ! defined( 'OPEN_CALENDAR_KIT_MAIN_FILE' ) ) {
    define( 'OPEN_CALENDAR_KIT_MAIN_FILE', OPEN_CALENDAR_KIT_PATH . 'open-calendar-kit.php' );
}

require_once OPEN_CALENDAR_KIT_PATH . 'includes/I18n.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Admin/OpeningHours.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Admin/ClosedDays.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Admin/EventNotice.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Admin/Settings.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Shortcodes/Calendar.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Shortcodes/OpeningHours.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Shortcodes/StatusToday.php';
require_once OPEN_CALENDAR_KIT_PATH . 'includes/Shortcodes/EventNotice.php';

class OpenCalendarKit_Plugin {
    const CAP_MANAGE = 'openkit_manage';
    const MENU_SLUG = 'open-calendar-kit';
    const PAGE_CALENDAR = 'open-calendar-kit-calendar';
    const PAGE_EVENT_NOTICE = 'open-calendar-kit-event-notice';
    const PAGE_SETTINGS = 'open-calendar-kit-settings';

    const SETTINGS_GROUP = 'openkit_settings';
    const SETTINGS_OPTION = 'openkit_settings';

    const OPTION_OPENING_HOURS = 'openkit_opening_hours';
    const OPTION_OPENING_HOURS_NOTE = 'openkit_opening_hours_note';
    const OPTION_EVENT_NOTICE_ENABLED = 'openkit_event_notice_enabled';
    const OPTION_EVENT_NOTICE_CONTENT = 'openkit_event_notice_content';
    const OPTION_DATA_VERSION = 'openkit_data_version';

    const LEGACY_OPTION_SETTINGS = 'okit_settings';
    const LEGACY_OPTION_OPENING_HOURS = 'bkit_mvp_opening_hours';
    const LEGACY_OPTION_OPENING_HOURS_NOTE = 'bkit_mvp_opening_hours_note';
    const LEGACY_OPTION_EVENT_NOTICE_ENABLED = 'bkit_mvp_event_notice_enabled';
    const LEGACY_OPTION_EVENT_NOTICE_CONTENT = 'bkit_mvp_event_notice_content';

    const CPT_CLOSED_DAY = 'openkit_closed_day';
    const LEGACY_CPT_CLOSED_DAY = 'bk_closed_day';

    const SHORTCODE_CALENDAR = 'openkit_calendar';
    const SHORTCODE_OPENING_HOURS = 'openkit_opening_hours';
    const SHORTCODE_STATUS_TODAY = 'openkit_status_today';
    const SHORTCODE_EVENT_NOTICE = 'openkit_event_notice';

    const AJAX_CALENDAR_MONTH = 'openkit_calendar_month';
    const AJAX_ADMIN_CALENDAR_MONTH = 'openkit_admin_calendar_month';
    const AJAX_SAVE_CLOSED_DAY = 'openkit_save_closed_day';
    const AJAX_DELETE_CLOSED_DAY = 'openkit_delete_closed_day';
    const AJAX_SAVE_OPEN_EXCEPTION = 'openkit_save_open_exception';
    const AJAX_DELETE_OPEN_EXCEPTION = 'openkit_delete_open_exception';

    const NONCE_FRONTEND = 'openkit_frontend';
    const NONCE_ADMIN = 'openkit_admin';
    const NONCE_OPENING_HOURS = 'openkit_save_opening_hours';
    const NONCE_EVENT_NOTICE = 'openkit_save_event_notice';
    const NONCE_CLOSED_DAY_META = 'openkit_closed_day_meta';

    const DATA_VERSION = '0.3.6-review-1';

    public function __construct() {
        add_action( 'init', [ __CLASS__, 'maybe_migrate_legacy_data' ], 5 );
        add_action( 'init', [ 'OpenCalendarKit_Admin_ClosedDays', 'register_cpt' ] );

        add_action( 'admin_init', [ 'OpenCalendarKit_Admin_Settings', 'register_settings' ] );
        add_action( 'admin_menu', [ 'OpenCalendarKit_Admin_OpeningHours', 'register_menu' ] );
        add_action( 'admin_menu', [ 'OpenCalendarKit_Admin_ClosedDays', 'register_menu' ] );
        add_action( 'admin_menu', [ 'OpenCalendarKit_Admin_EventNotice', 'register_menu' ] );
        add_action( 'admin_menu', [ 'OpenCalendarKit_Admin_Settings', 'register_menu' ] );

        add_action( 'add_meta_boxes', [ 'OpenCalendarKit_Admin_ClosedDays', 'register_metabox' ] );
        add_action( 'save_post_' . self::CPT_CLOSED_DAY, [ 'OpenCalendarKit_Admin_ClosedDays', 'save_metabox' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

        add_shortcode( self::SHORTCODE_CALENDAR, [ 'OpenCalendarKit_Shortcode_Calendar', 'render' ] );
        add_shortcode( self::SHORTCODE_OPENING_HOURS, [ 'OpenCalendarKit_Shortcode_OpeningHours', 'render' ] );
        add_shortcode( self::SHORTCODE_STATUS_TODAY, [ 'OpenCalendarKit_Shortcode_StatusToday', 'render' ] );
        add_shortcode( self::SHORTCODE_EVENT_NOTICE, [ 'OpenCalendarKit_Shortcode_EventNotice', 'render' ] );

        add_action( 'wp_ajax_' . self::AJAX_CALENDAR_MONTH, [ $this, 'ajax_calendar_month' ] );
        add_action( 'wp_ajax_nopriv_' . self::AJAX_CALENDAR_MONTH, [ $this, 'ajax_calendar_month' ] );
        add_action( 'wp_ajax_' . self::AJAX_ADMIN_CALENDAR_MONTH, [ 'OpenCalendarKit_Admin_ClosedDays', 'ajax_render_calendar_month' ] );
    }

    public static function maybe_migrate_legacy_data() {
        if ( get_option( self::OPTION_DATA_VERSION ) === self::DATA_VERSION ) {
            return;
        }

        self::migrate_legacy_option( self::SETTINGS_OPTION, self::LEGACY_OPTION_SETTINGS );
        self::migrate_legacy_option( self::OPTION_OPENING_HOURS, self::LEGACY_OPTION_OPENING_HOURS );
        self::migrate_legacy_option( self::OPTION_OPENING_HOURS_NOTE, self::LEGACY_OPTION_OPENING_HOURS_NOTE );
        self::migrate_legacy_option( self::OPTION_EVENT_NOTICE_ENABLED, self::LEGACY_OPTION_EVENT_NOTICE_ENABLED );
        self::migrate_legacy_option( self::OPTION_EVENT_NOTICE_CONTENT, self::LEGACY_OPTION_EVENT_NOTICE_CONTENT );
        self::migrate_legacy_closed_day_posts();

        update_option( self::OPTION_DATA_VERSION, self::DATA_VERSION );
    }

    private static function migrate_legacy_option( $new_option, $legacy_option ) {
        $missing = [ '__openkit_missing__' ];
        $new_value = get_option( $new_option, $missing );

        if ( $new_value !== $missing ) {
            return;
        }

        $legacy_value = get_option( $legacy_option, $missing );
        if ( $legacy_value === $missing ) {
            return;
        }

        add_option( $new_option, $legacy_value );
    }

    private static function migrate_legacy_closed_day_posts() {
        $query = new WP_Query(
            [
                'post_type'      => self::LEGACY_CPT_CLOSED_DAY,
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ]
        );

        $legacy_ids = ! empty( $query->posts ) ? array_map( 'intval', $query->posts ) : [];
        wp_reset_postdata();

        foreach ( $legacy_ids as $post_id ) {
            wp_update_post(
                [
                    'ID'        => $post_id,
                    'post_type' => self::CPT_CLOSED_DAY,
                ]
            );
        }
    }

    public function enqueue_assets() {
        OpenCalendarKit_I18n::with_locale(
            function () {
                wp_enqueue_style( 'open-calendar-kit', OPEN_CALENDAR_KIT_URL . 'assets/css/open-calendar-kit.css', [], OPEN_CALENDAR_KIT_VERSION );
                wp_enqueue_script( 'open-calendar-kit', OPEN_CALENDAR_KIT_URL . 'assets/js/open-calendar-kit.js', [ 'jquery' ], OPEN_CALENDAR_KIT_VERSION, true );
                wp_localize_script(
                    'open-calendar-kit',
                    'OPEN_CALENDAR_KIT',
                    [
                        'ajax_url'     => admin_url( 'admin-ajax.php' ),
                        'action'       => self::AJAX_CALENDAR_MONTH,
                        'nonce'        => wp_create_nonce( self::NONCE_FRONTEND ),
                        'locale'       => OpenCalendarKit_I18n::get_js_locale(),
                        'reason_label' => __( 'Reason:', 'open-calendar-kit' ),
                    ]
                );
            }
        );
    }

    public function enqueue_admin_assets( $hook ) {
        $post_type = get_post_type();
        $is_closed_days_cpt = in_array( $post_type, [ self::CPT_CLOSED_DAY, self::LEGACY_CPT_CLOSED_DAY ], true );
        $is_plugin_page = strpos( (string) $hook, self::MENU_SLUG ) !== false;
        $is_calendar_page = strpos( (string) $hook, self::PAGE_CALENDAR ) !== false;

        OpenCalendarKit_I18n::with_locale(
            function () use ( $is_closed_days_cpt, $is_plugin_page, $is_calendar_page ) {
                if ( $is_plugin_page || $is_closed_days_cpt || $is_calendar_page ) {
                    wp_enqueue_style( 'open-calendar-kit', OPEN_CALENDAR_KIT_URL . 'assets/css/open-calendar-kit.css', [], OPEN_CALENDAR_KIT_VERSION );
                }

                if ( ! $is_calendar_page ) {
                    return;
                }

                wp_enqueue_script( 'open-calendar-kit-admin', OPEN_CALENDAR_KIT_URL . 'assets/js/open-calendar-kit-admin.js', [ 'jquery' ], OPEN_CALENDAR_KIT_VERSION, true );
                wp_localize_script(
                    'open-calendar-kit-admin',
                    'OPEN_CALENDAR_KIT_ADMIN',
                    [
                        'action'                             => self::AJAX_ADMIN_CALENDAR_MONTH,
                        'save_closed_day_action'            => self::AJAX_SAVE_CLOSED_DAY,
                        'delete_closed_day_action'          => self::AJAX_DELETE_CLOSED_DAY,
                        'save_open_exception_action'        => self::AJAX_SAVE_OPEN_EXCEPTION,
                        'delete_open_exception_action'      => self::AJAX_DELETE_OPEN_EXCEPTION,
                        'nonce'                             => wp_create_nonce( self::NONCE_ADMIN ),
                        'generic_error'                     => __( 'Error', 'open-calendar-kit' ),
                        'confirm_reopen'                    => __( 'Remove this closed day and mark it open again?', 'open-calendar-kit' ),
                        'open_day_exceptionally'            => __( 'Open day exceptionally', 'open-calendar-kit' ),
                        'remove_exceptional_opening'        => __( 'Remove exceptional opening', 'open-calendar-kit' ),
                        'confirm_remove_exceptional_opening' => __( 'Remove this exceptional opening and use the normal weekday rule again?', 'open-calendar-kit' ),
                    ]
                );
            }
        );
    }

    public static function activate() {
        self::maybe_migrate_legacy_data();
        OpenCalendarKit_Admin_ClosedDays::register_cpt();
        self::ensure_roles_caps();

        if ( false === get_option( self::OPTION_OPENING_HOURS, false ) ) {
            add_option( self::OPTION_OPENING_HOURS, OpenCalendarKit_Admin_OpeningHours::default_hours() );
        }

        if ( false === get_option( self::OPTION_OPENING_HOURS_NOTE, false ) ) {
            add_option( self::OPTION_OPENING_HOURS_NOTE, '' );
        }

        if ( false === get_option( self::OPTION_EVENT_NOTICE_ENABLED, false ) ) {
            add_option( self::OPTION_EVENT_NOTICE_ENABLED, '0' );
        }

        if ( false === get_option( self::OPTION_EVENT_NOTICE_CONTENT, false ) ) {
            add_option( self::OPTION_EVENT_NOTICE_CONTENT, '' );
        }

        if ( false === get_option( self::SETTINGS_OPTION, false ) ) {
            add_option( self::SETTINGS_OPTION, OpenCalendarKit_Admin_Settings::defaults() );
        }

        flush_rewrite_rules();
    }

    private static function ensure_roles_caps() {
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( self::CAP_MANAGE );
        }

        $editor = get_role( 'editor' );
        if ( $editor ) {
            $editor->add_cap( self::CAP_MANAGE );
        }
    }

    public static function uninstall() {
        // Plugin data remains intentionally available after uninstall.
    }

    public function ajax_calendar_month() {
        OpenCalendarKit_I18n::with_locale(
            function () {
                check_ajax_referer( self::NONCE_FRONTEND, 'nonce' );

                $month = isset( $_POST['month'] ) ? sanitize_text_field( wp_unslash( $_POST['month'] ) ) : '';
                if ( ! preg_match( '/^\d{4}-\d{2}$/', $month ) ) {
                    wp_send_json_error( [ 'msg' => __( 'Invalid month', 'open-calendar-kit' ) ], 400 );
                }

                $show_legend = isset( $_POST['show_legend'] ) ? sanitize_text_field( wp_unslash( $_POST['show_legend'] ) ) : '';
                $week_starts_on = isset( $_POST['week_starts_on'] ) ? sanitize_text_field( wp_unslash( $_POST['week_starts_on'] ) ) : '';
                $max_width = isset( $_POST['max_width'] ) ? sanitize_text_field( wp_unslash( $_POST['max_width'] ) ) : '';

                $html = OpenCalendarKit_Shortcode_Calendar::render(
                    [
                        'month'          => $month,
                        'show_legend'    => $show_legend,
                        'week_starts_on' => $week_starts_on,
                        'max_width'      => $max_width,
                    ]
                );

                wp_send_json_success( [ 'html' => $html ] );
            }
        );
    }
}

<?php
/**
 * Internal bootstrap for OpenCalendarKit.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

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

    /**
     * Plugin Capability (für Menüs + Datenpflege im Backend)
     */
    const CAP_MANAGE = 'calendarkit_manage';
    const MENU_SLUG = 'open-calendar-kit';
    const PAGE_CALENDAR = 'open-calendar-kit-calendar';
    const PAGE_EVENT_NOTICE = 'open-calendar-kit-event-notice';
    const PAGE_SETTINGS = 'open-calendar-kit-settings';

    public function __construct() {
        add_action('init', ['BKIT_MVP_ClosedDays_Admin', 'register_cpt']);

        add_action('admin_init', ['BKIT_MVP_Settings', 'register_settings']);
        add_action('admin_menu', ['BKIT_MVP_OpeningHours_Admin', 'register_menu']);
        add_action('admin_menu', ['BKIT_MVP_ClosedDays_Admin', 'register_menu']);
        add_action('admin_menu', ['BKIT_MVP_EventNotice_Admin', 'register_menu']);
        add_action('admin_menu', ['BKIT_MVP_Settings', 'register_menu']);

        add_action('add_meta_boxes', ['BKIT_MVP_ClosedDays_Admin', 'register_metabox']);
        add_action('save_post_bk_closed_day', ['BKIT_MVP_ClosedDays_Admin', 'save_metabox']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_shortcode('okit_calendar', ['BKIT_MVP_Shortcode_Calendar', 'render']);
        add_shortcode('okit_opening_hours', ['BKIT_MVP_Shortcode_OpeningHours', 'render']);
        add_shortcode('okit_status_today', ['BKIT_MVP_Shortcode_StatusToday', 'render']);
        add_shortcode('okit_event_notice', ['BKIT_MVP_Shortcode_EventNotice', 'render']);

        add_action('wp_ajax_okit_calendar_month', [$this, 'ajax_calendar_month']);
        add_action('wp_ajax_nopriv_okit_calendar_month', [$this, 'ajax_calendar_month']);
    }

    public function enqueue_assets() {
        OpenCalendarKit_I18n::with_locale(function () {
            wp_enqueue_style('open-calendar-kit', OPEN_CALENDAR_KIT_URL . 'assets/css/open-calendar-kit.css', [], OPEN_CALENDAR_KIT_VERSION);
            wp_enqueue_script('open-calendar-kit', OPEN_CALENDAR_KIT_URL . 'assets/js/open-calendar-kit.js', ['jquery'], OPEN_CALENDAR_KIT_VERSION, true);
            wp_localize_script('open-calendar-kit', 'OPEN_CALENDAR_KIT', [
                'ajax_url'     => admin_url('admin-ajax.php'),
                'nonce'        => wp_create_nonce('okit_frontend'),
                'locale'       => OpenCalendarKit_I18n::get_js_locale(),
                'reason_label' => __('Reason:', 'open-calendar-kit'),
            ]);
        });
    }

    public function enqueue_admin_assets($hook) {
        $current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $is_closed_days_cpt = in_array(get_post_type(), ['bk_closed_day'], true);
        $is_calendar_page = (
            strpos((string) $hook, self::PAGE_CALENDAR) !== false ||
            $current_page === self::PAGE_CALENDAR
        );

        OpenCalendarKit_I18n::with_locale(function () use ($hook, $is_closed_days_cpt, $is_calendar_page) {
            if (strpos($hook, 'open-calendar-kit') !== false || $is_closed_days_cpt || $is_calendar_page) {
                wp_enqueue_style('open-calendar-kit', OPEN_CALENDAR_KIT_URL . 'assets/css/open-calendar-kit.css', [], OPEN_CALENDAR_KIT_VERSION);
            }

            if ($is_calendar_page) {
                wp_enqueue_script('open-calendar-kit-admin', OPEN_CALENDAR_KIT_URL . 'assets/js/open-calendar-kit-admin.js', ['jquery'], OPEN_CALENDAR_KIT_VERSION, true);
                wp_localize_script('open-calendar-kit-admin', 'OPEN_CALENDAR_KIT_ADMIN', [
                    'nonce'          => wp_create_nonce('okit_admin'),
                    'generic_error'  => __('Error', 'open-calendar-kit'),
                    'confirm_reopen' => __('Remove this closed day and mark it open again?', 'open-calendar-kit'),
                    'open_day_exceptionally' => __('Open day exceptionally', 'open-calendar-kit'),
                    'remove_exceptional_opening' => __('Remove exceptional opening', 'open-calendar-kit'),
                    'confirm_remove_exceptional_opening' => __('Remove this exceptional opening and use the normal weekday rule again?', 'open-calendar-kit'),
                ]);
            }
        });
    }

    /**
     * Activation:
     * - CPTs registrieren
     * - Rewrite flushen
     * - Capability calendarkit_manage an Admin + Redakteur geben
     * - Optionen nur initial anlegen, niemals überschreiben
     */
    public static function activate() {
        BKIT_MVP_ClosedDays_Admin::register_cpt();

        self::ensure_roles_caps();

        // Öffnungszeiten nur beim echten Erstinstallationsfall anlegen
        if (false === get_option('bkit_mvp_opening_hours', false)) {
            add_option('bkit_mvp_opening_hours', BKIT_MVP_OpeningHours_Admin::default_hours());
        }

        // Hinweistext unter Öffnungszeiten nur beim echten Erstinstallationsfall anlegen
        if (false === get_option('bkit_mvp_opening_hours_note', false)) {
            add_option('bkit_mvp_opening_hours_note', '');
        }

        if (false === get_option('bkit_mvp_event_notice_enabled', false)) {
            add_option('bkit_mvp_event_notice_enabled', '0');
        }

        if (false === get_option('bkit_mvp_event_notice_content', false)) {
            add_option('bkit_mvp_event_notice_content', '');
        }

        if (false === get_option(BKIT_MVP_Settings::OPTION_NAME, false)) {
            add_option(BKIT_MVP_Settings::OPTION_NAME, BKIT_MVP_Settings::defaults());
        }

        flush_rewrite_rules();
    }

    /**
     * Capabilities an Rollen vergeben
     */
    private static function ensure_roles_caps() {
        // Admin bekommt das Recht immer
        if ($admin = get_role('administrator')) {
            $admin->add_cap(self::CAP_MANAGE);
        }

        // Redakteur bekommt das Recht
        if ($editor = get_role('editor')) {
            $editor->add_cap(self::CAP_MANAGE);
        }
    }

    /**
     * Beim Uninstall Benutzerdaten NICHT löschen.
     * Sonst gehen gepflegte Öffnungszeiten/Hinweise verloren und bei Neuinstallation
     * tauchen wieder Defaultwerte auf.
     */
    public static function uninstall() {
        // Benutzerdaten bewusst nicht löschen.

        // Optional: Caps wieder entfernen
        // if ( $admin = get_role('administrator') ) { $admin->remove_cap(self::CAP_MANAGE); }
        // if ( $editor = get_role('editor') ) { $editor->remove_cap(self::CAP_MANAGE); }
    }

    public function ajax_calendar_month() {
        OpenCalendarKit_I18n::with_locale(function () {
            check_ajax_referer('okit_frontend', 'nonce');

            $month = isset($_POST['month']) ? sanitize_text_field(wp_unslash($_POST['month'])) : ''; // YYYY-MM
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
                wp_send_json_error(['msg' => __('Invalid month', 'open-calendar-kit')], 400);
            }

            $html = '';
            if (class_exists('BKIT_MVP_Shortcode_Calendar')) {
                $show_legend = isset($_POST['show_legend']) ? sanitize_text_field(wp_unslash($_POST['show_legend'])) : '';
                $week_starts_on = isset($_POST['week_starts_on']) ? sanitize_text_field(wp_unslash($_POST['week_starts_on'])) : '';
                $max_width = isset($_POST['max_width']) ? sanitize_text_field(wp_unslash($_POST['max_width'])) : '';

                $html = BKIT_MVP_Shortcode_Calendar::render([
                    'month'          => $month,
                    'show_legend'    => $show_legend,
                    'week_starts_on' => $week_starts_on,
                    'max_width'      => $max_width,
                ]);
            }

            wp_send_json_success(['html' => $html]);
        });
    }
}

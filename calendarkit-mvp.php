<?php
/**
 * Plugin Name: CalendarKit MVP
 * Description: Reservations helper with opening hours, closed days, clickable calendar + request modal.
 * Version: 0.3.6
 * Author: Jörn / ChatGPT
 * Text Domain: bookingkit-mvp
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'BKIT_MVP_PATH', plugin_dir_path( __FILE__ ) );
define( 'BKIT_MVP_URL', plugin_dir_url( __FILE__ ) );

require_once BKIT_MVP_PATH . 'includes/CPT/Reservation.php';
require_once BKIT_MVP_PATH . 'includes/Admin/OpeningHours.php';
require_once BKIT_MVP_PATH . 'includes/Admin/ClosedDays.php';
require_once BKIT_MVP_PATH . 'includes/Admin/EventNotice.php';
require_once BKIT_MVP_PATH . 'includes/Shortcodes/Calendar.php';
require_once BKIT_MVP_PATH . 'includes/Shortcodes/OpeningHours.php';
require_once BKIT_MVP_PATH . 'includes/Shortcodes/StatusToday.php';
require_once BKIT_MVP_PATH . 'includes/Shortcodes/EventNotice.php';

class CalendarKit_MVP {

    /**
     * Plugin Capability (für Menüs + Datenpflege im Backend)
     */
    const CAP_MANAGE = 'calendarkit_manage';

    public function __construct() {
        add_action('init', ['BKIT_MVP_Reservation', 'register_cpt']);
        add_action('init', ['BKIT_MVP_ClosedDays_Admin', 'register_cpt']);

        add_action('admin_menu', ['BKIT_MVP_OpeningHours_Admin', 'register_menu']);
        add_action('admin_menu', ['BKIT_MVP_ClosedDays_Admin', 'register_menu']);
        add_action('admin_menu', ['BKIT_MVP_EventNotice_Admin', 'register_menu']);

        add_action('add_meta_boxes', ['BKIT_MVP_ClosedDays_Admin', 'register_metabox']);
        add_action('save_post_bk_closed_day', ['BKIT_MVP_ClosedDays_Admin', 'save_metabox']);

        add_action('add_meta_boxes', ['BKIT_MVP_Reservation', 'register_metabox']);
        add_action('save_post_bk_reservation', ['BKIT_MVP_Reservation', 'save_metabox']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_shortcode('bk_calendar', ['BKIT_MVP_Shortcode_Calendar', 'render']);
        add_shortcode('bk_opening_hours', ['BKIT_MVP_Shortcode_OpeningHours', 'render']);
        add_shortcode('bk_status_today', ['BKIT_MVP_Shortcode_StatusToday', 'render']);
        add_shortcode('bk_event_notice', ['BKIT_MVP_Shortcode_EventNotice', 'render']);

        add_action('wp_ajax_bkit_mvp_submit_res', [$this, 'ajax_submit_res']);
        add_action('wp_ajax_nopriv_bkit_mvp_submit_res', [$this, 'ajax_submit_res']);

        // Kalender-Monat per AJAX nachladen (ohne Page-Reload)
        add_action('wp_ajax_bkit_mvp_calendar_month', [$this, 'ajax_calendar_month']);
        add_action('wp_ajax_nopriv_bkit_mvp_calendar_month', [$this, 'ajax_calendar_month']);

        // AJAX für Closed Day speichern (Admin)
        add_action('wp_ajax_bkit_mvp_save_closed_day', ['BKIT_MVP_ClosedDays_Admin', 'ajax_save']);
        // AJAX: Closed-Reason (öffentlich)
        add_action('wp_ajax_bkit_mvp_closed_reason', ['BKIT_MVP_ClosedDays_Admin', 'ajax_reason']);
        add_action('wp_ajax_nopriv_bkit_mvp_closed_reason', ['BKIT_MVP_ClosedDays_Admin', 'ajax_reason']);
        add_action('wp_ajax_bkit_mvp_get_closed', ['BKIT_MVP_ClosedDays_Admin', 'ajax_get_closed_info']);
        add_action('wp_ajax_nopriv_bkit_mvp_get_closed', ['BKIT_MVP_ClosedDays_Admin', 'ajax_get_closed_info']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('bookingkit-mvp', BKIT_MVP_URL . 'assets/css/bookingkit.css', [], '0.3.6');
        wp_enqueue_script('bookingkit-mvp', BKIT_MVP_URL . 'assets/js/bookingkit.js', ['jquery'], '0.3.6', true);
        wp_localize_script('bookingkit-mvp', 'BKIT_MVP', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('bkit_mvp_nonce'),
        ]);
    }

    public function enqueue_admin_assets($hook) {
        // Screens erkennen
        $is_closed_days_cpt = in_array(get_post_type(), ['bk_closed_day', 'bk_reservation'], true);
        // Submenu-Hook: <parent>_page_<slug>
        $is_calendar_page = (strpos($hook, 'calendarkit_page_calendarkit_calendar') !== false);

        if (strpos($hook, 'calendarkit') !== false || $is_closed_days_cpt || $is_calendar_page) {
            wp_enqueue_style('bookingkit-mvp', BKIT_MVP_URL . 'assets/css/bookingkit.css', [], '0.3.6');
        }

        if ($is_calendar_page) {
            wp_enqueue_script('bookingkit-admin', BKIT_MVP_URL . 'assets/js/bookingkit-admin.js', ['jquery'], '0.3.6', true);
            wp_localize_script('bookingkit-admin', 'BKIT_MVP_ADMIN', [
                'nonce' => wp_create_nonce('bkit_mvp_admin')
            ]);
        }
    }

    /**
     * Activation:
     * - CPTs registrieren
     * - Rewrite flushen
     * - Capability calendarkit_manage an Admin + Redakteur geben
     * - Optionen nur initial anlegen, niemals überschreiben
     */
    public static function activate() {
        BKIT_MVP_Reservation::register_cpt();
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

    public function ajax_submit_res() {
        check_ajax_referer('bkit_mvp_nonce', 'nonce');

        $date    = sanitize_text_field($_POST['date'] ?? '');
        $time    = sanitize_text_field($_POST['time'] ?? '');
        $persons = intval($_POST['persons'] ?? 0);
        $name    = sanitize_text_field($_POST['name'] ?? '');
        $phone   = sanitize_text_field($_POST['phone'] ?? '');
        $email   = sanitize_email($_POST['email'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($date) || empty($name) || empty($email)) {
            wp_send_json_error(['msg' => __('Please fill required fields', 'bookingkit-mvp')], 400);
        }

        $post_id = wp_insert_post([
            'post_type'    => 'bk_reservation',
            'post_status'  => 'publish',
            'post_title'   => sprintf(__('Reservation request %s – %s', 'bookingkit-mvp'), $date, $name),
            'post_content' => $message,
        ], true);

        if (is_wp_error($post_id)) {
            wp_send_json_error(['msg' => $post_id->get_error_message()], 500);
        }

        update_post_meta($post_id, '_bk_date', $date);
        update_post_meta($post_id, '_bk_time', $time);
        update_post_meta($post_id, '_bk_persons', $persons);
        update_post_meta($post_id, '_bk_name', $name);
        update_post_meta($post_id, '_bk_phone', $phone);
        update_post_meta($post_id, '_bk_email', $email);

        wp_send_json_success(['msg' => __('Request received', 'bookingkit-mvp')]);
    }

    public function ajax_calendar_month() {
        check_ajax_referer('bkit_mvp_nonce', 'nonce');

        $month = sanitize_text_field($_POST['month'] ?? ''); // YYYY-MM
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            wp_send_json_error(['msg' => 'Invalid month'], 400);
        }

        $html = '';
        if (class_exists('BKIT_MVP_Shortcode_Calendar')) {
            $html = BKIT_MVP_Shortcode_Calendar::render([
                'month' => $month,
                'show_legend' => '1',
            ]);
        }

        wp_send_json_success(['html' => $html]);
    }
}

new CalendarKit_MVP();

register_activation_hook(__FILE__, ['CalendarKit_MVP', 'activate']);
register_uninstall_hook(__FILE__, ['CalendarKit_MVP', 'uninstall']);

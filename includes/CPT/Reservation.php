<?php
if ( ! defined( 'ABSPATH' ) ) exit;
class BKIT_MVP_Reservation {
    public static function register_cpt() {
        $labels = ['name' => __('Reservations', 'bookingkit-mvp'),'singular_name' => __('Reservation', 'bookingkit-mvp')];
        register_post_type('bk_reservation', [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-calendar-alt',
            'supports' => ['title', 'editor'],
            // Keep CPT for storage/queries, but hide it from the admin menu.
            'show_in_menu' => false,
        ]);
        add_filter('manage_bk_reservation_posts_columns', [__CLASS__, 'cols']);
        add_action('manage_bk_reservation_posts_custom_column', [__CLASS__, 'col_content'], 10, 2);
        add_filter('manage_edit-bk_reservation_sortable_columns', [__CLASS__, 'sortable']);
        add_action('pre_get_posts', [__CLASS__, 'order_by_date']);
    }
    public static function cols($cols){
        $new = [];
        $new['cb'] = $cols['cb'];
        $new['title'] = __('Title');
        $new['_bk_date'] = __('Date','bookingkit-mvp');
        $new['_bk_time'] = __('Time','bookingkit-mvp');
        $new['_bk_persons'] = __('Persons','bookingkit-mvp');
        $new['_bk_name'] = __('Name','bookingkit-mvp');
        $new['_bk_phone'] = __('Phone','bookingkit-mvp');
        $new['_bk_email'] = __('Email','bookingkit-mvp');
        return $new;
    }
    public static function col_content($col, $post_id){
        $meta_cols = ['_bk_date','_bk_time','_bk_persons','_bk_name','_bk_phone','_bk_email'];
        if (in_array($col, $meta_cols, true)){
            echo esc_html(get_post_meta($post_id, $col, true));
        }
    }
    public static function sortable($cols){ $cols['_bk_date'] = '_bk_date'; return $cols; }
    public static function order_by_date($q){
        if (!is_admin() || $q->get('post_type') !== 'bk_reservation') return;
        if ($q->get('orderby') === '_bk_date'){
            $q->set('meta_key','_bk_date');
            $q->set('orderby','meta_value');
        }
    }
    public static function register_metabox() {
        add_meta_box('bk_res_details', __('Reservation Details','bookingkit-mvp'), [__CLASS__, 'render_metabox'], 'bk_reservation', 'normal', 'high');
    }
    public static function render_metabox($post){
        wp_nonce_field('bk_res_details','bk_res_details_nonce');
        $fields = [
            '_bk_date' => ['label'=>__('Date','bookingkit-mvp'), 'type'=>'date'],
            '_bk_time' => ['label'=>__('Time','bookingkit-mvp'), 'type'=>'time'],
            '_bk_persons' => ['label'=>__('Persons','bookingkit-mvp'), 'type'=>'number'],
            '_bk_name' => ['label'=>__('Name','bookingkit-mvp'), 'type'=>'text'],
            '_bk_phone' => ['label'=>__('Phone','bookingkit-mvp'), 'type'=>'text'],
            '_bk_email' => ['label'=>__('Email','bookingkit-mvp'), 'type'=>'email'],
        ];
        echo '<table class="form-table">';
        foreach ($fields as $key=>$cfg){
            $val = get_post_meta($post->ID, $key, true);
            echo '<tr><th><label for="'.$key.'">'.esc_html($cfg['label']).'</label></th><td>';
            printf('<input type="%s" id="%s" name="%s" value="%s" class="regular-text" />',
                esc_attr($cfg['type']), esc_attr($key), esc_attr($key), esc_attr($val));
            echo '</td></tr>';
        }
        echo '</table>';
    }
    public static function save_metabox($post_id){
        if (!isset($_POST['bk_res_details_nonce']) || !wp_verify_nonce($_POST['bk_res_details_nonce'],'bk_res_details')) return;
        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
        if ( ! current_user_can('edit_post', $post_id) ) return;
        foreach (['_bk_date','_bk_time','_bk_persons','_bk_name','_bk_phone','_bk_email'] as $k){
            $val = isset($_POST[$k]) ? sanitize_text_field($_POST[$k]) : '';
            update_post_meta($post_id, $k, $val);
        }
    }
}

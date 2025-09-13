<?php
if ( ! defined('ABSPATH') ) { exit; }

class RSHF_SafeMode {
    const OPTION = 'rshf_options';
    const TRANSIENT = 'rshf_safe_mode';
    const GET_KEY = 'rshf_safe';

    public static function is_enabled(){
        $opts = rshf_get_options();
        if( !empty($opts['safe_mode']) ) return true;
        if( get_transient(self::TRANSIENT) ){
            return true;
        }
        return false;
    }

    public static function enable_transient($minutes = 15){
        set_transient(self::TRANSIENT, 1, MINUTE_IN_SECONDS * absint($minutes));
    }

    public static function admin_bar_badge(){
        if( !is_admin_bar_showing() ) return;
        if( !self::is_enabled() ) return;
        add_action('admin_bar_menu', function($wp_admin_bar){
            $wp_admin_bar->add_node([
                'id' => 'rshf-safe',
                'title' => 'RSHF Safe Mode',
                'href' => admin_url('admin.php?page=rshf-settings'),
                'meta' => ['class' => 'rshf-safe-mode-badge']
            ]);
        }, 1000);
    }

    public static function maybe_toggle_by_request(){
        if( isset($_GET[self::GET_KEY]) && rshf_current_user_can_manage() ){
            if( $_GET[self::GET_KEY] == '1' ){
                self::enable_transient(15);
                add_action('admin_notices', function(){
                    rshf_admin_notice(__('حالت ایمن به مدت ۱۵ دقیقه فعال شد. اجرای اسنیپت‌های PHP موقتاً غیرفعال است.', 'readystudio-hf'), 'warning');
                });
            }
            if( $_GET[self::GET_KEY] === 'off' ){
                delete_transient(self::TRANSIENT);
                $opts = rshf_get_options();
                $opts['safe_mode'] = 0;
                update_option(self::OPTION, $opts);
                add_action('admin_notices', function(){
                    rshf_admin_notice(__('حالت ایمن غیرفعال شد.', 'readystudio-hf'), 'success');
                });
            }
        }
    }
}
add_action('init', ['RSHF_SafeMode', 'maybe_toggle_by_request']);
add_action('init', ['RSHF_SafeMode', 'admin_bar_badge']);

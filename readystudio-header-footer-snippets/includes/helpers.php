<?php
if ( ! defined('ABSPATH') ) { exit; }

function rshf_capability(){
    return is_multisite() ? 'manage_network_options' : 'manage_options';
}

function rshf_current_user_can_manage(){
    return current_user_can( rshf_capability() );
}

function rshf_get_options(){
    $defaults = [
        'head_code'   => '',
        'body_code'   => '',
        'footer_code' => '',
        'safe_mode'   => 0,
        'cache_bust'  => time(),
    ];
    $opts = get_option('rshf_options', []);
    if(!is_array($opts)) $opts = [];
    return array_merge($defaults, $opts);
}

function rshf_update_options($new){
    $opts = rshf_get_options();
    $opts = array_merge($opts, $new);
    if(isset($new['head_code']) || isset($new['body_code']) || isset($new['footer_code'])){
        $opts['cache_bust'] = time();
    }
    update_option('rshf_options', $opts);
    delete_transient('rshf_cache_head');
    delete_transient('rshf_cache_body');
    delete_transient('rshf_cache_footer');
}

function rshf_sanitize_code_field($val){
    if( rshf_current_user_can_manage() ){
        return (string)$val;
    }
    return wp_kses_post( (string)$val );
}

function rshf_admin_notice($message, $type = 'success'){
    printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($type), wp_kses_post($message));
}

function rshf_nonce_field($action){
    wp_nonce_field($action, $action . '_nonce');
}

function rshf_check_nonce($action){
    return isset($_POST[$action . '_nonce']) && wp_verify_nonce($_POST[$action . '_nonce'], $action);
}

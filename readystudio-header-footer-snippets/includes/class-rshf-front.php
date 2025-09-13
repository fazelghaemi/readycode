<?php
if ( ! defined('ABSPATH') ) { exit; }

class RSHF_Front {

    public static function execute_php_snippets(){
        if( is_admin() ) return;
        if( RSHF_SafeMode::is_enabled() ) return; // do not execute in safe mode

        $snippets = self::get_active_snippets('php');
        foreach($snippets as $s){
            $code = $s['content'];
            self::eval_php($code, $s['id']);
        }
    }

    public static function print_head(){
        // Aggregate global + snippets(header)
        $out = get_transient('rshf_cache_head');
        if(false === $out){
            $opts = rshf_get_options();
            $out = '';
            if( !empty($opts['head_code']) ){
                $out .= "\n" . $opts['head_code'] . "\n";
            }
            $snippets = self::get_active_snippets(['js','css','html'], 'header');
            $out .= self::render_snippets($snippets);
            set_transient('rshf_cache_head', $out, HOUR_IN_SECONDS);
        }
        if( $out ){
            echo "\n<!-- RSHF: head -->\n" . $out . "\n<!-- /RSHF -->\n";
        }
    }

    public static function print_body_open(){
        $out = get_transient('rshf_cache_body');
        if(false === $out){
            $opts = rshf_get_options();
            $out = '';
            if( !empty($opts['body_code']) ){
                $out .= "\n" . $opts['body_code'] . "\n";
            }
            $snippets = self::get_active_snippets(['js','css','html'], 'body');
            $out .= self::render_snippets($snippets);
            set_transient('rshf_cache_body', $out, HOUR_IN_SECONDS);
        }
        if( $out ){
            echo "\n<!-- RSHF: body_open -->\n" . $out . "\n<!-- /RSHF -->\n";
        }
    }

    public static function print_footer(){
        $out = get_transient('rshf_cache_footer');
        if(false === $out){
            $opts = rshf_get_options();
            $out = '';
            if( !empty($opts['footer_code']) ){
                $out .= "\n" . $opts['footer_code'] . "\n";
            }
            $snippets = self::get_active_snippets(['js','css','html'], 'footer');
            $out .= self::render_snippets($snippets);
            set_transient('rshf_cache_footer', $out, HOUR_IN_SECONDS);
        }
        if( $out ){
            echo "\n<!-- RSHF: footer -->\n" . $out . "\n<!-- /RSHF -->\n";
        }
    }

    public static function shortcode_snippet($atts){
        $atts = shortcode_atts(['id' => 0], $atts, 'rshf_snippet');
        $post_id = absint($atts['id']);
        if( !$post_id ) return '';

        $meta = rshf_get_snippet_meta($post_id);
        if( $meta['active'] !== '1' ) return '';

        $post = get_post($post_id);
        if( !$post || $post->post_type !== RSHF_CPT ) return '';

        $content = $post->post_content;
        return self::wrap_code($content, $meta['type']);
    }

    // Utilities

    protected static function get_active_snippets($types = ['js','css','html'], $location = null){
        $args = [
            'post_type'      => RSHF_CPT,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'meta_value_num',
            'meta_key'       => 'rshf_priority',
            'order'          => 'ASC',
            'meta_query'     => [
                [
                    'key'   => 'rshf_active',
                    'value' => '1',
                ],
            ],
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ];
        if( $location ){
            $args['meta_query'][] = [
                'key'   => 'rshf_location',
                'value' => $location,
            ];
        }
        $ids = get_posts($args);

        $out = [];
        foreach($ids as $id){
            $meta = rshf_get_snippet_meta($id);
            if( is_array($types) ){
                if( !in_array($meta['type'], $types, true) ) continue;
            } else {
                if( $meta['type'] !== $types ) continue;
            }
            $out[] = [
                'id'      => $id,
                'meta'    => $meta,
                'content' => get_post_field('post_content', $id)
            ];
        }
        return $out;
    }

    protected static function render_snippets($snippets){
        $out = '';
        foreach($snippets as $s){
            $out .= self::wrap_code($s['content'], $s['meta']['type']) . "\n";
        }
        return $out;
    }

    protected static function wrap_code($code, $type){
        $code = (string)$code;
        if($type === 'css'){
            return "<style id='rshf-css'>" . $code . "</style>";
        } elseif($type === 'js'){
            return "<script id='rshf-js' type='text/javascript'>" . $code . "</script>";
        } elseif($type === 'html'){
            return $code;
        } elseif($type === 'php'){
            // PHP in manual shortcode returns its output
            ob_start();
            self::eval_php($code, 0);
            return ob_get_clean();
        }
        return $code;
    }

    protected static function eval_php($code, $id = 0){
        if( RSHF_SafeMode::is_enabled() ){
            return;
        }
        // Strip opening tag if present
        $code = trim($code);
        if( strpos($code, '<?php') === 0 ){
            $code = substr($code, 5);
        }
        try {
            // Convert warnings/notices to exceptions for better DX
            set_error_handler(function($errno, $errstr, $errfile, $errline){
                if (error_reporting() === 0) return false;
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            });
            $result = eval($code);
            restore_error_handler();
            return $result;
        } catch (\Throwable $e){
            restore_error_handler();
            if( is_admin() ){
                add_action('admin_notices', function() use ($e, $id){
                    rshf_admin_notice(sprintf(__('خطا در اجرای اسنیپت PHP (ID: %d): %s', 'readystudio-hf'), $id, esc_html($e->getMessage())), 'error');
                });
            }
        }
    }
}

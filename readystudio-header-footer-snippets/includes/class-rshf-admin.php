<?php
if ( ! defined('ABSPATH') ) { exit; }

function rshf_register_settings(){
    register_setting('rshf_group', 'rshf_options', [
        'type' => 'array',
        'sanitize_callback' => function($input){
            $out = rshf_get_options();
            if( isset($input['head_code']) )   $out['head_code']   = rshf_sanitize_code_field($input['head_code']);
            if( isset($input['body_code']) )   $out['body_code']   = rshf_sanitize_code_field($input['body_code']);
            if( isset($input['footer_code']) ) $out['footer_code'] = rshf_sanitize_code_field($input['footer_code']);
            $out['safe_mode'] = isset($input['safe_mode']) ? 1 : 0;
            $out['cache_bust'] = time();
            return $out;
        },
        'default' => rshf_get_options(),
        'show_in_rest' => false,
    ]);
}

function rshf_register_admin_menus(){
    $cap = rshf_capability();
    add_menu_page(
        __('Ready HF & Snippets', 'readystudio-hf'),
        __('Ready HF', 'readystudio-hf'),
        $cap,
        'rshf',
        'rshf_render_page_main',
        'dashicons-editor-code',
        58
    );
    add_submenu_page('rshf', __('Global Scripts', 'readystudio-hf'), __('Global Scripts', 'readystudio-hf'), $cap, 'rshf', 'rshf_render_page_main');
    add_submenu_page('rshf', __('Snippets', 'readystudio-hf'), __('Snippets', 'readystudio-hf'), $cap, 'edit.php?post_type=' . RSHF_CPT);
    add_submenu_page('rshf', __('Settings', 'readystudio-hf'), __('Settings', 'readystudio-hf'), $cap, 'rshf-settings', 'rshf_render_page_settings');
    add_submenu_page('rshf', __('Import / Export', 'readystudio-hf'), __('Import / Export', 'readystudio-hf'), $cap, 'rshf-import-export', 'rshf_render_page_import_export');
}

function rshf_admin_enqueue($hook){
    if( isset($_GET['page']) && strpos($_GET['page'], 'rshf') === 0 ){
        wp_enqueue_style('rshf-admin', RSHF_URL . 'assets/admin.css', [], RSHF_VERSION);
        wp_enqueue_script('rshf-admin', RSHF_URL . 'assets/admin.js', ['jquery'], RSHF_VERSION, true);
        wp_localize_script('rshf-admin', 'RSHF', [
            'nonce' => wp_create_nonce('rshf_ajax'),
        ]);
    }
}
add_action('admin_enqueue_scripts', 'rshf_admin_enqueue');

function rshf_render_page_main(){
    if( !rshf_current_user_can_manage() ) wp_die(__('Access denied', 'readystudio-hf'));
    $opts = rshf_get_options();
    ?>
    <div class="wrap rshf-wrap">
        <h1 class="rshf-title">ReadyStudio — Header & Footer</h1>
        <p class="rshf-subtitle"><?php _e('کدهای سراسری هدر/بدنه/فوتر را اینجا درج کنید. برای مدیریت کدهای مجزا و قابل‌فعال/غیرفعال، از منوی «Snippets» استفاده کنید.', 'readystudio-hf'); ?></p>

        <form method="post" action="options.php" class="rshf-card">
            <?php settings_fields('rshf_group'); ?>
            <input type="hidden" name="rshf_options[cache_bust]" value="<?php echo esc_attr(time()); ?>" />

            <div class="rshf-grid">
                <div class="rshf-field">
                    <label for="rshf_head"><?php _e('Header &lt;head&gt;', 'readystudio-hf'); ?></label>
                    <textarea id="rshf_head" name="rshf_options[head_code]" rows="10" dir="ltr" spellcheck="false" placeholder="&lt;script&gt;...&lt;/script&gt;"><?php echo esc_textarea($opts['head_code']); ?></textarea>
                    <small><?php _e('برای کدهای آنالیتیکس/پیکسل/تأییدیه مالکیت.', 'readystudio-hf'); ?></small>
                </div>

                <div class="rshf-field">
                    <label for="rshf_body"><?php _e('Body (after &lt;body&gt;)', 'readystudio-hf'); ?></label>
                    <textarea id="rshf_body" name="rshf_options[body_code]" rows="8" dir="ltr" spellcheck="false" placeholder="&lt;script&gt;...&lt;/script&gt;"><?php echo esc_textarea($opts['body_code']); ?></textarea>
                    <small><?php _e('کدهایی که باید بلافاصله پس از باز شدن body قرار گیرند.', 'readystudio-hf'); ?></small>
                </div>

                <div class="rshf-field">
                    <label for="rshf_footer"><?php _e('Footer (&lt;/body&gt;)', 'readystudio-hf'); ?></label>
                    <textarea id="rshf_footer" name="rshf_options[footer_code]" rows="10" dir="ltr" spellcheck="false" placeholder="&lt;script&gt;...&lt;/script&gt;"><?php echo esc_textarea($opts['footer_code']); ?></textarea>
                    <small><?php _e('کدهای JS که بهتر است در انتهای صفحه بارگذاری شوند.', 'readystudio-hf'); ?></small>
                </div>
            </div>

            <p class="rshf-actions">
                <button type="submit" class="button button-primary button-hero"><?php _e('ذخیره تغییرات', 'readystudio-hf'); ?></button>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=edit.php?post_type=' . RSHF_CPT)); ?>"><?php _e('مدیریت Snippets', 'readystudio-hf'); ?></a>
            </p>
        </form>
    </div>
    <?php
}

function rshf_render_page_settings(){
    if( !rshf_current_user_can_manage() ) wp_die(__('Access denied', 'readystudio-hf'));
    $opts = rshf_get_options();
    ?>
    <div class="wrap rshf-wrap">
        <h1 class="rshf-title"><?php _e('Settings & Safe Mode', 'readystudio-hf'); ?></h1>
        <form method="post" action="options.php" class="rshf-card">
            <?php settings_fields('rshf_group'); ?>
            <div class="rshf-field-inline">
                <label><input type="checkbox" name="rshf_options[safe_mode]" value="1" <?php checked($opts['safe_mode'], 1); ?> /> <?php _e('فعال‌سازی حالت ایمن (غیرفعال‌سازی اجرای PHP Snippetها)', 'readystudio-hf'); ?></label>
                <a class="button" href="<?php echo esc_url( add_query_arg(['rshf_safe' => '1']) ); ?>"><?php _e('Safe Mode موقت (۱۵ دقیقه)', 'readystudio-hf'); ?></a>
                <a class="button" href="<?php echo esc_url( add_query_arg(['rshf_safe' => 'off']) ); ?>"><?php _e('غیرفعال کردن Safe Mode', 'readystudio-hf'); ?></a>
            </div>
            <p class="description"><?php _e('اگر اسنیپت PHP مشکل‌دار باعث خطای سایت شد، با افزودن ?rshf_safe=1 به URL (برای مدیر) اجرای PHP Snippetها تا ۱۵ دقیقه متوقف می‌شود.', 'readystudio-hf'); ?></p>
            <p class="rshf-actions">
                <button type="submit" class="button button-primary"><?php _e('ذخیره تنظیمات', 'readystudio-hf'); ?></button>
            </p>
        </form>
    </div>
    <?php
}

function rshf_render_page_import_export(){
    if( !rshf_current_user_can_manage() ) wp_die(__('Access denied', 'readystudio-hf'));

    // Export
    if( isset($_POST['rshf_export']) && rshf_check_nonce('rshf_export') ){
        $data = [
            'options'  => rshf_get_options(),
            'snippets' => [],
        ];
        $ids = get_posts([
            'post_type' => RSHF_CPT,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        foreach($ids as $id){
            $meta = rshf_get_snippet_meta($id);
            $data['snippets'][] = [
                'title'   => get_the_title($id),
                'content' => get_post_field('post_content', $id),
                'meta'    => $meta,
            ];
        }
        $json = wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="rshf-export-' . date('Ymd-His') . '.json"');
        echo $json;
        exit;
    }

    // Import
    if( isset($_POST['rshf_import']) && rshf_check_nonce('rshf_import') && !empty($_FILES['rshf_file']['tmp_name']) ){
        $raw = file_get_contents($_FILES['rshf_file']['tmp_name']);
        $data = json_decode($raw, true);
        if( is_array($data) ){
            if( isset($data['options']) && is_array($data['options']) ){
                rshf_update_options($data['options']);
            }
            if( isset($data['snippets']) && is_array($data['snippets']) ){
                foreach($data['snippets'] as $s){
                    $post_id = wp_insert_post([
                        'post_type'   => RSHF_CPT,
                        'post_status' => 'publish',
                        'post_title'  => sanitize_text_field($s['title'] ?? 'Imported Snippet'),
                        'post_content'=> (string)($s['content'] ?? ''),
                    ]);
                    if($post_id){
                        $meta = $s['meta'] ?? [];
                        foreach(['type','location','active','priority'] as $k){
                            if(isset($meta[$k])){
                                update_post_meta($post_id, 'rshf_' . $k, sanitize_text_field($meta[$k]));
                            }
                        }
                    }
                }
            }
            add_action('admin_notices', function(){
                rshf_admin_notice(__('درون‌ریزی با موفقیت انجام شد.', 'readystudio-hf'), 'success');
            });
        } else {
            add_action('admin_notices', function(){
                rshf_admin_notice(__('فایل JSON نامعتبر است.', 'readystudio-hf'), 'error');
            });
        }
    }

    ?>
    <div class="wrap rshf-wrap">
        <h1 class="rshf-title"><?php _e('Import / Export', 'readystudio-hf'); ?></h1>

        <div class="rshf-grid-2">
            <div class="rshf-card">
                <h2><?php _e('Export all settings & snippets', 'readystudio-hf'); ?></h2>
                <form method="post">
                    <?php rshf_nonce_field('rshf_export'); ?>
                    <p><?php _e('یک فایل JSON شامل تنظیمات و تمام اسنیپت‌ها دانلود خواهد شد.', 'readystudio-hf'); ?></p>
                    <button class="button button-primary"><?php _e('Export JSON', 'readystudio-hf'); ?></button>
                    <input type="hidden" name="rshf_export" value="1"/>
                </form>
            </div>
            <div class="rshf-card">
                <h2><?php _e('Import from JSON', 'readystudio-hf'); ?></h2>
                <form method="post" enctype="multipart/form-data">
                    <?php rshf_nonce_field('rshf_import'); ?>
                    <p><input type="file" name="rshf_file" accept="application/json" required /></p>
                    <button class="button"><?php _e('Import', 'readystudio-hf'); ?></button>
                    <input type="hidden" name="rshf_import" value="1"/>
                </form>
            </div>
        </div>
    </div>
    <?php
}

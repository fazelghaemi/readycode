<?php
if ( ! defined('ABSPATH') ) { exit; }

define('RSHF_CPT', 'rshf_snippet');

function rshf_register_snippet_cpt(){
    $labels = [
        'name'               => __('Snippets', 'readystudio-hf'),
        'singular_name'      => __('Snippet', 'readystudio-hf'),
        'menu_name'          => __('RSHF Snippets', 'readystudio-hf'),
        'add_new'            => __('Add New', 'readystudio-hf'),
        'add_new_item'       => __('Add New Snippet', 'readystudio-hf'),
        'edit_item'          => __('Edit Snippet', 'readystudio-hf'),
        'new_item'           => __('New Snippet', 'readystudio-hf'),
        'view_item'          => __('View Snippet', 'readystudio-hf'),
        'search_items'       => __('Search Snippets', 'readystudio-hf'),
        'not_found'          => __('No snippets found', 'readystudio-hf'),
        'not_found_in_trash' => __('No snippets found in Trash', 'readystudio-hf'),
    ];
    $args = [
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => false,
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
        'hierarchical'       => false,
        'supports'           => ['title', 'editor'],
        'has_archive'        => false,
        'rewrite'            => false,
        'menu_icon'          => 'dashicons-editor-code',
    ];
    register_post_type(RSHF_CPT, $args);
}
add_action('init', 'rshf_register_snippet_cpt');

function rshf_snippet_meta_keys(){
    return ['type','location','active','priority'];
}

function rshf_get_snippet_meta($post_id){
    $keys = rshf_snippet_meta_keys();
    $meta = [];
    foreach($keys as $k){ $meta[$k] = get_post_meta($post_id, 'rshf_' . $k, true); }
    $meta['type']     = $meta['type'] ?: 'js';
    $meta['location'] = $meta['location'] ?: 'header';
    $meta['active']   = ($meta['active'] === '') ? '1' : $meta['active'];
    $meta['priority'] = $meta['priority'] !== '' ? intval($meta['priority']) : 10;
    return $meta;
}

function rshf_save_snippet_meta($post_id){
    if( !isset($_POST['rshf_meta_nonce']) || !wp_verify_nonce($_POST['rshf_meta_nonce'], 'rshf_save_meta') ) return;
    if( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if( !rshf_current_user_can_manage() ) return;

    $input = [
        'type'     => isset($_POST['rshf_type']) ? sanitize_text_field($_POST['rshf_type']) : 'js',
        'location' => isset($_POST['rshf_location']) ? sanitize_text_field($_POST['rshf_location']) : 'header',
        'active'   => isset($_POST['rshf_active']) ? '1' : '0',
        'priority' => isset($_POST['rshf_priority']) ? intval($_POST['rshf_priority']) : 10,
    ];
    foreach( rshf_snippet_meta_keys() as $k ){
        update_post_meta($post_id, 'rshf_' . $k, $input[$k]);
    }
    delete_transient('rshf_cache_head');
    delete_transient('rshf_cache_body');
    delete_transient('rshf_cache_footer');
}
add_action('save_post_' . RSHF_CPT, 'rshf_save_snippet_meta');

function rshf_add_meta_boxes(){
    add_meta_box('rshf_meta', __('Snippet Settings', 'readystudio-hf'), 'rshf_render_meta_box', RSHF_CPT, 'side', 'high');
}
add_action('add_meta_boxes', 'rshf_add_meta_boxes');

function rshf_render_meta_box($post){
    $meta = rshf_get_snippet_meta($post->ID);
    wp_nonce_field('rshf_save_meta', 'rshf_meta_nonce'); ?>
    <p>
        <label for="rshf_type"><strong><?php _e('Type', 'readystudio-hf'); ?></strong></label><br>
        <select name="rshf_type" id="rshf_type">
            <option value="js"   <?php selected($meta['type'],'js'); ?>>JavaScript</option>
            <option value="css"  <?php selected($meta['type'],'css'); ?>>CSS</option>
            <option value="html" <?php selected($meta['type'],'html'); ?>>HTML</option>
            <option value="php"  <?php selected($meta['type'],'php'); ?>>PHP</option>
        </select>
    </p>
    <p>
        <label for="rshf_location"><strong><?php _e('Location', 'readystudio-hf'); ?></strong></label><br>
        <select name="rshf_location" id="rshf_location">
            <option value="header" <?php selected($meta['location'],'header'); ?>>&lt;head&gt;</option>
            <option value="body"   <?php selected($meta['location'],'body'); ?>>after &lt;body&gt;</option>
            <option value="footer" <?php selected($meta['location'],'footer'); ?>>footer (&lt;/body&gt;)</option>
            <option value="manual" <?php selected($meta['location'],'manual'); ?>>Manual (Shortcode)</option>
        </select>
    </p>
    <p>
        <label><input type="checkbox" name="rshf_active" value="1" <?php checked($meta['active'],'1'); ?> /> <?php _e('Active', 'readystudio-hf'); ?></label>
    </p>
    <p>
        <label for="rshf_priority"><strong><?php _e('Priority', 'readystudio-hf'); ?></strong></label><br>
        <input type="number" min="0" step="1" name="rshf_priority" id="rshf_priority" value="<?php echo esc_attr($meta['priority']); ?>" style="width:100%;">
    </p>
    <p><?php _e('For "manual" location, use shortcode: [rshf_snippet id="123"]', 'readystudio-hf'); ?></p>
<?php }

// Columns with toggle
function rshf_snippet_columns($columns){
    $new = [];
    $new['cb'] = $columns['cb'];
    $new['title'] = __('Title', 'readystudio-hf');
    $new['rshf_type'] = __('Type', 'readystudio-hf');
    $new['rshf_location'] = __('Location', 'readystudio-hf');
    $new['rshf_active'] = __('Active', 'readystudio-hf');
    $new['date'] = $columns['date'];
    return $new;
}
add_filter('manage_' . RSHF_CPT . '_posts_columns', 'rshf_snippet_columns');

function rshf_snippet_custom_column($column, $post_id){
    $meta = rshf_get_snippet_meta($post_id);
    if($column === 'rshf_type'){
        echo esc_html(strtoupper($meta['type']));
    } elseif($column === 'rshf_location'){
        echo esc_html($meta['location']);
    } elseif($column === 'rshf_active'){
        $checked = $meta['active'] === '1' ? 'checked' : '';
        echo '<label class="rshf-switch"><input type="checkbox" class="rshf-toggle" data-id="'.esc_attr($post_id).'" '.$checked.' /><span class="rshf-slider"></span></label>';
    }
}
add_action('manage_' . RSHF_CPT . '_posts_custom_column', 'rshf_snippet_custom_column', 10, 2);

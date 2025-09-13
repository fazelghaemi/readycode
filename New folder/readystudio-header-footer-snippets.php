<?php
/**
 * Plugin Name: ReadyStudio — Header & Footer + Snippets
 * Description: افزودن کدهای سفارشی به هدر/فوتر/بدنه و مدیریت اسنیپت‌های PHP/CSS/JS با حالت ایمن (Safe Mode)، ایمپورت/اکسپورت و سوئیچ فعال/غیرفعال.
 * Version: 1.1.0
 * Author: Ready Studio (Fazel Ghaemi)
 * Author URI: https://readystudio.ir
 * Text Domain: readystudio-hf
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: true
 */

if ( ! defined('ABSPATH') ) { exit; }

define('RSHF_VERSION', '1.1.0');
define('RSHF_FILE', __FILE__);
define('RSHF_PATH', plugin_dir_path(__FILE__));
define('RSHF_URL',  plugin_dir_url(__FILE__));

require_once RSHF_PATH . 'includes/helpers.php';
require_once RSHF_PATH . 'includes/class-rshf-safemode.php';
require_once RSHF_PATH . 'includes/class-rshf-snippets.php';
require_once RSHF_PATH . 'includes/class-rshf-admin.php';
require_once RSHF_PATH . 'includes/class-rshf-front.php';

function rshf_plugins_loaded(){
    load_plugin_textdomain('readystudio-hf', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'rshf_plugins_loaded');

register_activation_hook(__FILE__, function(){
    rshf_register_snippet_cpt();
    flush_rewrite_rules(false);
});

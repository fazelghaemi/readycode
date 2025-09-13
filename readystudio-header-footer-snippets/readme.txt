=== ReadyStudio — Header & Footer + Snippets (v1) ===
Contributors: readystudio
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add scripts/styles to header/body/footer and manage PHP/CSS/JS snippets safely. Includes Safe Mode, import/export, CPT-based snippet manager, and shortcode for manual placement.

== Description ==

- Global fields for `<head>`, body-open and footer.
- Snippet Manager (custom post type): types **PHP/CSS/JS/HTML**, locations **header/body/footer/manual**.
- **Safe Mode**: disable PHP execution (persistent or temporary via `?rshf_safe=1` for 15 minutes).
- **Import/Export** all settings and snippets as JSON.
- **Shortcode**: `[rshf_snippet id="123"]` for manual placement.
- Caching via transients for aggregated head/body/footer output.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin.
3. Go to **Ready HF -> Global Scripts** or **Ready HF -> Snippets**.

== Frequently Asked Questions ==

= Does it execute PHP? =
Yes, via PHP `eval()` wrapped with an error handler. Use with caution. Enable Safe Mode if anything goes wrong.

= Multisite support? =
The plugin is network-aware. Only network admins can manage it if network-activated.

== Changelog ==

= 1.0.0 =
Initial release.

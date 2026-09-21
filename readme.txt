=== EDH Block Converter for Divi ===
Contributors: encodedothost
Tags: divi, gutenberg, blocks, migration, converter
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Converts Divi page-builder content in posts and pages to native Gutenberg core blocks.

== Description ==

Divi 4 stores page content as nested shortcodes. Without Divi, the content is not usable. This plugin reads the Divi shortcodes of a post, and writes core blocks.

* Divi does not need to be active. The plugin reads the stored content.
* The plugin makes only core blocks. It adds no blocks of its own, thus you can remove it after the migration.
* Before each write, the plugin saves the original content. You can restore each post.
* A preview shows the result and a report before you convert a post.
* The plugin keeps the content and the basic design: alignment, colours, padding, margin, and backgrounds.
* The plugin does not keep hover effects, animations, responsive values, and custom CSS.

This plugin is not affiliated with Elegant Themes. Divi is a trademark of Elegant Themes, Inc.

= Modules =

Direct equivalents: section, row, column, specialty section, text, heading, image, button, divider, code, video, audio, gallery, toggle, accordion, social media follow, search, login, blog, portfolio, post title, post content, post navigation, comments, and menu.

Best-effort conversion (the report shows each one): blurb, call to action, fullwidth header, testimonial, person, pricing tables, tabs, slider, counters, countdown timer, map, video slider, and shop.

Not convertible (the report shows each one): contact form, email opt-in, sidebar, icon, and the WooCommerce product modules.

Shortcodes from other plugins go into shortcode blocks without changes.

= WP-CLI =

* `wp edh-divi scan [--post_type=<types>] [--state=<all|divi|converted>] [--format=<format>]`
* `wp edh-divi convert [<id>...] [--all] [--post_type=<types>] [--dry-run] [--show-markup] [--force] [--keep-form-shortcodes]`
* `wp edh-divi restore [<id>...] [--all] [--post_type=<types>] [--yes]`

== Installation ==

1. Make a database backup.
2. Install and activate the plugin.
3. Go to Tools > Block Converter for Divi.
4. Use Preview to examine a post, then use Convert.

== Frequently Asked Questions ==

= Does the plugin convert Divi 5 content? =

Not at this time. The scan shows Divi 5 posts, but the plugin does not convert them.

= Where is the backup? =

The original content is in the post meta `_edh_dg_original_content`. The uninstall procedure keeps the backups. To delete them at uninstall, define `EDH_DG_DELETE_BACKUPS` as `true` in `wp-config.php`.

= Does the conversion change the modified date of a post? =

No. The plugin keeps the modified date.

= Does the plugin convert the Divi Library and the Theme Builder? =

The plugin puts the content of a global module into each post that uses it. It does not convert Theme Builder templates.

== Changelog ==

= 0.1.1 =
* New name: EDH Block Converter for Divi. The slug, the text domain, the REST namespace, and the admin screen have the new name.
* Fix: a section with a background image keeps the content width of the theme.
* Tested up to WordPress 7.1.

= 0.1.0 =
* First version. Divi 4 shortcodes to core blocks, admin screen, REST routes, and WP-CLI commands.

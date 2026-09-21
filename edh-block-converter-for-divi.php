<?php
/**
 * Plugin Name:       EDH Block Converter for Divi
 * Plugin URI:        https://github.com/EncodeDotHost/edh-block-converter-for-divi
 * Description:       Converts Divi page-builder content in posts and pages to native Gutenberg core blocks.
 * Version:           0.1.1
 * Requires at least: 6.6
 * Requires PHP:      7.4
 * Author:            EncodeDotHost
 * Author URI:        https://encode.host
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       edh-block-converter-for-divi
 *
 * @package EDH\DiviGutenberg
 */

defined( 'ABSPATH' ) || exit;

define( 'EDH_DG_VERSION', '0.1.1' );
define( 'EDH_DG_FILE', __FILE__ );
define( 'EDH_DG_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDH_DG_URL', plugin_dir_url( __FILE__ ) );

require_once EDH_DG_DIR . 'includes/class-autoloader.php';

EDH\DiviGutenberg\Autoloader::register();

add_action(
	'plugins_loaded',
	static function () {
		EDH\DiviGutenberg\Plugin::instance()->init();
	}
);

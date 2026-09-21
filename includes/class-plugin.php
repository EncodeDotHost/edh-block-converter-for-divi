<?php
/**
 * Plugin loader.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

/**
 * Registers the admin screen, the REST routes, and the WP-CLI command.
 */
final class Plugin {

	const CAPABILITY = 'manage_options';

	/**
	 * Instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Gets the instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Adds the hooks.
	 */
	public function init() {
		add_action( 'rest_api_init', array( new Rest\Rest_Controller(), 'register_routes' ) );

		if ( is_admin() ) {
			( new Admin\Admin_Page() )->init();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'edh-divi', Cli\Cli_Command::class );
		}
	}
}

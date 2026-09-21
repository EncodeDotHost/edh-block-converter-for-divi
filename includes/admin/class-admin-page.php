<?php
/**
 * Admin screen.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Admin;

use EDH\DiviGutenberg\Plugin;
use EDH\DiviGutenberg\Rest\Rest_Controller;

/**
 * Tools > Divi to Gutenberg.
 *
 * The screen is a shell. The script gets the data from the REST routes.
 */
final class Admin_Page {

	const SLUG = 'edh-divi-gutenberg';

	/**
	 * Hook suffix of the screen.
	 *
	 * @var string
	 */
	private $hook = '';

	/**
	 * Adds the hooks.
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Adds the screen to the Tools menu.
	 */
	public function add_page() {
		$this->hook = (string) add_management_page(
			__( 'Divi to Gutenberg', 'edh-divi-gutenberg' ),
			__( 'Divi to Gutenberg', 'edh-divi-gutenberg' ),
			Plugin::CAPABILITY,
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Loads the script and the style on the screen of the plugin only.
	 *
	 * @param string $hook Current screen hook.
	 */
	public function enqueue( $hook ) {
		if ( $hook !== $this->hook ) {
			return;
		}

		wp_enqueue_style( self::SLUG, EDH_DG_URL . 'assets/admin.css', array(), EDH_DG_VERSION );
		wp_enqueue_script( self::SLUG, EDH_DG_URL . 'assets/admin.js', array( 'wp-api-fetch', 'wp-i18n' ), EDH_DG_VERSION, true );
		wp_set_script_translations( self::SLUG, 'edh-divi-gutenberg' );
		wp_add_inline_script(
			self::SLUG,
			'window.edhDiviGutenberg = ' . wp_json_encode( array( 'path' => '/' . Rest_Controller::REST_NAMESPACE ) ) . ';',
			'before'
		);
	}

	/**
	 * Prints the screen.
	 */
	public function render() {
		if ( ! current_user_can( Plugin::CAPABILITY ) ) {
			return;
		}
		?>
		<div class="wrap edh-dg">
			<h1><?php esc_html_e( 'Divi to Gutenberg', 'edh-divi-gutenberg' ); ?></h1>
			<p>
				<?php esc_html_e( 'This tool converts Divi 4 content to core blocks. It keeps the original content in a backup, and you can restore each post.', 'edh-divi-gutenberg' ); ?>
				<strong><?php esc_html_e( 'Make a database backup before you convert many posts.', 'edh-divi-gutenberg' ); ?></strong>
			</p>

			<div class="edh-dg-toolbar">
				<button type="button" class="button button-primary" id="edh-dg-convert-selected" disabled><?php esc_html_e( 'Convert selected posts', 'edh-divi-gutenberg' ); ?></button>
				<button type="button" class="button" id="edh-dg-restore-selected" disabled><?php esc_html_e( 'Restore selected posts', 'edh-divi-gutenberg' ); ?></button>
				<button type="button" class="button" id="edh-dg-refresh"><?php esc_html_e( 'Scan again', 'edh-divi-gutenberg' ); ?></button>
				<span id="edh-dg-status" role="status" aria-live="polite"></span>
			</div>
			<progress id="edh-dg-progress" value="0" max="1" hidden></progress>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<td class="manage-column check-column"><input type="checkbox" id="edh-dg-select-all" aria-label="<?php esc_attr_e( 'Select all posts', 'edh-divi-gutenberg' ); ?>"></td>
						<th scope="col"><?php esc_html_e( 'Title', 'edh-divi-gutenberg' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Type', 'edh-divi-gutenberg' ); ?></th>
						<th scope="col"><?php esc_html_e( 'State', 'edh-divi-gutenberg' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Modules', 'edh-divi-gutenberg' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Modules without a mapper', 'edh-divi-gutenberg' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Actions', 'edh-divi-gutenberg' ); ?></th>
					</tr>
				</thead>
				<tbody id="edh-dg-rows">
					<tr><td colspan="7"><?php esc_html_e( 'Scan in progress…', 'edh-divi-gutenberg' ); ?></td></tr>
				</tbody>
			</table>

			<div id="edh-dg-preview" hidden>
				<h2 id="edh-dg-preview-title"></h2>
				<h3><?php esc_html_e( 'Report', 'edh-divi-gutenberg' ); ?></h3>
				<ul id="edh-dg-preview-report"></ul>
				<h3><?php esc_html_e( 'Block markup', 'edh-divi-gutenberg' ); ?></h3>
				<textarea id="edh-dg-preview-markup" class="large-text code" rows="16" readonly></textarea>
			</div>
		</div>
		<?php
	}
}

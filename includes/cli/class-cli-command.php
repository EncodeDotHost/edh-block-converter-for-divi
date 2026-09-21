<?php
/**
 * WP-CLI command.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Cli;

use EDH\DiviGutenberg\Post_Processor;
use EDH\DiviGutenberg\Scanner;
use WP_CLI;
use WP_CLI\Utils;

/**
 * Converts Divi content to Gutenberg core blocks.
 */
final class Cli_Command {

	/**
	 * Lists the posts that use the Divi builder, and the converted posts.
	 *
	 * ## OPTIONS
	 *
	 * [--post_type=<types>]
	 * : Comma-separated post types. Default: all public post types.
	 *
	 * [--state=<state>]
	 * : Posts to show.
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - divi
	 *   - converted
	 * ---
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - ids
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     wp edh-divi scan --post_type=page
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function scan( $args, $assoc_args ) {
		$scanner = new Scanner();
		$found   = $scanner->find( Utils\get_flag_value( $assoc_args, 'state', 'all' ), $this->post_types( $assoc_args ) );
		$format  = Utils\get_flag_value( $assoc_args, 'format', 'table' );

		if ( 'ids' === $format ) {
			WP_CLI::line( implode( ' ', $found['ids'] ) );
			return;
		}

		$rows = array();
		foreach ( $found['ids'] as $post_id ) {
			$summary = $scanner->summary( $post_id );
			if ( $summary ) {
				$rows[] = array(
					'ID'          => $summary['id'],
					'type'        => $summary['type'],
					'status'      => $summary['status'],
					'converted'   => $summary['converted'] ? 'yes' : 'no',
					'modules'     => $summary['modules'],
					'unsupported' => implode( ', ', $summary['unsupported'] ),
					'title'       => $summary['title'],
				);
			}
		}

		Utils\format_items( $format, $rows, array( 'ID', 'type', 'status', 'converted', 'modules', 'unsupported', 'title' ) );
	}

	/**
	 * Converts posts to core blocks. The original content stays in a backup.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : IDs of the posts to convert.
	 *
	 * [--all]
	 * : Convert all posts that use the Divi builder.
	 *
	 * [--post_type=<types>]
	 * : With --all, comma-separated post types.
	 *
	 * [--dry-run]
	 * : Show the result. Do not write to the database.
	 *
	 * [--show-markup]
	 * : With --dry-run, print the block markup.
	 *
	 * [--force]
	 * : Convert a converted post again from its original content.
	 *
	 * [--keep-form-shortcodes]
	 * : Keep the Divi form shortcodes in shortcode blocks. They work only while Divi is active.
	 *
	 * ## EXAMPLES
	 *
	 *     wp edh-divi convert 42 --dry-run --show-markup
	 *     wp edh-divi convert --all --post_type=page
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function convert( $args, $assoc_args ) {
		$dry_run = (bool) Utils\get_flag_value( $assoc_args, 'dry-run', false );
		$force   = (bool) Utils\get_flag_value( $assoc_args, 'force', false );
		$options = array( 'keep_form_shortcodes' => (bool) Utils\get_flag_value( $assoc_args, 'keep-form-shortcodes', false ) );
		$ids     = $this->target_ids( $args, $assoc_args, $force ? 'all' : 'divi' );

		$processor = new Post_Processor();
		$counts    = array(
			'ok'      => 0,
			'skipped' => 0,
		);

		foreach ( $ids as $post_id ) {
			$result = $dry_run ? $processor->preview( $post_id, $options ) : $processor->convert( $post_id, $force, $options );

			if ( is_wp_error( $result ) ) {
				++$counts['skipped'];
				WP_CLI::warning( sprintf( '#%d: %s', $post_id, $result->get_error_message() ) );
				continue;
			}
			if ( ! $result->has_divi ) {
				++$counts['skipped'];
				WP_CLI::warning( sprintf( '#%d: no Divi 4 shortcodes.', $post_id ) );
				continue;
			}

			++$counts['ok'];
			WP_CLI::log( sprintf( '#%d: %s, %d blocks at the top level.', $post_id, $dry_run ? 'can convert' : 'converted', count( $result->blocks ) ) );

			foreach ( $result->report->notices() as $notice ) {
				WP_CLI::log( sprintf( '    [%s] %s: %s', $notice['level'], $notice['tag'], $notice['message'] ) );
			}
			if ( $dry_run && Utils\get_flag_value( $assoc_args, 'show-markup', false ) ) {
				WP_CLI::line( $result->markup );
			}
		}

		WP_CLI::success( sprintf( '%s: %d. Skipped: %d.', $dry_run ? 'Dry run, posts that can convert' : 'Converted', $counts['ok'], $counts['skipped'] ) );
	}

	/**
	 * Restores the original Divi content of converted posts.
	 *
	 * ## OPTIONS
	 *
	 * [<id>...]
	 * : IDs of the posts to restore.
	 *
	 * [--all]
	 * : Restore all converted posts.
	 *
	 * [--post_type=<types>]
	 * : With --all, comma-separated post types.
	 *
	 * [--yes]
	 * : Do not ask for confirmation.
	 *
	 * ## EXAMPLES
	 *
	 *     wp edh-divi restore 42
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function restore( $args, $assoc_args ) {
		$ids = $this->target_ids( $args, $assoc_args, 'converted' );

		WP_CLI::confirm( sprintf( 'Restore the Divi content of %d posts? Changes that you made after the conversion go to a revision.', count( $ids ) ), $assoc_args );

		$processor = new Post_Processor();
		$restored  = 0;

		foreach ( $ids as $post_id ) {
			$result = $processor->restore( $post_id );
			if ( is_wp_error( $result ) ) {
				WP_CLI::warning( sprintf( '#%d: %s', $post_id, $result->get_error_message() ) );
				continue;
			}
			++$restored;
			WP_CLI::log( sprintf( '#%d: restored.', $post_id ) );
		}

		WP_CLI::success( sprintf( 'Restored: %d.', $restored ) );
	}

	/**
	 * Gets the post IDs for a command.
	 *
	 * @param array  $args       Positional arguments.
	 * @param array  $assoc_args Named arguments.
	 * @param string $state      Scanner state for --all.
	 * @return int[]
	 */
	private function target_ids( $args, $assoc_args, $state ) {
		if ( Utils\get_flag_value( $assoc_args, 'all', false ) ) {
			$found = ( new Scanner() )->find( $state, $this->post_types( $assoc_args ) );
			$ids   = $found['ids'];
		} else {
			$ids = array_values( array_filter( array_map( 'absint', $args ) ) );
		}

		if ( ! $ids ) {
			WP_CLI::error( 'No posts. Give post IDs, or use --all.' );
		}

		return $ids;
	}

	/**
	 * Gets the post types argument.
	 *
	 * @param array $assoc_args Named arguments.
	 * @return string[]
	 */
	private function post_types( $assoc_args ) {
		$value = (string) Utils\get_flag_value( $assoc_args, 'post_type', '' );
		return array_values( array_filter( array_map( 'sanitize_key', explode( ',', $value ) ) ) );
	}
}

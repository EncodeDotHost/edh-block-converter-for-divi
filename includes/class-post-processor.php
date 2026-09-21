<?php
/**
 * Post processor.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

use WP_Error;

/**
 * Converts and restores posts in the database.
 */
final class Post_Processor {

	/**
	 * Converter.
	 *
	 * @var Converter
	 */
	private $converter;

	/**
	 * Backup.
	 *
	 * @var Backup
	 */
	private $backup;

	/**
	 * Constructor.
	 *
	 * @param Converter|null $converter Converter.
	 * @param Backup|null    $backup    Backup.
	 */
	public function __construct( $converter = null, $backup = null ) {
		$this->converter = $converter ? $converter : new Converter();
		$this->backup    = $backup ? $backup : new Backup();
	}

	/**
	 * Converts the content of a post without a database write.
	 *
	 * For a converted post, the preview uses the original content.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $options Converter options.
	 * @return Result|WP_Error
	 */
	public function preview( $post_id, array $options = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'edh_dg_not_found', __( 'The post does not exist.', 'edh-divi-gutenberg' ) );
		}

		$content = $this->backup->exists( $post->ID ) ? $this->backup->content( $post->ID ) : $post->post_content;

		return $this->converter->convert( $content, $post->ID, $options );
	}

	/**
	 * Converts a post, and writes the result.
	 *
	 * @param int                 $post_id Post ID.
	 * @param bool                $force   True to convert again from the original content.
	 * @param array<string,mixed> $options Converter options.
	 * @return Result|WP_Error
	 */
	public function convert( $post_id, $force = false, array $options = array() ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'edh_dg_not_found', __( 'The post does not exist.', 'edh-divi-gutenberg' ) );
		}
		if ( $this->is_converted( $post->ID ) && ! $force ) {
			return new WP_Error( 'edh_dg_already_converted', __( 'The post is already converted. Use the force option to convert it again.', 'edh-divi-gutenberg' ) );
		}

		$result = $this->preview( $post->ID, $options );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( ! $result->has_divi ) {
			return new WP_Error( 'edh_dg_no_divi', __( 'The post has no Divi 4 shortcodes.', 'edh-divi-gutenberg' ) );
		}
		if ( '' === $result->markup ) {
			return new WP_Error( 'edh_dg_empty', __( 'The conversion gave no blocks. The post is not changed.', 'edh-divi-gutenberg' ) );
		}

		$this->backup->store( $post );

		$written = $this->write_content( $post->ID, $result->markup );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		update_post_meta( $post->ID, '_et_pb_use_builder', 'off' );
		delete_post_meta( $post->ID, '_et_pb_old_content' );
		update_post_meta(
			$post->ID,
			Backup::META_CONVERTED,
			array(
				'version' => EDH_DG_VERSION,
				'time'    => time(),
			)
		);
		update_post_meta( $post->ID, Backup::META_REPORT, wp_slash( $result->report->to_array() ) );

		return $result;
	}

	/**
	 * Restores the original Divi content of a post.
	 *
	 * @param int $post_id Post ID.
	 * @return true|WP_Error
	 */
	public function restore( $post_id ) {
		if ( ! get_post( $post_id ) || ! $this->backup->exists( $post_id ) ) {
			return new WP_Error( 'edh_dg_no_backup', __( 'The post has no backup.', 'edh-divi-gutenberg' ) );
		}

		$written = $this->write_content( $post_id, $this->backup->content( $post_id ) );
		if ( is_wp_error( $written ) ) {
			return $written;
		}

		$this->backup->restore_meta( $post_id );
		$this->backup->clear( $post_id );

		return true;
	}

	/**
	 * Tells if the plugin converted a post.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function is_converted( $post_id ) {
		return metadata_exists( 'post', $post_id, Backup::META_CONVERTED );
	}

	/**
	 * Writes post content, and keeps the modified date. WordPress makes a revision when revisions are on.
	 *
	 * The content filters are off during the write. The source is content that
	 * the site already stores, and an administrator starts the operation. With
	 * the filters on, WP-CLI (which has no user) removes styles and embeds.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Content.
	 * @return true|WP_Error
	 */
	private function write_content( $post_id, $content ) {
		$had_filters = false !== has_filter( 'content_save_pre', 'wp_filter_post_kses' );
		if ( $had_filters ) {
			kses_remove_filters();
		}

		// A conversion is not an editorial change, thus keep the modified date.
		$keep_modified = static function ( $data, $postarr ) use ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post && isset( $postarr['ID'] ) && (int) $postarr['ID'] === (int) $post_id ) {
				$data['post_modified']     = $post->post_modified;
				$data['post_modified_gmt'] = $post->post_modified_gmt;
			}
			return $data;
		};
		add_filter( 'wp_insert_post_data', $keep_modified, 99, 2 );

		$updated = wp_update_post(
			wp_slash(
				array(
					'ID'           => $post_id,
					'post_content' => $content,
				)
			),
			true
		);

		remove_filter( 'wp_insert_post_data', $keep_modified, 99 );

		if ( $had_filters ) {
			kses_init_filters();
		}

		return is_wp_error( $updated ) ? $updated : true;
	}
}

<?php
/**
 * Backup of the original Divi content.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

/**
 * Stores the original content of a post in post meta, and restores it.
 */
final class Backup {

	const META_CONTENT   = '_edh_dg_original_content';
	const META_DIVI      = '_edh_dg_original_meta';
	const META_CONVERTED = '_edh_dg_converted';
	const META_REPORT    = '_edh_dg_report';

	/**
	 * Divi meta keys that the conversion changes.
	 *
	 * @var string[]
	 */
	const DIVI_KEYS = array( '_et_pb_use_builder', '_et_pb_old_content' );

	/**
	 * Tells if a post has a backup.
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function exists( $post_id ) {
		return metadata_exists( 'post', $post_id, self::META_CONTENT );
	}

	/**
	 * Gets the original content.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function content( $post_id ) {
		return (string) get_post_meta( $post_id, self::META_CONTENT, true );
	}

	/**
	 * Stores the original content. An existing backup is not changed.
	 *
	 * @param \WP_Post $post Post.
	 */
	public function store( $post ) {
		if ( $this->exists( $post->ID ) ) {
			return;
		}

		$divi = array();
		foreach ( self::DIVI_KEYS as $key ) {
			if ( metadata_exists( 'post', $post->ID, $key ) ) {
				$divi[ $key ] = get_post_meta( $post->ID, $key, true );
			}
		}

		update_post_meta( $post->ID, self::META_CONTENT, wp_slash( $post->post_content ) );
		update_post_meta( $post->ID, self::META_DIVI, wp_slash( $divi ) );
	}

	/**
	 * Restores the Divi meta values, and gets the original content.
	 *
	 * The caller writes the content to the post, then calls clear().
	 *
	 * @param int $post_id Post ID.
	 */
	public function restore_meta( $post_id ) {
		$divi = get_post_meta( $post_id, self::META_DIVI, true );
		$divi = is_array( $divi ) ? $divi : array();

		foreach ( self::DIVI_KEYS as $key ) {
			if ( array_key_exists( $key, $divi ) ) {
				update_post_meta( $post_id, $key, wp_slash( $divi[ $key ] ) );
			} else {
				delete_post_meta( $post_id, $key );
			}
		}
	}

	/**
	 * Deletes the backup and the conversion data of a post.
	 *
	 * @param int $post_id Post ID.
	 */
	public function clear( $post_id ) {
		foreach ( array( self::META_CONTENT, self::META_DIVI, self::META_CONVERTED, self::META_REPORT ) as $key ) {
			delete_post_meta( $post_id, $key );
		}
	}
}

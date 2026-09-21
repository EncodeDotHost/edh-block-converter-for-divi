<?php
/**
 * Scanner.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

use EDH\DiviGutenberg\Mapper\Mapper_Registry;
use WP_Query;

/**
 * Finds the posts that use the Divi builder, and the posts that the plugin converted.
 */
final class Scanner {

	const STATUSES = array( 'publish', 'draft', 'pending', 'private', 'future' );

	/**
	 * Gets the post types that the plugin can convert.
	 *
	 * @return string[]
	 */
	public function post_types() {
		$types = array_values( array_diff( get_post_types( array( 'public' => true ) ), array( 'attachment' ) ) );

		/**
		 * Filters the post types that the plugin can convert.
		 *
		 * @param string[] $types Post type names.
		 */
		return (array) apply_filters( 'edh_dg_post_types', $types );
	}

	/**
	 * Finds post IDs.
	 *
	 * @param string   $state      `divi` for posts to convert, `converted` for converted posts, `all` for the two.
	 * @param string[] $post_types Post types. Empty for all.
	 * @param int      $per_page   Page size. -1 for no limit.
	 * @param int      $page       Page number.
	 * @return array{ids:int[],total:int}
	 */
	public function find( $state = 'all', array $post_types = array(), $per_page = -1, $page = 1 ) {
		$divi      = array(
			'key'   => '_et_pb_use_builder',
			'value' => 'on',
		);
		$converted = array(
			'key'     => Backup::META_CONVERTED,
			'compare' => 'EXISTS',
		);

		if ( 'divi' === $state ) {
			$meta_query = array( $divi );
		} elseif ( 'converted' === $state ) {
			$meta_query = array( $converted );
		} else {
			$meta_query = array(
				'relation' => 'OR',
				$divi,
				$converted,
			);
		}

		$allowed    = $this->post_types();
		$post_types = $post_types ? array_values( array_intersect( $post_types, $allowed ) ) : $allowed;

		if ( ! $post_types ) {
			return array(
				'ids'   => array(),
				'total' => 0,
			);
		}

		$query = new WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => self::STATUSES,
				'fields'         => 'ids',
				'posts_per_page' => $per_page,
				'paged'          => max( 1, (int) $page ),
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- The Divi flag is post meta.
				'no_found_rows'  => -1 === $per_page,
			)
		);

		return array(
			'ids'   => array_map( 'intval', $query->posts ),
			'total' => -1 === $per_page ? count( $query->posts ) : (int) $query->found_posts,
		);
	}

	/**
	 * Gets the scan data of one post.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,mixed>|null
	 */
	public function summary( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}

		$backup    = new Backup();
		$converted = metadata_exists( 'post', $post->ID, Backup::META_CONVERTED );
		$content   = $converted && $backup->exists( $post->ID ) ? $backup->content( $post->ID ) : $post->post_content;

		$modules = array();
		if ( preg_match_all( '/\[(et_pb_[a-zA-Z0-9_]+)[\s\]]/', $content, $matches ) ) {
			$modules = array_count_values( $matches[1] );
		}

		$registry    = Mapper_Registry::with_defaults();
		$unsupported = array();
		foreach ( array_keys( $modules ) as $tag ) {
			if ( ! $registry->has( $tag ) && ! $this->is_child_tag( $tag ) ) {
				$unsupported[] = $tag;
			}
		}

		return array(
			'id'          => $post->ID,
			'title'       => get_the_title( $post ),
			'type'        => $post->post_type,
			'status'      => $post->post_status,
			'edit_url'    => get_edit_post_link( $post->ID, 'raw' ),
			'view_url'    => get_permalink( $post ),
			'converted'   => $converted,
			'is_divi5'    => false !== strpos( $content, '<!-- wp:divi/' ),
			'modules'     => array_sum( $modules ),
			'unsupported' => $unsupported,
		);
	}

	/**
	 * Tells if a tag is a child module. The parent mapper converts it.
	 *
	 * @param string $tag Divi tag.
	 * @return bool
	 */
	private function is_child_tag( $tag ) {
		return in_array(
			$tag,
			array( 'et_pb_accordion_item', 'et_pb_counter', 'et_pb_contact_field', 'et_pb_map_pin', 'et_pb_pricing_table', 'et_pb_signup_custom_field', 'et_pb_slide', 'et_pb_social_media_follow_network', 'et_pb_tab', 'et_pb_video_slider_item' ),
			true
		);
	}
}

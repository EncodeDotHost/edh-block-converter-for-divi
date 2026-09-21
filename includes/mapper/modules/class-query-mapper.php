<?php
/**
 * Query mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts the blog, portfolio, and post slider modules to a query loop block.
 */
final class Query_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$is_project = false !== strpos( $node->tag, 'portfolio' );
		$is_slider  = false !== strpos( $node->tag, 'slider' );
		$post_type  = $is_project ? 'project' : $node->attr( 'post_type', 'post' );

		if ( $is_slider || 'et_pb_filterable_portfolio' === $node->tag ) {
			$this->downgraded( $node, $context, __( 'The module is now a query loop. The slider or filter function is not kept.', 'edh-block-converter-for-divi' ) );
		}

		$query = array(
			'perPage'  => max( 1, (int) $node->attr( 'posts_number', '10' ) ),
			'pages'    => 0,
			'offset'   => max( 0, (int) $node->attr( 'offset_number', '0' ) ),
			'postType' => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $post_type ) ),
			'order'    => 'desc',
			'orderBy'  => 'date',
			'author'   => '',
			'search'   => '',
			'exclude'  => array(), // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude -- Attribute of the core query block, not a database query.
			'sticky'   => '',
			'inherit'  => $node->is_on( 'use_current_loop' ),
		);

		$terms = array_values( array_filter( array_map( 'intval', explode( ',', $node->attr( 'include_categories' ) ) ) ) );
		if ( $terms ) {
			$query['taxQuery'] = array( ( $is_project ? 'project_category' : 'category' ) => $terms );
		}

		$template = array();
		if ( $node->is_on( $is_slider ? 'show_image' : 'show_thumbnail', 'on' ) ) {
			$template[] = Block_Factory::dynamic( 'core/post-featured-image', array( 'isLink' => true ) );
		}
		if ( $node->is_on( 'show_title', 'on' ) ) {
			$template[] = Block_Factory::dynamic( 'core/post-title', array( 'isLink' => true ) );
		}
		if ( ! $is_project && $node->is_on( $is_slider ? 'show_meta' : 'show_date', 'on' ) ) {
			$template[] = Block_Factory::dynamic( 'core/post-date' );
		}
		if ( ! $is_project ) {
			$template[] = $node->is_on( 'show_content' )
				? Block_Factory::dynamic( 'core/post-content' )
				: Block_Factory::dynamic( 'core/post-excerpt' );
		}

		$is_grid        = ! $is_slider && ! $node->is_on( 'fullwidth', 'on' );
		$template_attrs = $is_grid
			? array(
				'layout' => array(
					'type'        => 'grid',
					'columnCount' => 3,
				),
			)
			: array();

		$inner = array( Block_Factory::dynamic( 'core/post-template', $template_attrs, $template ) );

		if ( ! $is_slider && $node->is_on( 'show_pagination', 'on' ) ) {
			$inner[] = Block_Factory::dynamic(
				'core/query-pagination',
				array(),
				array(
					Block_Factory::dynamic( 'core/query-pagination-previous' ),
					Block_Factory::dynamic( 'core/query-pagination-numbers' ),
					Block_Factory::dynamic( 'core/query-pagination-next' ),
				)
			);
		}

		$attrs = array(
			'queryId' => $context->next_query_id(),
			'query'   => $query,
		);

		return array( Block_Factory::container( 'core/query', $attrs, $inner, '<div class="wp-block-query">', '</div>' ) );
	}
}

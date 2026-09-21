<?php
/**
 * Post element mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Report;

/**
 * Converts the Divi modules that have a dynamic core block: search, login, post title, post content, post navigation, comments, and menu.
 */
final class Post_Element_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		switch ( $node->tag ) {
			case 'et_pb_search':
				return array(
					Block_Factory::dynamic(
						'core/search',
						array(
							'label'       => __( 'Search', 'edh-block-converter-for-divi' ),
							'showLabel'   => false,
							'placeholder' => $node->attr( 'placeholder' ),
							'buttonText'  => __( 'Search', 'edh-block-converter-for-divi' ),
						)
					),
				);

			case 'et_pb_login':
				$this->downgraded( $node, $context, __( 'The login module is now a login/out block. The title and the text are not kept.', 'edh-block-converter-for-divi' ) );
				return array( Block_Factory::dynamic( 'core/loginout', array( 'displayLoginAsForm' => true ) ) );

			case 'et_pb_post_title':
			case 'et_pb_fullwidth_post_title':
				return $this->wrap( $node, $this->post_title( $node ), $context );

			case 'et_pb_post_content':
			case 'et_pb_fullwidth_post_content':
				return array( Block_Factory::dynamic( 'core/post-content' ) );

			case 'et_pb_post_nav':
				$links = array(
					Block_Factory::dynamic( 'core/post-navigation-link', array( 'type' => 'previous' ) ),
					Block_Factory::dynamic( 'core/post-navigation-link' ),
				);
				$attrs = array(
					'layout' => array(
						'type'           => 'flex',
						'justifyContent' => 'space-between',
					),
				);
				return array( Block_Factory::group( $links, $attrs ) );

			case 'et_pb_comments':
				return array( Block_Factory::dynamic( 'core/comments', array( 'legacy' => true ) ) );

			case 'et_pb_menu':
			case 'et_pb_fullwidth_menu':
				$context->report->add( Report::INFO, $node->tag, __( 'The menu is now a navigation block. Select the menu in the block editor.', 'edh-block-converter-for-divi' ) );
				return array( Block_Factory::dynamic( 'core/navigation' ) );
		}

		$context->report->add( Report::UNSUPPORTED, $node->tag, __( 'Widget areas have no block equivalent in post content. The converter removed the module.', 'edh-block-converter-for-divi' ) );
		return array();
	}

	/**
	 * Builds the blocks for a post title module.
	 *
	 * @param Node $node Divi node.
	 * @return array
	 */
	private function post_title( Node $node ) {
		$align  = in_array( $node->attr( 'text_orientation' ), array( 'center', 'right' ), true ) ? $node->attr( 'text_orientation' ) : '';
		$blocks = array();
		$image  = $node->is_on( 'featured_image', 'on' ) ? Block_Factory::dynamic( 'core/post-featured-image' ) : null;

		if ( $image && 'above' === $node->attr( 'featured_placement' ) ) {
			$blocks[] = $image;
		}
		if ( $node->is_on( 'title', 'on' ) ) {
			$blocks[] = Block_Factory::dynamic(
				'core/post-title',
				array(
					'level'     => 1,
					'textAlign' => $align,
				)
			);
		}
		if ( $node->is_on( 'meta', 'on' ) ) {
			if ( $node->is_on( 'date', 'on' ) ) {
				$blocks[] = Block_Factory::dynamic( 'core/post-date', array( 'textAlign' => $align ) );
			}
			if ( $node->is_on( 'categories', 'on' ) ) {
				$blocks[] = Block_Factory::dynamic(
					'core/post-terms',
					array(
						'term'      => 'category',
						'textAlign' => $align,
					)
				);
			}
		}
		if ( $image && 'above' !== $node->attr( 'featured_placement' ) ) {
			$blocks[] = $image;
		}

		return $blocks;
	}
}

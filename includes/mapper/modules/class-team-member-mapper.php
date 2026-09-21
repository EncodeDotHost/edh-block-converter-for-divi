<?php
/**
 * Person mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Style\Style_Mapper;

/**
 * Converts `et_pb_team_member` to a group with an image, a heading, text, and social links.
 */
final class Team_Member_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$blocks   = array();
		$blocks[] = $this->image( $node->attr( 'image_url' ), $context, array( 'alt' => wp_strip_all_tags( $node->attr( 'name' ) ) ) );

		$name = $this->inline( $node->attr( 'name' ) );
		if ( '' !== $name ) {
			$blocks[] = Block_Factory::heading( $name, $this->level( $node->attr( 'header_level' ), 4 ) );
		}

		$position = $this->inline( $node->attr( 'position' ) );
		if ( '' !== $position ) {
			$blocks[] = Block_Factory::paragraph( '<em>' . $position . '</em>' );
		}

		$blocks = array_merge( array_values( array_filter( $blocks ) ), $context->html->convert( $node->content ) );

		$links = array();
		foreach ( array( 'facebook', 'twitter', 'linkedin' ) as $service ) {
			$url = $node->attr( $service . '_url' );
			if ( '' !== $url ) {
				$links[] = Block_Factory::dynamic(
					'core/social-link',
					array(
						'url'     => $url,
						'service' => $service,
					)
				);
			}
		}
		if ( $links ) {
			$blocks[] = Block_Factory::container( 'core/social-links', array(), $links, '<ul class="wp-block-social-links">', '</ul>' );
		}

		return $this->wrap( $node, $blocks, $context, true, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN ), 'body' );
	}
}

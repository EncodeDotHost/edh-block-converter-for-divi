<?php
/**
 * Blurb mapper.
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
 * Converts `et_pb_blurb` to a group with an image, a heading, and text.
 */
final class Blurb_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$align  = $context->styles->text_align( $node );
		$url    = $node->attr( 'url' );
		$new    = $node->is_on( 'url_new_window' );
		$blocks = array();

		if ( $node->is_on( 'use_icon' ) ) {
			$this->downgraded( $node, $context, __( 'Core blocks have no icon font. The converter removed the blurb icon.', 'edh-divi-gutenberg' ) );
		} else {
			$blocks[] = $this->image(
				$node->attr( 'image' ),
				$context,
				array(
					'alt'    => $node->attr( 'alt' ),
					'href'   => $url,
					'target' => $new ? '_blank' : '',
				),
				array( 'align' => 'left' === $node->attr( 'icon_placement' ) ? '' : 'center' )
			);
		}

		$title = $this->inline( $node->attr( 'title' ) );
		if ( '' !== $title ) {
			$blocks[] = Block_Factory::heading( $this->link( $title, $url, $new ), $this->level( $node->attr( 'header_level' ), 4 ), array( 'textAlign' => $align ) );
		}

		$blocks = array_merge( array_values( array_filter( $blocks ) ), $context->html->convert( $node->content, $align ) );

		return $this->wrap( $node, $blocks, $context, true, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN ), 'body' );
	}
}

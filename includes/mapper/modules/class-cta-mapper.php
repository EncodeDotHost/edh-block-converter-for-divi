<?php
/**
 * Call to action mapper.
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
 * Converts `et_pb_cta` to a group with a heading, text, and a button.
 */
final class Cta_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$align  = $context->styles->text_align( $node, 'text_orientation', 'center' );
		$blocks = array();

		$title = $this->inline( $node->attr( 'title' ) );
		if ( '' !== $title ) {
			$blocks[] = Block_Factory::heading( $title, $this->level( $node->attr( 'header_level' ), 2 ), array( 'textAlign' => $align ) );
		}

		$blocks   = array_merge( $blocks, $context->html->convert( $node->content, $align ) );
		$blocks[] = $this->button( $node, $node->attr( 'button_text' ), $node->attr( 'button_url' ), $align );

		return $this->wrap( $node, array_values( array_filter( $blocks ) ), $context, true, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN ), 'body' );
	}
}

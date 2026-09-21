<?php
/**
 * Heading mapper.
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
 * Converts `et_pb_heading` to a heading block.
 */
final class Heading_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$title = $this->inline( $node->attr( 'title' ) );
		if ( '' === $title ) {
			return array();
		}

		$attrs              = $context->styles->block_attrs( $node, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN, Style_Mapper::TEXT ), 'title' );
		$attrs['textAlign'] = $context->styles->text_align( $node, 'title_text_align', $node->attr( 'text_orientation' ) );

		$title = $this->link( $title, $node->attr( 'url' ), $node->is_on( 'url_new_window' ) );

		return array( Block_Factory::heading( $title, $this->level( $node->attr( 'title_level' ), 2 ), $attrs ) );
	}
}

<?php
/**
 * Column mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Structure;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Style\Style_Mapper;

/**
 * Converts a column that has no row. The row mapper converts all other columns.
 */
final class Column_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		return $this->wrap( $node, $context->convert_nodes( $node->children ), $context, false, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING ) );
	}
}

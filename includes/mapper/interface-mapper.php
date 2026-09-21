<?php
/**
 * Mapper contract.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts one Divi node to core blocks.
 */
interface Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Conversion context.
	 * @return array List of parsed-block arrays. Empty when the node gives no output.
	 */
	public function convert( Node $node, Context $context );
}

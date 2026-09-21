<?php
/**
 * Button mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_button` to a buttons block.
 */
final class Button_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$align = $node->attr( 'button_alignment' );
		$block = $this->button(
			$node,
			$node->attr( 'button_text' ),
			$node->attr( 'button_url' ),
			in_array( $align, array( 'center', 'right' ), true ) ? $align : ''
		);

		return $block ? array( $block ) : array();
	}
}

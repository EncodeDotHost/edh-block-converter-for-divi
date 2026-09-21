<?php
/**
 * Toggle and accordion mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_toggle` and `et_pb_accordion` to details blocks.
 */
final class Toggle_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		if ( 'et_pb_toggle' === $node->tag ) {
			return array( $this->details( $node, $context, $node->is_on( 'open' ) ) );
		}

		$blocks = array();
		foreach ( $node->child_modules( array( 'et_pb_accordion_item' ) ) as $index => $item ) {
			// Divi opens the first item of an accordion.
			$blocks[] = $this->details( $item, $context, 0 === $index );
		}

		return $this->wrap( $node, $blocks, $context );
	}

	/**
	 * Builds one details block.
	 *
	 * @param Node    $item    Toggle or accordion item.
	 * @param Context $context Context.
	 * @param bool    $open    True to show the content initially.
	 * @return array
	 */
	private function details( Node $item, Context $context, $open ) {
		return Block_Factory::details(
			$this->inline( $item->attr( 'title' ) ),
			$context->html->convert( $item->content ),
			$open,
			$context->styles->identity( $item )
		);
	}
}

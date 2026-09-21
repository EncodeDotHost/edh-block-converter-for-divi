<?php
/**
 * Map mapper.
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
 * Converts `et_pb_map` and `et_pb_fullwidth_map` to the text of the address and the pins.
 */
final class Map_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$blocks = array();

		foreach ( $node->child_modules( array( 'et_pb_map_pin' ) ) as $pin ) {
			$title = $this->inline( $pin->attr( 'title' ) );
			if ( '' !== $title ) {
				$blocks[] = Block_Factory::heading( $title, 4 );
			}
			if ( '' !== $pin->attr( 'pin_address' ) ) {
				$blocks[] = Block_Factory::paragraph( esc_html( $pin->attr( 'pin_address' ) ) );
			}
			$blocks = array_merge( $blocks, $context->html->convert( $pin->content ) );
		}

		if ( ! $blocks && '' !== $node->attr( 'address' ) ) {
			$blocks[] = Block_Factory::paragraph( esc_html( $node->attr( 'address' ) ) );
		}

		if ( ! $blocks ) {
			$context->report->add( Report::UNSUPPORTED, $node->tag, __( 'Core blocks have no map. The converter removed the map.', 'edh-divi-gutenberg' ) );
			return array();
		}

		$this->downgraded( $node, $context, __( 'Core blocks have no map. The converter kept the addresses and the pin text.', 'edh-divi-gutenberg' ) );

		return $this->wrap( $node, $blocks, $context );
	}
}

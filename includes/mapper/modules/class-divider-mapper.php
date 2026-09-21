<?php
/**
 * Divider mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Style\Spacing;
use EDH\DiviGutenberg\Style\Style_Mapper;

/**
 * Converts `et_pb_divider` to a separator, or to a spacer when the line is not visible.
 */
final class Divider_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		if ( $node->is_on( 'show_divider', 'on' ) ) {
			return array( Block_Factory::separator( Style_Mapper::color( $node->attr( 'color' ) ), $context->styles->identity( $node ) ) );
		}

		$height = Spacing::length( $node->attr( 'height' ) );

		return array( Block_Factory::spacer( '' === $height || '0' === $height ? '30px' : $height ) );
	}
}

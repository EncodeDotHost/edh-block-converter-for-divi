<?php
/**
 * Code mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_code` and `et_pb_fullwidth_code` to a custom HTML block or a shortcode block.
 */
final class Code_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$code = trim( $node->content );
		if ( '' === $code ) {
			return array();
		}

		unset( $context );

		if ( preg_match( '/^\[[a-zA-Z][\w\-]*(?:\s[^\]]*)?\](?:.*\[\/[\w\-]+\])?$/s', $code ) ) {
			return array( Block_Factory::shortcode( $code ) );
		}

		return array( Block_Factory::html( $code ) );
	}
}

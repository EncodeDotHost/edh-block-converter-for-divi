<?php
/**
 * Fallback mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Report;

/**
 * Converts a Divi module that has no mapper. It keeps the content that it can find.
 */
final class Fallback_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$blocks = $node->children ? $context->convert_nodes( $node->children ) : $context->html->convert( $node->content );

		$title = $node->attr( 'title' );
		if ( '' !== $title ) {
			array_unshift( $blocks, Block_Factory::heading( $this->inline( $title ), 3 ) );
		}

		if ( $blocks ) {
			$context->report->add( Report::DOWNGRADED, $node->tag, __( 'The module has no equivalent. The converter kept only its text content.', 'edh-divi-gutenberg' ) );
		} else {
			$context->report->add( Report::UNSUPPORTED, $node->tag, __( 'The module has no equivalent and no text content. The converter removed it.', 'edh-divi-gutenberg' ) );
		}

		return $blocks;
	}
}

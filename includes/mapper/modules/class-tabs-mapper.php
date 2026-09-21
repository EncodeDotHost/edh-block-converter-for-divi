<?php
/**
 * Tabs mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_tabs` to a heading and the content for each tab.
 */
final class Tabs_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$blocks = array();

		foreach ( $node->child_modules( array( 'et_pb_tab' ) ) as $tab ) {
			$title = $this->inline( $tab->attr( 'title' ) );
			if ( '' !== $title ) {
				$blocks[] = Block_Factory::heading( $title, 3 );
			}
			$blocks = array_merge( $blocks, $context->html->convert( $tab->content ) );
		}

		if ( $blocks ) {
			$this->downgraded( $node, $context, __( 'Core blocks have no tabs. Each tab is now a heading with its content.', 'edh-block-converter-for-divi' ) );
		}

		return $this->wrap( $node, $blocks, $context );
	}
}

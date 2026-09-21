<?php
/**
 * Shop mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_shop` to the WooCommerce `[products]` shortcode.
 */
final class Shop_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$atts = array(
			'limit'   => max( 1, (int) $node->attr( 'posts_number', '12' ) ),
			'columns' => max( 1, (int) $node->attr( 'columns_number', '4' ) ),
		);

		switch ( $node->attr( 'type', 'recent' ) ) {
			case 'featured':
				$atts['visibility'] = 'featured';
				break;
			case 'sale':
				$atts['on_sale'] = 'true';
				break;
			case 'best_selling':
				$atts['best_selling'] = 'true';
				break;
			case 'top_rated':
				$atts['top_rated'] = 'true';
				break;
			case 'product_category':
				$atts['category'] = preg_replace( '/[^a-z0-9,_\-]/i', '', $node->attr( 'include_categories' ) );
				break;
			default:
				$atts['orderby'] = 'date';
				$atts['order']   = 'DESC';
		}

		$shortcode = '[products';
		foreach ( $atts as $name => $value ) {
			if ( '' !== (string) $value ) {
				$shortcode .= ' ' . $name . '="' . $value . '"';
			}
		}

		$this->downgraded( $node, $context, __( 'The shop module is now the WooCommerce products shortcode.', 'edh-block-converter-for-divi' ) );

		return array( Block_Factory::shortcode( $shortcode . ']' ) );
	}
}

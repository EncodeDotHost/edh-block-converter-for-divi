<?php
/**
 * Testimonial mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Style\Style_Mapper;

/**
 * Converts `et_pb_testimonial` to a quote block.
 */
final class Testimonial_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$company = $this->link( $this->inline( $node->attr( 'company_name' ) ), $node->attr( 'url' ), $node->is_on( 'url_new_window' ) );
		$parts   = array_filter(
			array( $this->inline( $node->attr( 'author' ) ), $this->inline( $node->attr( 'job_title' ) ), $company ),
			'strlen'
		);

		$inner = $context->html->convert( $node->content );
		if ( ! $inner && ! $parts ) {
			return array();
		}

		$blocks   = array();
		$blocks[] = $this->image( $node->attr( 'portrait_url' ), $context, array( 'alt' => wp_strip_all_tags( $node->attr( 'author' ) ) ) );
		$blocks[] = Block_Factory::quote( $inner, implode( ', ', $parts ) );

		return $this->wrap( $node, array_values( array_filter( $blocks ) ), $context, false, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN ), 'body' );
	}
}

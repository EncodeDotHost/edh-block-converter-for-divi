<?php
/**
 * Image mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_image` and `et_pb_fullwidth_image` to an image block.
 */
final class Image_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$attrs = $context->styles->identity( $node );
		$align = $node->attr( 'align' );

		if ( $context->fullwidth || $node->is_on( 'force_fullwidth' ) ) {
			$attrs['align'] = 'full';
		} elseif ( in_array( $align, array( 'center', 'right' ), true ) ) {
			$attrs['align'] = $align;
		}

		$url = $node->attr( 'url' );
		if ( '' === $url && $node->is_on( 'show_in_lightbox' ) ) {
			$attrs['lightbox'] = array( 'enabled' => true );
		}

		$block = $this->image(
			$node->attr( 'src' ),
			$context,
			array(
				'alt'    => $node->attr( 'alt' ),
				'title'  => $node->attr( 'title_text' ),
				'href'   => $url,
				'target' => $node->is_on( 'url_new_window' ) ? '_blank' : '',
			),
			$attrs
		);

		return $block ? array( $block ) : array();
	}
}

<?php
/**
 * Gallery mapper.
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
 * Converts `et_pb_gallery` to a gallery block.
 */
final class Gallery_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$images = array();

		foreach ( array_filter( array_map( 'intval', explode( ',', $node->attr( 'gallery_ids' ) ) ) ) as $id ) {
			$url = function_exists( 'wp_get_attachment_image_url' ) ? wp_get_attachment_image_url( $id, 'large' ) : '';
			if ( ! $url ) {
				$context->report->add(
					Report::DROPPED,
					$node->tag,
					/* translators: %d: attachment ID. */
					sprintf( __( 'Gallery image %d is not in the media library. The converter removed it.', 'edh-divi-gutenberg' ), $id )
				);
				continue;
			}

			$images[] = Block_Factory::image(
				array(
					'url' => $url,
					'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
				),
				array(
					'id'       => $id,
					'sizeSlug' => 'large',
				)
			);
		}

		if ( ! $images ) {
			return array();
		}

		if ( $node->is_on( 'fullwidth' ) ) {
			$this->downgraded( $node, $context, __( 'The gallery slider is now a gallery grid.', 'edh-divi-gutenberg' ) );
		}

		return array( Block_Factory::gallery( $images, $context->styles->identity( $node ) ) );
	}
}

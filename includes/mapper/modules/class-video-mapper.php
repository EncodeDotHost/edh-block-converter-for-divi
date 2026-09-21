<?php
/**
 * Video mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_video` and `et_pb_video_slider` to video or embed blocks.
 */
final class Video_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$items = 'et_pb_video_slider' === $node->tag ? $node->child_modules( array( 'et_pb_video_slider_item' ) ) : array( $node );
		if ( 'et_pb_video_slider' === $node->tag ) {
			$this->downgraded( $node, $context, __( 'The video slider is now a list of videos.', 'edh-block-converter-for-divi' ) );
		}

		$blocks = array();
		foreach ( $items as $item ) {
			$src = $item->attr( 'src', $item->attr( 'src_webm' ) );
			if ( '' === $src ) {
				continue;
			}
			if ( preg_match( '#^https?://(?:www\.)?(?:youtube\.com|youtu\.be)/#i', $src ) ) {
				$blocks[] = Block_Factory::embed( $src, 'youtube', 'video' );
			} elseif ( preg_match( '#^https?://(?:www\.|player\.)?vimeo\.com/#i', $src ) ) {
				$blocks[] = Block_Factory::embed( $src, 'vimeo', 'video' );
			} else {
				$blocks[] = Block_Factory::video( $src, $item->attr( 'image_src' ) );
			}
		}

		return $blocks;
	}
}

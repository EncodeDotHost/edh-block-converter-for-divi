<?php
/**
 * Slider mapper.
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
 * Converts `et_pb_slider` and `et_pb_fullwidth_slider` to one cover or group for each slide.
 */
final class Slider_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$blocks = array();

		foreach ( $node->child_modules( array( 'et_pb_slide' ) ) as $slide ) {
			$inner = array();

			$heading = $this->inline( $slide->attr( 'heading' ) );
			if ( '' !== $heading ) {
				$inner[] = Block_Factory::heading( $heading, 2, array( 'textAlign' => 'center' ) );
			}

			$inner   = array_merge( $inner, $context->html->convert( $slide->content, 'center' ) );
			$inner[] = $this->image( $slide->attr( 'image' ), $context, array( 'alt' => $slide->attr( 'image_alt' ) ), array( 'align' => 'center' ) );
			$inner[] = $this->button( $slide, $slide->attr( 'button_text' ), $slide->attr( 'button_link' ), 'center' );
			$inner   = array_values( array_filter( $inner ) );

			if ( ! $inner ) {
				continue;
			}

			$attrs = $context->styles->block_attrs( $slide, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING ) );
			if ( $context->fullwidth ) {
				$attrs['align'] = 'full';
			}

			$background = $slide->attr( 'background_image' );
			if ( '' === $background ) {
				$blocks[] = Block_Factory::group( $inner, $attrs );
				continue;
			}

			$overlay  = $slide->is_on( 'use_bg_overlay' ) ? Style_Mapper::color( $slide->attr( 'bg_overlay_color' ) ) : '';
			$blocks[] = Block_Factory::cover(
				$inner,
				array(
					'url'     => $background,
					'id'      => $context->attachment_id( $background ),
					'overlay' => $overlay,
					'dim'     => '' === $overlay ? 0 : 50,
				),
				$attrs
			);
		}

		if ( $blocks ) {
			$this->downgraded( $node, $context, __( 'Core blocks have no slider. Each slide is now a separate block.', 'edh-divi-gutenberg' ) );
		}

		return $blocks;
	}
}

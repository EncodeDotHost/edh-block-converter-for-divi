<?php
/**
 * Fullwidth header mapper.
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
 * Converts `et_pb_fullwidth_header` to a full-width group or cover.
 */
final class Fullwidth_Header_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$align  = $context->styles->text_align( $node );
		$blocks = array();

		$blocks[] = $this->image( $node->attr( 'logo_image_url' ), $context, array( 'alt' => $node->attr( 'logo_alt_text' ) ), array( 'align' => 'center' === $align ? 'center' : '' ) );

		$title = $this->inline( $node->attr( 'title' ) );
		if ( '' !== $title ) {
			$blocks[] = Block_Factory::heading( $title, $this->level( $node->attr( 'title_level' ), 1 ), array( 'textAlign' => $align ) );
		}

		$subhead = $this->inline( $node->attr( 'subhead' ) );
		if ( '' !== $subhead ) {
			$blocks[] = Block_Factory::paragraph( $subhead, array( 'align' => $align ) );
		}

		$blocks = array_merge( $blocks, $context->html->convert( $node->content, $align ) );

		$buttons = array();
		foreach ( array( 'button_one', 'button_two' ) as $prefix ) {
			$text = $this->inline( $node->attr( $prefix . '_text' ) );
			if ( '' === $text ) {
				continue;
			}
			$colors = array();
			if ( $node->is_on( 'custom_' . $prefix ) ) {
				$colors = array(
					'text'       => Style_Mapper::color( $node->attr( $prefix . '_text_color' ) ),
					'background' => Style_Mapper::color( $node->attr( $prefix . '_bg_color' ) ),
				);
			}
			$buttons[] = Block_Factory::button( $text, $node->attr( $prefix . '_url' ), false, $colors );
		}
		if ( $buttons ) {
			$blocks[] = Block_Factory::buttons( $buttons, $align );
		}

		$blocks[] = $this->image( $node->attr( 'header_image_url' ), $context, array( 'alt' => $node->attr( 'image_alt_text' ) ), array( 'align' => 'center' ) );
		$blocks   = array_values( array_filter( $blocks ) );

		if ( ! $blocks ) {
			return array();
		}

		$attrs          = $context->styles->block_attrs( $node, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::TEXT ), 'content' );
		$attrs['align'] = 'full';

		$background = $node->attr( 'background_image', $node->attr( 'background_url' ) );
		if ( '' !== $background ) {
			$cover = array(
				'url'      => $background,
				'id'       => $context->attachment_id( $background ),
				'parallax' => $node->is_on( 'parallax' ),
			);
			return array( Block_Factory::cover( $blocks, $cover, $attrs ) );
		}

		return array( Block_Factory::group( $blocks, $attrs ) );
	}
}

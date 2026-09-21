<?php
/**
 * Section mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Structure;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Style\Style_Mapper;

/**
 * Converts `et_pb_section` to a full-width group, or to a cover when it has a background image.
 */
final class Section_Mapper extends Base_Mapper {

	/**
	 * Row mapper. It builds the columns of a specialty section.
	 *
	 * @var Row_Mapper
	 */
	private $rows;

	/**
	 * Constructor.
	 *
	 * @param Row_Mapper $rows Row mapper.
	 */
	public function __construct( Row_Mapper $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$was_fullwidth      = $context->fullwidth;
		$context->fullwidth = $node->is_on( 'fullwidth' );

		$columns = $node->child_modules( array( 'et_pb_column' ) );
		if ( $columns ) {
			// A specialty section holds columns without a row.
			$inner = $this->rows->build_columns( $columns, $node, $context );
		} else {
			$inner = $context->convert_nodes( $node->children );
		}

		$context->fullwidth = $was_fullwidth;

		if ( ! $inner ) {
			return array();
		}

		$attrs          = $context->styles->block_attrs( $node, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN ) );
		$attrs['align'] = 'full';

		$image = $node->attr( 'background_image' );
		if ( '' !== $image ) {
			return array(
				Block_Factory::cover(
					$inner,
					array(
						'url'      => $image,
						'id'       => $context->attachment_id( $image ),
						'parallax' => $node->is_on( 'parallax' ),
					),
					$attrs
				),
			);
		}

		// A fullwidth section has no content width limit.
		$attrs['layout'] = array( 'type' => $node->is_on( 'fullwidth' ) ? 'default' : 'constrained' );

		return array( Block_Factory::group( $inner, $attrs ) );
	}
}

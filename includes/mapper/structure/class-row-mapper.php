<?php
/**
 * Row mapper.
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
 * Converts `et_pb_row` and `et_pb_row_inner` to a columns block.
 *
 * A row with one column does not get a columns block.
 */
final class Row_Mapper extends Base_Mapper {

	const COLUMN_TAGS = array( 'et_pb_column', 'et_pb_column_inner' );

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$columns = $node->child_modules( self::COLUMN_TAGS );
		if ( ! $columns ) {
			return $this->wrap( $node, $context->convert_nodes( $node->children ), $context );
		}
		return $this->build_columns( $columns, $node, $context );
	}

	/**
	 * Builds the blocks for a list of Divi columns.
	 *
	 * @param Node[]  $columns Divi column nodes.
	 * @param Node    $owner   Row, or specialty section, that holds the columns.
	 * @param Context $context Context.
	 * @return array
	 */
	public function build_columns( array $columns, Node $owner, Context $context ) {
		$features = array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING );
		$is_row   = 'et_pb_section' !== $owner->tag;

		if ( 1 === count( $columns ) ) {
			$column = $columns[0];
			$this->inherit_column_design( $column, $owner, 1 );
			$blocks = $this->wrap( $column, $context->convert_nodes( $column->children ), $context, false, $features );
			return $is_row ? $this->wrap( $owner, $blocks, $context ) : $blocks;
		}

		$widths = $this->widths( $columns );
		$blocks = array();

		foreach ( $columns as $index => $column ) {
			$this->inherit_column_design( $column, $owner, $index + 1 );
			$attrs          = $context->styles->block_attrs( $column, $features );
			$attrs['width'] = $widths[ $index ];
			$blocks[]       = Block_Factory::column( $context->convert_nodes( $column->children ), $attrs );
		}

		$attrs = $is_row ? $context->styles->block_attrs( $owner, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN ) ) : array();

		return array( Block_Factory::columns( $blocks, $attrs ) );
	}

	/**
	 * Copies the legacy per-column design attributes of a row to the column.
	 *
	 * Old Divi versions saved `background_color_1` and `padding_top_1` on the row.
	 *
	 * @param Node $column Column node.
	 * @param Node $owner  Row node.
	 * @param int  $number Column number. The first column is 1.
	 */
	private function inherit_column_design( Node $column, Node $owner, $number ) {
		if ( '' === $column->attr( 'background_color' ) && '' !== $owner->attr( 'background_color_' . $number ) ) {
			$column->attrs['background_color'] = $owner->attr( 'background_color_' . $number );
		}

		if ( '' === $column->attr( 'custom_padding' ) ) {
			$sides = array();
			foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
				$sides[] = $owner->attr( 'padding_' . $side . '_' . $number );
			}
			if ( '' !== implode( '', $sides ) ) {
				$column->attrs['custom_padding'] = implode( '|', $sides );
			}
		}
	}

	/**
	 * Calculates the column widths from the Divi `type` fractions.
	 *
	 * @param Node[] $columns Divi column nodes.
	 * @return string[] Percent strings. Empty strings when all columns are equal.
	 */
	private function widths( array $columns ) {
		$fractions = array();
		foreach ( $columns as $column ) {
			$fractions[] = preg_match( '/^(\d+)_(\d+)$/', $column->attr( 'type', '4_4' ), $match ) && (int) $match[2] > 0
				? (int) $match[1] / (int) $match[2]
				: 1.0;
		}

		$total = array_sum( $fractions );
		$equal = count( array_unique( array_map( 'strval', $fractions ) ) ) === 1;

		$widths = array();
		foreach ( $fractions as $fraction ) {
			$widths[] = $equal || $total <= 0 ? '' : round( $fraction / $total * 100, 2 ) . '%';
		}
		return $widths;
	}
}

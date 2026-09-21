<?php
/**
 * Pricing tables mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_pricing_tables` to columns. Each table is a group.
 */
final class Pricing_Tables_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$tables = array();

		foreach ( $node->child_modules( array( 'et_pb_pricing_table' ) ) as $table ) {
			$blocks = array();

			$title = $this->inline( $table->attr( 'title' ) );
			if ( '' !== $title ) {
				$blocks[] = Block_Factory::heading( $title, 3, array( 'textAlign' => 'center' ) );
			}

			$subtitle = $this->inline( $table->attr( 'subtitle' ) );
			if ( '' !== $subtitle ) {
				$blocks[] = Block_Factory::paragraph( $subtitle, array( 'align' => 'center' ) );
			}

			$sum = $this->inline( $table->attr( 'sum' ) );
			if ( '' !== $sum ) {
				$per      = $this->inline( $table->attr( 'per' ) );
				$price    = '<strong>' . $this->inline( $table->attr( 'currency' ) ) . $sum . '</strong>' . ( '' !== $per ? ' / ' . $per : '' );
				$blocks[] = Block_Factory::paragraph( $price, array( 'align' => 'center' ) );
			}

			$blocks   = array_merge( $blocks, $this->features( $table, $context ) );
			$blocks[] = $this->button( $table, $table->attr( 'button_text' ), $table->attr( 'button_url' ), 'center' );
			$blocks   = array_values( array_filter( $blocks ) );

			if ( $blocks ) {
				$tables[] = $blocks;
			}
		}

		if ( ! $tables ) {
			return array();
		}

		$this->downgraded( $node, $context, __( 'The pricing tables are now columns of simple blocks.', 'edh-divi-gutenberg' ) );

		if ( 1 === count( $tables ) ) {
			return $this->wrap( $node, $tables[0], $context, true );
		}

		$columns = array();
		foreach ( $tables as $blocks ) {
			$columns[] = Block_Factory::column( $blocks );
		}

		return array( Block_Factory::columns( $columns, $context->styles->identity( $node ) ) );
	}

	/**
	 * Builds the feature list of one table.
	 *
	 * Divi stores one feature on each line. A `+` shows an included feature, and a `-` an excluded feature.
	 *
	 * @param Node    $table   Pricing table node.
	 * @param Context $context Context.
	 * @return array
	 */
	private function features( Node $table, Context $context ) {
		if ( false !== stripos( $table->content, '<li' ) ) {
			return $context->html->convert( $table->content );
		}

		$lines = preg_split( '/\r\n|\r|\n|<br\s*\/?>|<\/p>/i', $table->content );
		$items = array();

		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/<\/?p[^>]*>/i', '', $line ) );
			if ( '' === $line ) {
				continue;
			}
			$excluded = '-' === $line[0];
			$line     = trim( ltrim( $line, '+-' ) );
			if ( '' !== $line ) {
				$items[] = Block_Factory::list_item( $excluded ? '<s>' . $line . '</s>' : $line );
			}
		}

		return $items ? array( Block_Factory::list_block( $items ) ) : array();
	}
}

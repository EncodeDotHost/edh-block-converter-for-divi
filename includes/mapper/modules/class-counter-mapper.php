<?php
/**
 * Counter mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts the counter modules and the countdown timer to static text.
 */
final class Counter_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$blocks = array();

		switch ( $node->tag ) {
			case 'et_pb_counters':
				foreach ( $node->child_modules( array( 'et_pb_counter' ) ) as $counter ) {
					$label = trim( wp_strip_all_tags( $counter->content ) );
					if ( '' !== $label ) {
						$blocks[] = Block_Factory::paragraph( esc_html( $label ) . ': <strong>' . esc_html( $counter->attr( 'percent', '0' ) ) . '%</strong>' );
					}
				}
				break;

			case 'et_pb_countdown_timer':
				$title = $this->inline( $node->attr( 'title' ) );
				if ( '' !== $title ) {
					$blocks[] = Block_Factory::heading( $title, 4, array( 'textAlign' => 'center' ) );
				}
				if ( '' !== $node->attr( 'date_time' ) ) {
					$blocks[] = Block_Factory::paragraph( esc_html( $node->attr( 'date_time' ) ), array( 'align' => 'center' ) );
				}
				break;

			default:
				$number = esc_html( $node->attr( 'number', '0' ) ) . ( $node->is_on( 'percent_sign', 'on' ) ? '%' : '' );
				$title  = $this->inline( $node->attr( 'title' ) );
				$text   = '<strong>' . $number . '</strong>' . ( '' !== $title ? '<br>' . $title : '' );

				$blocks[] = Block_Factory::paragraph( $text, array( 'align' => 'center' ) );
		}

		if ( $blocks ) {
			$this->downgraded( $node, $context, __( 'Core blocks have no animated counter. The value is now static text.', 'edh-block-converter-for-divi' ) );
		}

		return $this->wrap( $node, $blocks, $context );
	}
}

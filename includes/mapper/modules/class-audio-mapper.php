<?php
/**
 * Audio mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;

/**
 * Converts `et_pb_audio` to an audio block with a text line for the title.
 */
final class Audio_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$src = $node->attr( 'audio' );
		if ( '' === $src ) {
			return array();
		}

		$blocks = array();
		$title  = $this->inline( $node->attr( 'title' ) );
		$artist = $this->inline( $node->attr( 'artist_name' ) );

		if ( '' !== $title ) {
			$blocks[] = Block_Factory::paragraph( '<strong>' . $title . '</strong>' . ( '' !== $artist ? ' – ' . $artist : '' ) );
		}
		$blocks[] = Block_Factory::audio( $src );

		return $this->wrap( $node, $blocks, $context );
	}
}

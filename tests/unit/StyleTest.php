<?php
/**
 * Style tests.
 *
 * @package EDH\DiviGutenberg
 */

use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Resolver\Global_Colors;
use EDH\DiviGutenberg\Style\Spacing;
use EDH\DiviGutenberg\Style\Style_Mapper;
use PHPUnit\Framework\TestCase;

final class StyleTest extends TestCase {

	public function test_spacing_keeps_only_valid_sides() {
		$this->assertSame(
			array(
				'top'    => '60px',
				'bottom' => '2em',
			),
			Spacing::parse( '60px||2em||true|false' )
		);
		$this->assertSame( array( 'top' => '10px' ), Spacing::parse( '10|calc(1px)|-5px|auto' ) );
		$this->assertSame(
			array(
				'top'  => '-5px',
				'left' => 'auto',
			),
			Spacing::parse( '-5px|||auto', true )
		);
		$this->assertSame( '0', Spacing::length( '0px' ) );
	}

	public function test_color_validation() {
		$this->assertSame( '#ffaa00', Style_Mapper::color( '#FFAA00' ) );
		$this->assertSame( 'rgba(0,0,0,0.5)', Style_Mapper::color( 'rgba(0, 0, 0, 0.5)' ) );
		$this->assertSame( '', Style_Mapper::color( 'red;} body{display:none' ) );
		$this->assertSame( '', Style_Mapper::color( 'url(javascript:x)' ) );
	}

	public function test_block_attrs() {
		$node  = Node::module(
			'et_pb_section',
			array(
				'background_color' => '#123456',
				'custom_padding'   => '10px|||',
				'module_id'        => 'my id"',
				'module_class'     => 'a  b<script>',
			)
		);
		$attrs = ( new Style_Mapper() )->block_attrs( $node, array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING ) );

		$this->assertSame( 'myid', $attrs['anchor'] );
		$this->assertSame( 'a bscript', $attrs['className'] );
		$this->assertSame( '#123456', $attrs['style']['color']['background'] );
		$this->assertSame( array( 'top' => '10px' ), $attrs['style']['spacing']['padding'] );
	}

	public function test_gradient_from_stops_and_from_legacy_values() {
		$mapper = new Style_Mapper();

		$stops = Node::module(
			'et_pb_section',
			array(
				'use_background_color_gradient'       => 'on',
				'background_color_gradient_stops'     => '#ff0000 0%|#0000ff 100%',
				'background_color_gradient_direction' => '90deg',
			)
		);
		$this->assertSame( 'linear-gradient(90deg,#ff0000 0%,#0000ff 100%)', $mapper->gradient( $stops ) );

		$legacy = Node::module(
			'et_pb_section',
			array(
				'use_background_color_gradient'   => 'on',
				'background_color_gradient_start' => '#111111',
				'background_color_gradient_end'   => '#222222',
			)
		);
		$this->assertSame( 'linear-gradient(180deg,#111111 0%,#222222 100%)', $mapper->gradient( $legacy ) );
	}

	public function test_global_colors() {
		$colors = new Global_Colors(
			array(
				'gcid-a' => '#aaaaaa',
				'gcid-b' => 'gcid-a',
			)
		);

		$this->assertSame( '#aaaaaa', $colors->resolve( 'gcid-a' ) );
		$this->assertSame( '#aaaaaa', $colors->resolve( 'var(--gcid-b)' ) );
		$this->assertSame( '#aaaaaa 0%|#aaaaaa 100%', $colors->resolve( 'gcid-a 0%|gcid-b 100%' ) );
		$this->assertSame( '', $colors->resolve( 'gcid-unknown' ) );
	}
}

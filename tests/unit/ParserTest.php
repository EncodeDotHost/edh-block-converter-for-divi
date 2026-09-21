<?php
/**
 * Parser tests.
 *
 * @package EDH\DiviGutenberg
 */

use EDH\DiviGutenberg\Parser\Attribute_Decoder;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Parser\Shortcode_Parser;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase {

	public function test_builds_the_structure_tree() {
		$nodes = ( new Shortcode_Parser() )->parse( '[et_pb_section][et_pb_row][et_pb_column type="1_2"][et_pb_text]<p>Hi</p>[/et_pb_text][/et_pb_column][et_pb_column type="1_2"][/et_pb_column][/et_pb_row][/et_pb_section]' );

		$this->assertCount( 1, $nodes );
		$this->assertSame( 'et_pb_section', $nodes[0]->tag );

		$row = $nodes[0]->children[0];
		$this->assertSame( 'et_pb_row', $row->tag );
		$this->assertCount( 2, $row->children );
		$this->assertSame( '1_2', $row->children[0]->attr( 'type' ) );
		$this->assertSame( '<p>Hi</p>', $row->children[0]->children[0]->content );
	}

	public function test_row_does_not_match_row_inner() {
		$nodes = ( new Shortcode_Parser() )->parse( '[et_pb_row_inner][et_pb_column_inner][et_pb_text]A[/et_pb_text][/et_pb_column_inner][/et_pb_row_inner]' );

		$this->assertSame( 'et_pb_row_inner', $nodes[0]->tag );
		$this->assertSame( 'et_pb_column_inner', $nodes[0]->children[0]->tag );
	}

	public function test_self_closing_and_unclosed_modules_are_leaves() {
		$nodes = ( new Shortcode_Parser() )->parse( '[et_pb_image src="https://example.com/a/" /][et_pb_divider][et_pb_text]T[/et_pb_text]' );

		$this->assertCount( 3, $nodes );
		$this->assertSame( 'https://example.com/a/', $nodes[0]->attr( 'src' ) );
		$this->assertSame( 'et_pb_divider', $nodes[1]->tag );
		$this->assertSame( 'T', $nodes[2]->content );
	}

	public function test_removes_wpautop_debris_and_keeps_outer_text() {
		$nodes = ( new Shortcode_Parser() )->parse( "<p>[et_pb_text]</p>\n<p>Body</p>\n<p>[/et_pb_text]</p>\n<p>After</p>" );

		$this->assertCount( 2, $nodes );
		$this->assertSame( '<p>Body</p>', $nodes[0]->content );
		$this->assertSame( Node::TYPE_HTML, $nodes[1]->type );
		$this->assertSame( '<p>After</p>', $nodes[1]->content );
	}

	public function test_restores_code_module_content() {
		$nodes = ( new Shortcode_Parser() )->parse( '[et_pb_code]<p>&#091;my_shortcode&#093;<!-- [et_pb_line_break_holder] --><b>x</b></p>[/et_pb_code]' );

		$this->assertSame( "[my_shortcode]\n<b>x</b>", $nodes[0]->content );
	}

	public function test_foreign_shortcodes_stay_in_the_content() {
		$nodes = ( new Shortcode_Parser() )->parse( '[et_pb_column][dsm_card title="x"]Body[/dsm_card][/et_pb_column]' );

		$this->assertSame( '[dsm_card title="x"]Body[/dsm_card]', $nodes[0]->content );
	}

	public function test_keeps_the_raw_source() {
		$source = '[et_pb_contact_form title="C"][et_pb_contact_field field_id="Name" /][/et_pb_contact_form]';
		$nodes  = ( new Shortcode_Parser() )->parse( 'x' . $source . 'y' );

		$this->assertSame( $source, $nodes[1]->raw );
	}

	public function test_decodes_divi_escapes() {
		$attrs = Attribute_Decoder::parse( ' title="Say %22hi%22 %91now%93" path="a%92b" admin_label="L"' );

		$this->assertSame( 'Say "hi" [now]', $attrs['title'] );
		$this->assertSame( 'a\\b', $attrs['path'] );
	}

	public function test_ignores_bookkeeping_and_responsive_attributes() {
		$attrs = Attribute_Decoder::parse( ' _builder_version="4.27" fb_built="1" custom_padding="1px" custom_padding_tablet="2px" custom_padding_last_edited="on|tablet" background_color__hover="#000" ab_subject="on"' );

		$this->assertSame( array( 'custom_padding' => '1px' ), $attrs );
	}
}

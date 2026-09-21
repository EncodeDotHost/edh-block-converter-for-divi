<?php
/**
 * Converter tests.
 *
 * Each `*.shortcode.txt` fixture has a `*.blocks.html` snapshot. The block
 * editor validator accepted each snapshot (see tests/validator).
 * Set UPDATE_SNAPSHOTS=1 to write the snapshots again, then validate them.
 *
 * @package EDH\DiviGutenberg
 */

use EDH\DiviGutenberg\Converter;
use EDH\DiviGutenberg\Report;
use EDH\DiviGutenberg\Resolver\Dynamic_Content;
use EDH\DiviGutenberg\Resolver\Global_Colors;
use EDH\DiviGutenberg\Resolver\Global_Module_Resolver;
use PHPUnit\Framework\TestCase;

final class ConverterTest extends TestCase {

	private function converter() {
		return new Converter(
			null,
			new Global_Colors( array( 'gcid-brand' => '#0c71c3' ) ),
			new Dynamic_Content(
				static function ( $field ) {
					return 'post_title' === $field ? 'About us' : null;
				}
			),
			new Global_Module_Resolver(
				static function ( $post_id ) {
					return 77 === $post_id ? '[et_pb_text global_module="77" text_orientation="center"]<p>Library copy</p>[/et_pb_text]' : '';
				}
			)
		);
	}

	public function test_fixtures_match_the_snapshots() {
		$files = glob( dirname( __DIR__ ) . '/fixtures/*.shortcode.txt' );
		$this->assertNotEmpty( $files );

		foreach ( $files as $file ) {
			$snapshot = str_replace( '.shortcode.txt', '.blocks.html', $file );
			$markup   = $this->converter()->convert( file_get_contents( $file ), 1 )->markup . "\n";

			if ( getenv( 'UPDATE_SNAPSHOTS' ) ) {
				file_put_contents( $snapshot, $markup );
			}

			$this->assertFileExists( $snapshot );
			$this->assertSame( file_get_contents( $snapshot ), $markup, basename( $file ) );
		}
	}

	public function test_content_without_divi_is_not_changed() {
		$result = $this->converter()->convert( '<!-- wp:paragraph --><p>Hello [gallery]</p><!-- /wp:paragraph -->' );

		$this->assertFalse( $result->has_divi );
		$this->assertSame( '', $result->markup );
	}

	public function test_global_module_uses_the_library_content() {
		$markup = $this->converter()->convert( '[et_pb_text global_module="77"]<p>Stale</p>[/et_pb_text]' )->markup;

		$this->assertStringContainsString( 'Library copy', $markup );
		$this->assertStringContainsString( 'has-text-align-center', $markup );
		$this->assertStringNotContainsString( 'Stale', $markup );
	}

	public function test_missing_global_module_uses_the_local_copy() {
		$result = $this->converter()->convert( '[et_pb_text global_module="404"]<p>Local</p>[/et_pb_text]' );

		$this->assertStringContainsString( 'Local', $result->markup );
		$this->assertCount( 1, $result->report->notices( Report::INFO ) );
	}

	public function test_dynamic_content_becomes_static_text() {
		$token  = '@ET-DC@' . base64_encode( '{"dynamic":true,"content":"post_title","settings":{"before":"Page: ","after":""}}' ) . '@';
		$result = $this->converter()->convert( '[et_pb_heading title="' . $token . '"][/et_pb_heading]', 1 );

		$this->assertStringContainsString( '>Page: About us</h2>', $result->markup );
		$this->assertCount( 1, $result->report->notices( Report::DOWNGRADED ) );
	}

	public function test_disabled_module_is_removed_and_reported() {
		$result = $this->converter()->convert( '[et_pb_text disabled_on="on|on|on"]<p>Hidden</p>[/et_pb_text][et_pb_text disabled_on="on|off|off"]<p>Shown</p>[/et_pb_text]' );

		$this->assertStringNotContainsString( 'Hidden', $result->markup );
		$this->assertStringContainsString( 'Shown', $result->markup );
		$this->assertCount( 1, $result->report->notices( Report::DROPPED ) );
	}

	public function test_form_shortcode_option() {
		$source = '[et_pb_contact_form title="C"][et_pb_contact_field field_id="Name" /][/et_pb_contact_form]';

		$default = $this->converter()->convert( $source );
		$this->assertStringNotContainsString( 'et_pb_contact_form', $default->markup );
		$this->assertCount( 1, $default->report->notices( Report::UNSUPPORTED ) );

		$kept = $this->converter()->convert( $source, 0, array( 'keep_form_shortcodes' => true ) );
		$this->assertStringContainsString( '<!-- wp:shortcode -->' . $source . '<!-- /wp:shortcode -->', $kept->markup );
	}

	public function test_unequal_columns_get_widths_and_equal_columns_do_not() {
		$unequal = $this->converter()->convert( '[et_pb_row][et_pb_column type="1_4"][et_pb_text]A[/et_pb_text][/et_pb_column][et_pb_column type="3_4"][et_pb_text]B[/et_pb_text][/et_pb_column][/et_pb_row]' )->markup;
		$equal   = $this->converter()->convert( '[et_pb_row][et_pb_column type="1_2"][et_pb_text]A[/et_pb_text][/et_pb_column][et_pb_column type="1_2"][et_pb_text]B[/et_pb_text][/et_pb_column][/et_pb_row]' )->markup;

		$this->assertStringContainsString( '{"width":"25%"}', $unequal );
		$this->assertStringContainsString( 'flex-basis:75%', $unequal );
		$this->assertStringNotContainsString( 'flex-basis', $equal );
	}

	public function test_unsafe_values_do_not_reach_the_markup() {
		$markup = $this->converter()->convert( '[et_pb_section background_color="red;x:expression(1)" module_id="a%22 onclick=%22x"][et_pb_row][et_pb_column type="4_4"][et_pb_button button_text="Go" button_url="javascript:alert(1)" custom_button="on" button_bg_color="#fff%22><script>"][/et_pb_button][/et_pb_column][/et_pb_row][/et_pb_section]' )->markup;

		$this->assertStringNotContainsString( 'expression', $markup );
		$this->assertStringNotContainsString( 'onclick="', $markup );
		$this->assertStringNotContainsString( '<script>', $markup );
	}
}

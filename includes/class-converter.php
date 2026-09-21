<?php
/**
 * Converter.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

use EDH\DiviGutenberg\Mapper\Mapper_Registry;
use EDH\DiviGutenberg\Parser\Shortcode_Parser;
use EDH\DiviGutenberg\Resolver\Dynamic_Content;
use EDH\DiviGutenberg\Resolver\Global_Colors;
use EDH\DiviGutenberg\Resolver\Global_Module_Resolver;

/**
 * Converts Divi content to block markup. It does not write to the database.
 */
final class Converter {

	/**
	 * Mapper registry.
	 *
	 * @var Mapper_Registry
	 */
	private $registry;

	/**
	 * Global colours.
	 *
	 * @var Global_Colors
	 */
	private $colors;

	/**
	 * Dynamic content resolver.
	 *
	 * @var Dynamic_Content
	 */
	private $dynamic;

	/**
	 * Global module resolver.
	 *
	 * @var Global_Module_Resolver
	 */
	private $globals;

	/**
	 * Constructor. Each argument has a default for use in WordPress.
	 *
	 * @param Mapper_Registry|null        $registry Mapper registry.
	 * @param Global_Colors|null          $colors   Global colours.
	 * @param Dynamic_Content|null        $dynamic  Dynamic content resolver.
	 * @param Global_Module_Resolver|null $globals  Global module resolver.
	 */
	public function __construct( $registry = null, $colors = null, $dynamic = null, $globals = null ) {
		$this->registry = $registry ? $registry : Mapper_Registry::with_defaults();
		$this->dynamic  = $dynamic ? $dynamic : new Dynamic_Content();
		$this->globals  = $globals ? $globals : new Global_Module_Resolver();

		if ( $colors ) {
			$this->colors = $colors;
		} else {
			$this->colors = function_exists( 'get_option' ) ? Global_Colors::from_wordpress() : new Global_Colors();
		}
	}

	/**
	 * Converts content.
	 *
	 * @param string              $content Post content.
	 * @param int                 $post_id Post ID, for dynamic content.
	 * @param array<string,mixed> $options Options. See Context::$options.
	 * @return Result
	 */
	public function convert( $content, $post_id = 0, array $options = array() ) {
		$context = new Context( $post_id, $this->registry, $this->colors, $this->dynamic, $this->globals, $options );
		$result  = new Result( $context->report );

		if ( ! Shortcode_Parser::has_divi_shortcodes( (string) $content ) ) {
			return $result;
		}

		$parser = new Shortcode_Parser();

		$result->has_divi = true;
		$result->blocks   = $context->convert_nodes( $parser->parse( $content ) );
		$result->markup   = Block_Serializer::serialize( $result->blocks );

		return $result;
	}
}

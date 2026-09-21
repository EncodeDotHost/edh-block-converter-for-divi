<?php
/**
 * Conversion context.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

use EDH\DiviGutenberg\Mapper\Mapper_Registry;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Parser\Shortcode_Parser;
use EDH\DiviGutenberg\Resolver\Dynamic_Content;
use EDH\DiviGutenberg\Resolver\Global_Colors;
use EDH\DiviGutenberg\Resolver\Global_Module_Resolver;
use EDH\DiviGutenberg\Style\Style_Mapper;

/**
 * Holds the state and the services of one post conversion.
 */
final class Context {

	const MAX_GLOBAL_DEPTH = 5;

	/**
	 * Post ID. Zero when the content has no post.
	 *
	 * @var int
	 */
	public $post_id = 0;

	/**
	 * Report.
	 *
	 * @var Report
	 */
	public $report;

	/**
	 * Style mapper.
	 *
	 * @var Style_Mapper
	 */
	public $styles;

	/**
	 * HTML splitter.
	 *
	 * @var Html_To_Blocks
	 */
	public $html;

	/**
	 * True while the converter is in a fullwidth section.
	 *
	 * @var bool
	 */
	public $fullwidth = false;

	/**
	 * Options. `keep_form_shortcodes` keeps the Divi form shortcodes.
	 *
	 * @var array<string,mixed>
	 */
	public $options = array();

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
	 * Current depth of nested global modules.
	 *
	 * @var int
	 */
	private $global_depth = 0;

	/**
	 * Last query ID.
	 *
	 * @var int
	 */
	private $query_id = 0;

	/**
	 * Cache of attachment IDs.
	 *
	 * @var array<string,int>
	 */
	private $attachments = array();

	/**
	 * Constructor.
	 *
	 * @param int                    $post_id  Post ID.
	 * @param Mapper_Registry        $registry Mapper registry.
	 * @param Global_Colors          $colors   Global colours.
	 * @param Dynamic_Content        $dynamic  Dynamic content resolver.
	 * @param Global_Module_Resolver $globals  Global module resolver.
	 * @param array<string,mixed>    $options  Options.
	 */
	public function __construct( $post_id, Mapper_Registry $registry, Global_Colors $colors, Dynamic_Content $dynamic, Global_Module_Resolver $globals, array $options = array() ) {
		$this->post_id  = (int) $post_id;
		$this->registry = $registry;
		$this->colors   = $colors;
		$this->dynamic  = $dynamic;
		$this->globals  = $globals;
		$this->options  = $options;
		$this->report   = new Report();
		$this->styles   = new Style_Mapper();
		$this->html     = new Html_To_Blocks();
	}

	/**
	 * Converts a list of nodes.
	 *
	 * @param Node[] $nodes Nodes.
	 * @return array
	 */
	public function convert_nodes( array $nodes ) {
		$blocks = array();
		foreach ( $nodes as $node ) {
			foreach ( $this->convert_node( $node ) as $block ) {
				$blocks[] = $block;
			}
		}
		return $blocks;
	}

	/**
	 * Converts one node.
	 *
	 * @param Node $node Node.
	 * @return array
	 */
	public function convert_node( Node $node ) {
		if ( Node::TYPE_HTML === $node->type ) {
			return $this->html->convert( $this->dynamic->resolve_in_text( $node->content, $this ) );
		}

		$global_id = (int) $node->attr( 'global_module' );
		if ( $global_id > 0 ) {
			$resolved = $this->resolve_global( $node, $global_id );
			if ( null !== $resolved ) {
				++$this->global_depth;
				$blocks = $this->convert_node( $resolved );
				--$this->global_depth;
				return $blocks;
			}
		}

		if ( $this->is_disabled( $node ) ) {
			$this->report->add( Report::DROPPED, $node->tag, __( 'The module is disabled in Divi. The converter removed it.', 'edh-divi-gutenberg' ) );
			return array();
		}

		foreach ( $node->attrs as $name => $value ) {
			$value                = $this->colors->resolve( $value );
			$node->attrs[ $name ] = $this->dynamic->resolve_in_text( $value, $this );
		}
		$node->content = $this->dynamic->resolve_in_text( $node->content, $this );

		$this->report->count_module( $node->tag );

		return $this->registry->get( $node->tag )->convert( $node, $this );
	}

	/**
	 * Gets the next query ID. Each query block on a page needs a different ID.
	 *
	 * @return int
	 */
	public function next_query_id() {
		return $this->query_id++;
	}

	/**
	 * Finds the attachment ID of an image URL.
	 *
	 * @param string $url URL.
	 * @return int Zero when the media library does not have the image.
	 */
	public function attachment_id( $url ) {
		if ( '' === $url || ! function_exists( 'attachment_url_to_postid' ) ) {
			return 0;
		}
		if ( ! isset( $this->attachments[ $url ] ) ) {
			$this->attachments[ $url ] = (int) attachment_url_to_postid( $url );
		}
		return $this->attachments[ $url ];
	}

	/**
	 * Loads the library content of a global module.
	 *
	 * @param Node $node      Node with the reference.
	 * @param int  $global_id Library post ID.
	 * @return Node|null Null when the library post is not available.
	 */
	private function resolve_global( Node $node, $global_id ) {
		if ( $this->global_depth >= self::MAX_GLOBAL_DEPTH ) {
			return null;
		}

		$content = $this->globals->content( $global_id );
		if ( '' === $content ) {
			$this->report->add(
				Report::INFO,
				$node->tag,
				/* translators: %d: Divi Library post ID. */
				sprintf( __( 'Global module %d is not in the Divi Library. The converter used the local copy.', 'edh-divi-gutenberg' ), $global_id )
			);
			return null;
		}

		$parser = new Shortcode_Parser();
		foreach ( $parser->parse( $content ) as $candidate ) {
			if ( Node::TYPE_MODULE === $candidate->type ) {
				unset( $candidate->attrs['global_module'] );
				// A library row is `et_pb_row`, also when the page uses it as an inner row.
				if ( $candidate->tag !== $node->tag && false !== strpos( $node->tag, '_inner' ) ) {
					$candidate->tag = $node->tag;
				}
				return $candidate;
			}
		}

		return null;
	}

	/**
	 * Tells if Divi hides the node on all devices.
	 *
	 * @param Node $node Node.
	 * @return bool
	 */
	private function is_disabled( Node $node ) {
		return $node->is_on( 'disabled' ) || 'on|on|on' === $node->attr( 'disabled_on' );
	}
}

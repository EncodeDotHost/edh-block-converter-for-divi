<?php
/**
 * Divi 4 shortcode parser.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Parser;

/**
 * Parses Divi 4 shortcode content to a Node tree.
 *
 * The parser does not use the WordPress shortcode registry, thus Divi does
 * not need to be active.
 */
final class Shortcode_Parser {

	// The line-break holder is a placeholder in code content, not a module.
	const TOKEN_PATTERN = '/\[(\/?)(et_pb_(?!line_break_holder)[a-zA-Z0-9_]+)((?:\s[^\]]*?)?)(\/?)\]/';

	/**
	 * Modules whose content is raw code.
	 *
	 * @var string[]
	 */
	const RAW_CONTENT_TAGS = array( 'et_pb_code', 'et_pb_fullwidth_code' );

	/**
	 * Tells if content has Divi 4 shortcodes.
	 *
	 * @param string $content Post content.
	 * @return bool
	 */
	public static function has_divi_shortcodes( $content ) {
		return false !== strpos( $content, '[et_pb_' );
	}

	/**
	 * Parses content.
	 *
	 * @param string $content Post content.
	 * @return Node[]
	 */
	public function parse( $content ) {
		$content = (string) $content;
		$tokens  = $this->tokenize( $content );

		return $this->parse_range( $content, $tokens, 0, count( $tokens ) - 1, 0, strlen( $content ) );
	}

	/**
	 * Finds all Divi tags, and pairs each opening tag with its closing tag.
	 *
	 * @param string $content Content.
	 * @return array<int,array<string,mixed>>
	 */
	private function tokenize( $content ) {
		$tokens = array();

		if ( ! preg_match_all( self::TOKEN_PATTERN, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			return $tokens;
		}

		foreach ( $matches as $match ) {
			$tokens[] = array(
				'start'   => $match[0][1],
				'end'     => $match[0][1] + strlen( $match[0][0] ),
				'closing' => '/' === $match[1][0],
				'tag'     => $match[2][0],
				'attrs'   => $match[3][0],
				'self'    => '/' === $match[4][0],
				'match'   => -1,
			);
		}

		$count = count( $tokens );
		for ( $i = 0; $i < $count; $i++ ) {
			if ( $tokens[ $i ]['closing'] || $tokens[ $i ]['self'] ) {
				continue;
			}
			$depth = 0;
			for ( $j = $i + 1; $j < $count; $j++ ) {
				if ( $tokens[ $j ]['tag'] !== $tokens[ $i ]['tag'] || $tokens[ $j ]['self'] ) {
					continue;
				}
				if ( ! $tokens[ $j ]['closing'] ) {
					++$depth;
				} elseif ( 0 === $depth ) {
					$tokens[ $i ]['match'] = $j;
					break;
				} else {
					--$depth;
				}
			}
		}

		return $tokens;
	}

	/**
	 * Builds the nodes of one token range.
	 *
	 * @param string $content    Full content.
	 * @param array  $tokens     All tokens.
	 * @param int    $first      Index of the first token in the range.
	 * @param int    $last       Index of the last token in the range.
	 * @param int    $text_start Offset where the range text starts.
	 * @param int    $text_end   Offset where the range text ends.
	 * @return Node[]
	 */
	private function parse_range( $content, array $tokens, $first, $last, $text_start, $text_end ) {
		$nodes  = array();
		$cursor = $text_start;
		$i      = $first;

		while ( $i <= $last ) {
			$token = $tokens[ $i ];

			$this->add_html_node( $nodes, substr( $content, $cursor, $token['start'] - $cursor ) );
			$cursor = $token['end'];

			// An unpaired closing tag has no meaning.
			if ( $token['closing'] ) {
				++$i;
				continue;
			}

			$node = Node::module( $token['tag'], Attribute_Decoder::parse( $token['attrs'] ) );

			if ( $token['match'] > $i && $token['match'] <= $last ) {
				$close = $tokens[ $token['match'] ];
				$inner = $this->parse_range( $content, $tokens, $i + 1, $token['match'] - 1, $token['end'], $close['start'] );

				if ( $this->has_module( $inner ) ) {
					$node->children = $inner;
				} else {
					$raw           = substr( $content, $token['end'], $close['start'] - $token['end'] );
					$node->content = $this->clean_content( $raw, in_array( $token['tag'], self::RAW_CONTENT_TAGS, true ) );
				}

				$cursor    = $close['end'];
				$i         = $token['match'] + 1;
				$node->raw = substr( $content, $token['start'], $close['end'] - $token['start'] );
			} else {
				$node->raw = substr( $content, $token['start'], $token['end'] - $token['start'] );
				++$i;
			}

			$nodes[] = $node;
		}

		$this->add_html_node( $nodes, substr( $content, $cursor, $text_end - $cursor ) );

		return $nodes;
	}

	/**
	 * Adds an HTML node when the text between two tags has real content.
	 *
	 * @param Node[] $nodes Node list.
	 * @param string $text  Text.
	 */
	private function add_html_node( array &$nodes, $text ) {
		$text = $this->clean_content( $text, false );
		if ( '' !== $text ) {
			$nodes[] = Node::html( $text );
		}
	}

	/**
	 * Tells if a node list has a module node.
	 *
	 * @param Node[] $nodes Nodes.
	 * @return bool
	 */
	private function has_module( array $nodes ) {
		foreach ( $nodes as $node ) {
			if ( Node::TYPE_MODULE === $node->type ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Removes the wpautop debris around builder tags, and restores encoded text.
	 *
	 * @param string $text   Raw inner text.
	 * @param bool   $is_raw True for the code modules.
	 * @return string
	 */
	private function clean_content( $text, $is_raw ) {
		$text = preg_replace( '/^(?:\s|<\/p>|<br\s*\/?>)+/i', '', $text );
		$text = preg_replace( '/(?:\s|<p>|<br\s*\/?>)+$/i', '', $text );

		if ( '' === $text || null === $text ) {
			return '';
		}

		// Divi changes `target` to keep WordPress from adding `rel` values.
		$text = str_replace( 'data-et-target-link=', 'target=', $text );

		if ( $is_raw ) {
			$text = str_replace( array( '&#091;', '&#093;', '&#91;', '&#93;' ), array( '[', ']', '[', ']' ), $text );
			$text = str_replace( '<!-- [et_pb_line_break_holder] -->', "\n", $text );
			$text = preg_replace( '/^<p>|<\/p>$/i', '', $text );
		}

		return trim( $text );
	}
}

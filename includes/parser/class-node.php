<?php
/**
 * Neutral content node.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Parser;

/**
 * One element of the parsed Divi content tree.
 *
 * The tree is independent of the source format. A Divi 5 parser can
 * produce the same tree at a later time.
 */
final class Node {

	const TYPE_MODULE = 'module';
	const TYPE_HTML   = 'html';

	/**
	 * Node type. One of the TYPE_* constants.
	 *
	 * @var string
	 */
	public $type = self::TYPE_MODULE;

	/**
	 * Divi 4 tag, for example `et_pb_text`. Empty for HTML nodes.
	 *
	 * @var string
	 */
	public $tag = '';

	/**
	 * Decoded attributes.
	 *
	 * @var array<string,string>
	 */
	public $attrs = array();

	/**
	 * Inner HTML of a leaf module, or the HTML of an HTML node.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * Child nodes.
	 *
	 * @var Node[]
	 */
	public $children = array();

	/**
	 * Original source text of a module.
	 *
	 * @var string
	 */
	public $raw = '';

	/**
	 * Creates a module node.
	 *
	 * @param string               $tag   Shortcode tag.
	 * @param array<string,string> $attrs Attributes.
	 * @return Node
	 */
	public static function module( $tag, array $attrs = array() ) {
		$node        = new self();
		$node->tag   = $tag;
		$node->attrs = $attrs;
		return $node;
	}

	/**
	 * Creates an HTML node.
	 *
	 * @param string $html HTML.
	 * @return Node
	 */
	public static function html( $html ) {
		$node          = new self();
		$node->type    = self::TYPE_HTML;
		$node->content = $html;
		return $node;
	}

	/**
	 * Gets an attribute.
	 *
	 * @param string $name     Attribute name.
	 * @param string $fallback Value when the attribute is absent or empty.
	 * @return string
	 */
	public function attr( $name, $fallback = '' ) {
		return isset( $this->attrs[ $name ] ) && '' !== $this->attrs[ $name ] ? $this->attrs[ $name ] : $fallback;
	}

	/**
	 * Tells if an on/off attribute is on.
	 *
	 * @param string $name     Attribute name.
	 * @param string $fallback Divi default for the attribute.
	 * @return bool
	 */
	public function is_on( $name, $fallback = 'off' ) {
		return 'on' === $this->attr( $name, $fallback );
	}

	/**
	 * Gets the child modules with one of the given tags.
	 *
	 * @param string[] $tags Tags. Empty for all modules.
	 * @return Node[]
	 */
	public function child_modules( array $tags = array() ) {
		$found = array();
		foreach ( $this->children as $child ) {
			if ( self::TYPE_MODULE === $child->type && ( ! $tags || in_array( $child->tag, $tags, true ) ) ) {
				$found[] = $child;
			}
		}
		return $found;
	}
}

<?php
/**
 * HTML splitter.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

use DOMDocument;
use DOMElement;
use DOMNode;
use EDH\DiviGutenberg\Mapper\Block_Factory;

/**
 * Splits rich HTML into paragraph, heading, list, image, quote, and separator blocks.
 *
 * HTML that has no safe block equivalent becomes a custom HTML block.
 */
final class Html_To_Blocks {

	const INLINE_TAGS = array( 'a', 'abbr', 'b', 'bdo', 'br', 'cite', 'code', 'del', 'em', 'font', 'i', 'img', 'ins', 'kbd', 'mark', 'q', 's', 'small', 'span', 'strong', 'sub', 'sup', 'time', 'u' );

	/**
	 * Document of the current conversion.
	 *
	 * @var DOMDocument
	 */
	private $dom;

	/**
	 * Default text alignment.
	 *
	 * @var string
	 */
	private $align = '';

	/**
	 * Converts HTML to blocks.
	 *
	 * @param string $html  HTML.
	 * @param string $align Default text alignment: `left`, `center`, `right`, or empty.
	 * @return array
	 */
	public function convert( $html, $align = '' ) {
		$html = trim( (string) $html );
		if ( '' === $html ) {
			return array();
		}

		if ( function_exists( 'wpautop' ) && ! preg_match( '/<(?:p|div|h[1-6]|ul|ol|blockquote|table|figure|pre|section)\b/i', $html ) ) {
			$html = wpautop( $html );
		}

		$this->align = in_array( $align, array( 'center', 'right' ), true ) ? $align : '';
		$this->dom   = new DOMDocument();

		$previous = libxml_use_internal_errors( true );
		$loaded   = $this->dom->loadHTML(
			'<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div>' . $html . '</div></body></html>'
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		$body = $loaded ? $this->dom->getElementsByTagName( 'body' )->item( 0 ) : null;
		if ( ! $body || ! $body->firstChild ) {
			return array( Block_Factory::html( $html ) );
		}

		$blocks = array();
		foreach ( $body->childNodes as $root ) {
			$blocks = array_merge( $blocks, $root instanceof DOMElement && 'div' === $root->nodeName ? $this->convert_children( $root ) : $this->convert_element( $root ) );
		}
		return $blocks;
	}

	/**
	 * Converts the child nodes of an element.
	 *
	 * @param DOMNode $parent_node Parent.
	 * @return array
	 */
	private function convert_children( DOMNode $parent_node ) {
		$blocks = array();
		$inline = '';

		foreach ( $parent_node->childNodes as $child ) {
			if ( XML_COMMENT_NODE === $child->nodeType ) {
				continue;
			}

			$is_inline = XML_TEXT_NODE === $child->nodeType || ( $child instanceof DOMElement && in_array( $child->nodeName, self::INLINE_TAGS, true ) );

			// An image that is not in a sentence becomes an image block.
			if ( $is_inline && $child instanceof DOMElement && '' === trim( $inline ) && $this->image_element( $child ) ) {
				$is_inline = false;
			}

			if ( $is_inline ) {
				$inline .= $this->dom->saveHTML( $child );
				continue;
			}

			$blocks = array_merge( $blocks, $this->flush_inline( $inline ) );
			$inline = '';

			if ( $child instanceof DOMElement ) {
				$blocks = array_merge( $blocks, $this->convert_element( $child ) );
			}
		}

		return array_merge( $blocks, $this->flush_inline( $inline ) );
	}

	/**
	 * Converts collected inline HTML to a paragraph.
	 *
	 * @param string $inline Inline HTML.
	 * @return array
	 */
	private function flush_inline( $inline ) {
		$inline = trim( $inline );
		if ( '' === $inline ) {
			return array();
		}
		return $this->text_block( $inline, $inline, $this->align );
	}

	/**
	 * Converts one block-level element.
	 *
	 * @param DOMNode $element Element.
	 * @return array
	 */
	private function convert_element( DOMNode $element ) {
		if ( ! $element instanceof DOMElement ) {
			return $this->flush_inline( (string) $element->textContent );
		}

		$name = $element->nodeName;

		if ( 'p' === $name ) {
			$image = $this->sole_image( $element );
			if ( $image ) {
				return array( $this->image_block( $image ) );
			}
			$inner = trim( $this->inner_html( $element ) );
			if ( '' === $inner || '&nbsp;' === $inner || "\xC2\xA0" === $inner ) {
				return array();
			}
			return $this->text_block( $inner, (string) $element->textContent, $this->alignment( $element ) );
		}

		if ( preg_match( '/^h([1-6])$/', $name, $match ) ) {
			$inner = trim( $this->inner_html( $element ) );
			if ( '' === $inner ) {
				return array();
			}
			$attrs = array( 'textAlign' => $this->alignment( $element ) );
			if ( $element->hasAttribute( 'id' ) ) {
				$attrs['anchor'] = $element->getAttribute( 'id' );
			}
			return array( Block_Factory::heading( $inner, (int) $match[1], $attrs ) );
		}

		if ( 'ul' === $name || 'ol' === $name ) {
			$list = $this->list_block( $element );
			return array( $list ? $list : Block_Factory::html( $this->dom->saveHTML( $element ) ) );
		}

		if ( 'blockquote' === $name ) {
			return array( $this->quote_block( $element ) );
		}

		if ( 'hr' === $name ) {
			return array( Block_Factory::separator() );
		}

		if ( 'img' === $name || 'a' === $name ) {
			$image = $this->image_element( $element );
			if ( $image ) {
				return array( $this->image_block( $element ) );
			}
		}

		$is_plain_wrapper = in_array( $name, array( 'div', 'section', 'article' ), true )
			&& ! $element->hasAttribute( 'class' )
			&& ! $element->hasAttribute( 'style' )
			&& ! $element->hasAttribute( 'id' );

		if ( $is_plain_wrapper ) {
			return $this->convert_children( $element );
		}

		return array( Block_Factory::html( $this->dom->saveHTML( $element ) ) );
	}

	/**
	 * Builds a paragraph, or a shortcode block when the text is one shortcode.
	 *
	 * @param string $inner Inner HTML.
	 * @param string $text  Text content.
	 * @param string $align Alignment.
	 * @return array
	 */
	private function text_block( $inner, $text, $align ) {
		$text = trim( $text );
		if ( $text === $inner && preg_match( '/^\[[a-zA-Z][\w\-]*(?:\s[^\]]*)?\](?:.*\[\/[\w\-]+\])?$/s', $text ) ) {
			return array( Block_Factory::shortcode( $text ) );
		}
		return array( Block_Factory::paragraph( $inner, array( 'align' => $align ) ) );
	}

	/**
	 * Builds a list block. Returns null when an item has content that a list item cannot hold.
	 *
	 * @param DOMElement $list_element List element.
	 * @return array|null
	 */
	private function list_block( DOMElement $list_element ) {
		$items = array();

		foreach ( $list_element->childNodes as $item ) {
			if ( ! $item instanceof DOMElement ) {
				continue;
			}
			if ( 'li' !== $item->nodeName ) {
				return null;
			}

			$html   = '';
			$nested = array();

			foreach ( $item->childNodes as $child ) {
				if ( $child instanceof DOMElement && in_array( $child->nodeName, array( 'ul', 'ol' ), true ) ) {
					$sub = $this->list_block( $child );
					if ( ! $sub ) {
						return null;
					}
					$nested[] = $sub;
				} elseif ( $child instanceof DOMElement && 'p' === $child->nodeName ) {
					$html .= ( '' === trim( $html ) ? '' : '<br>' ) . $this->inner_html( $child );
				} elseif ( $child instanceof DOMElement && ! in_array( $child->nodeName, self::INLINE_TAGS, true ) ) {
					return null;
				} else {
					$html .= $this->dom->saveHTML( $child );
				}
			}

			$items[] = Block_Factory::list_item( trim( $html ), $nested );
		}

		return $items ? Block_Factory::list_block( $items, 'ol' === $list_element->nodeName ) : null;
	}

	/**
	 * Builds a quote block.
	 *
	 * @param DOMElement $quote Blockquote element.
	 * @return array
	 */
	private function quote_block( DOMElement $quote ) {
		$citation = '';
		foreach ( array( 'cite', 'footer' ) as $tag ) {
			foreach ( iterator_to_array( $quote->getElementsByTagName( $tag ) ) as $cite ) {
				if ( $cite->parentNode === $quote ) {
					$citation = trim( $this->inner_html( $cite ) );
					$quote->removeChild( $cite );
				}
			}
		}
		return Block_Factory::quote( $this->convert_children( $quote ), $citation );
	}

	/**
	 * Gets the image of a paragraph that holds only an image.
	 *
	 * @param DOMElement $paragraph Paragraph.
	 * @return DOMElement|null The `img` element, or the `a` element around it.
	 */
	private function sole_image( DOMElement $paragraph ) {
		$found = null;
		foreach ( $paragraph->childNodes as $child ) {
			if ( XML_TEXT_NODE === $child->nodeType && '' === trim( str_replace( "\xC2\xA0", ' ', $child->textContent ) ) ) {
				continue;
			}
			if ( $found || ! $child instanceof DOMElement || ! $this->image_element( $child ) ) {
				return null;
			}
			$found = $child;
		}
		return $found;
	}

	/**
	 * Gets the `img` element of an image, or of a link that holds only an image.
	 *
	 * @param DOMElement $element Element.
	 * @return DOMElement|null
	 */
	private function image_element( DOMElement $element ) {
		if ( 'img' === $element->nodeName ) {
			return $element;
		}
		if ( 'a' === $element->nodeName && 1 === $element->childNodes->length && $element->firstChild instanceof DOMElement && 'img' === $element->firstChild->nodeName ) {
			return $element->firstChild;
		}
		return null;
	}

	/**
	 * Builds an image block from an `img` element or a linked image.
	 *
	 * @param DOMElement $element Element.
	 * @return array
	 */
	private function image_block( DOMElement $element ) {
		$img   = $this->image_element( $element );
		$attrs = array();
		$image = array(
			'url'   => $img->getAttribute( 'src' ),
			'alt'   => $img->getAttribute( 'alt' ),
			'title' => $img->getAttribute( 'title' ),
		);

		if ( 'a' === $element->nodeName ) {
			$image['href']   = $element->getAttribute( 'href' );
			$image['target'] = $element->getAttribute( 'target' );
		}

		$classes = $img->getAttribute( 'class' );
		if ( preg_match( '/\bwp-image-(\d+)\b/', $classes, $match ) ) {
			$attrs['id'] = (int) $match[1];
		}
		if ( preg_match( '/\bsize-([a-z0-9_\-]+)\b/', $classes, $match ) && ! empty( $attrs['id'] ) ) {
			$attrs['sizeSlug'] = $match[1];
		}
		if ( preg_match( '/\balign(left|center|right)\b/', $classes, $match ) ) {
			$attrs['align'] = $match[1];
		}

		return Block_Factory::image( $image, $attrs );
	}

	/**
	 * Gets the text alignment of an element.
	 *
	 * @param DOMElement $element Element.
	 * @return string
	 */
	private function alignment( DOMElement $element ) {
		if ( preg_match( '/text-align\s*:\s*(left|center|right)/i', $element->getAttribute( 'style' ), $match ) ) {
			return strtolower( $match[1] );
		}
		return $this->align;
	}

	/**
	 * Gets the inner HTML of an element.
	 *
	 * @param DOMNode $element Element.
	 * @return string
	 */
	private function inner_html( DOMNode $element ) {
		$html = '';
		foreach ( $element->childNodes as $child ) {
			$html .= $this->dom->saveHTML( $child );
		}
		return $html;
	}
}

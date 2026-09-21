<?php
/**
 * Mapper base class.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Report;
use EDH\DiviGutenberg\Style\Spacing;
use EDH\DiviGutenberg\Style\Style_Mapper;

/**
 * Shared helpers for the module mappers.
 */
abstract class Base_Mapper implements Mapper {

	/**
	 * Puts blocks in a group when the module has design or identity attributes.
	 *
	 * @param Node     $node     Divi node.
	 * @param array    $blocks   Blocks.
	 * @param Context  $context  Context.
	 * @param bool     $force    True to always add the group.
	 * @param string[] $features Style features for the group.
	 * @param string   $font     Divi font option prefix, for example `text`. Empty for no text styles.
	 * @return array
	 */
	protected function wrap( Node $node, array $blocks, Context $context, $force = false, array $features = array( Style_Mapper::BACKGROUND, Style_Mapper::PADDING, Style_Mapper::MARGIN ), $font = '' ) {
		if ( ! $blocks ) {
			return array();
		}

		if ( '' !== $font ) {
			$features[] = Style_Mapper::TEXT;
		}

		$attrs = $context->styles->block_attrs( $node, $features, $font );
		if ( ! $attrs && ! $force ) {
			return $blocks;
		}

		return array( Block_Factory::group( $blocks, $attrs ) );
	}

	/**
	 * Prepares a short text for a rich text field.
	 *
	 * @param string $text Text. HTML is kept.
	 * @return string
	 */
	protected function inline( $text ) {
		$text = trim( (string) $text );
		if ( false !== strpos( $text, '<' ) || preg_match( '/&[a-z0-9#]+;/i', $text ) ) {
			return $text;
		}
		return esc_html( $text );
	}

	/**
	 * Puts a link around inline HTML.
	 *
	 * @param string $html Inline HTML.
	 * @param string $url  URL. Empty for no link.
	 * @param bool   $new_tab True to open the link in a new tab.
	 * @return string
	 */
	protected function link( $html, $url, $new_tab = false ) {
		if ( '' === $url || '' === $html ) {
			return $html;
		}
		$target = $new_tab ? ' target="_blank" rel="noreferrer noopener"' : '';
		return '<a href="' . esc_url( $url ) . '"' . $target . '>' . $html . '</a>';
	}

	/**
	 * Gets a heading level from a Divi `h1` to `h6` value.
	 *
	 * @param string $value    Divi value.
	 * @param int    $fallback Fallback level.
	 * @return int
	 */
	protected function level( $value, $fallback ) {
		return preg_match( '/^h([1-6])$/', (string) $value, $match ) ? (int) $match[1] : $fallback;
	}

	/**
	 * Builds an image block for a URL.
	 *
	 * @param string  $url     Image URL.
	 * @param Context $context Context.
	 * @param array   $image   More image values. See Block_Factory::image().
	 * @param array   $attrs   More block attributes.
	 * @return array|null Null when the URL is empty.
	 */
	protected function image( $url, Context $context, array $image = array(), array $attrs = array() ) {
		if ( '' === $url ) {
			return null;
		}
		$id = $context->attachment_id( $url );
		if ( $id ) {
			$attrs['id']       = $id;
			$attrs['sizeSlug'] = 'full';
		}
		$image['url'] = $url;
		return Block_Factory::image( $image, $attrs );
	}

	/**
	 * Builds a buttons block with one button.
	 *
	 * @param Node    $node    Divi node.
	 * @param string  $text    Label.
	 * @param string  $url     URL.
	 * @param string  $justify Alignment.
	 * @param string  $prefix  Divi button option prefix, for example `button` or `button_one`.
	 * @return array|null Null when the label is empty.
	 */
	protected function button( Node $node, $text, $url, $justify = '', $prefix = 'button' ) {
		if ( '' === trim( $text ) ) {
			return null;
		}

		$colors = array();
		if ( $node->is_on( 'custom_' . $prefix ) ) {
			$colors = array(
				'text'       => Style_Mapper::color( $node->attr( $prefix . '_text_color' ) ),
				'background' => Style_Mapper::color( $node->attr( $prefix . '_bg_color' ) ),
				'radius'     => Spacing::length( $node->attr( $prefix . '_border_radius' ) ),
			);
		}

		$button = Block_Factory::button( $this->inline( $text ), $url, $node->is_on( 'url_new_window' ), $colors );
		return Block_Factory::buttons( array( $button ), $justify );
	}

	/**
	 * Adds a "downgraded" notice.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @param string  $message Message.
	 */
	protected function downgraded( Node $node, Context $context, $message ) {
		$context->report->add( Report::DOWNGRADED, $node->tag, $message );
	}
}

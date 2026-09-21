<?php
/**
 * Block serializer.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

/**
 * Writes parsed-block arrays as block markup.
 */
final class Block_Serializer {

	/**
	 * Serializes a list of top-level blocks. An empty line separates the blocks, as the editor does.
	 *
	 * @param array $blocks Blocks.
	 * @return string
	 */
	public static function serialize( array $blocks ) {
		return implode( "\n\n", array_map( array( __CLASS__, 'serialize_block' ), $blocks ) );
	}

	/**
	 * Serializes one block.
	 *
	 * @param array $block Block.
	 * @return string
	 */
	public static function serialize_block( array $block ) {
		if ( function_exists( 'serialize_block' ) ) {
			return serialize_block( $block );
		}

		$content = '';
		$index   = 0;
		foreach ( $block['innerContent'] as $chunk ) {
			$content .= is_string( $chunk ) ? $chunk : self::serialize_block( $block['innerBlocks'][ $index++ ] );
		}

		$name  = 0 === strpos( $block['blockName'], 'core/' ) ? substr( $block['blockName'], 5 ) : $block['blockName'];
		$attrs = empty( $block['attrs'] ) ? '' : self::attributes( $block['attrs'] ) . ' ';

		if ( '' === $content ) {
			return '<!-- wp:' . $name . ' ' . $attrs . '/-->';
		}

		return '<!-- wp:' . $name . ' ' . $attrs . '-->' . $content . '<!-- /wp:' . $name . ' -->';
	}

	/**
	 * Encodes block attributes with the escapes of the block grammar.
	 *
	 * @param array $attrs Attributes.
	 * @return string
	 */
	private static function attributes( array $attrs ) {
		$json = (string) json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Used only when WordPress is not loaded.

		return str_replace(
			array( '--', '<', '>', '&', '\\"' ),
			array( '\\u002d\\u002d', '\\u003c', '\\u003e', '\\u0026', '\\u0022' ),
			$json
		);
	}
}

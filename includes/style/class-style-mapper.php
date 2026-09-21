<?php
/**
 * Style mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Style;

use EDH\DiviGutenberg\Parser\Node;

/**
 * Maps basic Divi design attributes to block attributes.
 */
final class Style_Mapper {

	const BACKGROUND = 'background';
	const PADDING    = 'padding';
	const MARGIN     = 'margin';
	const TEXT       = 'text';

	/**
	 * Builds the `style`, `anchor`, and `className` block attributes.
	 *
	 * @param Node     $node        Divi node.
	 * @param string[] $features    Features that the target block supports.
	 * @param string   $font_prefix Divi font option prefix for the TEXT feature, for example `header`.
	 * @return array<string,mixed>
	 */
	public function block_attrs( Node $node, array $features, $font_prefix = '' ) {
		$attrs = $this->identity( $node );
		$style = array();

		if ( in_array( self::BACKGROUND, $features, true ) ) {
			$gradient = $this->gradient( $node );
			$color    = self::color( $node->attr( 'background_color' ) );
			if ( '' !== $gradient ) {
				$style['color']['gradient'] = $gradient;
			} elseif ( '' !== $color && $node->is_on( 'use_background_color', 'on' ) ) {
				$style['color']['background'] = $color;
			}
		}

		if ( in_array( self::TEXT, $features, true ) ) {
			$prefix = '' === $font_prefix ? '' : $font_prefix . '_';
			$color  = self::color( $node->attr( $prefix . 'text_color' ) );
			$size   = Spacing::length( $node->attr( $prefix . 'font_size' ) );
			if ( '' !== $color ) {
				$style['color']['text'] = $color;
			} elseif ( 'dark' === $node->attr( 'background_layout' ) ) {
				$style['color']['text'] = '#ffffff';
			}
			if ( '' !== $size && '0' !== $size ) {
				$style['typography']['fontSize'] = $size;
			}
		}

		if ( in_array( self::PADDING, $features, true ) ) {
			$padding = Spacing::parse( $node->attr( 'custom_padding' ) );
			if ( $padding ) {
				$style['spacing']['padding'] = $padding;
			}
		}

		if ( in_array( self::MARGIN, $features, true ) ) {
			$margin = Spacing::parse( $node->attr( 'custom_margin' ), true );
			if ( $margin ) {
				$style['spacing']['margin'] = $margin;
			}
		}

		if ( $style ) {
			$attrs['style'] = $style;
		}

		return $attrs;
	}

	/**
	 * Builds the `anchor` and `className` attributes from the CSS ID and classes.
	 *
	 * @param Node $node Divi node.
	 * @return array<string,string>
	 */
	public function identity( Node $node ) {
		$attrs = array();

		$anchor = preg_replace( '/[^a-zA-Z0-9_\-:.]/', '', $node->attr( 'module_id' ) );
		if ( '' !== $anchor ) {
			$attrs['anchor'] = $anchor;
		}

		$classes = preg_replace( '/[^a-zA-Z0-9_\- ]/', '', $node->attr( 'module_class' ) );
		$classes = trim( preg_replace( '/\s+/', ' ', $classes ) );
		if ( '' !== $classes ) {
			$attrs['className'] = $classes;
		}

		return $attrs;
	}

	/**
	 * Tells if a node has module-level design that needs a wrapper block.
	 *
	 * @param Node $node Divi node.
	 * @return bool
	 */
	public function needs_wrapper( Node $node ) {
		$attrs = $this->block_attrs( $node, array( self::BACKGROUND, self::PADDING, self::MARGIN ) );
		return ! empty( $attrs );
	}

	/**
	 * Gets the text alignment of a node.
	 *
	 * @param Node   $node     Divi node.
	 * @param string $attr     Attribute name.
	 * @param string $fallback Divi default.
	 * @return string `left`, `center`, `right`, or empty.
	 */
	public function text_align( Node $node, $attr = 'text_orientation', $fallback = '' ) {
		$value = $node->attr( $attr, $fallback );
		return in_array( $value, array( 'left', 'center', 'right' ), true ) ? $value : '';
	}

	/**
	 * Validates a CSS colour.
	 *
	 * @param string $value Value.
	 * @return string Empty when the value is not a colour.
	 */
	public static function color( $value ) {
		$value = trim( (string) $value );

		if ( preg_match( '/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value ) ) {
			return strtolower( $value );
		}
		if ( preg_match( '/^(?:rgb|rgba|hsl|hsla)\(\s*[0-9.,%\s\/deg]+\)$/i', $value ) ) {
			return strtolower( preg_replace( '/\s+/', '', $value ) );
		}

		return '';
	}

	/**
	 * Builds a CSS gradient from the Divi gradient attributes.
	 *
	 * @param Node $node Divi node.
	 * @return string Empty when the node has no gradient.
	 */
	public function gradient( Node $node ) {
		if ( ! $node->is_on( 'use_background_color_gradient' ) ) {
			return '';
		}

		$stops = array();
		foreach ( explode( '|', $node->attr( 'background_color_gradient_stops' ) ) as $stop ) {
			if ( preg_match( '/^(.+?)\s+(\d+(?:\.\d+)?%)$/', trim( $stop ), $match ) && '' !== self::color( $match[1] ) ) {
				$stops[] = self::color( $match[1] ) . ' ' . $match[2];
			}
		}

		if ( ! $stops ) {
			$start = self::color( $node->attr( 'background_color_gradient_start', '#2b87da' ) );
			$end   = self::color( $node->attr( 'background_color_gradient_end', '#29c4a9' ) );
			if ( '' === $start || '' === $end ) {
				return '';
			}
			$stops[] = $start . ' ' . $this->percent( $node->attr( 'background_color_gradient_start_position' ), '0%' );
			$stops[] = $end . ' ' . $this->percent( $node->attr( 'background_color_gradient_end_position' ), '100%' );
		}

		$type = $node->attr( 'background_color_gradient_type', 'linear' );
		if ( 'radial' === $type || 'circular' === $type ) {
			return 'radial-gradient(' . implode( ',', $stops ) . ')';
		}

		$direction = $node->attr( 'background_color_gradient_direction', '180deg' );
		if ( ! preg_match( '/^-?\d+(?:\.\d+)?deg$/', $direction ) ) {
			$direction = '180deg';
		}

		return 'linear-gradient(' . $direction . ',' . implode( ',', $stops ) . ')';
	}

	/**
	 * Validates a percent value.
	 *
	 * @param string $value    Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function percent( $value, $fallback ) {
		return preg_match( '/^\d+(?:\.\d+)?%$/', $value ) ? $value : $fallback;
	}
}

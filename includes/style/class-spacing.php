<?php
/**
 * Spacing values.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Style;

/**
 * Converts Divi pipe-separated spacing values.
 */
final class Spacing {

	const SIDES = array( 'top', 'right', 'bottom', 'left' );

	/**
	 * Parses `top|right|bottom|left|sync|sync` to a map of valid sides.
	 *
	 * @param string $value          Divi value.
	 * @param bool   $allow_negative True for margins.
	 * @return array<string,string>
	 */
	public static function parse( $value, $allow_negative = false ) {
		$parts  = explode( '|', (string) $value );
		$result = array();

		foreach ( self::SIDES as $index => $side ) {
			if ( ! isset( $parts[ $index ] ) ) {
				continue;
			}
			$length = self::length( $parts[ $index ], $allow_negative );
			if ( '' !== $length ) {
				$result[ $side ] = $length;
			}
		}

		return $result;
	}

	/**
	 * Validates one CSS length. Divi treats a number without a unit as pixels.
	 *
	 * @param string $value          Value.
	 * @param bool   $allow_negative True to accept negative values and `auto`.
	 * @return string Empty when the value is not valid.
	 */
	public static function length( $value, $allow_negative = false ) {
		$value = strtolower( trim( (string) $value ) );

		if ( $allow_negative && 'auto' === $value ) {
			return $value;
		}
		if ( ! preg_match( '/^(-?)(\d*\.?\d+)(px|em|rem|%|vw|vh|vmin|vmax|ch|ex|pt)?$/', $value, $match ) ) {
			return '';
		}
		if ( '-' === $match[1] && ! $allow_negative ) {
			return '';
		}
		if ( 0.0 === (float) $match[2] ) {
			return '0';
		}

		return $match[1] . $match[2] . ( isset( $match[3] ) && '' !== $match[3] ? $match[3] : 'px' );
	}
}

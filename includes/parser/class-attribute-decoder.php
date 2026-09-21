<?php
/**
 * Divi 4 attribute decoder.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Parser;

/**
 * Parses and decodes the attribute string of a Divi 4 shortcode.
 */
final class Attribute_Decoder {

	/**
	 * Attributes that hold no content or design data.
	 *
	 * @var string[]
	 */
	const BOOKKEEPING = array(
		'_builder_version',
		'_dynamic_attributes',
		'_module_preset',
		'fb_built',
		'bb_built',
		'global_colors_info',
		'locked',
		'collapsed',
		'template_type',
		'saved_tabs',
		'theme_builder_area',
		'hover_enabled',
		'sticky_enabled',
	);

	/**
	 * Parses an attribute string to a map of decoded values.
	 *
	 * @param string $text Text between the tag name and the closing bracket.
	 * @return array<string,string>
	 */
	public static function parse( $text ) {
		// wptexturize can change the quotes of a broken shortcode.
		$text = str_replace( array( '&#8220;', '&#8221;', '&#8243;', '&#8217;', '&#8242;' ), array( '"', '"', '"', "'", "'" ), $text );

		$attrs   = array();
		$pattern = '/([a-zA-Z0-9_\-]+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\']+))/';

		if ( ! preg_match_all( $pattern, $text, $matches, PREG_SET_ORDER ) ) {
			return $attrs;
		}

		foreach ( $matches as $match ) {
			$name = $match[1];
			if ( isset( $match[4] ) && '' !== $match[4] ) {
				$value = $match[4];
			} elseif ( isset( $match[3] ) && '' !== $match[3] ) {
				$value = $match[3];
			} else {
				$value = isset( $match[2] ) ? $match[2] : '';
			}

			if ( self::is_ignored( $name ) ) {
				continue;
			}

			$attrs[ $name ] = self::decode( $value );
		}

		return $attrs;
	}

	/**
	 * Decodes the Divi escape sequences of one value.
	 *
	 * Divi encodes the brackets last, thus decode them first.
	 *
	 * @param string $value Encoded value.
	 * @return string
	 */
	public static function decode( $value ) {
		$value = str_replace( array( '%91', '%93' ), array( '[', ']' ), $value );
		$value = str_replace( '%22', '"', $value );
		$value = str_ireplace( array( '%92', '%5c' ), '\\', $value );
		return $value;
	}

	/**
	 * Tells if the converter ignores an attribute.
	 *
	 * Version 1 ignores responsive, hover, and sticky values.
	 *
	 * @param string $name Attribute name.
	 * @return bool
	 */
	public static function is_ignored( $name ) {
		if ( in_array( $name, self::BOOKKEEPING, true ) ) {
			return true;
		}
		if ( 0 === strpos( $name, 'ab_' ) ) {
			return true;
		}
		return 1 === preg_match( '/(_tablet|_phone|_last_edited|__hover|__hover_enabled|__sticky|__sticky_enabled|__focus|__active|__checked)$/', $name );
	}
}

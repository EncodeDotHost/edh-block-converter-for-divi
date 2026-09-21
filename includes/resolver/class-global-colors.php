<?php
/**
 * Divi global colours.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Resolver;

/**
 * Replaces `gcid-*` references with the colour values.
 */
final class Global_Colors {

	/**
	 * Map of global colour ID to CSS colour.
	 *
	 * @var array<string,string>
	 */
	private $palette;

	/**
	 * Constructor.
	 *
	 * @param array<string,string> $palette Map of `gcid-*` to CSS colour.
	 */
	public function __construct( array $palette = array() ) {
		$this->palette = $palette;
	}

	/**
	 * Builds the palette from the Divi theme options of this site.
	 *
	 * @return Global_Colors
	 */
	public static function from_wordpress() {
		$options = get_option( 'et_divi', array() );
		$options = is_array( $options ) ? $options : array();

		$palette = array(
			'gcid-primary-color'   => isset( $options['accent_color'] ) ? $options['accent_color'] : '#2ea3f2',
			'gcid-secondary-color' => isset( $options['secondary_accent_color'] ) ? $options['secondary_accent_color'] : '#2ea3f2',
			'gcid-heading-color'   => isset( $options['header_color'] ) ? $options['header_color'] : '#666666',
			'gcid-body-color'      => isset( $options['font_color'] ) ? $options['font_color'] : '#666666',
			'gcid-link-color'      => isset( $options['link_color'] ) ? $options['link_color'] : '#2ea3f2',
		);

		$sources = array();
		if ( isset( $options['et_global_colors'] ) ) {
			$sources[] = $options['et_global_colors'];
		}
		if ( isset( $options['et_global_data']['global_colors'] ) ) {
			$sources[] = $options['et_global_data']['global_colors'];
		}

		foreach ( $sources as $source ) {
			$source = is_string( $source ) ? maybe_unserialize( $source ) : $source;
			if ( ! is_array( $source ) ) {
				continue;
			}
			foreach ( $source as $id => $data ) {
				if ( is_array( $data ) && ! empty( $data['color'] ) && is_string( $data['color'] ) ) {
					$palette[ $id ] = $data['color'];
				}
			}
		}

		return new self( $palette );
	}

	/**
	 * Replaces the global colour references in a value.
	 *
	 * @param string $value Attribute value.
	 * @return string
	 */
	public function resolve( $value ) {
		if ( false === strpos( $value, 'gcid-' ) ) {
			return $value;
		}

		// A global colour can refer to a different global colour.
		for ( $pass = 0; $pass < 3; $pass++ ) {
			if ( false === strpos( $value, 'gcid-' ) ) {
				break;
			}
			$value = preg_replace_callback(
				'/var\(--(gcid-[0-9a-z\-]+)\)|(gcid-[0-9a-z\-]+)/',
				function ( $found ) {
					$id = '' !== $found[1] ? $found[1] : $found[2];
					return isset( $this->palette[ $id ] ) ? $this->palette[ $id ] : '';
				},
				$value
			);
		}

		return $value;
	}
}

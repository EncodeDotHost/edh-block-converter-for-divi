<?php
/**
 * Class autoloader.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

/**
 * Maps `EDH\DiviGutenberg\Sub\Some_Class` to `includes/sub/class-some-class.php`.
 */
final class Autoloader {

	const PREFIX = 'EDH\\DiviGutenberg\\';

	/**
	 * Registers the autoloader.
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Loads one class or interface.
	 *
	 * @param string $class_name Fully qualified name.
	 */
	public static function load( $class_name ) {
		if ( 0 !== strpos( $class_name, self::PREFIX ) ) {
			return;
		}

		$parts = explode( '\\', substr( $class_name, strlen( self::PREFIX ) ) );
		$name  = strtolower( str_replace( '_', '-', array_pop( $parts ) ) );
		$dir   = __DIR__ . '/';

		foreach ( $parts as $part ) {
			$dir .= strtolower( str_replace( '_', '-', $part ) ) . '/';
		}

		foreach ( array( 'class-', 'interface-' ) as $type ) {
			$file = $dir . $type . $name . '.php';
			if ( is_readable( $file ) ) {
				require_once $file;
				return;
			}
		}
	}
}

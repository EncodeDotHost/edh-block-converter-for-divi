<?php
/**
 * Conversion report.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

/**
 * Collects the notices of one conversion.
 */
final class Report {

	const INFO        = 'info';
	const DOWNGRADED  = 'downgraded';
	const DROPPED     = 'dropped';
	const UNSUPPORTED = 'unsupported';

	/**
	 * Notices.
	 *
	 * @var array<int,array{level:string,tag:string,message:string}>
	 */
	private $notices = array();

	/**
	 * Count of converted modules for each tag.
	 *
	 * @var array<string,int>
	 */
	private $modules = array();

	/**
	 * Adds a notice. Identical notices are kept one time only.
	 *
	 * @param string $level   One of the level constants.
	 * @param string $tag     Divi tag.
	 * @param string $message Message.
	 */
	public function add( $level, $tag, $message ) {
		$notice = array(
			'level'   => $level,
			'tag'     => $tag,
			'message' => $message,
		);
		if ( ! in_array( $notice, $this->notices, true ) ) {
			$this->notices[] = $notice;
		}
	}

	/**
	 * Counts one module.
	 *
	 * @param string $tag Divi tag.
	 */
	public function count_module( $tag ) {
		$this->modules[ $tag ] = isset( $this->modules[ $tag ] ) ? $this->modules[ $tag ] + 1 : 1;
	}

	/**
	 * Gets the notices.
	 *
	 * @param string $level Optional level filter.
	 * @return array<int,array{level:string,tag:string,message:string}>
	 */
	public function notices( $level = '' ) {
		if ( '' === $level ) {
			return $this->notices;
		}
		return array_values(
			array_filter(
				$this->notices,
				static function ( $notice ) use ( $level ) {
					return $notice['level'] === $level;
				}
			)
		);
	}

	/**
	 * Gets the report as an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array() {
		return array(
			'modules' => $this->modules,
			'notices' => $this->notices,
		);
	}
}

<?php
/**
 * Conversion result.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg;

/**
 * Result of one content conversion.
 */
final class Result {

	/**
	 * True when the content had Divi shortcodes.
	 *
	 * @var bool
	 */
	public $has_divi = false;

	/**
	 * Parsed-block arrays.
	 *
	 * @var array
	 */
	public $blocks = array();

	/**
	 * Block markup.
	 *
	 * @var string
	 */
	public $markup = '';

	/**
	 * Report.
	 *
	 * @var Report
	 */
	public $report;

	/**
	 * Constructor.
	 *
	 * @param Report $report Report.
	 */
	public function __construct( Report $report ) {
		$this->report = $report;
	}
}

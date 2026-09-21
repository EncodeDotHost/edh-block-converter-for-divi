<?php
/**
 * Form mapper.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Mapper\Modules;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Mapper\Base_Mapper;
use EDH\DiviGutenberg\Mapper\Block_Factory;
use EDH\DiviGutenberg\Parser\Node;
use EDH\DiviGutenberg\Report;

/**
 * Reports the contact form and the email opt-in modules. Core blocks have no forms.
 */
final class Form_Mapper extends Base_Mapper {

	/**
	 * Converts a node.
	 *
	 * @param Node    $node    Divi node.
	 * @param Context $context Context.
	 * @return array
	 */
	public function convert( Node $node, Context $context ) {
		$context->report->add( Report::UNSUPPORTED, $node->tag, __( 'Core blocks have no forms. Replace the placeholder with a form block from a form plugin.', 'edh-block-converter-for-divi' ) );

		$blocks = array();
		$title  = $this->inline( $node->attr( 'title' ) );
		if ( '' !== $title ) {
			$blocks[] = Block_Factory::heading( $title, 3 );
		}

		if ( ! empty( $context->options['keep_form_shortcodes'] ) && '' !== $node->raw ) {
			$blocks[] = Block_Factory::shortcode( $node->raw );
		} else {
			$blocks[] = Block_Factory::paragraph( '<em>' . esc_html__( 'A Divi form was here. Add a form block at this position.', 'edh-block-converter-for-divi' ) . '</em>' );
		}

		return $blocks;
	}
}

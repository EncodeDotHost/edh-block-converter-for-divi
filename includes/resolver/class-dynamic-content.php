<?php
/**
 * Divi dynamic content.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Resolver;

use EDH\DiviGutenberg\Context;
use EDH\DiviGutenberg\Report;

/**
 * Replaces `@ET-DC@<base64 JSON>@` tokens with static values.
 *
 * Core blocks have no equivalent for most of the Divi dynamic fields,
 * thus the converter writes the value that the field has at this time.
 */
final class Dynamic_Content {

	const PATTERN = '/@ET-DC@([A-Za-z0-9+\/=]+)@/';

	/**
	 * Resolver. Receives the field name, the settings, and the post ID. Returns a string or null.
	 *
	 * @var callable|null
	 */
	private $resolver;

	/**
	 * Constructor.
	 *
	 * @param callable|null $resolver Optional resolver, for tests.
	 */
	public function __construct( $resolver = null ) {
		$this->resolver = $resolver;
	}

	/**
	 * Replaces all tokens in a text.
	 *
	 * @param string  $text    Text.
	 * @param Context $context Context.
	 * @return string
	 */
	public function resolve_in_text( $text, Context $context ) {
		if ( false === strpos( $text, '@ET-DC@' ) ) {
			return $text;
		}

		return preg_replace_callback(
			self::PATTERN,
			function ( $found ) use ( $context ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Divi stores the token as base64.
				$data = json_decode( (string) base64_decode( $found[1], true ), true );
				if ( ! is_array( $data ) || empty( $data['content'] ) ) {
					return '';
				}

				$settings = isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : array();
				$value    = $this->value( (string) $data['content'], $settings, $context->post_id );

				if ( null === $value ) {
					$context->report->add(
						Report::DROPPED,
						'dynamic-content',
						/* translators: %s: name of the Divi dynamic content field. */
						sprintf( __( 'Dynamic content field "%s" has no equivalent. The converter removed it.', 'edh-divi-gutenberg' ), $data['content'] )
					);
					return '';
				}

				$context->report->add(
					Report::DOWNGRADED,
					'dynamic-content',
					/* translators: %s: name of the Divi dynamic content field. */
					sprintf( __( 'Dynamic content field "%s" is now static text.', 'edh-divi-gutenberg' ), $data['content'] )
				);

				$before = isset( $settings['before'] ) ? (string) $settings['before'] : '';
				$after  = isset( $settings['after'] ) ? (string) $settings['after'] : '';

				return '' === $value ? '' : $before . $value . $after;
			},
			$text
		);
	}

	/**
	 * Gets the value of one field.
	 *
	 * @param string $field    Field name.
	 * @param array  $settings Field settings.
	 * @param int    $post_id  Post ID.
	 * @return string|null Null when the field has no equivalent.
	 */
	private function value( $field, array $settings, $post_id ) {
		if ( null !== $this->resolver ) {
			return call_user_func( $this->resolver, $field, $settings, $post_id );
		}
		if ( ! function_exists( 'get_post' ) ) {
			return null;
		}

		switch ( $field ) {
			case 'post_title':
				return $post_id ? get_the_title( $post_id ) : '';
			case 'post_excerpt':
				return $post_id ? get_the_excerpt( $post_id ) : '';
			case 'post_date':
				return $post_id ? (string) get_the_date( isset( $settings['custom_date_format'] ) ? $settings['custom_date_format'] : '', $post_id ) : '';
			case 'post_author':
				$post = get_post( $post_id );
				return $post ? get_the_author_meta( 'display_name', (int) $post->post_author ) : '';
			case 'post_featured_image':
				return $post_id ? (string) get_the_post_thumbnail_url( $post_id, 'full' ) : '';
			case 'post_link_url':
				$target = isset( $settings['post_id'] ) ? (int) $settings['post_id'] : $post_id;
				return $target ? (string) get_permalink( $target ) : '';
			case 'home_url':
				return home_url( '/' );
			case 'site_title':
				return get_bloginfo( 'name' );
			case 'site_tagline':
				return get_bloginfo( 'description' );
			case 'current_date':
				return date_i18n( isset( $settings['custom_date_format'] ) && '' !== $settings['custom_date_format'] ? $settings['custom_date_format'] : get_option( 'date_format' ) );
		}

		if ( 0 === strpos( $field, 'post_link_url_' ) && ! empty( $settings['post_id'] ) ) {
			return (string) get_permalink( (int) $settings['post_id'] );
		}
		if ( 0 === strpos( $field, 'custom_meta_' ) && $post_id ) {
			$meta = get_post_meta( $post_id, substr( $field, 12 ), true );
			return is_scalar( $meta ) ? (string) $meta : '';
		}

		return null;
	}
}

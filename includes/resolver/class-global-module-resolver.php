<?php
/**
 * Divi Library lookups.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Resolver;

/**
 * Loads the content of global modules from the Divi Library.
 */
final class Global_Module_Resolver {

	const POST_TYPES = array( 'et_pb_layout', 'et_header_layout', 'et_body_layout', 'et_footer_layout' );

	/**
	 * Loader. Receives the post ID, and returns the content.
	 *
	 * @var callable|null
	 */
	private $loader;

	/**
	 * Constructor.
	 *
	 * @param callable|null $loader Optional loader, for tests.
	 */
	public function __construct( $loader = null ) {
		$this->loader = $loader;
	}

	/**
	 * Gets the content of a library item.
	 *
	 * @param int $post_id Library post ID.
	 * @return string Empty when the item is not available.
	 */
	public function content( $post_id ) {
		if ( null !== $this->loader ) {
			return (string) call_user_func( $this->loader, (int) $post_id );
		}
		if ( ! function_exists( 'get_post' ) ) {
			return '';
		}

		$post = get_post( (int) $post_id );
		if ( ! $post || ! in_array( $post->post_type, self::POST_TYPES, true ) || 'trash' === $post->post_status ) {
			return '';
		}

		return (string) $post->post_content;
	}
}

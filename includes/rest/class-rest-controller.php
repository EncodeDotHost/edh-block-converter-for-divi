<?php
/**
 * REST controller.
 *
 * @package EDH\DiviGutenberg
 */

namespace EDH\DiviGutenberg\Rest;

use EDH\DiviGutenberg\Plugin;
use EDH\DiviGutenberg\Post_Processor;
use EDH\DiviGutenberg\Scanner;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

/**
 * REST routes for the admin screen.
 */
final class Rest_Controller {

	const REST_NAMESPACE = 'edh-divi-gutenberg/v1';

	/**
	 * Registers the routes.
	 */
	public function register_routes() {
		$id_arg = array(
			'id' => array(
				'type'              => 'integer',
				'required'          => true,
				'sanitize_callback' => 'absint',
			),
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/scan',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'scan' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => array(
					'page'     => array(
						'type'    => 'integer',
						'default' => 1,
						'minimum' => 1,
					),
					'per_page' => array(
						'type'    => 'integer',
						'default' => 50,
						'minimum' => 1,
						'maximum' => 200,
					),
					'state'    => array(
						'type'    => 'string',
						'default' => 'all',
						'enum'    => array( 'all', 'divi', 'converted' ),
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/preview/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $id_arg,
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/convert/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'convert' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $id_arg + array(
					'force' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/restore/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'restore' ),
				'permission_callback' => array( $this, 'can_manage' ),
				'args'                => $id_arg,
			)
		);
	}

	/**
	 * Checks the capability.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( Plugin::CAPABILITY );
	}

	/**
	 * Lists the Divi posts and the converted posts.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function scan( WP_REST_Request $request ) {
		$scanner = new Scanner();
		$found   = $scanner->find( $request['state'], array(), (int) $request['per_page'], (int) $request['page'] );

		return rest_ensure_response(
			array(
				'total' => $found['total'],
				'posts' => array_values( array_filter( array_map( array( $scanner, 'summary' ), $found['ids'] ) ) ),
			)
		);
	}

	/**
	 * Converts a post without a database write.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function preview( WP_REST_Request $request ) {
		$result = ( new Post_Processor() )->preview( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $this->with_status( $result );
		}

		return rest_ensure_response(
			array(
				'has_divi' => $result->has_divi,
				'markup'   => $result->markup,
				'report'   => $result->report->to_array(),
			)
		);
	}

	/**
	 * Converts a post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function convert( WP_REST_Request $request ) {
		$result = ( new Post_Processor() )->convert( (int) $request['id'], (bool) $request['force'] );
		if ( is_wp_error( $result ) ) {
			return $this->with_status( $result );
		}

		return rest_ensure_response(
			array(
				'converted' => true,
				'report'    => $result->report->to_array(),
				'post'      => ( new Scanner() )->summary( (int) $request['id'] ),
			)
		);
	}

	/**
	 * Restores a post.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return \WP_REST_Response|WP_Error
	 */
	public function restore( WP_REST_Request $request ) {
		$result = ( new Post_Processor() )->restore( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $this->with_status( $result );
		}

		return rest_ensure_response(
			array(
				'restored' => true,
				'post'     => ( new Scanner() )->summary( (int) $request['id'] ),
			)
		);
	}

	/**
	 * Adds an HTTP status to an error.
	 *
	 * @param WP_Error $error Error.
	 * @return WP_Error
	 */
	private function with_status( WP_Error $error ) {
		$error->add_data( array( 'status' => 'edh_dg_not_found' === $error->get_error_code() ? 404 : 409 ) );
		return $error;
	}
}

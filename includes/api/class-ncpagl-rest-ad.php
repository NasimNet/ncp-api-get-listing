<?php
/**
 * REST AD Class
 *
 * @package Includes/API
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use APIGetListing\Includes\Classes\Listing_Data;

/**
 * NCPAGL_REST_AD
 *
 * @package Includes/API
 * @version  1.0.0
 */
class NCPAGL_REST_AD extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'ncpagl/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ad/(?P<id>[\d]+)';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'ad_listing';

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => array( 'default' => 'view' ),
					),
				),
			)
		);
	}

	/**
	 * Get one item from the collection
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$params = $request->get_params();
		$post   = get_post( (int) $params['id'] );

		if ( ! $this->condition_post( $post ) ) {
			return new WP_Error( "ncpagl_rest_invalid_{$this->post_type}_id", __( 'Invalid ID.', 'ncp-rest-api' ), array( 'status' => 404 ) );
		}

		$data = $this->prepare_item_for_response( $post, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Check Post
	 *
	 * @param WP_Post $post Post Object.
	 * @return boolean
	 */
	private function condition_post( $post ) {
		if ( $post && $post->post_type === $this->post_type && 'publish' === $post->post_status ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {

		// if ( ncpagl_rest_check_post_permissions( $this->post_type, 'read' ) ) {
		// 	return new WP_Error( 'ncpagl_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'ncp-rest-api' ), array( 'status' => rest_authorization_required_code() ) );
		// }

		return true;
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param WP_Post         $post WP_Post.
	 * @param WP_REST_Request $request $request Full data about the request.
	 * @return mixed
	 */
	public function prepare_item_for_response( $post, $request ) {
		$post_id   = $post->ID;
		$post_data = new Listing_Data( $post_id );

		return array(
			'post_id'      => $post_id,
			'author'       => $post_data->get_user(),
			'post_title'   => get_the_title( $post_id ),
			'post_content' => $post->post_content,
			'category_id'  => $post_data->get_category(),
			'featured'     => is_sticky( $post_id ),
			'images'       => $post_data->get_images(),
			'meta_input'   => $post_data->get_post_meta(),
		);
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'id' => array(
				'description'       => 'The Ad ID..',
				'type'              => 'integer',
				'sanitize_callback' => 'absint',
			),
		);
	}
}

<?php
/**
 * REST ADs Class
 *
 * @package Includes/API
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NCPAGL_REST_Ads
 *
 * @package Includes/API
 * @version  1.0.0
 */
class NCPAGL_REST_ADS extends WP_REST_Controller {

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
	protected $rest_base = 'ads/(?P<pid>[\d]+)';

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
					'callback'            => array( $this, 'get_items' ),
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
	public function get_items( $request ) {
		$params  = $request->get_params();
		$post_id = absint( $params['pid'] );
		error_log( print_r( $post_id, true ) );

		if ( $post_id ) {
			$data = $this->prepare_item_for_response( $post_id, $params );
			if ( ! empty( $data ) ) {
				return rest_ensure_response( $data );
			} else {
				return new WP_Error(
					'ncpagl_rest_nothing_found',
					__( 'Nothing found', 'ncp-rest-api' ),
					array( 'status' => 404 )
				);
			}
		} else {
			return new WP_Error(
				'ncpagl_rest_invalide_parametrs',
				__( 'There are no necessary parameters', 'ncp-rest-api' ),
				array( 'status' => 400 )
			);
		}

	}

	/**
	 * Check if a given request has access to get a specific item
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {

		// if ( ncpra_rest_check_post_permissions( $this->post_type, 'read' ) ) {
		// 	return new WP_Error( 'ncpra_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'ncp-rest-api' ), array( 'status' => rest_authorization_required_code() ) );
		// }

		return true;
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param array  $defaults Defaults.
	 * @param object $request $_GET.
	 * @return mixed
	 */
	public function prepare_item_for_response( $post_id, $params ) {
		global $wpdb;

		$post_table      = $wpdb->prefix . 'posts';
		$post_meta_table = $wpdb->prefix . 'postmeta';

		$post_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(ID) FROM $post_table WHERE post_type = 'ad_listing' AND ID = %d",
				$post_id
			)
		);

		if ( $post_exists ) {
			$query = $wpdb->prepare(
				"
					SELECT p.ID
					FROM $post_table AS p
					INNER JOIN $post_meta_table AS pm ON p.ID = pm.post_id
					WHERE p.post_type = 'ad_listing'
					AND p.post_status = 'publish'
					AND p.ID != %d
					AND p.ID < %d
					AND pm.meta_key = 'cp_mobile'
					AND pm.meta_value != ''
					ORDER BY p.ID DESC
					LIMIT 5
				",
				$post_id,
				$post_id
			);

			return $wpdb->get_col( $query );
		}

		return false;
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'order'   => array(
				'description'       => 'Order',
				'type'              => 'string',
				'sanitize_callback' => 'rest_validate_request_arg',
			),
			'orderby' => array(
				'description'       => 'OrderBy',
				'type'              => 'string',
				'sanitize_callback' => 'rest_validate_request_arg',
			),
			's'       => array(
				'description'       => 'Search Keywords',
				'type'              => 'string',
				'sanitize_callback' => 'rest_validate_request_arg',
			),
		);
	}
}

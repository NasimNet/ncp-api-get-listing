<?php
/**
 * Process Insert Post
 *
 * @author nasimnet
 * @package ncp-api-get-listing
 * @since 1.0
 */

namespace APIGetListing\Includes\Classes;

use APIGetListing\Includes\Classes\Insert_Post;

/**
 * Process_Insert_Post
 *
 * @package ncp-api-get-listing
 * @version  1.0.0
 */
class Process_Insert_Post {

	/**
	 * Hooks
	 *
	 * @return void
	 */
	public static function hooks() {
		add_action( 'wp_head', array( __CLASS__, 'process' ) );
	}

	public static function process() {
		if ( ! isset( $_GET['get_listing'] ) ) {
			return;
		}

		$per_page = 3;
		if ( ! empty( $_GET['per_page'] ) ) {
			$per_page = absint( $_GET['per_page'] );
		}

		$paged = get_option( 'ncpagl_paged ', 1 );
		if ( ! empty( $_GET['paged'] ) ) {
			$paged = absint( $_GET['paged'] );
		}

		$posts = self::get_posts( $per_page, $paged );
		$posts = wp_list_pluck( $posts, 'id' );

		foreach ( $posts as $post_id ) {
			$response = wp_remote_get( "https://www.fori.me/old/wp-json/ncpagl/v1/ad/{$post_id}" );
			if ( ! is_wp_error( $response ) ) {
				$result = json_decode( wp_remote_retrieve_body( $response ), true );

				// Check User
				$username = $result['author']['username'];
				$usermeta = null;
				if ( ! empty( $result['author']['usermeta'] ) ) {
					$usermeta = $result['author']['usermeta'];
				}

				$user_id = self::get_user_id( $username, $usermeta );

				// Insert Post
				$result['post_status'] = 'publish';
				$result['post_type']   = 'ad_listing';
				$result['post_author'] = $user_id;

				$post_id = new Insert_Post( $result );

			}
		}

		update_option( 'ncpagl_paged', $paged + 1 );

	}

	private static function get_posts( $per_page, $paged ) {
		$response = wp_remote_get( "https://www.fori.me/old/wp-json/ncpagl/v1/ads?per_page={$per_page}&paged={$paged}" );

		if ( ! is_wp_error( $response ) ) {
			return json_decode( wp_remote_retrieve_body( $response ), true );
		}
	}

	/**
	 * Insert User and Update user meta
	 *
	 * @return integer User ID
	 */
	public static function get_user_id( $username, $usermeta = array() ) {

		$user_id = username_exists( $username );

		if ( ! $user_id ) {
			$userdata = array(
				'user_login' => $username,
				'user_pass'  => null,
				'user_email' => self::create_email( $username ),
			);

			$user_id = wp_insert_user( $userdata );

			if ( ! is_wp_error( $user_id ) && ! empty( $usermeta ) ) {
				foreach ( $usermeta as $meta_key => $meta_value ) {
					update_user_meta( $user_id, $meta_key, $meta_value );
				}
			}
		}

		return $user_id;
	}

	/**
	 * Create Email by site adress
	 *
	 * @return string Email adress
	 */
	private static function create_email( $username ) {
		$domain = preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );
		return $username . '@' . $domain;
	}

}

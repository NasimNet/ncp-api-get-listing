<?php
/**
 * Core
 *
 * @author nasimnet
 * @package ncp-api-get-listing
 * @since 1.0
 */

namespace APIGetListing\Includes\Classes;

use APIGetListing\Includes\Classes\Process_Insert_Post;

/**
 * Core
 *
 * @package ncp-api-get-listing
 * @version  1.0.0
 */
class Core {

	/**
	 * Hooks
	 *
	 * @return void
	 */
	public static function actions() {
		// add_filter( 'fw_settings_options', array( __CLASS__, 'settings' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ), 10 );
		Process_Insert_Post::hooks();

	}

	/**
	 * Load settings
	 *
	 * @param  array $options options array.
	 * @return array
	 */
	public static function settings( $options ) {
		include NCP_APIGETLISTING_PATH . 'includes/settings.php';
		return array_merge( $options, $plugin_options );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 1.0.0
	 */
	public static function register_rest_routes() {
		$user_id = get_current_user_id();

		$controllers = array(
			'ad'  => 'NCPAGL_REST_AD',
			'ads' => 'NCPAGL_REST_ADS',
		);

		foreach ( $controllers as $namespace => $controller ) {
			include NCP_APIGETLISTING_PATH . '/includes/api/class-ncpagl-rest-' . $namespace . '.php';
			$instance = new $controller( $user_id );
			$instance->register_routes();
		}
	}

}

Core::actions();

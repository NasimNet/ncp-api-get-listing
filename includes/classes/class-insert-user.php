<?php
/**
 * Insert User
 *
 * @author nasimnet
 * @package ncp-api-get-listing
 * @since 1.0
 */

namespace APIGetListing\Includes\Classes;

/**
 * Insert_User
 *
 * @package ncp-api-get-listing
 * @version  1.0.0
 */
class Insert_User {

	/**
	 * User Name
	 *
	 * @var string Mobile Number
	 */
	public $username;

	/**
	 * User Meta
	 *
	 * @var array
	 */
	public $usermeta;

	/**
	 * Construct
	 *
	 * @param string $username
	 * @param array $usermeta
	 */
	public function __construct( $username, $usermeta = array() ) {
		$this->username = $username;
		$this->usermeta = $usermeta;
	}

	/**
	 * Insert User and Update user meta
	 *
	 * @return integer User ID
	 */
	public function run() {

		$username = $this->username;
		$user_id  = username_exists( $username );

		if ( ! $user_id ) {

			$user_email = $this->create_email();
			$userdata   = array(
				'user_login' => $username,
				'user_pass'  => null,
				'user_email' => $user_email,
			);

			$user_id = wp_insert_user( $userdata );

			if ( ! is_wp_error( $user_id ) ) {
				if ( ! empty( $this->usermeta ) ) {
					foreach ( $this->usermeta as $meta_key => $meta_value ) {
						update_user_meta( $user_id, $meta_key, $meta_value );
					}
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
	private function create_email() {
		$domain = preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );
		return $this->username . '@' . $domain;
	}

}

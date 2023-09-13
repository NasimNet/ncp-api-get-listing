<?php
/**
 * ADS
 *
 * @package ncp-rest-api
 * @author nasimnet
 * @since 1.0
 */

namespace APIGetListing\Includes\Classes;

use ISATIS_Images;
use APIGetListing\Includes\Classes\Helper;

/**
 * Core Class
 */
class Listing_Data {

	/**
	 * Post ID
	 *
	 * @var integer
	 */
	private $post_id;

	/**
	 * Construct
	 *
	 * @param integer $post_id post id.
	 */
	public function __construct( $post_id ) {
		$this->post_id = $post_id;
	}

	/**
	 * Retrieves user information based on post ID.
	 *
	 * @return array|false User information or false if user mobile is not found or invalid.
	 */
	public function get_user() {
		$user_mobile = get_post_meta( $this->post_id, 'cp_mobile', true );
		if ( $user_mobile ) {

			// Convert to English Number
			$persian_numbers = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
			$arabic_numbers  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );

			$user_mobile = str_replace( $persian_numbers, range( 0, 9 ), $user_mobile );
			$user_mobile = str_replace( $arabic_numbers, range( 0, 9 ), $user_mobile );

			// Validate user mobile format
			$user = array();
			if ( preg_match( '/^09[0-9]{9}$/', $user_mobile ) ) {
				$user['username'] = $user_mobile;

				$user_id            = get_post_field( 'post_author', $this->post_id );
				$membership_pack    = get_user_meta( $user_id, 'active_membership_pack', true );
				$membership_expires = get_user_meta( $user_id, 'membership_expires', true );
				$bump_pack          = get_user_meta( $user_id, 'bump_pack', true );

				// Check if membership pack and membership expiry date exist
				if ( $membership_pack && $membership_expires ) {
					$user['usermeta']['active_membership_pack'] = $membership_pack;
					$user['usermeta']['membership_expires']     = $membership_expires;
				}

				// Check if bump pack exist
				if ( $bump_pack ) {
					$user['usermeta']['bump_pack'] = $bump_pack;
				}

				return $user;
			}
		}

		return false;
	}

	/**
	 * Retrieve the source URLs of the images associated with the post.
	 *
	 * @return array An array of image source URLs.
	 */
	public function get_images() {
		$images = ISATIS_Images::get_ad_images( $this->post_id );

		/**
		 * Image id ha ro begir
		 */
		$images_src = array();
		foreach ( $images as $id ) {
			$image_src = wp_get_attachment_image_src( $id, 'full' );

			if ( ! empty( $image_src ) ) {
				$images_src[] = $image_src[0];
			}
		}

		return $images_src;
	}

		/**
	 * Retrieve the source URLs of the images associated with the post.
	 *
	 * @return array An array of image source URLs.
	 */
	public function get_feature_image() {
		$image_id = ISATIS_Images::get_feature_image_id( $this->post_id );
		if ( $image_id ) {
			return wp_get_attachment_image_src( $image_id, 'full' )[0];
		}
	}

	/**
	 * Get Category
	 *
	 * @return integer Category ID
	 */
	public function get_category() {
		return appthemes_get_custom_taxonomy( $this->post_id, APP_TAX_CAT, 'term_id' );
	}

	/**
	 * Retrieves post meta data for a specific post.
	 *
	 * @return array The post meta data with meta key as the array key and the corresponding meta value.
	 */
	public function get_post_meta() {
		$postmetas = get_post_meta( $this->post_id );

		$output = array();
		foreach ( $postmetas as $meta_key => $meta_value ) {

			if ( 'cp_' === substr( $meta_key, 0, 3 ) ) {
				$type = $this->get_field_type( $meta_key );
				if ( 'checkbox' === $type || 'radio' === $type ) {
					$output[ $meta_key ] = $meta_value;
				} else {
					$meta_value = $meta_value[0];

					if ( 'cp_price' === $meta_key ) {
						$meta_value = '';
					}

					if ( is_numeric( $meta_value ) ) {
						$meta_value = Helper::convert_english_number( $meta_value );
					}

					$output[ $meta_key ] = $meta_value;
				}
			}
		}

		return $output;
	}

	/**
	 * Retrieves the field type for a given field name.
	 *
	 * @param string $field_name The name of the field.
	 * @return string|false The field type if found, false otherwise.
	 */
	private function get_field_type( $field_name ) {
		global $wpdb;

		// Check if the field type is cached in the Transients
		$field_type = get_transient( 'cp_ad_field_type_' . $field_name );

		// If the field type is not cached, fetch it from the database.
		if ( false === $field_type ) {
			$field_type = $wpdb->get_var(
				$wpdb->prepare( "SELECT field_type FROM $wpdb->cp_ad_fields WHERE field_name = %s", $field_name )
			);

			// If the field type is not empty, cache it in the Transients API for one day.
			if ( ! empty( $field_type ) ) {
				set_transient( 'cp_ad_field_type_' . $field_name, $field_type, DAY_IN_SECONDS );
			}
		}

		return $field_type;
	}

	/**
	 * Retrieves the form fields for a specific post.
	 *
	 * @return array|false The form fields if available, false otherwise.
	 */
	private function get_form_fields() {
		$post_id     = $this->post_id;
		$category_id = appthemes_get_custom_taxonomy( $post_id, APP_TAX_CAT, 'term_id' );
		$form_id     = cp_get_form_id( $category_id );
		$form_fields = cp_get_custom_form_fields( $form_id );

		if ( $form_fields ) {
			return $form_fields;
		}

		return false;
	}

	/**
	 * Displays all the custom fields on the single ad page, by default they are placed in the list area.
	 *
	 * @param string $location location fileds.
	 * @return array
	 */
	public function get_details( $location = 'list' ) {
		$form_fields = $this->get_form_fields();
		$post        = get_post( $this->post_id );

		if ( ! $form_fields || ! $post ) {
			return false;
		}

		$args = array();

		$disallow_fields = apply_filters( 'cp_ad_disabled_details_fields', array( 'cp_price', 'cp_currency' ), $post, $location );

		foreach ( $form_fields as $field ) {

			// external plugins can modify or disable field.
			$field = apply_filters( 'cp_ad_details_field', $field, $post, $location );
			if ( ! $field ) {
				continue;
			}

			if ( in_array( $field->field_name, $disallow_fields, true ) ) {
				continue;
			}

			$post_meta_val = get_post_meta( $post->ID, $field->field_name, true );
			if ( empty( $post_meta_val ) ) {
				continue;
			}

			if ( 'checkbox' === $field->field_type ) {
				$post_meta_val = get_post_meta( $post->ID, $field->field_name, false );
				$post_meta_val = implode( ', ', $post_meta_val );
			}

			$args[] = array(
				'label' => esc_html( translate( $field->field_label, APP_TD ) ), // phpcs:ignore
				'value' => $post_meta_val,
				'key'   => $field->field_name,
			);
			$args   = apply_filters( 'cp_ad_details_' . $field->field_name, $args, $field, $post, $location );
		}

		return $args;
	}

	/**
	 * Get Contanc
	 *
	 * @return array
	 */
	public function get_contact() {
		$form_fields = $this->get_form_fields();
		$post        = get_post( $this->post_id );

		if ( ! $form_fields || ! $post ) {
			return false;
		}

		$allow_fields = array( 'cp_mobile', 'cp_phone', 'cp_email' );

		/**
		 * If ISATIS Theme Activated , get options
		 */
		if ( class_exists( 'ISATIS_Ad_Contact' ) ) {
			$allow_fields = array(
				nasim_get_option( 'isa_call_mobile_field' )[0],
				nasim_get_option( 'isa_call_phone_field' )[0],
				nasim_get_option( 'isa_call_email_field' )[0],
			);

			$social = nasim_get_option( 'ad_social_network_icons' );
			$social = wp_list_pluck( wp_list_pluck( $social, 'field' ), 0 );

			$allow_fields = array_merge( $social, $allow_fields );
		}

		$args = array();
		foreach ( $form_fields as $field ) {

			if ( ! in_array( $field->field_name, $allow_fields, true ) ) {
				continue;
			}

			$post_meta_val = get_post_meta( $post->ID, $field->field_name, true );
			if ( empty( $post_meta_val ) ) {
				continue;
			}

			$args[] = array(
				'label' => esc_html( translate( $field->field_label, APP_TD ) ), //phpcs:ignore
				'value' => $post_meta_val,
				'key'   => $field->field_name,
			);
		}

		return $args;
	}

	/**
	 * Get Place
	 *
	 * @return string
	 */
	public function get_place() {
		$adress_array = nasim_get_option( 'isatis_single_type_adress' );

		$adress = array();
		foreach ( $adress_array as $meta ) {
			$adress[ $meta ] = get_post_meta( $this->post_id, $meta, true );
		}
		return implode( ' ، ', array_filter( $adress ) );
	}
}

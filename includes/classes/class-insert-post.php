<?php
/**
 * Insert Post
 *
 * @author nasimnet
 * @package ncp-api-get-listing
 * @since 1.0
 */

namespace APIGetListing\Includes\Classes;

/**
 * Insert_Post
 *
 * @package ncp-api-get-listing
 * @version  1.0.0
 */
class Insert_Post {

	/**
	 * Post Category
	 *
	 * @var integer
	 */
	private $category = false;

	/**
	 * Post Featuer images url
	 *
	 * @var string Url
	 */
	private $feature_image = '';
	/**
	 * Post images url
	 *
	 * @var array
	 */
	private $images = array();

	/**
	 * is featured?
	 *
	 * @var boolean
	 */
	private $is_featured = false;

	/**
	 * Post Array
	 *
	 * @var array
	 */
	private $postarr = array();

	/**
	 * Construct
	 *
	 * @param array $params
	 */
	public function __construct( $params ) {
		$this->postarr = $this->process_params( $params );
	}

	/**
	 * Insert Post
	 *
	 * @return integer
	 */
	public function init() {
		$postarr = $this->postarr;

		// Check Insert post or update post
		$post_id = wp_insert_post( $postarr );
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// check featured post
		if ( $this->is_featured ) {
			stick_post( $post_id );
		}

		// set the custom post type categories .
		if ( $this->category ) {
			wp_set_post_terms( $post_id, $this->category, 'ad_cat', false );
		}

		// set feature_image
		if ( $this->feature_image ) {
			$image_id = $this->upload_image( $post_id, $this->feature_image, $postarr['post_title'] );
			if ( $image_id ) {
				update_post_meta( $post_id, '_cp_banner_image', absint( $image_id ) );
				update_post_meta( $image_id, '_app_attachment_type', 'file' );
			}
		}

		// set image.
		if ( ! empty( $this->images ) ) {
			$images = array();
			foreach ( $this->images as $image_url ) {
				$image_id = $this->upload_image( $post_id, $image_url, $postarr['post_title'] );
				if ( $image_id ) {
					$images[] = $image_id;
					update_post_meta( $image_id, '_app_attachment_type', 'file' );
				}
			}

			if ( ! empty( $images ) ) {
				update_post_meta( $post_id, '_cp_banner_image', absint( $images[0] ) );
				update_post_meta( $post_id, '_app_media', $images );
				$meta_value = array(
					'count' => count( $images ),
					'ids'   => $images,
				);
				update_post_meta( $post_id, '_isatis_ad_images', $meta_value );
			}
		}

		// set 'cp_sys_expire_date'
		$ad_length = get_post_meta( $post_id, 'cp_sys_ad_duration', true );
		if ( empty( $ad_length ) ) {
			$cp_options = get_option( 'cp_options' );
			$ad_length  = $cp_options['prun_period'];
		}

		$ad_expire_date = gmdate( 'Y-m-d H:i:s', strtotime( "+{$ad_length} days" ) );
		update_post_meta( $post_id, 'cp_sys_expire_date', $ad_expire_date );

		// update serach index
		$this->update_search_index( $post_id, $postarr );

		return $post_id;
	}

	/**
	 * Process Params before insert post
	 *
	 * @param array $params
	 * @return array
	 */
	private function process_params( $params ) {
		$params = stripslashes_deep( $params );

		unset( $params['post_id'] );
		unset( $params['author'] );

		if ( ! empty( $params['category_id'] ) ) {
			$this->category = absint( $params['category_id'] );
			unset( $params['category_id'] );
		}

		if ( ! empty( $params['featured'] ) ) {
			$this->is_featured = true;
			unset( $params['featured'] );
		}

		// Check if the 'feature_image' parameter is not empty
		if ( ! empty( $params['feature_image'] ) ) {
			$feature_image = esc_url( $params['feature_image'] );

			// We check if the image exists or not
			$image_response = wp_remote_head( $feature_image );
			if ( 200 === wp_remote_retrieve_response_code( $image_response ) ) {
				$this->feature_image = $feature_image;
			}
		}

		if ( ! empty( $params['images'] ) ) {
			$this->images = $params['images'];
			unset( $params['images'] );
		}

		return $params;
	}

	/**
	 * Upload Image
	 *
	 * @param integer $post_id
	 * @param string  $imageurl Image Url.
	 * @param string  $post_title
	 * @return integer Image ID
	 */
	private function upload_image( $post_id, $imageurl, $post_title ) {

		include_once ABSPATH . 'wp-admin/includes/image.php';

		$imagetypes = explode( '/', wp_get_image_mime( $imageurl ) );
		$imagetype  = end( $imagetypes );
		$uniq_name  = gmdate( 'dmY' ) . '' . (int) microtime( true );
		$filename   = $uniq_name . '.' . $imagetype;

		$uploaddir  = wp_upload_dir();
		$uploadfile = $uploaddir['path'] . '/' . $filename;
		$contents   = file_get_contents( $imageurl );
		$savefile   = fopen( $uploadfile, 'w' );
		fwrite( $savefile, $contents );
		fclose( $savefile );

		$wp_filetype = wp_check_filetype( basename( $filename ), null );
		$attachment  = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => esc_html( $post_title ),
			'post_content'   => esc_html( $post_title ),
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment, $uploadfile, $post_id );

		$imagenew     = get_post( $attach_id );
		$fullsizepath = get_attached_file( $imagenew->ID );
		$attach_data  = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		return $attach_id;
	}

	/**
	 * Create Search Index
	 *
	 * @param integer $post_id
	 * @param array   $postarr
	 * @return void
	 */
	private function update_search_index( $post_id, $postarr ) {

		$post        = get_post( $post_id );
		$index_array = array();

		// Post Title
		$index_array[] = $post->post_title;
		if ( false !== strpos( $post->post_title, "'" ) ) {
			$index_array[] = str_replace( "'", '', $post->post_title );
		}

		// Post Content
		$content       = wp_strip_all_tags( $post->post_content, true );
		$content       = strip_shortcodes( $content );
		$index_array[] = $content;

		// Post Meta's
		foreach ( $postarr['meta_input'] as $meta_key => $meta_value ) {
			if ( appthemes_str_starts_with( $meta_key, 'cp_sys_' ) ) {
				continue;
			}
			$index_array[] = implode( ', ', (array) $meta_value );
		}

		$terms = wp_get_object_terms( $post->ID, APP_TAX_CAT );
		foreach ( $terms as $term ) {
			$index_array[] = $term->name;
		}

		$terms = wp_get_object_terms( $post->ID, APP_TAX_TAG );
		foreach ( $terms as $term ) {
			$index_array[] = $term->name;
		}

		$index_array = array_map( 'trim', $index_array );
		$index_array = array_unique( $index_array );

		$index_string = implode( ', ', $index_array );

		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_content_filtered' => $index_string ), array( 'ID' => $post_id ) );

	}

}

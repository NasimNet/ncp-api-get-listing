<?php
/**
 * Helper
 *
 * @author nasimnet
 * @package ncp-api-get-listing
 * @since 1.0
 */

namespace APIGetListing\Includes\Classes;

/**
 * Helper
 *
 * @package ncp-api-get-listing
 * @version  1.0.0
 */
class Helper {

	public static function convert_english_number( $number ) {
		$persian_numbers = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		$arabic_numbers  = array( '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );

		$number = str_replace( $persian_numbers, range( 0, 9 ), $number );
		$number = str_replace( $arabic_numbers, range( 0, 9 ), $number );

		return $number;
	}
}

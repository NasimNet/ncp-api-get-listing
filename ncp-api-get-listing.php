<?php //phpcs:ignore
/**
 * Plugin Name: وبسرویس دریافت آگهی ها
 * Plugin URI: https://nasimnet.ir
 * Description: این افزونه به صورت اختصاصی برای سایت forime توسط واحد برنامه نویسی نسیم نت طراحی شده است و استفاده آن بر روی سایت دیگر مجاز نمی باشد.
 * Version: 1.0
 * Author: NasimNet
 * Author URI: https://nasimnet.ir
 * License: GPLv3
 * License URI: https://nasimnet.ir/copyright-balck-list-nasimnet/
 */

defined( 'ABSPATH' ) || exit;

/**
 * NCP_API_Get_Listing
 *
 * @package ncp-api-get-listing
 * @version  1.0.0
 */
class NCP_API_Get_Listing {

	/**
	 * Instance
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Contruct
	 */
	public function __construct() {
		$this->define_constants();
		$this->load_files();
	}

	/**
	 * Create an instance from this class.
	 *
	 * @access public
	 * @since  4.0
	 * @return Class
	 */
	public static function instance() {
		if ( is_null( ( self::$instance ) ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define Constans
	 *
	 * @return void
	 */
	private function define_constants() {
		define( 'NCP_APIGETLISTING_VER', '1.0' );
		define( 'NCP_APIGETLISTING_PATH', plugin_dir_path( __FILE__ ) );
		define( 'NCP_APIGETLISTING_URL', plugin_dir_url( __FILE__ ) );
	}

	/**
	 * Load Files
	 *
	 * @return void
	 */
	private function load_files() {
		require_once NCP_APIGETLISTING_PATH . 'includes/class-autoloader.php';
		require_once NCP_APIGETLISTING_PATH . 'includes/classes/class-core.php';
	}

}

NCP_API_Get_Listing::instance();

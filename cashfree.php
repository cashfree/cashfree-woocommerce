<?php
/**
 * Plugin Name: Cashfree Test
 * Version: 4.3.8
 * Plugin URI: https://github.com/cashfree/cashfree-woocommerce
 * Description: Payment gateway plugin by Cashfree Payments for Woocommerce sites.
 * Author: devcashfree
 * Author URI: https://cashfree.com
 * Developer: Cashfree Dev
 * Developer URI: techsupport@gocashfree.com
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 * Requires at least: 4.4
 * Tested up to: 6.0
 * WC requires at least: 3.0
 * WC tested up to: 6.3.1
 *
 *
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

// for offers
define ('WPCO_URL', trailingslashit(plugins_url('/',__FILE__)));

/**
 * Cashfree main class.
 */
class WC_Cashfree {

	/**
	 * Payment gateway id.
	 *
	 * @var string
	 */
	const PAYMENT_GATEWAY_ID = 'cashfree';

	/**
	 * Payment gateway settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * A log object returned by wc_get_logger().
	 *
	 * @var WC_Logger
	 */
	public static $log;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );

		define( 'WC_CASHFREE_FILE', __FILE__ );
		define( 'WC_CASHFREE_DIR_PATH', plugin_dir_path( __FILE__ ) );
		define( 'WC_CASHFREE_DIR_URL', plugin_dir_url( __FILE__ ) );
		define( 'WC_CASHFREE_VERSION', $plugin_data['Version'] );

		$this->settings = get_option( 'woocommerce_' . self::PAYMENT_GATEWAY_ID . '_settings' );
		$this->enabled  = 'yes' === $this->settings['enabled'] && ! empty( $this->settings['merchant_id'] );

		require_once WC_CASHFREE_DIR_PATH . 'includes/wc-cashfree-functions.php';
		require_once WC_CASHFREE_DIR_PATH . 'includes/http/class-wc-cashfree-adapter.php';

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'load_gateways' ) );
		add_filter( 'woocommerce_before_add_to_cart_form' , array( $this, 'wp_cashfree_offers' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
	}

	public function wp_cashfree_offers() {
		if ( $this->settings['enabledOffers'] === 'yes') {
			// External Scripts
			wp_register_style('Font_Awesome','https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
			wp_enqueue_style('Font_Awesome');

			wp_register_style("Font",'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
			wp_enqueue_style('Font');

			wp_register_style("Jquery",'https://cdn.jsdelivr.net/npm/tw-elements/dist/css/index.min.css');
			wp_enqueue_style('Jquery');

			wp_register_script('Js','https://cdn.tailwindcss.com',null, null, true );
			wp_enqueue_script('Js');

			wp_register_script('Tw','https://cdn.jsdelivr.net/npm/tw-elements/dist/js/index.min.js',null, null, true );
			wp_enqueue_script('Tw');

			wp_register_script('Tw1','https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css',null, null, true );
			wp_enqueue_script('Tw1');


		wp_enqueue_script('woocommerce-react-script', WPCO_URL . 'dist/bundle.js', ['jquery','wp-element'], wp_rand(), true);
		wp_enqueue_script('woocommerce-react-script1', WPCO_URL . 'dist/main.js', ['jquery','wp-element'], wp_rand(), true);
	
		wp_register_style( 'woocommerce-react-css', WPCO_URL . 'dist/style.css', array(), '20120208', 'all' );
			
		wp_enqueue_style( 'woocommerce-react-css' );
	
		global $product;
		$price = $product->get_price();

	
		echo'<div id="cashfree-offer-widget" data-amount='.$price.' data-appId='.$this->settings['app_id'].' data-isOffers='.$this->settings['offers'].' data-isPayLater='.$this->settings['payLater'].' data-isEmi='.$this->settings['emi'].'></div>';
		}
	}

	/**
	 * Add Cashfree payment gateway.
	 *
	 * @param array $methods List of payment methods.
	 *
	 * @return array
	 */
	public static function load_gateways( $methods ) {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		include_once WC_CASHFREE_DIR_PATH . 'includes/gateways/class-wc-cashfree-gateway.php';
		include_once WC_CASHFREE_DIR_PATH . 'includes/gateways/class-wc-cashfree-payments.php';

		array_push( $methods, 'WC_Cashfree_Payments' );
		return $methods;
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param mixed $links Plugin Action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url(
				'admin.php?page=wc-settings&tab=checkout&section=' . self::PAYMENT_GATEWAY_ID
			) . '">' . __( 'Settings', 'cashfree' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public function load_scripts() {
		if ( $this->enabled && ! is_checkout() ) {
			wc_cashfree_js( $this->settings );
		}
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level   Optional. Default 'info'.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( ! isset( self::$log ) ) {
			self::$log = wc_get_logger();
		}

		self::$log->log( $level, $message, array( 'source' => self::PAYMENT_GATEWAY_ID ) );
	}
}

new WC_Cashfree();

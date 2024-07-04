<?php
/**
 * Plugin Name: Cashfree
 * Version: 4.7.2
 * Plugin URI: https://github.com/cashfree/cashfree-woocommerce
 * Description: Payment gateway plugin by Cashfree Payments for Woocommerce sites.
 * Author: devcashfree
 * Author URI: https://cashfree.com
 * Developer: Cashfree Dev
 * Developer URI: techsupport@gocashfree.com
 * Text Domain: woocommerce-extension
 * Domain Path: /languages
 * Requires at least: 4.4
 * Tested up to: 6.5
 * WC requires at least: 3.0
 * WC tested up to: 8.8
 *
 *
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

// Adding this for making compatible with HPOC
add_action('before_woocommerce_init', function() {
	if (class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class))
    {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
	}
});

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
		if($this->settings) {
			$this->enabled  = 'yes' === $this->settings['enabled'];
		}

		require_once WC_CASHFREE_DIR_PATH . 'includes/wc-cashfree-functions.php';
		require_once WC_CASHFREE_DIR_PATH . 'includes/http/class-wc-cashfree-adapter.php';

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'plugin_action_links' ) );
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'load_gateways' ) );
		add_filter( 'woocommerce_before_add_to_cart_form' , array( $this, 'wp_cashfree_offers' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_cashfree_woocommerce_block_support' ) );
	}

	public function wp_cashfree_offers() {
		if ( isset($this->settings['enabledOffers']) &&
			$this->settings['enabledOffers'] === 'yes' &&
			isset($this->settings['sandbox']) &&
			$this->settings['sandbox'] === 'no') {
			// External Scripts
			wp_register_script('cf-woocommerce-js', 'https://sdk.cashfree.com/js/widget/1.0.1/cashfree-widget.prod.js', null, null, true );
			wp_enqueue_script('cf-woocommerce-js');
			
			add_filter( 'cf-woocommerce_enqueue_styles', '__return_false' );
		
			global $product;
			$price = $product->get_price();

			echo'<div id="cashfree-widget" data-amount='.$price.' data-appId='.$this->settings['app_id'].' data-isOffers='.$this->settings['offers'].' data-isPayLater='.$this->settings['payLater'].' data-isEmi='.$this->settings['emi'].'></div>';
			
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

	public static function woocommerce_gateway_cashfree_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once WC_CASHFREE_DIR_PATH . 'includes/gateways/class-wc-cashfree-block-support.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$container = Automattic\WooCommerce\Blocks\Package::container();
					// registers as shared instance.
					$container->register(
						WC_Cashfree_Blocks_Support::class,
						function() {
							return new WC_Cashfree_Blocks_Support();
						}
					);
					$payment_method_registry->register(
						$container->get( WC_Cashfree_Blocks_Support::class )
					);
				},
				5
			);
		}
	}
}

new WC_Cashfree();
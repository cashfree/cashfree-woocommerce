<?php
/**
 * Cashfree gateway
 */

defined( 'ABSPATH' ) || exit;

require_once ABSPATH . 'wp-admin/includes/plugin.php';

/**
 * Cashfree gateway abstract class.
 */
abstract class WC_Cashfree_Gateway extends WC_Payment_Gateway {

	/**
	 * Cashfree adapter instance.
	 *
	 * @var WC_Cashfree_Adapter
	 */

	protected $adapter;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->has_fields = true;
		$this->supports = array( 'products', 'refunds' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->order_button_text = $this->get_option( 'order_button_text' );
		$this->sandbox = 'yes' === $this->get_option( 'sandbox', 'yes' );
		$this->in_context = 'yes' === $this->get_option( 'in_context', 'yes' );
		$this->debug = 'yes' === $this->get_option( 'debug', 'no' );
		$this->token_param = "{$this->id}-token";

		$this->load_dependencies();
		$this->setup_actions();
	}

	/**
	 * Loads the required dependencies.
	 */
	private function load_dependencies() {
		require_once WC_CASHFREE_DIR_PATH . 'includes/class-wc-cashfree-api.php';
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-checkout.php';
		new WC_Cashfree_Api( $this->id );
		$this->adapter = new WC_Cashfree_Adapter( $this );
	}

	/**
	 * Sets up the required actions.
	 */
	private function setup_actions() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'api_' . $this->id . '_capture', array( $this, 'capture' ), 10, 2 );
		add_action( 'api_' . $this->id . '_cancel', array( $this, 'cancel' ), 10, 2 );
		add_action( 'api_' . $this->id . '_notify', array( $this, 'notify' ), 10, 2 );
		add_action( 'api_' . $this->id . '_dismiss', array( $this, 'dismiss' ), 10, 2 );
	}

	/**
	 * Checks whether the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		$parent_available = parent::is_available();
		$app_id = $this->get_app_id();
		$secret_key = $this->get_secret_key();
		
		return $parent_available && !empty($app_id) && !empty($secret_key);
	}

	/**
	* Get the environment.
	* @return string
	*/
	public function get_environment() {
		return ($this->sandbox !== true) ? 'production' : 'sandbox';
	}

	/**
	 * Get App ID.
	 *
	 * @return string The app ID.
	 */
	public function get_app_id() {
		return $this->get_option( 'app_id' );
	}

	/**
	 * Get secret key.
	 *
	 * @return string The secret key.
	 */
	public function get_secret_key() {
		return $this->get_option( 'secret_key' );
	}

	/**
	 * Build the payment fields area.
	 */
	public function payment_fields() {
		wc_get_cashfree_template( 'payment-fields.php', array( 'gateway' => $this ) );
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id Order id.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		try {
			$response = $this->adapter->checkout( $order_id );
		} catch ( Exception $e ) {
			wc_add_notice( $e->getMessage(), 'error' );
			return array( 'result' => 'failure' );
		}

		$order   = wc_get_order( $order_id );
		$pay_url = $order->get_checkout_payment_url( true );
		$redirect_url = add_query_arg( $this->token_param, $response['order_token'], $pay_url );

		return array(
			'result'   => 'success',
			'redirect' => $redirect_url,
		);
	}

	/**
	 * Capture an approved order.
	 *
	 * @param array $data Post data.
	 * @param string $order_key Order key.
	 */
	public function capture( $data, $order_key ) {
		$order_id = $data['order_id'];
		$order = $this->get_order( $order_id, $order_key );
		if ( !$order || !$order->needs_payment() && !$order->has_status('processing') && !$order->has_status('completed') ) {
			wc_add_notice( __( 'Cashfree capture error.', 'cashfree' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		try {
			$response = $this->adapter->capture( $data );
			switch ($response->payment_status) {
				case 'SUCCESS':
					$order->payment_complete( $response->cf_payment_id );
					$order->add_order_note(
						sprintf(
							__( 'Cashfree payment successful <br/>Transaction Id: %1$s.', 'cashfree' ),
							$response->cf_payment_id
						)
					);
					wp_safe_redirect( $this->get_return_url( $order ) );
					exit;
					break;
				case 'CANCELLED':
					$order_status = 'cancelled';
					break;
				case 'FAILED':
					$order_status = 'failed';
					break;
				default:
					$order_status = 'pending';
					break;
			}
		} catch ( Exception $e ) {
			$order_status = 'pending';
			$order->update_status( $order_status );
			$order->add_order_note(
				sprintf( /* translators: %1$s: transaction id %2$s: error code */
					__( 'Cashfree capture %1$s. ID: %2$s. Error: %3$s.', 'cashfree' ),
					$order_status,
					$order->get_id(),
					$e->getMessage()
				)
			);
		}

		if (isset($order_status)) {
			$order->update_status( $order_status );
			wc_add_notice( __( 'Cashfree capture error.', 'cashfree' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
	}

	/**
	 * Cancel a checkout.
	 *
	 * @param array  $post_data  Post data.
	 * @param string $order_key  Order key.
	 */
	public function cancel( $post_data, $order_key ) {
		$order_id = $post_data['order_id'];
		$order = $this->get_order( $order_id, $order_key );

		if ( ! $order || ! $order->needs_payment() ) {
			wc_add_notice( __( $post_data['transaction_msg'], 'cashfree' ), 'error' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		$transaction_status = $post_data['transaction_status'];

		switch ( $transaction_status ) {
			case 'CANCELLED':
				$order_status = 'cancelled';
				break;

			case 'FAILED':
				$order_status = 'failed';
				break;

			default:
				$order_status = 'pending';
				break;
		}

		$order->update_status( $order_status );
		$order->add_order_note(
			sprintf(
				/* translators: %1$s: transaction id %2$s: error code */
				__( 'Cashfree capture %1$s. ID: %2$s. Code: %3$s.', 'cashfree' ),
				$order_status,
				$order->get_id(),
				$post_data['transaction_msg']
			)
		);

		wc_add_notice( __( $post_data['transaction_msg'], 'cashfree' ), 'error' );
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/**
	 * Dismiss a checkout.
	 *
	 * @param array $post_data post data.
	 * @param string $order_key      Order key.
	 */
	public function dismiss( $post_data, $order_key ) {
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/**
	 * Webhook a checkout.
	 *
	 * @param array $post_data Post data.
	 * @param string $order_key Order key.
	 */
	public function notify( $post_data, $order_key ) {
		if ( $post_data['txStatus'] !== 'SUCCESS' ) {
			return;
		}

		$order_id = $post_data['orderId'];
		$order = $this->get_order( $order_id, $order_key );

		if ( ! $order || ! $order->needs_payment() ) {
			return;
		}

		try {
			$post_data['order_status'] = 'PAID';
			$post_data['order_id'] = $order_id;
			$post_data['transaction_msg'] = $post_data['txMsg'];

			$this->adapter->notify( $post_data );
			$order->payment_complete( $post_data['referenceId'] );
			$order->add_order_note(
				sprintf(
					__( 'Webhook - Cashfree payment successful <br/>Transaction Id: %1$s.', 'cashfree' ),
					$post_data['referenceId']
				)
			);
		} catch ( Exception $e ) {
			WC_Cashfree::log( 'notify : ' . $e->getMessage(), 'critical' );
		}
	}

	/**
	 * Process a refund if supported.
	 *
	 * @param int    $order_id Order ID.
	 * @param float  $amount Refund amount.
	 * @param string $reason Refund reason.
	 *
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error( 'error', __( 'Refund failed: Invalid order ID', 'woocommerce' ) );
		}

		$transaction_id = $order->get_transaction_id();
		if ( ! $transaction_id ) {
			return new WP_Error( 'error', __( 'Refund failed: No transaction ID', 'woocommerce' ) );
		}

		$refund_id = $order_id . '-' . uniqid();

		try {
			$refund = $this->adapter->refund( $order_id, $refund_id, $amount, $reason );

			$order->add_order_note(
				sprintf(
					__( 'Refund Id: %s', 'woocommerce' ),
					$refund->cf_refund_id
				)
			);

			do_action( 'woo_cashfree_refund_success', $refund->cf_refund_id, $order_id, $refund );

			return true;
		} catch ( Exception $e ) {
			return new WP_Error(
				'error',
				sprintf(
					/* translators: %1$s: transaction id %2$s: error code */
					__( 'Cashfree refund failed. ID: %1$s. Code: %2$s.', 'cashfree' ),
					$transaction_id,
					$e->getMessage()
				)
			);
		}
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public function load_scripts() {
		if (!$this->is_available() || !is_checkout() || empty($_GET[$this->token_param])) {
			return;
		}

		$token = wc_clean(wp_unslash($_GET[$this->token_param]));
		$key = wc_clean(wp_unslash($_GET['key']));
		$cf_version = get_plugin_data(WC_CASHFREE_DIR_PATH . 'cashfree.php')['Version'];

		wc_cashfree_js($this->settings);

		wc_cashfree_script('wc-cashfree-checkout', [
			'token' => $token,
			'environment' => $this->get_environment(),
			'capture_url' => WC_Cashfree_Request_Checkout::get_url('capture', $key, $this->id),
			'cancel_url' => WC_Cashfree_Request_Checkout::get_url('cancel', $key, $this->id),
			'dismiss_url' => WC_Cashfree_Request_Checkout::get_url('dismiss', $key, $this->id),
			'woo_version' => WC()->version,
			'cf_version' => $cf_version,
			'in_context' => $this->in_context,
		]);
	}

	/**
	 * Get order instance.
	 *
	 * @param string $order_id Order ID.
	 * @param string $order_key Order key.
	 *
	 * @return bool|WC_Order|WC_Order_Refund Order instance, or false if not found or invalid.
	 */
	protected function get_order( $order_id, $order_key ) {
		$order = wc_get_order( $order_id );
		if ( $order && $order->get_payment_method() === $this->id && hash_equals( $order_key, $order->get_order_key() ) ) {
			return $order;
		}
		return false;
	}

}

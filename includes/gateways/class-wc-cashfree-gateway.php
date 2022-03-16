<?php
/**
 * Cashfree gateway
 */

defined( 'ABSPATH' ) || exit;

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
		$this->supports   = array( 'products', 'refunds' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->sandbox     = 'yes' === $this->get_option( 'sandbox', 'yes' );
		$this->debug       = 'yes' === $this->get_option( 'debug', 'no' );
		$this->token_param = $this->id . '-token';

		require_once WC_CASHFREE_DIR_PATH . 'includes/class-wc-cashfree-api.php';
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-checkout.php';

		new WC_Cashfree_Api( $this->id );
		$this->adapter = new WC_Cashfree_Adapter( $this );

		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		add_action( 'api_' . $this->id . '_capture', array( $this, 'capture' ), 10, 2 );
		add_action( 'api_' . $this->id . '_cancel', array( $this, 'cancel' ), 10, 2 );
		add_action( 'api_' . $this->id . '_notify', array( $this, 'notify' ), 10, 2 );
		add_action( 'api_' . $this->id . '_dismiss', array( $this, 'dismiss' ), 10, 2 );
	}

	/**
	 * Check if the gateway is available for use.
	 *
	 * @return bool
	 */
	public function is_available() {
		return (
			parent::is_available() &&
			! empty( $this->get_app_id() ) &&
			! empty( $this->get_secret_key() )
		);
	}

	/**
	 * Get Environment.
	 *
	 * @return string
	 */
	public function get_environment() {
		if($this->sandbox != 'yes') {
			return 'production';
		} else {
			return 'sandbox';
		}
	}

	/**
	 * Get App ID.
	 *
	 * @return string
	 */
	public function get_app_id() {
		return $this->get_option( 'app_id' );
	}

	/**
	 * Get secret key.
	 *
	 * @return string
	 */
	public function get_secret_key() {
		return $this->get_option( 'secret_key' );
	}

	/**
	 * Build the payment fields area.
	 */
	public function payment_fields() {
		wc_cashfree_script( 'wc-cashfree-js' );
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

		$order        = wc_get_order( $order_id );
		$pay_url      = $order->get_checkout_payment_url( true );
		$redirect_url = add_query_arg( $this->token_param, $response['order_token'], $pay_url );

		return array(
			'result'   => 'success',
			'redirect' => $redirect_url,
		);
	}

	/**
	 * Capture an approved order.
	 *
	 * @param array $post_data post data.
	 * @param string $order_key      Order key.
	 */
	public function capture( $post_data, $order_key ) {
		$order_id = $post_data['orderId'];
		$order = $this->get_order( $order_id, $order_key );

		if ( $order && $order->needs_payment() ) {
			try {
				$this->adapter->capture( $post_data );
				$order->payment_complete( $post_data['transaction_id'] );
			} catch ( Exception $e ) {
				if($post_data['transaction_status'] === 'CANCELLED') {
					$order_status = 'cancelled';
				} elseif($post_data['transaction_status'] === 'FAILED') {
					$order_status = 'failed';
				} else {
					$order_status = 'pending';
				}
				$order->update_status( $order_status );
				$order->add_order_note(
					sprintf( /* translators: %1$s: transaction id %2$s: error code */
						__( 'Cashfree capture %1$s. ID: %2$s. Code: %3$s.', 'cashfree' ),
						$order_status,
						$order->get_id(),
						$e->getMessage()
					)
				);
			}
			wp_safe_redirect( $this->get_return_url( $order ) );
			exit;
		}
		wc_add_notice( __( 'Cashfree capture error.', 'cashfree' ), 'error' );
		wp_safe_redirect( wc_get_checkout_url() );
	}

	/**
	 * Cancel a checkout.
	 *
	 * @param array $post_data post data.
	 * @param string $order_key      Order key.
	 */
	public function cancel( $post_data, $order_key ) {
		$order_id = $post_data['orderId'];
		$order = $this->get_order( $order_id, $order_key );

		if ( $order && $order->needs_payment() ) {
			if($post_data['transaction_status'] === 'CANCELLED') {
				$order_status = 'cancelled';
			} elseif($post_data['transaction_status'] === 'FAILED') {
				$order_status = 'failed';
			} else {
				$order_status = 'pending';
			}
			$order->update_status( $order_status );
			$order->add_order_note(
				sprintf( /* translators: %1$s: transaction id %2$s: error code */
					__( 'Cashfree capture %1$s. ID: %2$s. Code: %3$s.', 'cashfree' ),
					$order_status,
					$order->get_id(),
					$post_data['transaction_msg']
				)
			);
		}
		wc_add_notice( __( $post_data['transaction_msg'], 'cashfree' ) );
		wp_safe_redirect( wc_get_checkout_url() );
	}

	/**
	 * Dismiss a checkout.
	 *
	 * @param array $post_data post data.
	 * @param string $order_key      Order key.
	 */
	public function dismiss( $post_data, $order_key ) {
		wp_safe_redirect( wc_get_checkout_url() );
	}

	/**
	 * Webhook a checkout.
	 *
	 * @param array $post_data post data.
	 * @param string $order_key Order key.
	 */
	public function notify( $post_data, $order_key ) {
		if($post_data['txStatus'] === 'SUCCESS') {
			$order_id = $post_data['orderId'];
			$order = $this->get_order( $order_id, $order_key );

			if ( $order && $order->needs_payment() ) {
				try {
					$post_data['order_status'] = 'PAID';
					$post_data['transaction_msg'] = $post_data['txMsg'];
					$this->adapter->notify( $post_data );
					$order->payment_complete( $post_data['referenceId'] );
				} catch ( Exception $e ) {
					WC_Cashfree::log( 'notify : ' . $e->getMessage(), 'critical' );
				}
			}
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
		$order          = wc_get_order( $order_id );
		$transaction_id = $order->get_transaction_id();
		if (! $order or ! $order->get_transaction_id())
		{
			return new WP_Error('error', __('Refund failed: No transaction ID', 'woocommerce'));
		}
		$refund_id      = $order_id . '-' . uniqid();

		try {
			$refund = $this->adapter->refund( $transaction_id, $refund_id, $amount, $reason );
			$order->add_order_note( __( 'Refund Id: ' . $refund->{'refundId'}, 'woocommerce' ) );
              /**
               * @var $jsonResponse->refundId -- Provides the Cashfree Refund ID
               * @var $order_id -> Refunded Order ID
               * @var $refund -> WooCommerce Refund Instance.
               */
              do_action( 'woo_cashfree_refund_success', $refund->{'refundId'}, $order_id, $refund );
		} catch ( Exception $e ) {
			return new WP_Error(
				'error',
				sprintf( /* translators: %1$s: transaction id %2$s: error code */
					__( 'Cashfree refund failed. ID: %1$s. Code: %2$s.', 'cashfree' ),
					$transaction_id,
					$e->getMessage()
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}
		return true;
	}

	/**
	 * Register/queue frontend scripts.
	 */
	public function load_scripts() {
		if ( $this->is_available() && is_checkout() && ! empty( $_GET[ $this->token_param ] ) ) {
			wc_cashfree_js( $this->settings );

			wc_cashfree_script(
				'wc-cashfree-checkout',
				array( 
					'token' => wc_clean( wp_unslash( $_GET[ $this->token_param ] ) ),
					'environment' 	=> $this->get_environment(),
					'capture_url' 	=> WC_Cashfree_Request_Checkout::get_url( 'capture', wc_clean( wp_unslash( $_GET[ 'key' ] ) ), $this->id ),
					'cancel_url' 	=> WC_Cashfree_Request_Checkout::get_url( 'cancel', wc_clean( wp_unslash( $_GET[ 'key' ] ) ), $this->id ), 
					'dismiss_url' 	=> WC_Cashfree_Request_Checkout::get_url( 'dismiss', wc_clean( wp_unslash( $_GET[ 'key' ] ) ), $this->id ) )
			);
		}
	}

	/**
	 * Get order instance.
	 *
	 * @param string $transaction_id Transaction id.
	 * @param string $order_key      Order key.
	 *
	 * @return bool|WC_Order|WC_Order_Refund
	 */
	protected function get_order( $order_id, $order_key ) {
		$order = wc_get_order( $order_id );
		if ( $order && $this->id === $order->get_payment_method() && hash_equals( $order->get_order_key(), $order_key ) ) {
			return $order;
		}
		return false;
	}
}

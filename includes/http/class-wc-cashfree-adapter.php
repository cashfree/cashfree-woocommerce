<?php
/**
 * Cashfree adapter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cashfree adapter class.
 */
class WC_Cashfree_Adapter {

	/**
	 * Cashfree gateway instance.
	 *
	 * @var WC_Cashfree_Gateway
	 */
	protected $gateway;

	/**
	 * Constructor.
	 *
	 * @param WC_Cashfree_Gateway $gateway Cashfree gateway instance.
	 */
	public function __construct( $gateway ) {
		$this->gateway = $gateway;
	}

	/**
	 * Create a checkout using the given order ID.
	 *
	 * @param string $order_id Order ID.
	 *
	 * @return array An array containing the order token and environment.
	 *
	 * @throws Exception If an error occurs while creating the checkout.
	 */
	public function checkout( $order_id ) {
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-checkout.php';

		// Build the request params.
		$request_params = WC_Cashfree_Request_Checkout::build( $order_id, $this->gateway );

		// Get the Cashfree URL and set the order URL.
		$env_value = $this->getCurlValue();
		$order_url = $env_value['curlUrl'] . '/' . $order_id;

		// Set the request headers.
		$args = array(
			'timeout' => 30,
			'headers' => array(
				'x-api-version'   => '2022-09-01',
				'x-client-id'     => $this->gateway->settings['app_id'],
				'x-client-secret' => $this->gateway->settings['secret_key'],
			),
		);

		// Make the request to get the order.
		$response = wp_remote_get( $order_url, $args );
		$http_code = wp_remote_retrieve_response_code( $response );

		// Check if the request was successful.
		if ( $http_code === 200 ) {
			$cf_order = json_decode( wp_remote_retrieve_body( $response ) );

			// Check if the order has already been paid for.
			if ( $cf_order === 'PAID' ) {
				throw new Exception( 'Please reach out to the support team' );
			}

			// Check if the order details are correct.
			if (
				strtotime( $cf_order->order_expiry_time ) > time()
				&& round( $cf_order->order_amount ) === round( wc_get_order( $order_id )->get_total() )
				&& $cf_order->order_currency === wc_get_order( $order_id )->get_currency()
			) {
				$response = array(
					'order_token' => $cf_order->payment_session_id,
					'environment' => $env_value['environment'],
				);

				return $response;
			} else {
				throw new Exception( 'Please reach out to the support team' );
			}
		}

		// If the order is not found, create a new checkout.
		$curl_post_field = json_encode( $request_params );

		try {
			$result = $this->curlPostRequest( $env_value['curlUrl'], $curl_post_field, $request_params['order_id'] );

			$response = array(
				'order_token' => $result->payment_session_id,
				'environment' => $env_value['environment'],
			);

			// Save the order cart.
			try {
				$this->cashfreeCheckoutCartSave( $order_id );
			} catch ( Exception $exception ) {
				WC_Cashfree::log( 'CartDetails: ' . $exception->getMessage(), 'critical' );
			}

			return $response;
		} catch ( Exception $e ) {
			throw new Exception( $e->getMessage() );
		}
	}

	/**
	 * Capture an order.
	 *
	 * @param array $postData post data.
	 *
	 * @return array
	 *
	 * @throws Exception If payment method is not properly configured or response status code is invalid.
	 */
	public function capture(array $postData)
	{
		$curlValue = $this->getCurlValue();
		$inContext = $this->gateway->settings['in_context'] === 'yes';
		$orderStatus = $postData['order_status'];
		$orderId = $postData['order_id'];
		
		if ($inContext && $orderStatus !== 'PAID') {
			throw new Exception($postData['transaction_msg']);
		}
		
		$orderUrl = $curlValue['curlUrl'] . '/' . $orderId . '/payments';
		$result = $this->curlGetRequest($orderUrl);
		
		return $result;
	}

	/**
	 * Notify an order.
	 *
	 * @param array $post_data post data.
	 *
	 * @return array
	 *
	 * @throws Exception If payment method is not properly configured.
	 * @throws ApiException If response status code is invalid.
	 */
	public function notify( $post_data ) {
		return $this->capture( $post_data );
	}

	/**
	 * Refund a capture transaction.
	 *
	 * @param string $id          Transaction id.
	 * @param string $refund_id   Refund id.
	 * @param float  $amount      Amount to refund.
	 * @param string $description Refund description.
	 *
	 * @return array
	 *
	 * @throws Exception If payment method is not properly configured.
	 * @throws ApiException If response status code is invalid.
	 */
	public function refund( $order_id, $refund_id, $amount, $description ) {
		$getEnvValue = $this->getCurlValue();
		$cartData = array(
			'refund_amount'	=> $amount,
			'refund_id'		=> $refund_id,
			'refund_note' 	=> $description,
		);
		$curlPostfield = json_encode($cartData);             
	
		$refundUrl = $getEnvValue['curlUrl']."/".$order_id."/refunds";
		
		try{
			$result = $this->curlPostRequest($refundUrl, $curlPostfield);
			return $result;
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}

	}


	private function cashfreeCheckoutCartSave($order_id) {
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-items.php';
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-billing.php';
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-shipping.php';

		$order = new WC_Order( $order_id );

		$billing_address=$order->get_address( 'billing' ) ;
		if ( !empty($billing_address) ) {
			$postCode = (!empty($billing_address['postcode'])) ? $billing_address['postcode'] : "";
		}
		$billing_address = WC_Cashfree_Request_Billing::build( $order_id );
		$shipping_address = WC_Cashfree_Request_Shipping::build( $order_id );
		$cartData = array(
			'shipping_address'	=> $shipping_address['shippingAddress'],
			'billing_address'	=> $billing_address['billingAddress'],
			'pincode'      		=> $postCode,
			'customer_note'    	=> $order->get_currency(),
			'items'           	=> array_map(
				function( $item ) use ( $order ) {
					return WC_Cashfree_Request_Item::build( $order, $item );
				},
				array_values( $order->get_items() )
			)

		);

		if (!empty($billing_address['data'])) {
			$cartData['customer_billing_address'] = $billing_address['data'];
		}
		if (!empty($shipping_address['data'])) {
			$cartData['customer_shipping_address'] = $shipping_address['data'];
		}

		$getEnvValue = $this->getCurlValue();
		$addCartCurlUrl = $getEnvValue['curlUrl']."/".$order_id."/cart";

		$curlPostfield = json_encode($cartData);

		try{
			$this->curlPostRequest($addCartCurlUrl, $curlPostfield);
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}

	}

	// Get config values for gateway environment
	public function getCurlValue() {
		$isSandbox = $this->gateway->settings['sandbox'] === 'yes';
		$baseUrl = $isSandbox ? 'https://sandbox.cashfree.com' : 'https://api.cashfree.com';
	
		return [
			'curlUrl' => "{$baseUrl}/pg/orders",
			'environment' => $isSandbox ? 'sandbox' : 'production'
		];
	}	

	// Post request for gateway
	private function curlPostRequest($curlUrl, $data, $idemKey = "") {
		$headers = [
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'x-api-version' => '2022-09-01',
			'x-client-id' => $this->gateway->settings['app_id'],
			'x-client-secret' => $this->gateway->settings['secret_key']
		];
		
		if(!empty($idemKey)) {
			$headers['x-idempotency-key'] = $idemKey;
		}
		
		$args = [
			'body'        => $data,
			'timeout'     => 30,
			'headers'     => $headers,
		];

		$response = wp_remote_post( $curlUrl, $args );
		$http_code = wp_remote_retrieve_response_code( $response );
		$body     = json_decode(wp_remote_retrieve_body( $response ));

		if($http_code === 200) {
			return $body;
		} else {
			throw new Exception($body->message);
		}
	}

	// Get request for gateway
	private function curlGetRequest($curlUrl) {
		$args = array(
			'timeout'     => '30',
			'headers'     => array(
				'x-api-version' 	=> 	'2021-05-21',
				'x-client-id' 		=> 	$this->gateway->settings['app_id'],
				'x-client-secret'	=>  $this->gateway->settings['secret_key'],
			),
		);

		$response = wp_remote_get( $curlUrl, $args );

		$http_code = wp_remote_retrieve_response_code( $response );
		$body     = json_decode(wp_remote_retrieve_body( $response ));
		if($http_code == 200) {
			return $body[0];	
		} else {
			throw new Exception($body[0]->payment_message);
		}
		
	}
}

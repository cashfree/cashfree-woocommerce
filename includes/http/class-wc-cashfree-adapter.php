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
	 * Create a checkout using given parameters.
	 *
	 * @param string $order_id Order id.
	 *
	 * @return array
	 *
	 * @throws Exception If payment method is not properly configured.
	 * @throws ApiException If response status code is invalid.
	 */
	public function checkout( $order_id ) {
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-checkout.php';

		$requestParams = WC_Cashfree_Request_Checkout::build( $order_id, $this->gateway );
		
		$getEnvValue = $this->getCurlValue();

		$curlPostfield = json_encode($requestParams);

		try{
			$result = $this->curlPostRequest($getEnvValue['curlUrl'], $curlPostfield, $requestParams['order_id']);
			$response = [
				'order_token'       => $result->order_token,
				'environment'       => $getEnvValue['environment'],
				'payment_link'      => $result->payment_link,
			];

			//Order Cart save	
			try {
				$this->cashfreeCheckoutCartSave( $order_id );				
			} catch ( Exception $exception ) {
				WC_Cashfree::log( 'CartDetails : ' . $exception->getMessage(), 'critical' );
			}
			return $response;

		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}
	}

	/**
	 * Capture an order.
	 *
	 * @param array $post_data post data.
	 *
	 * @return array
	 *
	 * @throws Exception If payment method is not properly configured.
	 * @throws ApiException If response status code is invalid.
	 */
	public function capture( $post_data ) {
		$getEnvValue = $this->getCurlValue();
		if($this->gateway->settings['in_context'] === "yes") {
			if($post_data['order_status'] === 'PAID') {
				$getOrderUrl = $getEnvValue['curlUrl']."/".$post_data['orderId']."/payments";
				try{
					$result = $this->curlGetRequest($getOrderUrl);
					return $result;
				} catch(Exception $e) {
					throw new Exception($e->getMessage());
				}
			} else {
				throw new Exception($post_data['transaction_msg']);
			}
		} else {
			$getOrderUrl = $getEnvValue['curlUrl']."/".$post_data['order_id']."/payments";
			try{
				$result = $this->curlGetRequest($getOrderUrl);
				return $result;
			} catch(Exception $e) {
				throw new Exception($e->getMessage());
			}
		}
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
		$cartData = array(
			'shipping_address'	=> WC_Cashfree_Request_Shipping::build( $order_id ),
			'billing_address'	=> WC_Cashfree_Request_Billing::build( $order_id ),
			'pincode'      		=> $postCode,
			'customer_note'    	=> $order->get_currency(),
			'items'           	=> array_map(
				function( $item ) use ( $order ) {
					return WC_Cashfree_Request_Item::build( $order, $item );
				},
				array_values( $order->get_items() )
			),
		);

		$getEnvValue = $this->getCurlValue();
		$addCartCurlUrl = $getEnvValue['curlUrl']."/".$order_id."/cart";

		$curlPostfield = json_encode($cartData);

		try{
			$this->curlPostRequest($addCartCurlUrl, $curlPostfield);
		} catch(Exception $e) {
			throw new Exception($e->getMessage());
		}

	}

	public function getCurlValue() {
		if ( $this->gateway->settings['sandbox'] != 'yes' ) {
			$curlURL = 'https://api.cashfree.com/pg/orders';
			$environment = 'production';
		} else {
			$curlURL = 'https://sandbox.cashfree.com/pg/orders';
			$environment = 'sandbox';
		}

		return array(
			"curlUrl" => $curlURL,
			"environment" => $environment
		);
	}

	private function curlPostRequest($curlUrl, $data, $idemKey = "") {
		$headers = array(
			'Accept' 			=>	'application/json',
			'Content-Type' 		=>	'application/json',
			'x-api-version' 	=> 	'2021-05-21',
			'x-client-id' 		=> 	$this->gateway->settings['app_id'],
			'x-client-secret'	=>  $this->gateway->settings['secret_key'],
		);
		
		if(!empty($idemKey)) {
			$headers['x-idempotency-key'] = $idemKey;
		}
		
		$args = array(
			'body'        => $data,
			'timeout'     => '30',
			'headers'     => $headers,
		);

		$response = wp_remote_post( $curlUrl, $args );
		$http_code = wp_remote_retrieve_response_code( $response );
		$body     = json_decode(wp_remote_retrieve_body( $response ));

		if($http_code == 200) {
			return $body;
		} else {
			throw new Exception($body->message);
		}
	}

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

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

		$curl = curl_init();

		curl_setopt_array($curl, [
			CURLOPT_URL             => $getEnvValue['curlUrl'],
			CURLOPT_RETURNTRANSFER  => true,
			CURLOPT_ENCODING        => "",
			CURLOPT_MAXREDIRS       => 10,
			CURLOPT_TIMEOUT         => 30,
			CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST   => "POST",
			CURLOPT_POSTFIELDS      => $curlPostfield,
			CURLOPT_HTTPHEADER      => [
				"Accept:            application/json",
				"Content-Type:      application/json",
				"x-api-version:     2021-05-21",
				"x-client-id:       ".$this->gateway->settings['app_id'],
				"x-client-secret:   ".$this->gateway->settings['secret_key'],
				"x-idempotency-key: ".$requestParams['order_id']
			],
		]);

		$result = curl_exec($curl);
		curl_close($curl);
		$jsonData = json_decode($result);
		if (null !== $jsonData && !empty($jsonData->order_token))
		{
			$response = [
				'order_token'       => $jsonData->order_token,
				'environment'       => $getEnvValue['environment'],
			];
			return $response;
		} else {
			throw new Exception($jsonData->message);
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

		if($post_data['order_status'] === 'PAID') {
			$getEnvValue = $this->getCurlValue();
			$getOrderUrl = $getEnvValue['curlUrl']."/".$post_data['orderId'];

			$curl = curl_init();

			curl_setopt_array($curl, [
				CURLOPT_URL 			=> $getOrderUrl,
				CURLOPT_RETURNTRANSFER 	=> true,
				CURLOPT_ENCODING 		=> "",
				CURLOPT_MAXREDIRS 		=> 10,
				CURLOPT_TIMEOUT 		=> 30,
				CURLOPT_HTTP_VERSION 	=> CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST 	=> "GET",
				CURLOPT_HTTPHEADER 		=> [
					"Accept: 			application/json",
					"Content-Type: 		application/json",
					"x-api-version: 	2021-05-21",
					"x-client-id: 		".$this->gateway->settings['app_id'],
					"x-client-secret: 	".$this->gateway->settings['secret_key']
				],
			]);

			$response = curl_exec($curl);
			curl_close($curl);
			$jsonData = json_decode($response);
			if (null !== $jsonData && !empty($jsonData->order_status) && $jsonData->order_status === 'PAID')
			{
				return $jsonData;
			} else {
				throw new Exception("Signature mismatch.");
			}
		} else {
			throw new Exception($post_data['transaction_msg']);
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
	public function refund( $transaction_id, $refund_id, $amount, $description ) {
		$cf_refund_request                  = array();
		$cf_refund_request["appId"]         = $this->gateway->settings['app_id'];
		$cf_refund_request["secretKey"]     = $this->gateway->settings['secret_key'];
		$cf_refund_request["referenceId"]   = $transaction_id;  
		$cf_refund_request["refundAmount"]  = $amount;
		$cf_refund_request["refundNote"]    = $description;
		$timeout                            = 30;                
		if ( $this->gateway->settings['sandbox'] !== 'yes' )
		{
			$apiEndpoint = "https://api.cashfree.com";
		} 
		else {
		  $apiEndpoint = "https://test.cashfree.com";
		}
		$refundUrl = $apiEndpoint."/api/v1/order/refund";
		$postBody = array("body" => $cf_refund_request, "timeout" => $timeout);
		$cf_refund_result = wp_remote_retrieve_body(wp_remote_post(esc_url($refundUrl),$postBody));
		$refund = json_decode($cf_refund_result);

		if ($refund->{'status'} == "OK" && isset($refund->{'refundId'})) 
		{ 
			return $refund;
		} 
		else 
		{
			throw new Exception($refund->{'reason'});
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
}

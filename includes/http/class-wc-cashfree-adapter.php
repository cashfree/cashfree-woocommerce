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

    const API_VERSION_20220901 = '2022-09-01';

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
        $cf_order_id = $order_id;

        // Get the Cashfree URL and set the order URL.
        $env_value = $this->getCurlValue();
        if($this->gateway->settings['order_id_prefix_text'] == "yes"){
            $encoded_string = md5(home_url());
            $order_id_prefix_text = substr($encoded_string, 0, 4);
            $cf_order_id = $order_id_prefix_text.'_'.$order_id;
        }
        $order_url = $env_value['curlUrl'] . '/' . $cf_order_id;

        // Set the request headers.
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'x-api-version'   => self::API_VERSION_20220901,
                'x-client-id'     => $this->gateway->settings['app_id'],
                'x-client-secret' => $this->gateway->settings['secret_key'],
                'x-request-id' => 'cf-woo-'.$cf_order_id.'-'.time().'-',
            ),
        );

        // Make the request to get the order.
        $response = wp_remote_get( $order_url, $args );
        $http_code = wp_remote_retrieve_response_code( $response );

        // Check if the request was successful.
        if ( $http_code === 200 ) {
            $cf_order = json_decode( wp_remote_retrieve_body( $response ) );

            // Check if the order has already been paid for.
            if ( $cf_order->order_status === 'PAID' ) {
                throw new Exception( 'Please reach out to the support team' );
            }

            // Check if the order details are correct.
            if (
                strtotime( $cf_order->order_expiry_time ) > time()
                && round( $cf_order->order_amount ) === round( wc_get_order( $order_id )->get_total() )
                && $cf_order->order_currency === wc_get_order( $order_id )->get_currency()
            ) {
                $cashfree_wc_response = array(
                    'payment_session_id' => $cf_order->payment_session_id,
                    'environment' => $env_value['environment'],
                    'order_id' => $cf_order_id,
                );

                return $cashfree_wc_response;
            } else {
                throw new Exception( 'Please reach out to the support team' );
            }
        }

        // Build the request params.
        $request_params = WC_Cashfree_Request_Checkout::build( $order_id, $this->gateway, $cf_order_id );

        // If the order is not found, create a new checkout.
        $curl_post_field = json_encode( $request_params );

        try {
            $result = $this->curlPostRequest( $env_value['curlUrl'], $curl_post_field, self::API_VERSION_20220901, $request_params['order_id'] );
            $cashfree_wc_response = array(
                'payment_session_id' => $result->payment_session_id,
                'environment' => $env_value['environment'],
                'order_id' => $cf_order_id,
            );

            return $cashfree_wc_response;
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
        $cfOrderId = $postData['order_id'];

        $orderUrl = $curlValue['curlUrl'] . '/' . $cfOrderId . '/payments';
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
     * @param string $order_id    order id.
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

        $cf_order_id = $order_id;

        if($this->gateway->settings['order_id_prefix_text'] == "yes"){
            $encoded_string = md5(home_url());
            $order_id_prefix_text = substr($encoded_string, 0, 4);
            $cf_order_id = $order_id_prefix_text.'_'.$order_id;
        }

        $refundUrl = $getEnvValue['curlUrl']."/".$cf_order_id."/refunds";

        try{
            $result = $this->curlPostRequest($refundUrl, $curlPostfield, self::API_VERSION_20220901, $cf_order_id);
            return $result;
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
    private function curlPostRequest($curlUrl, $data, $apiVersion, $cf_orderId) {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'x-api-version' => $apiVersion,
            'x-client-id' => $this->gateway->settings['app_id'],
            'x-client-secret' => $this->gateway->settings['secret_key'],
            'x-request-id' => 'cf-woo-'.$cf_orderId.'-'.time().'-',
        ];

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
                'x-api-version' 	=> 	self::API_VERSION_20220901,
                'x-client-id' 		=> 	$this->gateway->settings['app_id'],
                'x-client-secret'	=>  $this->gateway->settings['secret_key']
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

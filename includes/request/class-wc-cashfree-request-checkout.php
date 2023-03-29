<?php
/**
 * Checkout request
 */

defined( 'ABSPATH' ) || exit;

/**
 * Checkout request class.
 */
class WC_Cashfree_Request_Checkout {

	/**
	 * Build request.
	 *
	 * @param string             $order_id Order id.
	 * @param WC_Cashfree_Gateway $gateway Cashfree gateway instance.
	 *
	 * @return array
	 */
	public static function build( $order_id, $gateway ) {
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-billing.php';

		$order = wc_get_order( $order_id );

		$customer_email = $order->get_billing_email();
		$customer_phone = self::get_phone_number($order);
		$billing_address = WC_Cashfree_Request_Billing::build( $order_id );
		$customerName = $billing_address ? $billing_address['data']['full_name'] : '';
		
		if ( is_user_logged_in() ) {
			$customer = new WC_Customer( $order->get_user_id() );
			$customer_email = $customer->get_email();
			$customerId = (string) $customer->get_id();
		} else {
			$customerId = "woocommerceCustomer";
		}

		$data = array(
			'customer_details' => array(
				'customer_id' => $customerId,
				'customer_email' => $customer_email,
				'customer_phone' => $customer_phone,
				'customer_name' => $customerName
			),
			'order_id' => (string) $order_id,
			'order_amount' => $order->get_total(),
			'order_currency' => $order->get_currency(),
			'order_note' => 'WooCommerce',
			'order_meta' => array(
				'notify_url' => self::get_notify_url( 'notify', $order->get_order_key(), $gateway ),
				'return_url' => self::get_return_url('capture', $order->get_order_key(), $gateway)
			)
		);

		return $data;
	}

	/**
	 * Create Api callback URLs.
	 *
	 * @param string $action     Action to perform.
	 * @param string $order_key  Order key.
	 * @param string $gateway_id Cashfree gateway id.
	 *
	 * @return string
	 */
	public static function get_url( $action, $order_key, $gateway_id ) {
		return add_query_arg(
			array(
				'action' => $action,
				'order_key' => $order_key,
			),
			WC()->api_request_url( $gateway_id )
		);
	}

	/**
	 * Create Api return URL.
	 *
	 * @param string $gateway Cashfree gateway.
	 *
	 * @return string
	 */
	public static function get_return_url( $action, $order_key, $gateway ) {
		$query_args = array(
			'order_id'    => '{order_id}',
			'order_key' => $order_key,
			'action'      => $action
		);
		$api_request_url = WC()->api_request_url( $gateway->id );
		return add_query_arg( $query_args, $api_request_url );
	}


	/**
	 * Create API webhook URL for Cashfree gateway.
	 *
	 * @param string $action    The action to perform.
	 * @param string $order_key The order key.
	 * @param object $gateway   The Cashfree gateway object.
	 *
	 * @return string The webhook URL.
	 */
	public static function get_notify_url( $action, $order_key, $gateway ) {
		$query_params = '';

		$wc_notify_url = add_query_arg(
			array(
				'order_key' => $order_key,
				'action'    => $action
			),
			WC()->api_request_url( $gateway->id )
		);
		

		$redirect_data = base64_encode( "notify_url=$wc_notify_url&platform=woo" );
		$prefix_url = $gateway->settings['sandbox'] === 'yes' ? 'https://payments-test.cashfree.com' : 'https://payments.cashfree.com';
		$query_params = "$prefix_url/pgbillpayuiapi/integrations/webhook?redirectData=$redirect_data";

		return $query_params;
	}

	/**
	 * Get valid phone number
	 *
	 * @param object $order  array.
	 *
	 * @return string
	 */
	public static function get_phone_number($order)
	{
		$phone = '';

		if (!empty($order->get_billing_phone())) {
			$phone = $order->get_billing_phone();
		} elseif ($order->get_shipping_method()) {
			$address = $order->get_address('shipping');
			$phone = empty($address['phone']) ? '' : $address['phone'];
		}

		$customerPhone = !empty($phone) ? preg_replace("/[^0-9]/", '', $phone) : '9999999999';

		if (strpos($phone, '+') === 0) {
			$customerPhone = '+' . $customerPhone;
		} elseif (strpos($phone, '0') === 0) {
			$customerPhone = substr($customerPhone, 1);
		}

		return $customerPhone;
	}
}

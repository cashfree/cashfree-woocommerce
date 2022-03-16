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
		require_once WC_CASHFREE_DIR_PATH . 'includes/request/class-wc-cashfree-request-item.php';

		$order = wc_get_order( $order_id );

		if ( is_user_logged_in() ) {
			$customer 		= new WC_Customer( $order->get_user_id() );
			$customerEmail 	= $customer->get_email();
			$customerId 	= $customer->get_id();
		} else {
			$customerEmail = $order->get_billing_email();
			$customerId = "woocommerceCustomer";
		}

		if(!empty($order->get_billing_phone())) {
			$phone = $order->get_billing_phone();
		} else {
			if ( $order->get_shipping_method() ) {
				$address = $order->get_address( 'shipping' );
				$phone = empty( $address['phone'] ) ? '' : $address['phone'];
			} else {
				$phone = '';
			}
		}

		if(!empty($phone)) {
			$customerPhone = preg_replace("/[^0-9]/", '', $phone);
		} else {
			$customerPhone = '9999999999';
		}

		$data = array(
			"customer_details"      => array(
				"customer_id"       => $customerId,
				"customer_email"    => $customerEmail,
				"customer_phone"    => $customerPhone
			),
			"order_id"          => (string) $order_id,
			"order_amount"      => $order->get_total(),
			"order_currency"    => $order->get_currency(),
			"order_note"        => "WooCommerce",
			"order_meta"        => array(
				"notify_url" => "self::get_url( 'notify', $order->get_order_key(), $gateway->id )"
			)
		);

		return $data;
	}

	/**
	 * Create Api URL.
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
				'action'    => $action,
				'order_key' => $order_key,
			),
			WC()->api_request_url( $gateway_id )
		);
	}
}

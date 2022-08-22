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

		if ( is_user_logged_in() ) {
			$customer 		= new WC_Customer( $order->get_user_id() );
			$customerEmail 	= $customer->get_email();
			$customerId 	= (string) $customer->get_id();
		} else {
			$customerEmail = $order->get_billing_email();
			$customerId = "woocommerceCustomer";
		}

		$customerPhone = self::get_phone_number($order);
		$billing_address = WC_Cashfree_Request_Billing::build( $order_id );
		$customerName = "";

		if(!empty($billing_address) == true) {
			$customerName = $billing_address['data']['full_name'];
		}

		$data = array(
			"customer_details"      => array(
				"customer_id"       => $customerId,
				"customer_email"    => $customerEmail,
				"customer_phone"    => $customerPhone,
				"customer_name"		=> $customerName
			),
			"order_id"          => (string) $order_id,
			"order_amount"      => $order->get_total(),
			"order_currency"    => $order->get_currency(),
			"order_note"        => "WooCommerce",
			"order_meta"        => array(
			"notify_url" 		=> self::get_notify_url( 'notify', $order->get_order_key(), $gateway ),
			"return_url"		=> self::get_return_url('capture', $order->get_order_key(), $gateway)
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
				'action'    => $action,
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
		return add_query_arg(
			array(
				'order_id'    => '{order_id}',
				'order_token' => '{order_token}',
				'order_key' => $order_key,
				'action' 	  => $action
			),
			WC()->api_request_url( $gateway->id )
		);
	}

	/**
	 * Create Api webhooks URL.
	 *
	 * @param string $gateway Cashfree gateway.
	 *
	 * @return string
	 */
	public static function get_notify_url( $action, $order_key, $gateway ) {
		$wc_notify_url =  add_query_arg(
			array(
				'order_key' => $order_key,
				'action' 	  => $action
			),
			WC()->api_request_url( $gateway->id )
		);

		$redirectData = base64_encode("notify_url=".$wc_notify_url."&platform=woo");
		if ( $gateway->settings['sandbox'] != 'yes' ) {
			$prefixUrl = 'https://payments.cashfree.com';
		} else {
			$prefixUrl = 'https://payments-test.cashfree.com';
		}
		$query_params = $prefixUrl."/pgbillpayuiapi/integrations/webhook?redirectData=".$redirectData;
		return $query_params;
	}

	/**
	 * Get valid phone number
	 *
	 * @param object $order  array.
	 *
	 * @return string
	 */
	public static function get_phone_number( $order ) {
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
			if (strpos($phone, '+') === 0) {
				$customerPhone = '+'.preg_replace("/[^0-9]/", '', $phone);
			} elseif(strpos($phone, '0') === 0) {
				$customerPhone = substr(preg_replace("/[^0-9]/", '', $phone), 1);
			} else {
				$customerPhone = preg_replace("/[^0-9]/", '', $phone);
			}
		} else {
			$customerPhone = '9999999999';
		}

		return $customerPhone;
	}
}

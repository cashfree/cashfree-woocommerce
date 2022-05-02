<?php
/**
 * Billing request
 */

defined( 'ABSPATH' ) || exit;

/**
 * Billing request class.
 */
class WC_Cashfree_Request_Billing {

	/**
	 * Build request.
	 *
	 * @param WC_Order $order Order instance.
	 *
	 * @return array
	 */
	public static function build( $order_id ) {
		$order = new WC_Order( $order_id );
		$billing_address=$order->get_address( 'billing' ) ;
		$billingAddress = "";
		if ( is_user_logged_in() ) {
			$customer 		= new WC_Customer( $order->get_user_id() );
			if(!empty($customer->get_billing_address_1())) {
				$billingAddress." ".$customer->get_billing_address_1();
			}
			if(!empty($customer->get_billing_address_2())) {
				$billingAddress." ".$customer->get_billing_address_2();
			}
			if(!empty($customer->get_billing_city())) {
				$billingAddress." ".$customer->get_billing_city();
			}
			if(!empty($customer->get_billing_state())) {
				$billingAddress." ".$customer->get_billing_state();
			}
			return substr($billingAddress,0,254);
		} elseif(!empty($billing_address)) {
			if(!empty($billing_address['address_1'])) {
				$billingAddress = $billingAddress." ".$billing_address['address_1'];
			}
			if(!empty($billing_address['address_2'])) {
				$billingAddress = $billingAddress." ".$billing_address['address_2'];
			}
			if(!empty($billing_address['city'])) {
				$billingAddress = $billingAddress." ".$billing_address['city'];
			}
			if(!empty($billing_address['state'])) {
				$billingAddress = $billingAddress." ".$billing_address['state'];
			}
			return substr($billingAddress,0,254);
		} else {
			$billingAddress = "";
		}

		return $billingAddress;
	}
}

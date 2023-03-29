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
		$addressData = array();
		$billingAddressArray = array();
		$billingAddress = "";
		if ( is_user_logged_in() ) {
			$customer = new WC_Customer( $order->get_user_id() );
			if(!empty($customer->get_billing_first_name())) {
				$billingAddressArray['full_name'] = $customer->get_billing_first_name(). " ". $customer->get_billing_last_name();
			}
			if(!empty($customer->get_billing_address_1())) {
				$billingAddressArray['address_1'] = substr($customer->get_billing_address_1(),0,254);
				$billingAddress = $billingAddress." ".$customer->get_billing_address_1();
			}
			if(!empty($customer->get_billing_address_2())) {
				$billingAddressArray['address_2'] = substr($customer->get_billing_address_2(),0,254);
				$billingAddress = $billingAddress." ".$customer->get_billing_address_2();
			}
			if(!empty($customer->get_billing_city())) {
				$billingAddressArray['city'] = substr($customer->get_billing_city(),0,254);
				$billingAddress = $billingAddress." ".$customer->get_billing_city();
			}
			if(!empty($customer->get_billing_state())) {
				$billingAddressArray['state'] = substr($customer->get_billing_state(),0,254);
				$billingAddress = $billingAddress." ".$customer->get_billing_state();
			}
			if(!empty($customer->get_billing_country())) {
				$billingAddressArray['country'] = substr($customer->get_billing_country(),0,254);
			}
			if(!empty($customer->get_billing_postcode())) {
				$billingAddressArray['pincode'] = $customer->get_billing_postcode();
			}
			$addressData['data'] = $billingAddressArray;
			$addressData['billingAddress'] = substr($billingAddress,0,254);
			return $addressData;
		} elseif(!empty($billing_address)) {
			if(!empty($billing_address['first_name'])) {
				$billingAddressArray['full_name'] = $billing_address['first_name']. " " . $billing_address['last_name'];
			}
			if(!empty($billing_address['address_1'])) {
				$billingAddressArray['address_1'] = substr($billing_address['address_1'],0,254);
				$billingAddress = $billingAddress." ".$billing_address['address_1'];
			}
			if(!empty($billing_address['address_2'])) {
				$billingAddressArray['address_2'] = substr($billing_address['address_2'],0,254);
				$billingAddress = $billingAddress." ".$billing_address['address_2'];
			}
			if(!empty($billing_address['city'])) {
				$billingAddressArray['city'] = substr($billing_address['city'],0,254);
				$billingAddress = $billingAddress." ".$billing_address['city'];
			}
			if(!empty($billing_address['state'])) {
				$billingAddressArray['state'] = substr($billing_address['state'],0,254);
				$billingAddress = $billingAddress." ".$billing_address['state'];
			}
			if(!empty($billing_address['country'])) {
				$billingAddressArray['country'] = substr($billing_address['country'],0,254);
			}
			if(!empty($billing_address['postcode'])) {
				$billingAddressArray['postcode'] = $billing_address['postcode'];
			}
			$addressData['data'] = $billingAddressArray;
			$addressData['billingAddress'] = substr($billingAddress,0,254);
			return $addressData;
		} else {
			return $addressData;
		}
	}
}

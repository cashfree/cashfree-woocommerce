<?php
/**
 * Shipping request
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shipping request class.
 */
class WC_Cashfree_Request_Shipping {

	/**
	 * Build request.
	 *
	 * @param WC_Order $order Order instance.
	 *
	 * @return array
	 */
	public static function build( $order_id ) {
		$order = new WC_Order( $order_id );
		$shipping_address=$order->get_address( 'shipping' ) ;
		$addressData = array();
		$shippingAddressArray = array();
		$shippingAddress = "";
		if ( !empty($shipping_address) ) {
			if(!empty($shipping_address['first_name'])) {
				$shippingAddressArray['full_name'] = $shipping_address['first_name']. " " . $shipping_address['last_name'];
			}
			if(!empty($shipping_address['address_1'])) {
				$shippingAddressArray['address_1'] = substr($shipping_address['address_1'],0,254);
				$shippingAddress = $shippingAddress." ".$shipping_address['address_1'];
			}
			if(!empty($shipping_address['address_2'])) {
				$shippingAddressArray['address_2'] = substr($shipping_address['address_2'],0,254);
				$shippingAddress = $shippingAddress." ".$shipping_address['address_2'];
			}
			if(!empty($shipping_address['city'])) {
				$shippingAddressArray['city'] = substr($shipping_address['city'],0,254);
				$shippingAddress = $shippingAddress." ".$shipping_address['city'];
			}
			if(!empty($shipping_address['state'])) {
				$shippingAddressArray['state'] = substr($shipping_address['state'],0,254);
				$shippingAddress = $shippingAddress." ".$shipping_address['state'];
			}
			if(!empty($shipping_address['country'])) {
				$shippingAddressArray['country'] = substr($shipping_address['country'],0,254);
			}
			if(!empty($shipping_address['postcode'])) {
				$shippingAddressArray['postcode'] = $shipping_address['postcode'];
			}
			$addressData['data'] = $shippingAddressArray;
			$addressData['shippingAddress'] = substr($shippingAddress,0,254);
			return $addressData;
		}

		return $addressData;
		
	}
}

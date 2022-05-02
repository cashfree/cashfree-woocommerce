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
		$shippingAddress = "";
		if ( !empty($shipping_address) ) {
			if(!empty($shipping_address['address_1'])) {
				$shippingAddress." ".$shipping_address['address_1'];
			}
			if(!empty($shipping_address['address_2'])) {
				$shippingAddress." ".$shipping_address['address_2'];
			}
			if(!empty($shipping_address['city'])) {
				$shippingAddress." ".$shipping_address['city'];
			}
			if(!empty($shipping_address['state'])) {
				$shippingAddress." ".$shipping_address['state'];
			}
			return substr($shippingAddress,0,254);
		}

		return $shippingAddress;
		
	}
}

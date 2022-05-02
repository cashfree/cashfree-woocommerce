<?php
/**
 * Item request
 */

defined( 'ABSPATH' ) || exit;

/**
 * Item request class.
 */
class WC_Cashfree_Request_Item {

	/**
	 * Build request.
	 *
	 * @param WC_Order              $order Order instance.
	 * @param WC_Order_Item_Product $item  Item in cart instance.
	 *
	 * @return array
	 */
	public static function build( $order, $item ) {
		$product = $item->get_product();

		return array(
			'reference'   			=> substr( $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(), 0, 254 ),
			'name'        			=> substr( $item->get_name(), 0, 254 ),
			'description' 			=> substr( $product->get_description(), 0, 499 ),
			"tags" 					=> [],
			'details_url' 			=> get_permalink( $item->get_product_id() ),
			'image_url'   			=> (string) wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
			'original_unit_price'  	=> $product->get_regular_price(),
			'discounted_unit_price'	=> $product->get_price(),
			'quantity'         		=> $item->get_quantity(),
			'currency' 				=>  $order->get_currency()
		);
	}
}

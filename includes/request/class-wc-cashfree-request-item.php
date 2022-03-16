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
			'reference'   => $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(),
			'name'        => $item->get_name(),
			'description' => substr( $product->get_description(), 0, 1024 ),
			'url'         => get_permalink( $item->get_product_id() ),
			'image_url'   => wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
			'unit_price'  => WC_Cashfree_Adapter::decimal(
				$order->get_line_subtotal( $item, false, false ) / $item->get_quantity()
			),
			'qty'         => $item->get_quantity(),
		);
	}
}

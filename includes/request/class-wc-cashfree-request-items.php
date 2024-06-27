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
            'item_id'   			    => substr( $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id(), 0, 254 ),
            'item_name'        			=> mb_convert_encoding(substr( $item->get_name(), 0, 254 ), 'UTF-8', 'UTF-8'),
            'item_description' 			=> mb_convert_encoding(substr( $product->get_description(), 0, 499 ), 'UTF-8', 'UTF-8'),
            "tags" 					    => [],
            'item_details_url' 			=> get_permalink( $item->get_product_id() ),
            'item_image_url'   			=> (string) wp_get_attachment_image_url( $product->get_image_id(), 'full' ),
            'item_original_unit_price'  => $product->get_regular_price() ? $product->get_regular_price() : 0,
            'item_discounted_unit_price'=> $product->get_price() ? $product->get_price() : 0,
            'item_quantity'         	=> $item->get_quantity(),
            'item_currency' 			=>  $order->get_currency()
        );
	}
}

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
	public static function build( $item ) {
        return array(
            'item_name'        			=> mb_convert_encoding(substr( $item->get_name(), 0, 254 ), 'UTF-8', 'UTF-8'),
            'item_original_unit_price'  => self::cfConvertToNumber($item->get_total() ? $item->get_total() : 0),
            'item_quantity'         	=> $item->get_quantity(),
        );
	}

    public static function cfConvertToNumber($input) {
		$amount = (float) str_replace(',', '', $input);
		$amount = number_format($amount, 2, '.', '');
		return $amount;
	}
}

<?php
/**
 * Cashfree gateway
 */

defined( 'ABSPATH' ) || exit;

/**
 * Cashfree gateway class.
 */
final class WC_Cashfree_Payments extends WC_Cashfree_Gateway {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = WC_Cashfree::PAYMENT_GATEWAY_ID;
		$this->icon               = "https://cashfreelogo.cashfree.com/wix/cflogo.svg";
		$this->method_description = __( 'Pay securely via Card/Net Banking/Wallet via Cashfree.', 'cashfree' );
		parent::__construct();
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include WC_CASHFREE_DIR_PATH . 'includes/settings/cashfree-payments.php';
	}
}

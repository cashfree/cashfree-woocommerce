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
		$this->method_title       = __( 'Cashfree Payments', 'cashfree' );
		$this->method_description = __( 'Accept payments using credit and debit cards.', 'cashfree' );
		$this->order_button_text  = __( 'Pay Now', 'cashfree' );
		$this->icon               = "https://cashfreelogo.cashfree.com/wix/cflogo.svg";

		parent::__construct();
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = include WC_CASHFREE_DIR_PATH . 'includes/settings/cashfree-payments.php';
	}
}

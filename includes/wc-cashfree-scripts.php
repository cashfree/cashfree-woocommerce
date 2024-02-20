<?php
/**
 * Cashfree scripts
 */

defined( 'ABSPATH' ) || exit;

return array(
	'wc-cashfree-checkout-js'       => array(
		'src'     => 'https://sdk.cashfree.com/js/v3/cashfree.js',
		'deps'    => array(),
		'version' => WC_CASHFREE_VERSION,
	),
	'wc-cashfree-checkout' => array(
		'src'     => WC_CASHFREE_DIR_URL . 'assets/js/checkout.js',
		'deps'    => array(),
		'version' => WC_CASHFREE_VERSION,
	),
);

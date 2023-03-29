<?php
/**
 * Cashfree scripts
 */

defined( 'ABSPATH' ) || exit;

return array(
	'wc-cashfree-pippin-js'       => array(
		'src'     => 'https://sdk.cashfree.com/js/pippin/2.0.0/pippin.min.js',
		'deps'    => array(),
		'version' => WC_CASHFREE_VERSION,
	),
	'wc-cashfree-sandbox-drop-js'       => array(
		'src'     => 'https://sdk.cashfree.com/js/ui/2.0.0/cashfree.sandbox.js',
		'deps'    => array(),
		'version' => WC_CASHFREE_VERSION,
	),
	'wc-cashfree-prod-drop-js'       => array(
		'src'     => 'https://sdk.cashfree.com/js/ui/2.0.0/cashfree.prod.js',
		'deps'    => array(),
		'version' => WC_CASHFREE_VERSION,
	),
	'wc-cashfree-checkout' => array(
		'src'     => WC_CASHFREE_DIR_URL . 'assets/js/checkout.js',
		'deps'    => array(),
		'version' => WC_CASHFREE_VERSION,
	),
);

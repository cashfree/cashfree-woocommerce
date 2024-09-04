<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

final class WC_Cashfree_Blocks_Support extends AbstractPaymentMethodType {

	protected $name = 'cashfree';
	protected $settings = [];
    protected $gateway;

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_cashfree_settings', [] );
		$gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways[ $this->name ];
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return filter_var( $this->get_setting( 'enabled', false ), FILTER_VALIDATE_BOOLEAN );
	}


	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'cashfree',
			WC_CASHFREE_DIR_URL . 'assets/js/index.js',
			[
				'wc-blocks-registry',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			],
			false,
			true
		);
		return [ 'cashfree' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'                    => $this->get_setting( 'title' ),
			'description'              => $this->get_setting( 'description' ),
			'supports'                 => $this->get_supported_features(),
		];
	}
}

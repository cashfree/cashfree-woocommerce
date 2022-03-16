<?php
/**
 * Cashfree functions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the script and inject parameters.
 *
 * @param string     $handle Script handle the data will be attached to.
 * @param array|null $params Parameters injected.
 */
function wc_cashfree_script( $handle, $params = null ) {
	$script = ( include 'wc-cashfree-scripts.php' )[ $handle ];

	wp_enqueue_script( $handle, $script['src'], $script['deps'], $script['version'], true );

	if ( null !== $params ) {
		wp_localize_script( $handle, str_replace( '-', '_', $handle ) . '_params', $params );
	}
}

/**
 * Register and load wc-cashfree script.
 *
 * @param array $settings Gateway settings.
 */
function wc_cashfree_js( $settings ) {
	wc_cashfree_script( 'wc-cashfree-js' );

	wc_cashfree_script(
		'wc-cashfree-checkout',
		array(
			'sandbox'    => 'yes' === $settings['sandbox'],
			'merchantId' => $settings['app_id'],
			'locale'     => get_locale(),
		)
	);
}

<?php
/**
 * Cashfree functions
 */

defined( 'ABSPATH' ) || exit;

function wc_get_cashfree_template( $template_name, $args = array(), $template_path = '' ) {
	wc_get_template( $template_name, $args, $template_path, WC_CASHFREE_DIR_PATH . 'templates/' );
}

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
	if ($settings["in_context"] === "yes") {
		wc_cashfree_script( 'wc-cashfree-pippin-js' );
	} else {
		if($settings["sandbox"] === "no") {
			wc_cashfree_script('wc-cashfree-prod-drop-js');
		} else {
			wc_cashfree_script('wc-cashfree-sandbox-drop-js');
		}
	}
	wc_cashfree_script('wc-cashfree-checkout');
}

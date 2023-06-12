<?php
/**
 * Cashfree pay now settings
 */

defined( 'ABSPATH' ) || exit;

return array(
	array(
		'type'  => 'title',
		'description'  => __( '*To ensure seamless payment integration, it is necessary to comply with payment regulations by whitelisting your domain. Please click on the <a href="https://docs.cashfree.com/docs/developers-whitelisting" target="_blank">link</a> provided to whitelist your domain.', 'cashfree' ),
	),
	'enabled'   		=> array(
		'title'   		=> __( 'Enable/Disable', 'cashfree' ),
		'type'    		=> 'checkbox',
		'label'   		=> __( 'Enable Cashfree Payments', 'cashfree' ),
		'default' 		=> 'yes',
		
	),
	'title'                  => array(
		'title'  		=> __( 'Title', 'cashfree' ),
		'type'        	=> 'text',
		'description' 	=> __( 'This controls the title which the user sees during checkout.', 'cashfree' ),
		'default'     	=> __( 'Cashfree Payments', 'cashfree' ),
		'desc_tip'    	=> true,
	),
	'description' => array(
		'title'       => __( 'Description', 'cashfree' ),
		'type'        => 'textarea',
		'description' => __( 'Payment method description that the customer will see on your checkout.', 'cashfree' ),
		'default'     => __( 'Pay securely via Card/Net Banking/Wallet via Cashfree.', 'cashfree' ),
	),
	'app_id'        	=> array(
		'title'       	=> __( 'App ID', 'cashfree' ),
		'type'        	=> 'text',
		'description' 	=> __( 'Get your App ID from Cashfree.', 'cashfree' ),
		'default'     	=> '',
		'desc_tip'    	=> true,
	),
	'secret_key'     	=> array(
		'title'       	=> __( 'Secret key', 'cashfree' ),
		'type'        	=> 'password',
		'description' 	=> __( 'Get your secret Key from Cashfree.', 'cashfree' ),
		'default'     	=> '',
		'desc_tip'    	=> true,
	),
	'sandbox'			=> array(
		'title'       	=> __( 'Cashfree sandbox', 'cashfree' ),
		'type'        	=> 'checkbox',
		'label'       	=> __( 'Enable Cashfree sandbox', 'cashfree' ),
		'default'     	=> 'no',
		'description' 	=> __( 'Cashfree sandbox can be used to test payments.', 'cashfree' ),
	),
	'debug'				=> array(
		'title'       	=> __( 'Debug log', 'cashfree' ),
		'type'        	=> 'checkbox',
		'label'       	=> __( 'Enable logging', 'cashfree' ),
		'default'     	=> 'no',
		'description' 	=> __( 'Log Cashfree events, such as HTTP requests.', 'cashfree' ),
	),
	'order_button_text' => array(
		'title'       => __('Order button text', 'cashfree'),
		'type'        =>  'text',
		'default'     => __('Pay Now', 'cashfree'),
		'description' => __('This controls the title which the user sees during checkout.', 'cashfree'),
		'desc_tip'    => true
	),
	'in_context'		=> array(
		'title'       	=> __( 'In Context', 'cashfree' ),
		'type'        	=> 'checkbox',
		'label'       	=> __( 'Enable In Context', 'cashfree' ),
		'default'     	=> 'yes',
		'description' 	=> __( 'Cashfree In Context can be used to accept payments without redirection', 'cashfree' ),
		'desc_tip'    => true
	),
	'enabledOffers'   	=> array(
		'title'   		=> __( 'Widget Enable/Disable', 'cashfree' ),
		'type'    		=> 'checkbox',
		'label'   		=> __( 'Widget Enable of Cashfree Payments', 'cashfree' ),
		'default' 		=> 'no',
	),
	'offers'   			=> array(
		'title'   		=> __( 'Enable/Disable Offers', 'cashfree' ),
		'type'    		=> 'checkbox',
		'label'   		=> __( 'Enable/Disable Offers on widget', 'cashfree' ),
		'default' 		=> 'no',
	),	
	'payLater'   		=> array(
		'title'   		=> __( 'Enable/Disable Paylater', 'cashfree' ),
		'type'    		=> 'checkbox',
		'label'   		=> __( 'Enable/Disable Paylater on widget', 'cashfree' ),
		'default' 		=> 'no',
	),
	'emi'   			=> array(
		'title'   		=> __( 'Enable/Disable EMI', 'cashfree' ),
		'type'    		=> 'checkbox',
		'label'   		=> __( 'Enable/Disable EMI on widget', 'cashfree' ),
		'default' 		=> 'no',
	),
);
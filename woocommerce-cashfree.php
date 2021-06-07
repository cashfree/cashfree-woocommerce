<?php
/*
* Plugin Name: Cashfree
* Plugin URI: https://www.gocashfree.com
* Description: Payment gateway plugin by Cashfree for Woocommerce sites
* Version: 4.2.2
* Author: Cashfree Dev
* Author URI: techsupport@gocashfree.com
* WC requires at least: 3.0
* WC tested up to: 5.3
*/

if ( ! defined( 'ABSPATH' ) )
{
    exit; // Exit if accessed directly
}

require_once __DIR__.'/includes/response.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
 
// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'woocommerce_cashfree_init', 0 );
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'cashfree_action_links' );

function cashfree_action_links( $links ) {
  $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout') ) .'">Setup</a>';
  $url =  "https://github.com/cashfree/cashfree_woocommerce_kit/tree/master/woocommerce-cashfree-wp4x-wc2xV3";
  $links[] = '<a href="'.esc_url($url).'"" target="_blank">Github</a>';
  return $links;
}

function woocommerce_cashfree_init() {
  // If the parent WC_Payment_Gateway class doesn't exist
  // it means WooCommerce is not installed on the site
  // so do nothing
  if (!class_exists('WC_Payment_Gateway'))
  {
    return;
  }
   
  // If we made it this far, then include our Gateway Class
  class WC_Gateway_cashfree extends WC_Payment_Gateway 
  {
    const DEFAULT_LABEL                  = 'Cashfree';
    const DEFAULT_DESCRIPTION            = 'Pay securely via Card/Net Banking/Wallet via Cashfree.';
    /**
     * Unique ID for the gateway
     * @var string  
    **/
    public $id = 'cashfree';

    /**
     * Title of the payment method shown on the admin page.
     * @var string
    */
    public $method_title = 'Cashfree';

    protected $visibleSettings = array(
      'enabled',
      'title',
      'description',
      'environment',
      'app_id',
      'secret_key'
    );
    
    // Setup our Gateway's id, description and other values
    function __construct()
    {
      global $woocommerce;
      global $wpdb;
	    $this->order_button_text  = __( 'Proceed to Cashfree', 'wc_gateway_cashfree' );
      $this->icon               = 'https://s3-ap-southeast-1.amazonaws.com/cfreeimages/cf-mailer-logo.png';
      $this->method_description = "Cashfree payment gateway redirects customers to checkout page to fill in their payment details and complete the payment";
      $this->has_fields         = false;
      $this->init_form_fields();
      $this->init_settings();     
      $this->environment        = $this->settings['environment'];
      $this->app_id             = $this->settings['app_id'];
      $this->secret_key         = $this->settings['secret_key'];
      $this->description        = $this->settings['description'];
      $this->title              = $this->settings['title'];

      add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	    add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_cashfree_response' ) );
    }
  
  
    // Build the administration fields for this specific Gateway
    public function init_form_fields() {
      $defaultFormFields = array(
        'enabled' => array(
          'title'       => __('Enable/Disable', 'wc_gateway_cashfree'),
          'type'        => 'checkbox',
          'label'       => __('Enable Cashfree payment gateway.', 'wc_gateway_cashfree'),
          'default'     => 'no',
          'description' => 'Show in the Payment List as a payment option'
        ),
        'title' => array(
          'title'       => __('Title', $this->id),
          'type'        => 'text',
          'default'     => __(static::DEFAULT_LABEL, $this->id),
          'description' => __('This controls the title which the user sees during checkout.', $this->id),
          'desc_tip'    => true
        ),
        'description'   => array(
          'title'       => __('Description:', 'wc_gateway_cashfree'),
          'type'        => 'textarea',
          'default'     => __(static::DEFAULT_DESCRIPTION, $this->id),
          'description' => __('This controls the description which the user sees during checkout.', 'wc_gateway_cashfree'),
          'desc_tip'    => true
        ),
        'environment'   => array (
          'type'        => 'select',
          'options'     => array (
            'sandbox'   => __ ( 'Test Mode', 'wc_gateway_cashfree' ),
            'production'=> __ ( 'Live Mode', 'wc_gateway_cashfree' ) 
          ),
          'default'     => 'sandbox',
          'title'       => __ ( 'Active Environment', 'wc_gateway_cashfree' ),
          'class'       => array (
            'wc_gateway_cashfree-active-environment' 
          ),
          'tool_tip'    => true,
          'description' => __ ( 'You can enable Test mode or Live mode with this setting. When testing the plugin, enable Test mode and you can run test transactions using your Cashfree account.
          When you are ready to go live, enable Live mode.', 'wc_gateway_cashfree' ) 
        ),
        'app_id'        => array(
          'title'       => __('App Id', 'wc_gateway_cashfree'),
          'type'        => 'text',
          'description' => __('Copy from your dashboard or contact Cashfree Team', 'wc_gateway_cashfree'),
          'desc_tip'    => true
        ),
        'secret_key'    => array(
          'title'       => __('Secret Key', 'wc_gateway_cashfree'),
          'type'        => 'password',
          'description' => __('Copy from your dashboard or contact Cashfree Team', 'wc_gateway_cashfree'),
          'desc_tip'    => true
        ),                
      );
      foreach ($defaultFormFields as $key => $value)
      {
        if (in_array($key, $this->visibleSettings, true))
        {
            $this->form_fields[$key] = $value;
        }
      }
    }

    function check_cashfree_response()
    {
      global $woocommerce, $wpdb;

      if($_GET['wc-api']== get_class( $this ) && sanitize_text_field($_REQUEST["act"]) == 'ret')
      {
        $cashfreeResponse = new Cashfree_Response();
        
        $cashfreeResponse->process($_POST);      
      }

      if($_GET['wc-api']== get_class( $this ) && sanitize_text_field($_REQUEST["act"]) == 'notify')
      {
        $cashfreeResponse = new Cashfree_Response();
        
        sleep(30);
        
        $cashfreeResponse->process($_POST);      
      }
    }

    public function getEnvironment()
    {
      $environment = $this->get_option( 'environment' ) === 'sandbox' ? 'sandbox' : 'production';
      return $environment;
    }
  
    // Submit payment and handle response
    public function process_payment( $order_id )
    {           
      $order          = wc_get_order( $order_id );
      $first_name     = $order->get_billing_first_name();
      $last_name      = $order->get_billing_last_name();
      $phone_number   = $order->get_billing_phone();
      $customerName   = $first_name." ".$last_name;
      $customerEmail  = $order->get_billing_email();
      $customerPhone  = $phone_number;

      $this->notify_url  = add_query_arg( 'wc-api', get_class( $this ), home_url('/') );
      $this->return_url = add_query_arg(array('act' => "ret"), $this->notify_url);
      $this->notify_url = add_query_arg(array('act' => "notify"), $this->notify_url);
      
      $cf_request                   = array();
      $cf_request["appId"]          = $this->app_id;
      $cf_request["secretKey"]      = $this->secret_key;
      $cf_request["orderId"]        = $order_id.'_'.time();  
      $cf_request["orderAmount"]    = $order->get_total();
      $cf_request["orderCurrency"]  = $order->get_currency();
      $cf_request["customerPhone"]  = $customerPhone;
      $cf_request["customerName"]   = $customerName;
      $cf_request["customerEmail"]  =  $customerEmail;
      $cf_request["source"]         =  "woocommerce";
      $cf_request["returnUrl"]      = $this->return_url;		
      $cf_request["notifyUrl"]      = $this->notify_url;
      $timeout                      = 30;                
      if ( $this->getEnvironment() === 'sandbox' )
      {
        $apiEndpoint = "https://test.cashfree.com";
      } 
      elseif ($this->getEnvironment() === 'production') 
      {
        $apiEndpoint = "https://api.cashfree.com";
      }
      $apiEndpoint = $apiEndpoint."/api/v1/order/create";
      $postBody = array("body" => $cf_request, "timeout" => $timeout);
      $cf_result = wp_remote_retrieve_body(wp_remote_post(esc_url($apiEndpoint),$postBody));
		  $jsonResponse = json_decode($cf_result);
		
      if ($jsonResponse->{'status'} == "OK") 
      {
        $paymentLink = $jsonResponse->{"paymentLink"};
        return array('result' => 'success', 'redirect' => $paymentLink);
      } 
      else 
      {
		    wc_add_notice( __( 'Gateway request failed - '.$jsonResponse->reason, 'woocommerce' ) ,'error');	
        return array('result' => 'failed');
      }
      exit;
    }

    /**
     * Add a woocommerce notification message
     *
     * @param string $message Notification message
     * @param string $type Notification type, default = notice
     */
    function add_notice($message, $type = 'notice')
    {
        global $woocommerce;
        $type = in_array($type, array('notice','error','success'), true) ? $type : 'notice';
        // Check for existence of new notification api. Else use previous add_error
        if (function_exists('wc_add_notice'))
        {
            wc_add_notice($message, $type);
        }
        else
        {
            // Retrocompatibility WooCommerce < 2.1
            switch ($type)
            {
                case "error" :
                    $woocommerce->add_error($message);
                    break;
                default :
                    $woocommerce->add_message($message);
                    break;
            }
        }
    }
  }
  
  add_filter( 'woocommerce_payment_gateways', 'add_cashfree_gateway' );
  
  function add_cashfree_gateway( $methods ) {
    $methods[] = 'WC_Gateway_cashfree';
    return $methods;
  }
}
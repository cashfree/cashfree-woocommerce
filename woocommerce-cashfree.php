<?php
/*
Plugin Name: Cashfree
Plugin URI: https://www.gocashfree.com
Description: Payment gateway plugin by Cashfree for Woocommerce sites
Version: 4.3.0
Author: Cashfree Dev
Author URI: https://cashfree.com
Author URI: techsupport@gocashfree.com
Text Domain: cashfree
WC requires at least: 3.0
WC tested up to: 6.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Cashfree Payments is an Indian payment aggregator helping you cater to all your customers' payment needs. Cashfree Payments seamlessly integrates with Woocommerce to let you accept payments for your Indian business on your WordPress website. With a Cashfree Payments hosted checkout and an intuitive dashboard you can be up and running in no time.
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
    $links[] = '<a href="'. esc_url( get_admin_url(null, 'admin.php?page=wc-settings&tab=checkout') ) .'">Setting</a>';
    $url =  "https://github.com/cashfree/cashfree-woocommerce";
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
        const DEFAULT_LABEL             = 'Cashfree Payments';
        const DEFAULT_ORDER_BUTTON_TEXT = 'Proceed with Cashfree';
        const DEFAULT_DESCRIPTION       = 'Pay securely via Card/Net Banking/Wallet via Cashfree.';
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
            'order_button_text',
            'description',
            'environment',
            'app_id',
            'secret_key'
        );
    
        // Setup our Gateway's id, description and other values
        function __construct()
        {
            $this->init_form_fields();
            $this->init_settings(); 
            global $woocommerce;
            global $wpdb;
            $this->icon               = 'https://cashfreelogo.cashfree.com/wix/cflogo.svg';
            $this->method_description = "Cashfree payment gateway redirects customers to checkout page to fill in their payment details and complete the payment";
            $this->has_fields         = false;    
            $this->environment        = $this->settings['environment'];
            $this->app_id             = $this->settings['app_id'];
            $this->secret_key         = $this->settings['secret_key'];
            $this->description        = $this->settings['description'];
            $this->title              = $this->settings['title'];
            $this->order_button_text  = $this->settings['order_button_text'];

            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_cashfree_response' ) );
        }
  
  
        // Build the administration fields for this specific Gateway
        public function init_form_fields() {
            $defaultFormFields = array(
                'enabled' => array(
                  'title'       => __('Enable/Disable', $this->id),
                  'type'        => 'checkbox',
                  'label'       => __('Enable Cashfree payment gateway.', $this->id),
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
                'order_button_text' => array(
                  'title'       => __('Order button text', $this->id),
                  'type'        =>  'text',
                  'default'     => __(static::DEFAULT_ORDER_BUTTON_TEXT, $this->id),
                  'description' => __('This controls the title which the user sees during checkout.', $this->id),
                  'desc_tip'    => true
                ),
                'description'   => array(
                  'title'       => __('Description:', $this->id),
                  'type'        => 'textarea',
                  'default'     => __(static::DEFAULT_DESCRIPTION, $this->id),
                  'description' => __('This controls the description which the user sees during checkout.', $this->id),
                  'desc_tip'    => true
                ),
                'environment'   => array (
                  'type'        => 'select',
                  'options'     => array (
                    'sandbox'   => __ ( 'Test Mode', $this->id ),
                    'production'=> __ ( 'Live Mode', $this->id ) 
                  ),
                  'default'     => 'sandbox',
                  'title'       => __ ( 'Active Environment', $this->id ),
                  'class'       => array (
                    'wc_gateway_cashfree-active-environment' 
                  ),
                  'tool_tip'    => true,
                  'description' => __ ( 'You can enable Test mode or Live mode with this setting. When testing the plugin, enable Test mode and you can run test transactions using your Cashfree account.
                  When you are ready to go live, enable Live mode.', $this->id ) 
                ),
                'app_id'        => array(
                  'title'       => __('App Id', $this->id),
                  'type'        => 'text',
                  'description' => __('Copy from your dashboard or contact Cashfree Team', $this->id),
                  'desc_tip'    => true
                ),
                'secret_key'    => array(
                  'title'       => __('Secret Key', $this->id),
                  'type'        => 'password',
                  'description' => __('Copy from your dashboard or contact Cashfree Team', $this->id),
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

        public function admin_options()
        {
            echo '<h3>'.__('Cashfree Payment Gateway', $this->id) . '</h3>';
            echo '<p>'.__('Allows payments by Credit/Debit Cards, NetBanking, UPI, and multiple Wallets') . '</p>';
            echo '<table class="form-table">';

            // Generate the HTML For the settings form.
            $this->generate_settings_html();
            echo '</table>';
        }

        public $supports = array(
            'refunds'
        );

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

        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = wc_get_order($order_id);

            if (! $order or ! $order->get_transaction_id())
            {
                return new WP_Error('error', __('Refund failed: No transaction ID', 'woocommerce'));
            }

            $referenceId = $order->get_transaction_id();

            $cf_refund_request                  = array();
            $cf_refund_request["appId"]         = $this->app_id;
            $cf_refund_request["secretKey"]     = $this->secret_key;
            $cf_refund_request["referenceId"]   = $referenceId;  
            $cf_refund_request["refundAmount"]  = $amount;
            $cf_refund_request["refundNote"]    = $reason;
            $timeout                            = 30;                
            if ( $this->getEnvironment() === 'sandbox' )
            {
              $apiEndpoint = "https://test.cashfree.com";
            } 
            elseif ($this->getEnvironment() === 'production') 
            {
              $apiEndpoint = "https://api.cashfree.com";
            }
            $apiEndpoint = $apiEndpoint."/api/v1/order/refund";
            $postBody = array("body" => $cf_refund_request, "timeout" => $timeout);
            $cf_refund_result = wp_remote_retrieve_body(wp_remote_post(esc_url($apiEndpoint),$postBody));
            $refund = json_decode($cf_refund_result);

            if ($refund->{'status'} == "OK" && isset($refund->{'refundId'})) 
            {
              $order->add_order_note( __( 'Refund Id: ' . $refund->{'refundId'}, 'woocommerce' ) );
              /**
               * @var $jsonResponse->refundId -- Provides the Cashfree Refund ID
               * @var $order_id -> Refunded Order ID
               * @var $refund -> WooCommerce Refund Instance.
               */
              do_action( 'woo_cashfree_refund_success', $refund->{'refundId'}, $order_id, $refund );

              return true;
            } 
            else 
            {
              return new WP_Error('error', __($refund->{'reason'}, 'woocommerce'));
            }
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
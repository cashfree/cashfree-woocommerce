<?php

require_once __DIR__.'/../woocommerce-cashfree.php';

class Cashfree_Response
{
    public function __construct()
    {
        $this->cashfree = new WC_Gateway_cashfree(false);

        $this->msg['message'] = "";

        $this->msg['class'] = "";
    }

    function process($postArgs)
    {
        global $woocommerce, $wpdb;

        $orderId = sanitize_text_field($postArgs["orderId"]);
		list($orderId) = explode('_', $orderId);
		$order = new WC_Order($orderId);

        if ($order->has_status( array( 'processing', 'completed' ) ) )
        {
            $this->redirectUser($order);
        }

        $success = false;

        if ($order && !empty($postArgs['referenceId']))
        {
          
            if ($postArgs["txStatus"] == 'SUCCESS')
            {
                if($postArgs["orderAmount"] == $order->get_total())
                {
                    $success = $this->verifySignature($postArgs);
                }
                else
                {
                    $postArgs["txMsg"] = "Order amount is mismatched";
                }
            }

            if($success == true){
                
                $this->updateOrder($order, $success, $postArgs["txMsg"], $postArgs['referenceId']);

                $this->redirectUser($order);
            }
            else 
            {
                $this->updateOrder($order, $success, $postArgs['txMsg'], null);

                wp_redirect(wc_get_checkout_url());
                exit;
            }
     
        }
        
        else 
        {
            $this->updateOrder($order, $success, $postArgs['txMsg'], null);

            wp_redirect(wc_get_checkout_url());
            exit;
        }
      
    }

    protected function redirectUser($order)
    {
        $redirectUrl = $this->cashfree->get_return_url($order);

        wp_redirect($redirectUrl);
        exit;
    }

    protected function verifySignature($postArgs)
    {
        $cashfree_response = array();
        $cashfree_response["orderId"] = $postArgs['orderId'];
        $cashfree_response["orderAmount"] = sanitize_text_field($postArgs["orderAmount"]);
        $cashfree_response["txStatus"] = sanitize_text_field($postArgs["txStatus"]);
        $cashfree_response["referenceId"] = sanitize_text_field($postArgs["referenceId"]);
        $cashfree_response["txTime"] = sanitize_text_field($postArgs["txTime"]);
        $cashfree_response["txMsg"] = sanitize_text_field($postArgs["txMsg"]);
        $cashfree_response["paymentMode"] = sanitize_text_field($postArgs["paymentMode"]);
        $cashfree_response["signature"] = sanitize_text_field($postArgs["signature"]);

        $secret_key = $this->cashfree->settings['secret_key'];
        $data = "{$cashfree_response['orderId']}{$cashfree_response['orderAmount']}{$cashfree_response['referenceId']}{$cashfree_response['txStatus']}{$cashfree_response['paymentMode']}{$cashfree_response['txMsg']}{$cashfree_response['txTime']}";
        $hash_hmac = hash_hmac('sha256', $data, $secret_key, true) ;
        $computedSignature = base64_encode($hash_hmac);
        if ($cashfree_response["signature"] != $computedSignature) 
        {
            return false;
        }
        return true;
    }

    /**
     * Modifies existing order and handles success case
     *
     * @param $success, & $order
     */
    public function updateOrder(& $order, $success, $txMsg, $referenceId = null)
    {
        global $woocommerce;

        if ($success === true)
        {
            $order->payment_complete();
            $order->set_transaction_id($referenceId);
            $order->add_order_note('Cashfree payment successful. Reference id ' . $referenceId);
            $order->add_order_note($txMsg);
            $this->msg['message'] = "Thank you for shopping with us. Your payment has been confirmed. Cashfree reference id is: <b>".$referenceId."</b>.";
            $this->msg['class'] = 'success';   

            if (isset($woocommerce->cart) === true)
            {
                $woocommerce->cart->empty_cart();
            }
        }
        else
        {
            $order->update_status( 'failed', __( $txMsg, 'woocommerce' ));
            $this->msg['class'] = 'error';
            $this->msg['message'] = $txMsg. ". Please try again.";
        }
        
        $this->cashfree->add_notice($this->msg['message'], $this->msg['class']);

    }
}
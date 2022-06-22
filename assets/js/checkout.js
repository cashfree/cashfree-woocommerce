/* global wc_cashfree_checkout_params */
jQuery( document ).ready(
	function( $ ) {
		let dismissflag = true;
		//Set env
		const  env = wc_cashfree_checkout_params.environment; //or production

		//Create Success Callback
		const  successCallback  =  function (data) {
			dismissflag = false;
			woocommerceFormSubmit(data, wc_cashfree_checkout_params.capture_url);
		}

		//Create Failure Callback
		const  failureCallback  =  function (data) {
			woocommerceFormSubmit(data, wc_cashfree_checkout_params.cancel_url);
		}

		//Create Dismiss Callback
		const  dismissCallback  =  function () {
			if(dismissflag) {
				pippin_form=document.createElement('FORM');
				pippin_form.name='cashfreeDismissForm';
				pippin_form.method='POST';
				pippin_form.action=wc_cashfree_checkout_params.dismiss_url;
				document.body.appendChild(pippin_form);
				pippin_form.submit();
			}
		}
		
		//Submit callback form
		const  woocommerceFormSubmit  =  function (data, url) {
			pippin_form=document.createElement('FORM');
			pippin_form.name='cashfreeForm';
			pippin_form.method='POST';
			pippin_form.action=url;

			pippin_tb=document.createElement('INPUT');
			pippin_tb.type='HIDDEN';
			pippin_tb.name='orderId';
			pippin_tb.value=data.order.orderId;
			pippin_form.appendChild(pippin_tb);

			pippin_tb=document.createElement('INPUT');
			pippin_tb.type='HIDDEN';
			pippin_tb.name='transaction_status';
			pippin_tb.value=data.transaction.txStatus;
			pippin_form.appendChild(pippin_tb);

			pippin_tb=document.createElement('INPUT');
			pippin_tb.type='HIDDEN';
			pippin_tb.name='order_status';
			pippin_tb.value=data.order.status;
			pippin_form.appendChild(pippin_tb);

			pippin_tb=document.createElement('INPUT');
			pippin_tb.type='HIDDEN';
			pippin_tb.name='transaction_id';
			pippin_tb.value=data.transaction.transactionId;
			pippin_form.appendChild(pippin_tb);

			pippin_tb=document.createElement('INPUT');
			pippin_tb.type='HIDDEN';
			pippin_tb.name='transaction_msg';
			pippin_tb.value=data.transaction.txMsg;
			pippin_form.appendChild(pippin_tb);
			document.body.appendChild(pippin_form);
			pippin_form.submit();
		}
		Pippin.setOrderMetaPlatform("wc-" + wc_cashfree_checkout_params.woo_version);
		Pippin(env, wc_cashfree_checkout_params.token, successCallback, failureCallback, dismissCallback);
	}
);
